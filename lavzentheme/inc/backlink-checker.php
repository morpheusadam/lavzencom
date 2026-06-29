<?php
/**
 * Backlink Spam Checker — standalone admin tool (Tools → Backlink Spam Checker).
 *
 * Live, line-by-line results streamed via SSE (admin-ajax text/event-stream),
 * with an automatic AJAX-polling fallback when host buffering (Hostinger
 * hcdn / LiteSpeed) breaks streaming. The tool is fully self-contained:
 *   - only admin_menu + wp_ajax_* hooks (NO front-end hooks),
 *   - unique lavzen_blc_ prefix on everything,
 *   - CSS/JS are inlined in the admin page (admin pages aren't hcdn-cached, and
 *     this keeps deployment to a single file with no static-asset cache issues).
 *
 * Nothing else in the theme is touched.
 *
 * Endpoints (all admin-ajax, capability + nonce guarded):
 *   - lavzen_blc_start  (POST) : sanitise+dedupe the list, store it in a
 *                                  transient under a job token, return {job,total}.
 *   - lavzen_blc_stream (GET)  : EventSource — stream one `result` event per
 *                                  domain + a final `done` summary event.
 *   - lavzen_blc_batch  (POST) : check a small slice, return JSON (fallback).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

/* ============================ config ============================ */

/** Capability required to use the tool. */
function lavzen_blc_cap() {
	return apply_filters( 'lavzen_blc_cap', 'manage_options' );
}

/** Maximum domains accepted per job (resource guard). */
function lavzen_blc_max() {
	return (int) apply_filters( 'lavzen_blc_max', 1000 );
}

/** Per-request HTTP timeout, seconds. */
function lavzen_blc_timeout() {
	return (int) apply_filters( 'lavzen_blc_timeout', 5 );
}

/** Polling batch size (server caps it; the client may request smaller). */
function lavzen_blc_batch_size() {
	return (int) apply_filters( 'lavzen_blc_batch_size', 8 );
}

/** Score thresholds → label. */
function lavzen_blc_threshold_spam() {
	return (int) apply_filters( 'lavzen_blc_threshold_spam', 60 );
}
function lavzen_blc_threshold_suspicious() {
	return (int) apply_filters( 'lavzen_blc_threshold_suspicious', 30 );
}

/** High-risk / abuse-heavy TLDs. */
function lavzen_blc_risky_tlds() {
	return apply_filters(
		'lavzen_blc_risky_tlds',
		array(
			'xyz', 'top', 'work', 'click', 'loan', 'gq', 'tk', 'ml', 'cf', 'ga',
			'pw', 'icu', 'buzz', 'rest', 'country', 'stream', 'download', 'review',
			'kim', 'cricket', 'science', 'party', 'gdn', 'bid', 'win', 'men',
			'date', 'faith', 'racing', 'accountant', 'trade', 'webcam', 'mom',
			'lol', 'cyou', 'sbs', 'autos', 'quest', 'cam', 'monster', 'beauty',
			'hair', 'skin', 'makeup', 'boats', 'fit', 'rodeo', 'ooo',
		)
	);
}

/** Spam keyword fragments commonly found in junk-link domains / anchors. */
function lavzen_blc_spam_words() {
	return apply_filters(
		'lavzen_blc_spam_words',
		array(
			'casino', 'poker', 'porn', 'xxx', 'sex', 'escort', 'viagra', 'cialis',
			'pills', 'pharma', 'replica', 'payday', 'gambling', 'betting', 'slot',
			'adult', 'vashikaran', 'forex', 'essay', 'backlink', 'linkbuilding',
			'cheap-', 'buy-', 'free-', 'best-seo', 'rankboost',
		)
	);
}

/* ============================ admin menu + page ============================ */

/** Register the tool under Tools (separate, no interference). */
function lavzen_blc_menu() {
	add_management_page(
		__( 'Backlink Spam Checker', 'lavzentheme' ),
		__( 'Backlink Spam Checker', 'lavzentheme' ),
		lavzen_blc_cap(),
		'lavzen-backlink-checker',
		'lavzen_blc_render'
	);
}
add_action( 'admin_menu', 'lavzen_blc_menu' );

