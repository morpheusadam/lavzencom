<?php
/**
 * Plugin: Security
 * Description: Hardening controls for the theme. A Code Studio submenu with
 * toggle-able security features. Feature 1: Technology-fingerprint hardening
 * (hide the WordPress/version tells that Wappalyzer, BuiltWith, WhatRuns, etc.
 * read — generator meta, REST/RSD/pingback/oEmbed/emoji discovery, the core
 * version query string, and the X-Powered-By / X-Pingback response headers).
 *
 * Settings live in the `lavtheme_security` option (array of on/off flags).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* ============================== settings ================================== */

/** Default feature flags. */
function lavtheme_security_defaults() {
	return array(
		'fingerprint' => 1, // hide technology/version fingerprint.
	);
}

/** Current settings (option merged over defaults). */
function lavtheme_security_opts() {
	$o = get_option( 'lavtheme_security', array() );
	return wp_parse_args( is_array( $o ) ? $o : array(), lavtheme_security_defaults() );
}

/** Is a feature enabled? */
function lavtheme_security_on( $key ) {
	$o = lavtheme_security_opts();
	return ! empty( $o[ $key ] );
}

/* ============================== admin menu ================================ */

lavtheme_plugins_register_menu(
	array(
		'slug'     => 'lavtheme-security',
		'title'    => __( 'Security', 'lavtheme' ),
		'callback' => 'lavtheme_security_render',
		'position' => 21,
	)
);

/** Save the settings form (own-page POST, nonce + cap guarded). */
function lavtheme_security_save() {
	if ( empty( $_POST['lavtheme_security_save'] ) ) {
		return;
	}
	if ( ! current_user_can( lavtheme_plugins_cap() ) ) {
		return;
	}
	check_admin_referer( 'lavtheme_security' );

	$in  = isset( $_POST['sec'] ) && is_array( $_POST['sec'] ) ? wp_unslash( $_POST['sec'] ) : array();
	$out = array(
		'fingerprint' => empty( $in['fingerprint'] ) ? 0 : 1,
	);
	update_option( 'lavtheme_security', $out );
	add_settings_error( 'lavtheme_security', 'saved', __( 'Security settings saved.', 'lavtheme' ), 'updated' );
}
add_action( 'admin_init', 'lavtheme_security_save' );

