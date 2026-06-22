<?php
/**
 * Plugin: WP Dash
 * Description: A modern, animated analytics dashboard for the WordPress admin —
 * real site counts plus optimized SVG/CSS charts (area, bars, radial rings) and
 * a pixel-art activity heatmap. No chart library; pure SVG + CSS + a tiny JS.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

// WP Dash menu → open the Code Studio code editor focused on the "wp-dash"
// context (HTML/CSS/JS/PHP for the dashboard), instead of rendering a page.
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			lavtheme_plugins_parent_slug(),
			__( 'WP Dash', 'lavtheme' ),
			__( 'WP Dash', 'lavtheme' ),
			lavtheme_plugins_cap(),
			'admin.php?page=' . lavtheme_plugins_parent_slug() . '&cs_context=wp-dash'
		);
	},
	22
);

/** True on the WP Dash admin screen. */
function lavtheme_wp_dash_is_screen() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return is_admin() && isset( $_GET['page'] ) && 'lavtheme-wp-dash' === $_GET['page'];
}

/** Enqueue the dashboard CSS/JS only on its own screen. */
function lavtheme_wp_dash_assets() {
	if ( ! lavtheme_wp_dash_is_screen() ) {
		return;
	}
	$base = get_theme_file_uri( 'plugins/wp-dash/assets/' );
	$dir  = get_theme_file_path( 'plugins/wp-dash/assets/' );
	wp_enqueue_style( 'lavtheme-wp-dash', $base . 'dashboard.css', array(), file_exists( $dir . 'dashboard.css' ) ? (string) filemtime( $dir . 'dashboard.css' ) : '1' );
	wp_enqueue_script( 'lavtheme-wp-dash', $base . 'dashboard.js', array(), file_exists( $dir . 'dashboard.js' ) ? (string) filemtime( $dir . 'dashboard.js' ) : '1', true );
}
add_action( 'admin_enqueue_scripts', 'lavtheme_wp_dash_assets' );

/* ------------------------------------------------------------------ data --- */

/**
 * Real site metrics + representative trend series for the charts.
 *
 * Counts are live; the per-month/per-day series are derived from real published
 * post dates where possible and fall back to a smooth sample so the charts always
 * render. Swap the series for an analytics source when wired.
 *
 * @return array
 */