/* ============================ core checker ============================ */

/**
 * Extract a clean host from a raw domain/URL line.
 *
 * @param string $raw Raw line.
 * @return string Lowercased host without a leading www., or '' on failure.
 */
function lavzen_blc_host( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}
	$with = ( false === strpos( $raw, '//' ) ) ? 'http://' . $raw : $raw;
	$host = wp_parse_url( $with, PHP_URL_HOST );
	if ( ! $host ) {
		// Last resort: take the first whitespace/slash-delimited token.
		$host = preg_replace( '#^([^/\s]+).*$#', '$1', $raw );
	}
	$host = strtolower( (string) $host );
	$host = preg_replace( '/^www\./', '', $host );
	return trim( $host );
}

/**
 * HTTP reachability probe (HEAD, falling back to GET). Never throws.
 *
 * @param string $url URL to probe.
 * @return array{code:int,err:string}
 */
function lavzen_blc_http( $url ) {
	$args = array(
		'timeout'     => lavzen_blc_timeout(),
		'redirection' => 3,
		'sslverify'   => false,
		'user-agent'  => 'lavzen-backlink-checker/1.0',
		'headers'     => array( 'Accept' => '*/*' ),
	);

	$res = wp_remote_head( $url, $args );
	if ( is_wp_error( $res ) ) {
		$res = wp_remote_get( $url, $args ); // some servers reject HEAD.
	}
	if ( is_wp_error( $res ) ) {
		$code = $res->get_error_code();
		return array( 'code' => 0, 'err' => $code ? (string) $code : 'error' );
	}

	$code = (int) wp_remote_retrieve_response_code( $res );
	if ( $code >= 400 ) {
		// Many hosts return 405/403 on HEAD — confirm with one GET.
		$res2 = wp_remote_get( $url, $args );
		if ( ! is_wp_error( $res2 ) ) {
			$code = (int) wp_remote_retrieve_response_code( $res2 );
		}
	}
	return array( 'code' => $code, 'err' => '' );
}

/**
 * Run all spam-detection checks on one input line.
 *
 * Accepts an optional anchor after a pipe or tab: "example.com | anchor text".
 *
 * @param string $line Raw line.
 * @return array Result payload (input, host, anchor, label, score, http, reasons).
 */
