<?php
/**
 * Account + Auth subsystem — page routing, helpers, and the login/register handler.
 *
 * Ported from the legacy inc/code-studio-{auth,account}.php (the account/auth
 * parts; the Code Studio CSS/JS injection is replaced by the Context system +
 * Injector). Authentication is delegated to WordPress core (wp_signon /
 * wp_create_user) on nonce-checked self-POSTs — behavior preserved per the brief.
 * Page ids come from lavzen_{account,auth}_page_id() (conditionals.php, reading
 * the legacy option keys for cutover continuity).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

/* ============================ EDD page urls ============================ */

if ( ! function_exists( 'lavzen_edd_page_url' ) ) {
	/**
	 * A useful EDD/account URL with graceful fallbacks.
	 */
	function lavzen_edd_page_url( string $which ): string {
		$map = array(
			'history'          => 'purchase_history_page',
			'purchase_history' => 'purchase_history_page',
			'checkout'         => 'purchase_page',
			'login'            => 'login_page',
		);
		if ( isset( $map[ $which ] ) && function_exists( 'edd_get_option' ) ) {
			$pid = (int) edd_get_option( $map[ $which ], 0 );
			if ( $pid && get_post( $pid ) ) {
				return get_permalink( $pid );
			}
		}
		if ( 'checkout' === $which && function_exists( 'edd_get_checkout_uri' ) ) {
			return edd_get_checkout_uri();
		}
		return '';
	}
}

/* ============================ auth helpers ============================ */

function lavzen_login_url( string $view = '' ): string {
	$id   = function_exists( 'lavzen_auth_page_id' ) ? lavzen_auth_page_id() : 0;
	$base = $id ? get_permalink( $id ) : wp_login_url();
	return 'register' === $view ? add_query_arg( 'action', 'register', $base ) : $base;
}

function lavzen_auth_redirect_for( $user ): string {
	if ( $user instanceof WP_User && in_array( 'administrator', (array) $user->roles, true ) ) {
		return admin_url();
	}
	return home_url( '/' );
}

add_filter(
	'login_redirect',
	static function ( $redirect_to, $requested, $user ) {
		return $user instanceof WP_User ? lavzen_auth_redirect_for( $user ) : $redirect_to;
	},
	10,
	3
);

function lavzen_auth_error( $msg = null ): string {
	static $e = '';
	if ( null !== $msg ) {
		$e = (string) $msg;
	}
	return $e;
}

/** Handle the login/register self-POST on the auth page (core does the auth). */
function lavzen_auth_handle(): void {
	if ( is_admin() || ! ( function_exists( 'lavzen_is_auth' ) && lavzen_is_auth() ) ) {
		return;
	}
	if ( is_user_logged_in() && empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_safe_redirect( lavzen_auth_redirect_for( wp_get_current_user() ) );
		exit;
	}
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		return;
	}
	$mode = isset( $_POST['lav_auth'] ) ? sanitize_key( wp_unslash( $_POST['lav_auth'] ) ) : '';

	if ( 'login' === $mode ) {
		if ( ! isset( $_POST['lav_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lav_login_nonce'] ) ), 'lav_login' ) ) {
			lavzen_auth_error( __( 'Security check failed. Please try again.', 'lavzentheme' ) );
			return;
		}
		$creds = array(
			'user_login'    => isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '',
			'user_password' => isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '', // never sanitise a password.
			'remember'      => ! empty( $_POST['rememberme'] ),
		);
		$user = wp_signon( $creds, is_ssl() );
		if ( is_wp_error( $user ) ) {
			lavzen_auth_error( wp_strip_all_tags( $user->get_error_message() ) );
			return;
		}
		wp_safe_redirect( lavzen_auth_redirect_for( $user ) );
		exit;
	}

	if ( 'register' === $mode ) {
		if ( ! get_option( 'users_can_register' ) ) {
			lavzen_auth_error( __( 'Registration is currently disabled.', 'lavzentheme' ) );
			return;
		}
		if ( ! isset( $_POST['lav_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lav_register_nonce'] ) ), 'lav_register' ) ) {
			lavzen_auth_error( __( 'Security check failed. Please try again.', 'lavzentheme' ) );
			return;
		}
		$login = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ), true ) : '';
		$email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$pass  = isset( $_POST['user_pass'] ) ? (string) wp_unslash( $_POST['user_pass'] ) : ''; // never sanitise a password.

		$err = new WP_Error();
		if ( '' === $login || ! validate_username( $login ) ) {
			$err->add( 'login', __( 'Please enter a valid username.', 'lavzentheme' ) );
		} elseif ( username_exists( $login ) ) {
			$err->add( 'login', __( 'That username is already taken.', 'lavzentheme' ) );
		}
		if ( ! is_email( $email ) ) {
			$err->add( 'email', __( 'Please enter a valid email address.', 'lavzentheme' ) );
		} elseif ( email_exists( $email ) ) {
			$err->add( 'email', __( 'That email is already registered.', 'lavzentheme' ) );
		}
		if ( strlen( $pass ) < 8 ) {
			$err->add( 'pass', __( 'Password must be at least 8 characters.', 'lavzentheme' ) );
		}
		$err = apply_filters( 'registration_errors', $err, $login, $email );
		if ( is_wp_error( $err ) && $err->has_errors() ) {
			lavzen_auth_error( wp_strip_all_tags( $err->get_error_message() ) );
			return;
		}
		$uid = wp_create_user( $login, $pass, $email );
		if ( is_wp_error( $uid ) ) {
			lavzen_auth_error( wp_strip_all_tags( $uid->get_error_message() ) );
			return;
		}
		if ( function_exists( 'wp_new_user_notification' ) ) {
			wp_new_user_notification( $uid, null, 'admin' );
		}
		$user = get_user_by( 'id', $uid );
		wp_set_current_user( $uid );
		wp_set_auth_cookie( $uid, true, is_ssl() );
		if ( $user instanceof WP_User ) {
			do_action( 'wp_login', $user->user_login, $user );
		}
		wp_safe_redirect( lavzen_auth_redirect_for( $user ) );
		exit;
	}
}
add_action( 'template_redirect', 'lavzen_auth_handle', 5 );

