<?php
/**
 * Theme Code Studio — My Account context.
 *
 * A unified, designed account dashboard (Dashboard / Orders / Downloads /
 * Profile) wired to EDD, rendered on a seeded "My Account" page. Editable in
 * Code Studio exactly like the Shop/Single contexts: it reuses the downloads
 * (dl) plumbing — the panel dispatches the 'account' context to lavtheme_cs_dl_*
 * (ctxIsDl()), and the 'account' branches live in code-studio-downloads.php /
 * Lav_CS_Source_Reader.
 *
 * Editors: Global (CSS/JS) + Template (HTML/PHP·CSS·JS·Mobile) ←
 * template-parts/account.php + assets/css/account.css + assets/js/account.js.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* ============================ page + routing ============================ */

/** The "My Account" page id (seeded once; filterable). */
function lavtheme_account_page_id() {
	$id = (int) get_option( 'lavtheme_account_page_id', 0 );
	return (int) apply_filters( 'lavtheme_account_page_id', $id );
}

/** True on the My Account page. */
function lavtheme_is_account() {
	$id = lavtheme_account_page_id();
	return $id && is_page( $id );
}

/** The current account view (dashboard|orders|downloads|profile). */
function lavtheme_account_view() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view switch.
	$view    = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'dashboard';
	$allowed = array( 'dashboard', 'orders', 'downloads', 'profile' );
	return in_array( $view, $allowed, true ) ? $view : 'dashboard';
}

/** URL of an account view (e.g. lavtheme_account_url('orders')). */
function lavtheme_account_url( $view = '' ) {
	$id   = lavtheme_account_page_id();
	$base = $id ? get_permalink( $id ) : home_url( '/' );
	if ( $view && 'dashboard' !== $view ) {
		return add_query_arg( 'view', $view, $base );
	}
	return $base;
}

/** Run-once: create the "My Account" page so the dashboard has a home. */
function lavtheme_account_seed_page() {
	if ( get_option( 'lavtheme_account_page_id' ) ) {
		return;
	}
	if ( ! is_admin() || ! current_user_can( 'edit_pages' ) ) {
		return; // only seed from an admin context.
	}

	$existing = get_page_by_path( 'my-account' );
	if ( $existing ) {
		update_option( 'lavtheme_account_page_id', (int) $existing->ID );
		return;
	}

	$id = wp_insert_post(
		array(
			'post_title'     => __( 'My Account', 'lavtheme' ),
			'post_name'      => 'my-account',
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_content'   => '',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_option( 'lavtheme_account_page_id', (int) $id );
	}
}
add_action( 'admin_init', 'lavtheme_account_seed_page' );

/**
 * Render the account body: the editable Template override, else the real file.
 * Called by the wrapper template (template-parts/account-page-template.php).
 */
function lavtheme_cs_account_render() {
	$body = lavtheme_is_account() ? lavtheme_cs_dl_compose_body( 'account', 'template-parts/account.php' ) : '';
	if ( '' !== $body ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered, syntax-checked admin code.
		return;
	}
	$path = get_theme_file_path( 'template-parts/account.php' );
	if ( is_readable( $path ) ) {
		include $path;
	}
}

/**
 * Route the My Account page through its wrapper template.
 *
 * @param string $template Resolved template path.
 * @return string
 */
function lavtheme_cs_account_page_template( $template ) {
	if ( is_admin() ) {
		return $template;
	}
	if ( lavtheme_is_account() ) {
		$custom = get_theme_file_path( 'template-parts/account-page-template.php' );
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'lavtheme_cs_account_page_template', 99 );

/** Inject the account context CSS (override-or-file) in the head — single source. */
function lavtheme_cs_account_head() {
	if ( ! lavtheme_is_account() ) {
		return;
	}
	$css = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'account' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$c = (string) lavtheme_cs_dl_get( 'account', $r['slug'], 'css' );
			if ( 'design' === $r['slug'] ) {
				$m = (string) lavtheme_cs_dl_get( 'account', 'design', 'mcss' );
				if ( '' !== trim( $m ) ) {
					$c .= "\n" . $m;
				}
			}
			if ( 'global' === $r['slug'] ) {
				$bg = (string) lavtheme_cs_dl_get( 'account', 'global', 'bg' );
				if ( '' !== trim( $bg ) ) {
					$c .= "\n" . $bg;
				}
			}
			if ( '' !== trim( $c ) ) {
				$css .= "\n" . $c;
			}
		}
	} else {
		$css = (string) lavtheme_cs_dl_get( 'account', 'design', 'css' ) . "\n" . (string) lavtheme_cs_dl_get( 'account', 'design', 'mcss' );
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-account-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS sanitised on save; file is trusted.
	}
}
add_action( 'wp_head', 'lavtheme_cs_account_head', 7 );

/** Inject the account context JS (override-or-file) in the footer. */
function lavtheme_cs_account_footer() {
	if ( ! lavtheme_is_account() ) {
		return;
	}
	$js  = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'account' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$j = (string) lavtheme_cs_dl_get( 'account', $r['slug'], 'js' );
			if ( '' !== trim( $j ) ) {
				$js .= ';(function(){' . $j . '})();';
			}
		}
	} else {
		$js = (string) lavtheme_cs_dl_get( 'account', 'design', 'js' );
	}
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-account-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored, closing-tag neutralised on save.
	}
}
add_action( 'wp_footer', 'lavtheme_cs_account_footer', 101 );