function lavzen_blc_check( $line ) {
	$line   = trim( (string) $line );
	$anchor = '';

	foreach ( array( '|', "\t" ) as $sep ) {
		if ( false !== strpos( $line, $sep ) ) {
			$parts  = explode( $sep, $line, 2 );
			$line   = trim( $parts[0] );
			$anchor = trim( $parts[1] );
			break;
		}
	}

	$host = lavzen_blc_host( $line );

	if ( '' === $host || false === strpos( $host, '.' ) ) {
		return array(
			'input'   => $line,
			'host'    => $host,
			'anchor'  => $anchor,
			'label'   => 'invalid',
			'score'   => 0,
			'http'    => '',
			'reasons' => array( __( 'Invalid domain', 'lavzentheme' ) ),
		);
	}

	$score   = 0;
	$reasons = array();

	// 1) High-risk TLD.
	$dot = strrchr( $host, '.' );
	$tld = $dot ? strtolower( substr( $dot, 1 ) ) : '';
	if ( $tld && in_array( $tld, lavzen_blc_risky_tlds(), true ) ) {
		$score    += 40;
		$reasons[] = sprintf( /* translators: %s: TLD. */ __( 'High-risk TLD .%s', 'lavzentheme' ), $tld );
	}

	// 2) Spam keywords in the host.
	$hits = 0;
	foreach ( lavzen_blc_spam_words() as $w ) {
		if ( false !== strpos( $host, $w ) ) {
			$hits++;
		}
	}
	if ( $hits > 0 ) {
		$score    += min( 35, 15 * $hits );
		$reasons[] = sprintf(
			/* translators: %d: number of keywords. */
			_n( '%d spam keyword in domain', '%d spam keywords in domain', $hits, 'lavzentheme' ),
			$hits
		);
	}

	// 3) Excessive hyphens.
	$hyphens = substr_count( $host, '-' );
	if ( $hyphens >= 3 ) {
		$score    += 15;
		$reasons[] = sprintf( /* translators: %d: hyphen count. */ __( '%d hyphens in domain', 'lavzentheme' ), $hyphens );
	}

	// 4) Many digits.
	$digits = (int) preg_match_all( '/\d/', $host );
	if ( $digits >= 4 ) {
		$score    += 10;
		$reasons[] = __( 'Many digits in domain', 'lavzentheme' );
	}

	// 5) Unusually long label (random-looking gibberish).
	$longest = 0;
	foreach ( explode( '.', $host ) as $label ) {
		$longest = max( $longest, strlen( $label ) );
	}
	if ( $longest >= 25 ) {
		$score    += 10;
		$reasons[] = __( 'Unusually long domain label', 'lavzentheme' );
	}

	// 6) Suspicious anchor text.
	if ( '' !== $anchor ) {
		foreach ( lavzen_blc_spam_words() as $w ) {
			if ( false !== stripos( $anchor, $w ) ) {
				$score    += 20;
				$reasons[] = __( 'Suspicious anchor text', 'lavzentheme' );
				break;
			}
		}
	}

	// 7) HTTP reachability.
	$http = lavzen_blc_http( 'http://' . $host . '/' );
	if ( 0 === $http['code'] ) {
		$score    += 20;
		$reasons[] = sprintf( /* translators: %s: error. */ __( 'Unreachable (%s)', 'lavzentheme' ), $http['err'] );
	} elseif ( $http['code'] >= 400 ) {
		$score    += 15;
		$reasons[] = sprintf( /* translators: %d: HTTP code. */ __( 'HTTP %d', 'lavzentheme' ), $http['code'] );
	}

	if ( $score >= lavzen_blc_threshold_spam() ) {
		$label = 'spam';
	} elseif ( $score >= lavzen_blc_threshold_suspicious() ) {
		$label = 'suspicious';
	} else {
		$label = 'clean';
		if ( empty( $reasons ) ) {
			$reasons[] = __( 'No spam signals detected', 'lavzentheme' );
		}
	}

	return array(
		'input'   => $line,
		'host'    => $host,
		'anchor'  => $anchor,
		'label'   => $label,
		'score'   => (int) $score,
		'http'    => $http['code'] ? (string) $http['code'] : $http['err'],
		'reasons' => $reasons,
	);
}

/**
 * Parse a raw textarea/file blob into a clean, deduped, capped list.
 *
 * @param string $raw Raw input.
 * @return string[]
 */
