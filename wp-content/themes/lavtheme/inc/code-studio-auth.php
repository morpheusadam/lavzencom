<?php
/**
 * Theme Code Studio — Login / Register context (the "auth" page).
 *
 * A designed front-end auth page (seeded at /login/), rendered with the theme's
 * Liquid-Glass design and editable in Code Studio exactly like the other
 * dl-style contexts (Global + Template, full Export/Import). It reuses the
 * downloads (dl) plumbing — the panel dispatches the 'auth' context to
 * lavtheme_cs_dl_* via ctxIsDl(); the 'auth' branches live in
 * code-studio-downloads.php / Lav_CS_Source_Reader.
 *
 * Security: authentication is done by WordPress core (wp_signon /
 * register_new_user) on a nonce-checked self-POST — no custom password handling.
 *
 * Role-based redirect after login: administrators → wp-admin; everyone else →
 * the site home page.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* ============================ page + routing ============================ */

/** The "Login" page id (seeded once; filterable). */
function lavtheme_auth_page_id() {
	$id = (int) get_option( 'lavtheme_login_page_id', 0 );
	return (int) apply_filters( 'lavtheme_login_page_id', $id );
}

/** True on the Login/Register page. */
function lavtheme_is_auth() {
	$id = lavtheme_auth_page_id();
	return $id && is_page( $id );
}

/** URL of the login page (optionally a sub-view, e.g. 'register'). */
function lavtheme_login_url( $view = '' ) {
	$id   = lavtheme_auth_page_id();
	$base = $id ? get_permalink( $id ) : wp_login_url();
	if ( 'register' === $view ) {
		return add_query_arg( 'action', 'register', $base );
	}
	return $base;
}

/** Where a user goes after authenticating: admins → wp-admin, others → home. */
function lavtheme_auth_redirect_for( $user ) {
	if ( $user instanceof WP_User && in_array( 'administrator', (array) $user->roles, true ) ) {
		return admin_url();
	}
	return home_url( '/' );
}

/** Apply the same role rule to WordPress's own login (wp-login.php). */
function lavtheme_auth_login_redirect( $redirect_to, $requested, $user ) {
	if ( $user instanceof WP_User ) {
		return lavtheme_auth_redirect_for( $user );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'lavtheme_auth_login_redirect', 10, 3 );

/** Store / read the current auth error message (rendered by the template). */
function lavtheme_auth_error( $msg = null ) {
	static $e = '';
	if ( null !== $msg ) {
		$e = (string) $msg;
	}
	return $e;
}

/**
 * Handle the login / register POST on the auth page, before any output.
 * Authentication is delegated to WordPress core; we only validate the nonce and
 * route the result.
 */
function lavtheme_auth_handle() {
	if ( is_admin() || ! lavtheme_is_auth() ) {
		return;
	}

	// Already signed in → send them where they belong (no login form for them).
	if ( is_user_logged_in() && empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_safe_redirect( lavtheme_auth_redirect_for( wp_get_current_user() ) );
		exit;
	}

	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		return;
	}
	$mode = isset( $_POST['lav_auth'] ) ? sanitize_key( wp_unslash( $_POST['lav_auth'] ) ) : '';

	if ( 'login' === $mode ) {
		if ( ! isset( $_POST['lav_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lav_login_nonce'] ) ), 'lav_login' ) ) {
			lavtheme_auth_error( __( 'Security check failed. Please try again.', 'lavtheme' ) );
			return;
		}
		$creds = array(
			'user_login'    => isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '',
			'user_password' => isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '', // never sanitise a password
			'remember'      => ! empty( $_POST['rememberme'] ),
		);
		$user = wp_signon( $creds, is_ssl() );
		if ( is_wp_error( $user ) ) {
			lavtheme_auth_error( wp_strip_all_tags( $user->get_error_message() ) );
			return;
		}
		wp_safe_redirect( lavtheme_auth_redirect_for( $user ) );
		exit;
	}

	if ( 'register' === $mode ) {
		if ( ! get_option( 'users_can_register' ) ) {
			lavtheme_auth_error( __( 'Registration is currently disabled.', 'lavtheme' ) );
			return;
		}
		if ( ! isset( $_POST['lav_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lav_register_nonce'] ) ), 'lav_register' ) ) {
			lavtheme_auth_error( __( 'Security check failed. Please try again.', 'lavtheme' ) );
			return;
		}
		$login = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ), true ) : '';
		$email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$pass  = isset( $_POST['user_pass'] ) ? (string) wp_unslash( $_POST['user_pass'] ) : ''; // never sanitise a password

		$err = new WP_Error();
		if ( '' === $login || ! validate_username( $login ) ) {
			$err->add( 'login', __( 'Please enter a valid username.', 'lavtheme' ) );
		} elseif ( username_exists( $login ) ) {
			$err->add( 'login', __( 'That username is already taken.', 'lavtheme' ) );
		}
		if ( ! is_email( $email ) ) {
			$err->add( 'email', __( 'Please enter a valid email address.', 'lavtheme' ) );
		} elseif ( email_exists( $email ) ) {
			$err->add( 'email', __( 'That email is already registered.', 'lavtheme' ) );
		}
		if ( strlen( $pass ) < 8 ) {
			$err->add( 'pass', __( 'Password must be at least 8 characters.', 'lavtheme' ) );
		}
		// Let plugins (EDD, etc.) add their own registration checks.
		$err = apply_filters( 'registration_errors', $err, $login, $email );
		if ( is_wp_error( $err ) && $err->has_errors() ) {
			lavtheme_auth_error( wp_strip_all_tags( $err->get_error_message() ) );
			return;
		}

		$uid = wp_create_user( $login, $pass, $email );
		if ( is_wp_error( $uid ) ) {
			lavtheme_auth_error( wp_strip_all_tags( $uid->get_error_message() ) );
			return;
		}
		// Notify the admin a new user registered (the user chose their own password).
		if ( function_exists( 'wp_new_user_notification' ) ) {
			wp_new_user_notification( $uid, null, 'admin' );
		}
		// Sign the new user in immediately, then route by role.
		$user = get_user_by( 'id', $uid );
		wp_set_current_user( $uid );
		wp_set_auth_cookie( $uid, true, is_ssl() );
		if ( $user instanceof WP_User ) {
			do_action( 'wp_login', $user->user_login, $user );
		}
		wp_safe_redirect( lavtheme_auth_redirect_for( $user ) );
		exit;
	}
}
add_action( 'template_redirect', 'lavtheme_auth_handle', 5 );