/* ============================ account helpers ============================ */

/** Current account sub-view from ?view= (dashboard|orders|downloads|profile). */
function lavzen_account_view(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$v = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'dashboard';
	return in_array( $v, array( 'dashboard', 'orders', 'downloads', 'profile' ), true ) ? $v : 'dashboard';
}

/** Account page URL for a sub-view. */
function lavzen_account_url( string $view = 'dashboard' ): string {
	$id   = function_exists( 'lavzen_account_page_id' ) ? lavzen_account_page_id() : 0;
	$base = $id ? get_permalink( $id ) : home_url( '/' );
	return 'dashboard' === $view ? $base : add_query_arg( 'view', $view, $base );
}

/* ============================ routing ============================ */

add_filter(
	'template_include',
	static function ( $template ) {
		if ( is_admin() ) {
			return $template;
		}
		if ( function_exists( 'lavzen_is_auth' ) && lavzen_is_auth() ) {
			$t = get_theme_file_path( 'template-parts/auth-page-template.php' );
			return is_readable( $t ) ? $t : $template;
		}
		if ( function_exists( 'lavzen_is_account' ) && lavzen_is_account() ) {
			$t = get_theme_file_path( 'template-parts/account-page-template.php' );
			return is_readable( $t ) ? $t : $template;
		}
		return $template;
	},
	99
);

/* ============================ page seeding ============================ */

add_action(
	'admin_init',
	static function () {
		if ( ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		// Login page.
		if ( ! get_option( 'lavtheme_login_page_id' ) ) {
			$existing = get_page_by_path( 'login' );
			$id       = $existing ? (int) $existing->ID : (int) wp_insert_post( array( 'post_title' => __( 'Login', 'lavzentheme' ), 'post_name' => 'login', 'post_status' => 'publish', 'post_type' => 'page', 'comment_status' => 'closed', 'ping_status' => 'closed' ) );
			if ( $id && ! is_wp_error( $id ) ) {
				update_option( 'lavtheme_login_page_id', $id );
			}
		}
		// Account page.
		if ( ! get_option( 'lavtheme_account_page_id' ) ) {
			$existing = get_page_by_path( 'account' );
			$id       = $existing ? (int) $existing->ID : (int) wp_insert_post( array( 'post_title' => __( 'My Account', 'lavzentheme' ), 'post_name' => 'account', 'post_status' => 'publish', 'post_type' => 'page' ) );
			if ( $id && ! is_wp_error( $id ) ) {
				update_option( 'lavtheme_account_page_id', $id );
			}
		}
	}
);