function lavzen_blc_parse_list( $raw ) {
	$raw   = (string) $raw;
	$raw   = str_replace( array( "\r\n", "\r" ), "\n", $raw );
	$lines = preg_split( '/\n+/', $raw );
	$out   = array();
	$max   = lavzen_blc_max();

	foreach ( $lines as $l ) {
		$l = trim( sanitize_text_field( $l ) );
		if ( '' === $l ) {
			continue;
		}
		$out[] = $l;
		if ( count( $out ) >= $max ) {
			break;
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * Fetch a stored job list by token.
 *
 * @param string $token Job token.
 * @return string[]
 */
function lavzen_blc_job( $token ) {
	$token = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $token );
	if ( '' === $token ) {
		return array();
	}
	$list = get_transient( 'lavzen_blc_' . $token );
	return is_array( $list ) ? $list : array();
}

/* ============================ AJAX: start ============================ */

/** AJAX: store the submitted list and return a job token. */
function lavzen_blc_ajax_start() {
	if ( ! current_user_can( lavzen_blc_cap() ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lavzentheme' ) ), 403 );
	}
	check_ajax_referer( 'lavzen_blc', 'nonce' );

	$raw  = isset( $_POST['domains'] ) ? (string) wp_unslash( $_POST['domains'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per-line in parser.
	$list = lavzen_blc_parse_list( $raw );
	if ( empty( $list ) ) {
		wp_send_json_error( array( 'message' => __( 'No valid domains found.', 'lavzentheme' ) ) );
	}

	$token = wp_generate_password( 20, false, false );
	set_transient( 'lavzen_blc_' . $token, $list, HOUR_IN_SECONDS );

	wp_send_json_success(
		array(
			'job'   => $token,
			'total' => count( $list ),
		)
	);
}
add_action( 'wp_ajax_lavzen_blc_start', 'lavzen_blc_ajax_start' );

/* ============================ AJAX: SSE stream ============================ */

/** Emit one SSE event and flush. */
function lavzen_blc_sse( $event, $data ) {
	echo 'event: ' . $event . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed event name.
	echo 'data: ' . wp_json_encode( $data ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON for SSE.
	if ( function_exists( 'flush' ) ) {
		@flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}

/** AJAX (GET): stream results as Server-Sent Events. */
function lavzen_blc_ajax_stream() {
	if ( ! current_user_can( lavzen_blc_cap() ) ) {
		status_header( 403 );
		exit;
	}
	$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'lavzen_blc' ) ) {
		status_header( 400 );
		exit;
	}
	$token = isset( $_GET['job'] ) ? sanitize_text_field( wp_unslash( $_GET['job'] ) ) : '';
	$list  = lavzen_blc_job( $token );

	// Disable buffering / gzip so each event reaches the browser immediately.
	@ini_set( 'zlib.output_compression', '0' ); // phpcs:ignore WordPress.PHP.IniSet.Risky,WordPress.PHP.NoSilencedErrors.Discouraged
	@ini_set( 'output_buffering', '0' );        // phpcs:ignore WordPress.PHP.IniSet.Risky,WordPress.PHP.NoSilencedErrors.Discouraged
	@ini_set( 'implicit_flush', '1' );          // phpcs:ignore WordPress.PHP.IniSet.Risky,WordPress.PHP.NoSilencedErrors.Discouraged

	if ( ! headers_sent() ) {
		header( 'Content-Type: text/event-stream; charset=utf-8' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' );   // nginx: don't buffer.
		header( 'Content-Encoding: none' );  // defeat gzip on this response.
	}

	while ( ob_get_level() > 0 ) {
		@ob_end_flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
	ob_implicit_flush( true );
	ignore_user_abort( true );

	// 2KB padding comment forces proxies/LiteSpeed past their buffer threshold.
	echo ':' . str_repeat( ' ', 2048 ) . "\n\n";
	if ( function_exists( 'flush' ) ) {
		@flush(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$total = count( $list );
	lavzen_blc_sse( 'start', array( 'total' => $total ) );

	$sum = array(
		'total'      => $total,
		'clean'      => 0,
		'suspicious' => 0,
		'spam'       => 0,
		'invalid'    => 0,
	);

	$i = 0;
	foreach ( $list as $line ) {
		if ( connection_aborted() ) {
			break;
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 15 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- reset per domain so the job never trips max_execution_time.
		}
		$r          = lavzen_blc_check( $line );
		$r['index'] = ++$i;
		if ( isset( $sum[ $r['label'] ] ) ) {
			$sum[ $r['label'] ]++;
		}
		lavzen_blc_sse( 'result', $r );
	}

	lavzen_blc_sse( 'done', $sum );
	exit;
}
add_action( 'wp_ajax_lavzen_blc_stream', 'lavzen_blc_ajax_stream' );

/* ============================ AJAX: polling batch ============================ */

/** AJAX (POST): check one slice and return JSON (streaming fallback). */
function lavzen_blc_ajax_batch() {
	if ( ! current_user_can( lavzen_blc_cap() ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lavzentheme' ) ), 403 );
	}
	check_ajax_referer( 'lavzen_blc', 'nonce' );

	$token  = isset( $_POST['job'] ) ? sanitize_text_field( wp_unslash( $_POST['job'] ) ) : '';
	$list   = lavzen_blc_job( $token );
	$total  = count( $list );
	$offset = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;
	$size   = isset( $_POST['size'] ) ? (int) $_POST['size'] : lavzen_blc_batch_size();
	$size   = max( 1, min( 25, $size ) );

	$slice = array_slice( $list, $offset, $size );
	$out   = array();
	$i     = $offset;
	foreach ( $slice as $line ) {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 15 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		$r          = lavzen_blc_check( $line );
		$r['index'] = ++$i;
		$out[]      = $r;
	}

	$next = $offset + count( $slice );
	wp_send_json_success(
		array(
			'results' => $out,
			'next'    => $next,
			'total'   => $total,
			'done'    => $next >= $total,
		)
	);
}
add_action( 'wp_ajax_lavzen_blc_batch', 'lavzen_blc_ajax_batch' );

/* ============================ admin page render ============================ */

/** Render the tool page (markup + inlined CSS/JS). */
function lavzen_blc_render() {
	if ( ! current_user_can( lavzen_blc_cap() ) ) {
		return;
	}

	$cfg = array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'lavzen_blc' ),
		'max'   => lavzen_blc_max(),
	);
	?>
	<div class="wrap lavblc-wrap">
		<h1><?php esc_html_e( 'Backlink Spam Checker', 'lavzentheme' ); ?></h1>
		<p class="description">
			<?php
			printf(
				/* translators: %d: max domains. */
				esc_html__( 'Paste up to %d domains / backlink URLs (one per line). Optional anchor text after a pipe: example.com | cheap seo links. Results stream live; if your host buffers the stream the tool automatically switches to polling.', 'lavzentheme' ),
				(int) lavzen_blc_max()
			);
			?>
		</p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="lavblc-domains"><?php esc_html_e( 'Domains / backlinks', 'lavzentheme' ); ?></label></th>
				<td>
					<textarea id="lavblc-domains" rows="8" class="large-text code" placeholder="example.com&#10;spammy-casino-site.xyz&#10;https://foo.top/path | buy cheap links"></textarea>
					<p>
						<label class="button" style="cursor:pointer;">
							<?php esc_html_e( 'Load from .txt file', 'lavzentheme' ); ?>
							<input type="file" id="lavblc-file" accept=".txt,text/plain" hidden>
						</label>
						<label for="lavblc-mode" style="margin-left:12px;"><?php esc_html_e( 'Mode:', 'lavzentheme' ); ?></label>
						<select id="lavblc-mode">
							<option value="auto"><?php esc_html_e( 'Auto (SSE → fallback to polling)', 'lavzentheme' ); ?></option>
							<option value="sse"><?php esc_html_e( 'Streaming (SSE) only', 'lavzentheme' ); ?></option>
							<option value="poll"><?php esc_html_e( 'Polling only', 'lavzentheme' ); ?></option>
						</select>
					</p>
				</td>
			</tr>
		</table>

		<p>
			<button type="button" class="button button-primary" id="lavblc-run"><?php esc_html_e( 'Run check', 'lavzentheme' ); ?></button>
			<button type="button" class="button" id="lavblc-stop" disabled><?php esc_html_e( 'Stop', 'lavzentheme' ); ?></button>
			<button type="button" class="button" id="lavblc-clear"><?php esc_html_e( 'Clear', 'lavzentheme' ); ?></button>
			<span id="lavblc-transport" class="lavblc-pill"></span>
		</p>

		<div id="lavblc-summary" class="lavblc-summary" hidden>
			<span class="s-total">0</span> <?php esc_html_e( 'checked', 'lavzentheme' ); ?> ·
			<span class="s-spam">0</span> <?php esc_html_e( 'spam', 'lavzentheme' ); ?> ·
			<span class="s-susp">0</span> <?php esc_html_e( 'suspicious', 'lavzentheme' ); ?> ·
			<span class="s-clean">0</span> <?php esc_html_e( 'clean', 'lavzentheme' ); ?> ·
			<span class="s-invalid">0</span> <?php esc_html_e( 'invalid', 'lavzentheme' ); ?>
			<span class="lavblc-prog"></span>
		</div>

		<div id="lavblc-console" class="lavblc-console" aria-live="polite"></div>

		<p>
			<button type="button" class="button" id="lavblc-export" disabled><?php esc_html_e( 'Export disavow .txt', 'lavzentheme' ); ?></button>
			<label style="margin-left:10px;"><input type="checkbox" id="lavblc-export-susp"> <?php esc_html_e( 'Include suspicious', 'lavzentheme' ); ?></label>
		</p>
	</div>

	<style>
		.lavblc-console{background:#0b1120;color:#cdd3e4;font-family:Consolas,Menlo,Monaco,monospace;font-size:13px;line-height:1.5;border-radius:8px;padding:14px 16px;height:440px;overflow-y:auto;border:1px solid #2b3450;white-space:pre-wrap;word-break:break-word}
		.lavblc-console .ln{display:block;padding:2px 0;border-bottom:1px solid rgba(255,255,255,.04)}
		.lavblc-console .clean{color:#7ee787}
		.lavblc-console .suspicious{color:#e3b341}
		.lavblc-console .spam{color:#ff7b72}
		.lavblc-console .invalid{color:#9aa3bd}
		.lavblc-console .sys{color:#79c0ff}
		.lavblc-console .badge{display:inline-block;min-width:78px;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
		.lavblc-console .idx{color:#6e7681}
		.lavblc-console .meta{color:#8b949e}
		.lavblc-summary{font-size:14px;margin:6px 0 12px;padding:8px 12px;background:#fff;border:1px solid #dcdcde;border-radius:6px;display:inline-block}
		.lavblc-summary .s-spam{color:#d63638;font-weight:700}
		.lavblc-summary .s-susp{color:#bd8600;font-weight:700}
		.lavblc-summary .s-clean{color:#1a7f37;font-weight:700}
		.lavblc-summary .lavblc-prog{margin-left:10px;color:#646970}
		.lavblc-pill{display:inline-block;margin-left:10px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;background:#eef;color:#3a3a79;vertical-align:middle}
		.lavblc-pill.sse{background:#e6f4ea;color:#1a7f37}
		.lavblc-pill.poll{background:#fdf4e3;color:#8a6d00}
	</style>

	<script>
	var LAVBLC = <?php echo wp_json_encode( $cfg ); ?>;
	</script>
	<?php
	// Main logic — static (no PHP interpolation needed), kept in a NOWDOC.
	echo "<script>\n" . lavzen_blc_inline_js() . "\n</script>";
}

/**
 * The tool's client-side logic (vanilla JS). Kept out of the render function
 * body for readability; emitted inline so there is no cacheable static asset.
 *
 * @return string
 */
function lavzen_blc_inline_js() {
	return <<<'JS'
( function () {
	'use strict';
	var cfg = window.LAVBLC || {};
	var $ = function ( id ) { return document.getElementById( id ); };

	var elRun = $( 'lavblc-run' ), elStop = $( 'lavblc-stop' ), elClear = $( 'lavblc-clear' );
	var elDomains = $( 'lavblc-domains' ), elFile = $( 'lavblc-file' ), elMode = $( 'lavblc-mode' );
	var elConsole = $( 'lavblc-console' ), elSummary = $( 'lavblc-summary' ), elTransport = $( 'lavblc-transport' );
	var elExport = $( 'lavblc-export' ), elExportSusp = $( 'lavblc-export-susp' ), elProg = elSummary.querySelector( '.lavblc-prog' );

	var es = null;            // active EventSource
	var aborted = false;      // user pressed Stop
	var job = '';             // job token
	var total = 0;
	var processed = 0;
	var sseTimer = null;      // buffering watchdog
	var sseSawData = false;
	var sum = { total: 0, clean: 0, suspicious: 0, spam: 0, invalid: 0 };
	var spamList = [];        // {host,label}
	var seenHosts = {};

	function post( data ) {
		var body = Object.keys( data ).map( function ( k ) {
			return encodeURIComponent( k ) + '=' + encodeURIComponent( data[ k ] );
		} ).join( '&' );
		return fetch( cfg.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body
		} ).then( function ( r ) { return r.json(); } );
	}

	function line( cls, text ) {
		var atBottom = ( elConsole.scrollHeight - elConsole.scrollTop - elConsole.clientHeight ) < 40;
		var d = document.createElement( 'span' );
		d.className = 'ln ' + cls;
		d.textContent = text;
		elConsole.appendChild( d );
		if ( atBottom ) { elConsole.scrollTop = elConsole.scrollHeight; }
	}

	function setTransport( label, cls ) {
		elTransport.textContent = label;
		elTransport.className = 'lavblc-pill ' + ( cls || '' );
	}

	function renderResult( r ) {
		processed++;
		if ( typeof sum[ r.label ] !== 'undefined' ) { sum[ r.label ]++; }
		sum.total = processed;
		var reasons = ( r.reasons && r.reasons.length ) ? r.reasons.join( ', ' ) : '';
		var http = r.http ? ( ' [' + r.http + ']' ) : '';
		var idx = '#' + ( r.index || processed ) + '/' + total;
		var badge = ( r.label || 'clean' ).toUpperCase();
		while ( badge.length < 10 ) { badge += ' '; }
		line( r.label, idx + '  ' + badge + ' ' + ( r.host || r.input || '?' ) + http + ( reasons ? '  — ' + reasons : '' ) );

		if ( r.host && ( r.label === 'spam' || r.label === 'suspicious' ) && ! seenHosts[ r.host ] ) {
			seenHosts[ r.host ] = r.label;
			spamList.push( { host: r.host, label: r.label } );
		}
		paintSummary();
	}

	function paintSummary() {
		elSummary.hidden = false;
		elSummary.querySelector( '.s-total' ).textContent = sum.total;
		elSummary.querySelector( '.s-spam' ).textContent = sum.spam;
		elSummary.querySelector( '.s-susp' ).textContent = sum.suspicious;
		elSummary.querySelector( '.s-clean' ).textContent = sum.clean;
		elSummary.querySelector( '.s-invalid' ).textContent = sum.invalid;
		elProg.textContent = total ? ( '(' + processed + '/' + total + ')' ) : '';
		elExport.disabled = spamList.length === 0;
	}

	function finish( msg ) {
		closeSse();
		elRun.disabled = false;
		elStop.disabled = true;
		line( 'sys', msg || '— done —' );
		elExport.disabled = spamList.length === 0;
	}

	function closeSse() {
		if ( sseTimer ) { clearTimeout( sseTimer ); sseTimer = null; }
		if ( es ) { try { es.close(); } catch ( e ) {} es = null; }
	}

	/* ----- SSE transport ----- */
	function runSse() {
		setTransport( 'SSE', 'sse' );
		sseSawData = false;
		var url = cfg.ajax + '?action=lavzen_blc_stream&job=' + encodeURIComponent( job ) + '&nonce=' + encodeURIComponent( cfg.nonce );
		try {
			es = new EventSource( url, { withCredentials: true } );
		} catch ( e ) {
			line( 'sys', 'SSE unavailable — switching to polling.' );
			runPoll();
			return;
		}

		// Buffering watchdog: if no event arrives in 6s, the host buffered it.
		sseTimer = setTimeout( function () {
			if ( ! sseSawData && ! aborted ) {
				line( 'sys', 'No stream within 6s (host buffering) — switching to polling.' );
				closeSse();
				runPoll();
			}
		}, 6000 );

		es.addEventListener( 'start', function () { sseSawData = true; } );
		es.addEventListener( 'result', function ( ev ) {
			sseSawData = true;
			if ( aborted ) { return; }
			try { renderResult( JSON.parse( ev.data ) ); } catch ( e ) {}
		} );
		es.addEventListener( 'done', function ( ev ) {
			sseSawData = true;
			try { var s = JSON.parse( ev.data ); reconcile( s ); } catch ( e ) {}
			finish( '— done (streamed) —' );
		} );
		es.onerror = function () {
			if ( aborted ) { return; }
			// If we already finished, ignore. Otherwise fall back from where we are.
			closeSse();
			if ( processed < total ) {
				line( 'sys', 'Stream dropped — continuing with polling.' );
				runPoll();
			} else {
				finish();
			}
		};
	}

	// Trust the server's authoritative summary when streaming completes.
	function reconcile( s ) {
		if ( ! s ) { return; }
		sum.total = s.total != null ? s.total : sum.total;
		sum.clean = s.clean != null ? s.clean : sum.clean;
		sum.suspicious = s.suspicious != null ? s.suspicious : sum.suspicious;
		sum.spam = s.spam != null ? s.spam : sum.spam;
		sum.invalid = s.invalid != null ? s.invalid : sum.invalid;
		processed = sum.total;
		paintSummary();
	}

	/* ----- Polling transport ----- */
	function runPoll() {
		setTransport( 'POLLING', 'poll' );
		pollNext( processed );
	}

	function pollNext( offset ) {
		if ( aborted ) { finish( '— stopped —' ); return; }
		post( { action: 'lavzen_blc_batch', nonce: cfg.nonce, job: job, offset: offset, size: 8 } )
			.then( function ( res ) {
				if ( ! res || ! res.success ) {
					finish( 'Error: ' + ( ( res && res.data && res.data.message ) || 'batch failed' ) );
					return;
				}
				res.data.results.forEach( renderResult );
				if ( res.data.done || aborted ) {
					finish( '— done (polling) —' );
				} else {
					pollNext( res.data.next );
				}
			} )
			.catch( function () { finish( 'Network error.' ); } );
	}

	/* ----- run ----- */
	function reset() {
		closeSse();
		aborted = false;
		processed = 0; total = 0;
		sum = { total: 0, clean: 0, suspicious: 0, spam: 0, invalid: 0 };
		spamList = []; seenHosts = {};
		elConsole.innerHTML = '';
		elSummary.hidden = true;
		elExport.disabled = true;
	}

	elRun.addEventListener( 'click', function () {
		reset();
		var raw = elDomains.value || '';
		if ( ! raw.trim() ) { line( 'sys', 'Nothing to check — paste some domains first.' ); return; }
		elRun.disabled = true; elStop.disabled = false;
		line( 'sys', 'Starting…' );

		post( { action: 'lavzen_blc_start', nonce: cfg.nonce, domains: raw } )
			.then( function ( res ) {
				if ( ! res || ! res.success ) {
					finish( 'Error: ' + ( ( res && res.data && res.data.message ) || 'could not start' ) );
					return;
				}
				job = res.data.job;
				total = res.data.total;
				line( 'sys', total + ' domain(s) queued.' );
				paintSummary();
				var mode = elMode.value;
				if ( mode === 'poll' ) { runPoll(); }
				else if ( mode === 'sse' ) { runSse(); }
				else { ( typeof window.EventSource !== 'undefined' ) ? runSse() : runPoll(); }
			} )
			.catch( function () { finish( 'Network error starting the job.' ); } );
	} );

	elStop.addEventListener( 'click', function () {
		aborted = true;
		closeSse();
		finish( '— stopped —' );
	} );

	elClear.addEventListener( 'click', function () {
		reset();
		line( 'sys', 'Cleared.' );
		elSummary.hidden = true;
	} );

	elFile.addEventListener( 'change', function () {
		var f = this.files && this.files[ 0 ];
		if ( ! f ) { return; }
		var rd = new FileReader();
		rd.onload = function ( e ) {
			var cur = elDomains.value.trim();
			elDomains.value = ( cur ? cur + '\n' : '' ) + String( e.target.result );
		};
		rd.readAsText( f );
	} );

	elExport.addEventListener( 'click', function () {
		var includeSusp = elExportSusp.checked;
		var rows = spamList.filter( function ( x ) {
			return includeSusp ? true : x.label === 'spam';
		} );
		if ( ! rows.length ) { return; }
		// Google Disavow format: one "domain:host" per line, deduped.
		var seen = {}, lines = [ '# Disavow file generated by lavtheme Backlink Spam Checker' ];
		rows.forEach( function ( x ) {
			if ( seen[ x.host ] ) { return; }
			seen[ x.host ] = 1;
			lines.push( 'domain:' + x.host );
		} );
		var blob = new Blob( [ lines.join( '\n' ) + '\n' ], { type: 'text/plain' } );
		var a = document.createElement( 'a' );
		a.href = URL.createObjectURL( blob );
		a.download = 'disavow-' + new Date().toISOString().slice( 0, 10 ) + '.txt';
		document.body.appendChild( a );
		a.click();
		setTimeout( function () { URL.revokeObjectURL( a.href ); document.body.removeChild( a ); }, 0 );
	} );
}() );
JS;
}