/** Run-once: create the "Login" page so the auth design has a home. */
function lavtheme_auth_seed_page() {
	if ( get_option( 'lavtheme_login_page_id' ) ) {
		return;
	}
	if ( ! is_admin() || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$existing = get_page_by_path( 'login' );
	if ( $existing ) {
		update_option( 'lavtheme_login_page_id', (int) $existing->ID );
		return;
	}
	$id = wp_insert_post(
		array(
			'post_title'     => __( 'Login', 'lavtheme' ),
			'post_name'      => 'login',
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_content'   => '',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_option( 'lavtheme_login_page_id', (int) $id );
	}
}
add_action( 'admin_init', 'lavtheme_auth_seed_page' );

/**
 * Render the auth body: the editable Template override, else the real file.
 * Called by the wrapper template (template-parts/auth-page-template.php).
 */
function lavtheme_cs_auth_render() {
	$body = lavtheme_is_auth() ? lavtheme_cs_dl_compose_body( 'auth', 'template-parts/auth.php' ) : '';
	if ( '' !== $body ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered, syntax-checked admin code.
		return;
	}
	$path = get_theme_file_path( 'template-parts/auth.php' );
	if ( is_readable( $path ) ) {
		include $path;
	}
}

/** Route the Login page through its wrapper template. */
function lavtheme_cs_auth_page_template( $template ) {
	if ( is_admin() ) {
		return $template;
	}
	if ( lavtheme_is_auth() ) {
		$custom = get_theme_file_path( 'template-parts/auth-page-template.php' );
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'lavtheme_cs_auth_page_template', 99 );

/** Inject the auth context CSS (override-or-file) in the head — single source. */
function lavtheme_cs_auth_head() {
	if ( ! lavtheme_is_auth() ) {
		return;
	}
	$css = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'auth' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$c = (string) lavtheme_cs_dl_get( 'auth', $r['slug'], 'css' );
			if ( 'design' === $r['slug'] ) {
				$m = (string) lavtheme_cs_dl_get( 'auth', 'design', 'mcss' );
				if ( '' !== trim( $m ) ) {
					$c .= "\n" . $m;
				}
			}
			if ( 'global' === $r['slug'] ) {
				$bg = (string) lavtheme_cs_dl_get( 'auth', 'global', 'bg' );
				if ( '' !== trim( $bg ) ) {
					$c .= "\n" . $bg;
				}
			}
			if ( '' !== trim( $c ) ) {
				$css .= "\n" . $c;
			}
		}
	} else {
		$css = (string) lavtheme_cs_dl_get( 'auth', 'design', 'css' ) . "\n" . (string) lavtheme_cs_dl_get( 'auth', 'design', 'mcss' );
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-auth-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'lavtheme_cs_auth_head', 7 );

/** Inject the auth context JS (override-or-file) in the footer. */
function lavtheme_cs_auth_footer() {
	if ( ! lavtheme_is_auth() ) {
		return;
	}
	$js  = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'auth' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$j = (string) lavtheme_cs_dl_get( 'auth', $r['slug'], 'js' );
			if ( '' !== trim( $j ) ) {
				$js .= ';(function(){' . $j . '})();';
			}
		}
	} else {
		$js = (string) lavtheme_cs_dl_get( 'auth', 'design', 'js' );
	}
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-auth-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_footer', 'lavtheme_cs_auth_footer', 101 );