/** Render the Security admin screen. */
function lavtheme_security_render() {
	if ( ! current_user_can( lavtheme_plugins_cap() ) ) {
		return;
	}
	$o = lavtheme_security_opts();
	?>
	<div class="wrap lavsec">
		<h1><?php esc_html_e( 'Security', 'lavtheme' ); ?></h1>
		<?php settings_errors( 'lavtheme_security' ); ?>
		<p class="lavsec-lead"><?php esc_html_e( 'Hardening controls for the site. Toggle a feature and save.', 'lavtheme' ); ?></p>

		<form method="post" action="">
			<?php wp_nonce_field( 'lavtheme_security' ); ?>
			<input type="hidden" name="lavtheme_security_save" value="1">

			<div class="lavsec-card">
				<label class="lavsec-row">
					<span class="lavsec-toggle">
						<input type="checkbox" name="sec[fingerprint]" value="1" <?php checked( ! empty( $o['fingerprint'] ) ); ?>>
						<span class="lavsec-slider" aria-hidden="true"></span>
					</span>
					<span class="lavsec-meta">
						<span class="lavsec-h"><?php esc_html_e( 'Hide technology fingerprint', 'lavtheme' ); ?></span>
						<span class="lavsec-d"><?php esc_html_e( 'Strip the WordPress/version signals that Wappalyzer, BuiltWith & WhatRuns read.', 'lavtheme' ); ?></span>
					</span>
				</label>
				<ul class="lavsec-list">
					<li><?php esc_html_e( 'Removes the “generator” meta + version (head, feeds, RSD).', 'lavtheme' ); ?></li>
					<li><?php esc_html_e( 'Removes REST API, RSD, WLW-manifest, oEmbed & shortlink discovery links.', 'lavtheme' ); ?></li>
					<li><?php esc_html_e( 'Removes the emoji detection script/style.', 'lavtheme' ); ?></li>
					<li><?php esc_html_e( 'Drops the X-Pingback, X-Powered-By & X-Redirect-By response headers.', 'lavtheme' ); ?></li>
					<li><?php esc_html_e( 'Strips the core ?ver= query that leaks the WordPress version (keeps file-based cache-busting).', 'lavtheme' ); ?></li>
				</ul>
				<p class="lavsec-note"><?php esc_html_e( 'Note: detectors can still infer WordPress from /wp-content/ asset paths and login cookies. Full path-cloaking is a heavier, riskier change — ask to add it.', 'lavtheme' ); ?></p>
			</div>

			<div class="lavsec-card lavsec-soon">
				<span class="lavsec-h"><?php esc_html_e( 'More features coming', 'lavtheme' ); ?></span>
				<span class="lavsec-d"><?php esc_html_e( 'Login hardening, security headers (CSP/HSTS/X-Frame), XML-RPC lockdown, REST/user-enumeration blocking, file-edit lockdown.', 'lavtheme' ); ?></span>
			</div>

			<p><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save changes', 'lavtheme' ); ?></button></p>
		</form>
	</div>

	<style>
		.lavsec-lead{color:#646970;font-size:14px;margin:6px 0 18px}
		.lavsec-card{max-width:760px;background:#fff;border:1px solid #e2e4e7;border-radius:12px;padding:22px 24px;margin:0 0 18px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
		.lavsec-row{display:flex;align-items:flex-start;gap:16px;cursor:pointer}
		.lavsec-toggle{position:relative;flex:0 0 46px;width:46px;height:26px;margin-top:2px}
		.lavsec-toggle input{position:absolute;opacity:0;width:46px;height:26px;margin:0;cursor:pointer;z-index:2}
		.lavsec-slider{position:absolute;inset:0;background:#c3c4c7;border-radius:999px;transition:.2s}
		.lavsec-slider::before{content:"";position:absolute;top:3px;left:3px;width:20px;height:20px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
		.lavsec-toggle input:checked+.lavsec-slider{background:#2271b1}
		.lavsec-toggle input:checked+.lavsec-slider::before{transform:translateX(20px)}
		.lavsec-toggle input:focus-visible+.lavsec-slider{box-shadow:0 0 0 2px #fff,0 0 0 4px #2271b1}
		.lavsec-meta{display:flex;flex-direction:column;gap:3px}
		.lavsec-h{font-size:15px;font-weight:600;color:#1d2327}
		.lavsec-d{font-size:13px;color:#646970;line-height:1.5}
		.lavsec-list{margin:14px 0 0 62px;color:#50575e;font-size:13px;line-height:1.7;list-style:disc}
		.lavsec-note{margin:14px 0 0 62px;color:#996800;background:#fcf9e8;border:1px solid #f0e6b8;border-radius:8px;padding:10px 12px;font-size:12.5px;line-height:1.5;max-width:600px}
		.lavsec-soon{display:flex;flex-direction:column;gap:4px;opacity:.75}
	</style>
	<?php
}

/* ===================== Feature 1: fingerprint hardening =================== */

/**
 * Register the fingerprint-hiding hooks. Runs at load (before wp_head fires),
 * so the default actions are already registered by core and can be removed.
 */
function lavtheme_security_fingerprint() {
	// Generator / version: <meta name="generator">, feeds, RSD.
	remove_action( 'wp_head', 'wp_generator' );
	add_filter( 'the_generator', '__return_empty_string' );
	remove_action( 'wp_head', 'edd_version_in_header' ); // EDD's own generator meta.

	// Catch-all: buffer wp_head and strip ANY <meta name="generator"> (EDD,
	// Elementor, future plugins) — covers generators added via closures we can't
	// remove by reference.
	add_action( 'wp_head', function () { ob_start(); }, 0 );
	add_action(
		'wp_head',
		function () {
			$head = (string) ob_get_clean();
			echo preg_replace( '/[ \t]*<meta[^>]+name=(["\'])generator\1[^>]*>\s*/i', '', $head ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered core/plugin head markup.
		},
		PHP_INT_MAX
	);

	// Legacy discovery links.
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );

	// REST API discovery (head link + the HTTP Link: header).
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );

	// oEmbed discovery.
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );

	// Emoji detection (also a small perf win, and a WordPress tell).
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	// X-Pingback header + disable the pingback XML-RPC method.
	add_filter(
		'wp_headers',
		function ( $headers ) {
			unset( $headers['X-Pingback'] );
			return $headers;
		}
	);
	add_filter(
		'xmlrpc_methods',
		function ( $methods ) {
			unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
			return $methods;
		}
	);

	// Drop X-Powered-By (PHP) + X-Redirect-By.
	add_action(
		'send_headers',
		function () {
			if ( ! headers_sent() ) {
				header_remove( 'X-Powered-By' );
			}
		},
		0
	);
	add_filter( 'x_redirect_by', '__return_false' );

	// Mask ALL asset versions: replace every ?ver=… with a stable 8-char hash, so
	// fingerprinters can't read "EDD 3.6.8", "jQuery 3.7.1", etc., while per-version
	// cache-busting still works (same version → same hash).
	$mask_ver = function ( $src ) {
		if ( ! $src || false === strpos( $src, 'ver=' ) ) {
			return $src;
		}
		return preg_replace_callback(
			'/([?&]ver=)([^&#]+)/',
			function ( $m ) {
				$salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'lavsec';
				return $m[1] . substr( md5( $m[2] . $salt ), 0, 8 );
			},
			$src
		);
	};
	add_filter( 'style_loader_src', $mask_ver, 9999 );
	add_filter( 'script_loader_src', $mask_ver, 9999 );

	// Drop jQuery Migrate on the front (a WordPress/version tell; modern jQuery
	// rarely needs it).
	add_action(
		'wp_default_scripts',
		function ( $scripts ) {
			if ( is_admin() ) {
				return;
			}
			if ( isset( $scripts->registered['jquery'] ) && ! empty( $scripts->registered['jquery']->deps ) ) {
				$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
			}
		}
	);

	// Remove the wp-embed script and the RSS feed <link> advertisements.
	add_action( 'wp_enqueue_scripts', function () { wp_dequeue_script( 'wp-embed' ); }, 100 );
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
}

if ( lavtheme_security_on( 'fingerprint' ) ) {
	lavtheme_security_fingerprint();
}