function lavtheme_wp_dash_data() {
	$posts    = (int) wp_count_posts( 'post' )->publish;
	$comments = (int) wp_count_comments()->approved;
	$users    = (int) count_users()['total_users'];
	$products = post_type_exists( 'download' ) ? (int) wp_count_posts( 'download' )->publish : 0;

	// Posts published per month, last 8 months (one light query).
	$months = array();
	$labels = array();
	for ( $i = 7; $i >= 0; $i-- ) {
		$ts            = strtotime( "first day of -$i month" );
		$labels[]      = wp_date( 'M', $ts );
		$months[ wp_date( 'Y-m', $ts ) ] = 0;
	}
	$recent = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'date_query'     => array( array( 'after' => '8 months ago' ) ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	foreach ( $recent as $pid ) {
		$k = get_the_date( 'Y-m', $pid );
		if ( isset( $months[ $k ] ) ) {
			$months[ $k ]++;
		}
	}
	$series = array_values( $months );
	if ( array_sum( $series ) === 0 ) {
		$series = array( 4, 7, 5, 9, 8, 12, 10, 15 ); // sample so the chart is never flat-empty.
	}

	// A 7-day sparkline-ish series for the area chart (sample, ready to wire).
	$visits = array( 320, 410, 380, 520, 610, 560, 720 );

	// Goal rings (percent).
	$rings = array(
		array( 'label' => __( 'SEO health', 'lavtheme' ), 'value' => 86, 'color' => '#7c83ff' ),
		array( 'label' => __( 'Performance', 'lavtheme' ), 'value' => 72, 'color' => '#22d3ee' ),
		array( 'label' => __( 'Content', 'lavtheme' ), 'value' => 64, 'color' => '#a3e635' ),
	);

	// Pixel heatmap: 7 rows × 20 cols of intensity 0–4 (sample activity).
	$heat = array();
	$seed = $posts + $comments + 7;
	for ( $r = 0; $r < 7; $r++ ) {
		$row = array();
		for ( $c = 0; $c < 20; $c++ ) {
			$seed  = ( $seed * 1103515245 + 12345 ) & 0x7fffffff;
			$row[] = (int) ( $seed % 5 );
		}
		$heat[] = $row;
	}

	return array(
		'cards'  => array(
			array( 'label' => __( 'Posts', 'lavtheme' ), 'value' => $posts, 'delta' => '+12%', 'up' => true, 'spark' => array( 3, 5, 4, 6, 5, 8, 7, 9 ), 'color' => '#7c83ff' ),
			array( 'label' => __( 'Comments', 'lavtheme' ), 'value' => $comments, 'delta' => '+5%', 'up' => true, 'spark' => array( 6, 5, 7, 6, 8, 7, 9, 8 ), 'color' => '#22d3ee' ),
			array( 'label' => __( 'Users', 'lavtheme' ), 'value' => $users, 'delta' => '+3%', 'up' => true, 'spark' => array( 2, 3, 3, 4, 5, 5, 6, 7 ), 'color' => '#a3e635' ),
			array( 'label' => __( 'Products', 'lavtheme' ), 'value' => $products, 'delta' => $products ? '+8%' : '0%', 'up' => (bool) $products, 'spark' => array( 4, 4, 5, 6, 6, 7, 8, 9 ), 'color' => '#e8c547' ),
		),
		'labels' => $labels,
		'bars'   => $series,
		'visits' => $visits,
		'rings'  => $rings,
		'heat'   => $heat,
	);
}

/* ---------------------------------------------------------------- helpers --- */

/** Build an SVG polyline/area path from a numeric series within a viewbox. */
function lavtheme_wp_dash_path( $series, $w, $h, $pad = 6 ) {
	$series = array_map( 'floatval', (array) $series );
	$n      = count( $series );
	if ( $n < 2 ) {
		return array( 'line' => '', 'area' => '' );
	}
	$max  = max( $series );
	$min  = min( $series );
	$span = ( $max - $min ) ?: 1;
	$stepx = ( $w - $pad * 2 ) / ( $n - 1 );
	$pts   = array();
	foreach ( $series as $i => $v ) {
		$x     = $pad + $i * $stepx;
		$y     = $h - $pad - ( ( $v - $min ) / $span ) * ( $h - $pad * 2 );
		$pts[] = round( $x, 1 ) . ',' . round( $y, 1 );
	}
	$line = 'M' . implode( ' L', $pts );
	$area = $line . ' L' . round( $pad + ( $n - 1 ) * $stepx, 1 ) . ',' . ( $h - $pad ) . ' L' . $pad . ',' . ( $h - $pad ) . ' Z';
	return array( 'line' => $line, 'area' => $area );
}

/* ----------------------------------------------------------------- render --- */

/** Render the WP Dash analytics screen. */
function lavtheme_wp_dash_render() {
	if ( ! current_user_can( lavtheme_plugins_cap() ) ) {
		return;
	}
	$d    = lavtheme_wp_dash_data();
	$area = lavtheme_wp_dash_path( $d['visits'], 560, 150 );
	?>
	<div class="wrap lavd-wrap">
		<div class="lavd" data-lavd>

			<header class="lavd-top">
				<div>
					<h1 class="lavd-h1"><?php esc_html_e( 'WP Dash', 'lavtheme' ); ?></h1>
					<p class="lavd-sub"><?php esc_html_e( 'Live overview of your site — content, engagement and health.', 'lavtheme' ); ?></p>
				</div>
				<span class="lavd-live"><span class="lavd-live-dot" aria-hidden="true"></span><?php esc_html_e( 'Live', 'lavtheme' ); ?></span>
			</header>

			<!-- stat cards -->
			<section class="lavd-cards">
				<?php foreach ( $d['cards'] as $c ) : $sp = lavtheme_wp_dash_path( $c['spark'], 120, 40, 3 ); ?>
					<article class="lavd-card" style="--c:<?php echo esc_attr( $c['color'] ); ?>;">
						<span class="lavd-card-label"><?php echo esc_html( $c['label'] ); ?></span>
						<span class="lavd-num" data-count="<?php echo esc_attr( $c['value'] ); ?>">0</span>
						<span class="lavd-delta <?php echo $c['up'] ? 'up' : 'down'; ?>">
							<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true"><path d="<?php echo $c['up'] ? 'M4 17l6-6 4 4 6-7' : 'M4 7l6 6 4-4 6 7'; ?>" fill="none" stroke="currentColor" stroke-width="2.4"/></svg>
							<?php echo esc_html( $c['delta'] ); ?>
						</span>
						<svg class="lavd-spark" viewBox="0 0 120 40" preserveAspectRatio="none" aria-hidden="true">
							<path class="lavd-spark-area" d="<?php echo esc_attr( $sp['area'] ); ?>"></path>
							<path class="lavd-spark-line" d="<?php echo esc_attr( $sp['line'] ); ?>"></path>
						</svg>
					</article>
				<?php endforeach; ?>
			</section>

			<!-- main grid: area chart + rings -->
			<section class="lavd-grid">
				<article class="lavd-panel lavd-area-panel">
					<div class="lavd-panel-head">
						<h2><?php esc_html_e( 'Traffic — last 7 days', 'lavtheme' ); ?></h2>
						<span class="lavd-chip"><?php esc_html_e( 'Demo data', 'lavtheme' ); ?></span>
					</div>
					<svg class="lavd-area" viewBox="0 0 560 150" preserveAspectRatio="none" role="img" aria-label="<?php esc_attr_e( 'Weekly traffic area chart', 'lavtheme' ); ?>">
						<defs>
							<linearGradient id="lavdArea" x1="0" y1="0" x2="0" y2="1">
								<stop offset="0" stop-color="#7c83ff" stop-opacity=".45"/>
								<stop offset="1" stop-color="#7c83ff" stop-opacity="0"/>
							</linearGradient>
						</defs>
						<path class="lavd-area-fill" d="<?php echo esc_attr( $area['area'] ); ?>" fill="url(#lavdArea)"></path>
						<path class="lavd-area-line" d="<?php echo esc_attr( $area['line'] ); ?>" fill="none"></path>
					</svg>
				</article>

				<article class="lavd-panel lavd-rings-panel">
					<div class="lavd-panel-head"><h2><?php esc_html_e( 'Health scores', 'lavtheme' ); ?></h2></div>
					<div class="lavd-rings">
						<?php foreach ( $d['rings'] as $r ) : $dash = 2 * M_PI * 26; ?>
							<div class="lavd-ring" style="--rc:<?php echo esc_attr( $r['color'] ); ?>;">
								<svg viewBox="0 0 64 64" width="76" height="76">
									<circle cx="32" cy="32" r="26" class="lavd-ring-bg"/>
									<circle cx="32" cy="32" r="26" class="lavd-ring-fg" data-pct="<?php echo esc_attr( $r['value'] ); ?>"
										style="stroke-dasharray:<?php echo esc_attr( $dash ); ?>;stroke-dashoffset:<?php echo esc_attr( $dash ); ?>;"/>
								</svg>
								<span class="lavd-ring-val" data-count="<?php echo esc_attr( $r['value'] ); ?>" data-suffix="%">0%</span>
								<span class="lavd-ring-label"><?php echo esc_html( $r['label'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</article>
			</section>

			<!-- bars + pixel heatmap -->
			<section class="lavd-grid">
				<article class="lavd-panel lavd-bars-panel">
					<div class="lavd-panel-head"><h2><?php esc_html_e( 'Posts published — last 8 months', 'lavtheme' ); ?></h2></div>
					<div class="lavd-bars" role="img" aria-label="<?php esc_attr_e( 'Monthly posts bar chart', 'lavtheme' ); ?>">
						<?php
						$max = max( 1, max( $d['bars'] ) );
						foreach ( $d['bars'] as $i => $v ) :
							$pct = round( ( $v / $max ) * 100 );
							?>
							<div class="lavd-bar-col">
								<span class="lavd-bar-v"><?php echo esc_html( $v ); ?></span>
								<span class="lavd-bar" style="--h:<?php echo esc_attr( $pct ); ?>%;--i:<?php echo esc_attr( $i ); ?>;"></span>
								<span class="lavd-bar-x"><?php echo esc_html( $d['labels'][ $i ] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</article>

				<article class="lavd-panel lavd-pixel-panel">
					<div class="lavd-panel-head">
						<h2><?php esc_html_e( 'Activity', 'lavtheme' ); ?></h2>
						<span class="lavd-chip pixel"><?php esc_html_e( 'PIXEL', 'lavtheme' ); ?></span>
					</div>
					<div class="lavd-heat" aria-hidden="true">
						<?php
						$n = 0;
						foreach ( $d['heat'] as $row ) :
							foreach ( $row as $cell ) :
								?>
								<span class="lavd-px" data-l="<?php echo esc_attr( $cell ); ?>" style="--n:<?php echo esc_attr( $n ); ?>;"></span>
								<?php
								$n++;
							endforeach;
						endforeach;
						?>
					</div>
					<div class="lavd-heat-legend">
						<span><?php esc_html_e( 'Less', 'lavtheme' ); ?></span>
						<i data-l="0"></i><i data-l="1"></i><i data-l="2"></i><i data-l="3"></i><i data-l="4"></i>
						<span><?php esc_html_e( 'More', 'lavtheme' ); ?></span>
					</div>
				</article>
			</section>

		</div>
	</div>
	<?php
}

/* ===================================================================
 * Native WordPress Dashboard (Dashboard → Home): apply the editable
 * "wp-dash" Code Studio context — glassmorphism liquid skin by default.
 * CSS/JS/HTML are override-or-file (assets/dash-skin.css, dash-skin.js,
 * template.php). Scoped strictly to the dashboard screen.
 * ================================================================ */

/** True on the native Dashboard home screen only. */
function lavtheme_wp_dash_is_home() {
	if ( ! is_admin() ) {
		return false;
	}
	if ( function_exists( 'get_current_screen' ) ) {
		$s = get_current_screen();
		if ( $s && isset( $s->base ) ) {
			return 'dashboard' === $s->base;
		}
	}
	return isset( $GLOBALS['pagenow'] ) && 'index.php' === $GLOBALS['pagenow'];
}

/** Read a wp-dash context value (override-or-file) via the Code Studio plumbing. */
function lavtheme_wp_dash_ctx_get( $type ) {
	if ( function_exists( 'lavtheme_cs_dl_get' ) ) {
		return (string) lavtheme_cs_dl_get( 'wp-dash', 'design', $type );
	}
	// Fallback: read the shipped default file directly.
	$map  = array( 'css' => 'assets/dash-skin.css', 'js' => 'assets/dash-skin.js' );
	$file = isset( $map[ $type ] ) ? get_theme_file_path( 'plugins/wp-dash/' . $map[ $type ] ) : '';
	return ( $file && is_readable( $file ) ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/** Inject the dashboard skin CSS into the Dashboard <head>. */
function lavtheme_wp_dash_skin_css() {
	if ( ! lavtheme_wp_dash_is_home() ) {
		return;
	}
	$css = lavtheme_wp_dash_ctx_get( 'css' );
	$m   = function_exists( 'lavtheme_cs_dl_get' ) ? (string) lavtheme_cs_dl_get( 'wp-dash', 'design', 'mcss' ) : '';
	if ( '' !== trim( $m ) ) {
		$css .= "\n@media (max-width:782px){\n" . $m . "\n}";
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-wpdash-skin">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored CSS, sanitised on save.
	}
}
add_action( 'admin_head', 'lavtheme_wp_dash_skin_css' );

/** Render the editable glass hero at the top of the Dashboard. */
function lavtheme_wp_dash_skin_hero() {
	if ( ! lavtheme_wp_dash_is_home() ) {
		return;
	}
	$body = function_exists( 'lavtheme_cs_dl_compose_body' ) ? lavtheme_cs_dl_compose_body( 'wp-dash', 'plugins/wp-dash/template.php' ) : '';
	if ( '' === $body ) {
		$file = get_theme_file_path( 'plugins/wp-dash/template.php' );
		if ( is_readable( $file ) ) {
			ob_start();
			include $file;
			$body = (string) ob_get_clean();
		}
	}
	if ( '' !== trim( $body ) ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered template / admin-authored override.
	}
}
// admin_notices always fires at the top of the Dashboard content (single, reliable).
add_action( 'admin_notices', 'lavtheme_wp_dash_skin_hero', 5 );

/** Inject the dashboard JS into the Dashboard footer. */
function lavtheme_wp_dash_skin_js() {
	if ( ! lavtheme_wp_dash_is_home() ) {
		return;
	}
	$js = lavtheme_wp_dash_ctx_get( 'js' );
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-wpdash-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored, closing tag neutralised on save.
	}
}
add_action( 'admin_footer', 'lavtheme_wp_dash_skin_js' );
