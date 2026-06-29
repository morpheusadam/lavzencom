<?php
/**
 * Login / Register body — split-panel auth screen. Presentation only;
 * authentication is handled by lavzen_auth_handle() (WordPress core).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_reg_enabled = (bool) get_option( 'users_can_register' );
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$lav_action    = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$lav_tab       = 'register' === $lav_action ? 'register' : 'login';
$lav_regdone   = isset( $_GET['lavreg'] ) && 'sent' === sanitize_key( wp_unslash( $_GET['lavreg'] ) );
$lav_loggedout = isset( $_GET['loggedout'] );
// phpcs:enable WordPress.Security.NonceVerification.Recommended
$lav_err  = function_exists( 'lavzen_auth_error' ) ? lavzen_auth_error() : '';
$lav_self = function_exists( 'lavzen_login_url' ) ? lavzen_login_url() : wp_login_url();
?>
<section class="lav-auth<?php echo 'register' === $lav_tab ? ' show-register' : ''; ?>" aria-label="<?php esc_attr_e( 'Account access', 'lavzentheme' ); ?>">
	<div class="glass la-card">
		<aside class="la-brand">
			<div class="la-brand-bg" aria-hidden="true"></div>
			<div class="la-brand-in">
				<a class="la-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
				<h2 class="la-brand-h"><?php esc_html_e( 'Build, automate & rank — from one account.', 'lavzentheme' ); ?></h2>
				<ul class="la-brand-points">
					<li><span class="la-tick" aria-hidden="true"></span><?php esc_html_e( 'Instant access to every download you own', 'lavzentheme' ); ?></li>
					<li><span class="la-tick" aria-hidden="true"></span><?php esc_html_e( 'Track orders & licenses in one dashboard', 'lavzentheme' ); ?></li>
					<li><span class="la-tick" aria-hidden="true"></span><?php esc_html_e( 'Secure checkout & priority support', 'lavzentheme' ); ?></li>
				</ul>
			</div>
		</aside>
		<div class="la-form-wrap">
			<a class="la-home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
			<div class="la-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Sign in or create an account', 'lavzentheme' ); ?>">
				<button class="la-tab" type="button" role="tab" id="la-tab-login" aria-controls="la-pane-login" data-auth-tab="login" aria-selected="<?php echo 'login' === $lav_tab ? 'true' : 'false'; ?>"><?php esc_html_e( 'Sign in', 'lavzentheme' ); ?></button>
				<button class="la-tab" type="button" role="tab" id="la-tab-register" aria-controls="la-pane-register" data-auth-tab="register" aria-selected="<?php echo 'register' === $lav_tab ? 'true' : 'false'; ?>"><?php esc_html_e( 'Create account', 'lavzentheme' ); ?></button>
			</div>
			<?php if ( '' !== $lav_err ) : ?>
				<div class="la-alert la-alert--error" role="alert"><?php echo esc_html( $lav_err ); ?></div>
			<?php elseif ( $lav_regdone ) : ?>
				<div class="la-alert la-alert--success" role="status"><?php esc_html_e( 'Account created — check your email to set your password, then sign in.', 'lavzentheme' ); ?></div>
			<?php elseif ( $lav_loggedout ) : ?>
				<div class="la-alert la-alert--success" role="status"><?php esc_html_e( 'You have been signed out.', 'lavzentheme' ); ?></div>
			<?php endif; ?>

			<form class="la-pane la-pane-login" id="la-pane-login" role="tabpanel" aria-labelledby="la-tab-login" method="post" action="<?php echo esc_url( $lav_self ); ?>" novalidate>
				<input type="hidden" name="lav_auth" value="login">
				<?php wp_nonce_field( 'lav_login', 'lav_login_nonce' ); ?>
				<header class="la-pane-head"><h1><?php esc_html_e( 'Welcome back', 'lavzentheme' ); ?></h1><p><?php esc_html_e( 'Sign in to your account to continue.', 'lavzentheme' ); ?></p></header>
				<label class="la-field">
					<span class="la-label"><?php esc_html_e( 'Username or email', 'lavzentheme' ); ?></span>
					<span class="la-input"><span class="la-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span><input type="text" name="log" autocomplete="username" required placeholder="you@example.com" aria-required="true"></span>
				</label>
				<label class="la-field">
					<span class="la-label"><?php esc_html_e( 'Password', 'lavzentheme' ); ?></span>
					<span class="la-input"><span class="la-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><input type="password" name="pwd" autocomplete="current-password" required placeholder="••••••••" aria-required="true"><button type="button" class="la-toggle" data-pwtoggle aria-label="<?php esc_attr_e( 'Show password', 'lavzentheme' ); ?>" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></button></span>
				</label>
				<div class="la-row">
					<label class="la-check"><input type="checkbox" name="rememberme" value="forever"> <span><?php esc_html_e( 'Remember me', 'lavzentheme' ); ?></span></label>
					<a class="la-link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'lavzentheme' ); ?></a>
				</div>
				<button type="submit" class="btn btn-primary la-submit"><span class="la-submit-t"><?php esc_html_e( 'Sign in', 'lavzentheme' ); ?></span><span class="la-spin" aria-hidden="true"></span></button>
				<p class="la-alt la-alt-cta"><?php esc_html_e( "Haven't registered yet?", 'lavzentheme' ); ?> <a class="la-link la-link-strong" href="<?php echo esc_url( function_exists( 'lavzen_login_url' ) ? lavzen_login_url( 'register' ) : '#' ); ?>" data-auth-tab="register"><?php esc_html_e( 'Create an account →', 'lavzentheme' ); ?></a></p>
			</form>

			<form class="la-pane la-pane-register" id="la-pane-register" role="tabpanel" aria-labelledby="la-tab-register" method="post" action="<?php echo esc_url( $lav_self ); ?>" novalidate>
				<input type="hidden" name="lav_auth" value="register">
				<?php wp_nonce_field( 'lav_register', 'lav_register_nonce' ); ?>
				<?php if ( ! $lav_reg_enabled ) : ?><div class="la-alert la-alert--error" role="alert"><?php esc_html_e( 'Sign-ups are currently closed. (Admin: enable “Anyone can register” in Settings → General.)', 'lavzentheme' ); ?></div><?php endif; ?>
				<header class="la-pane-head"><h1><?php esc_html_e( 'Create your account', 'lavzentheme' ); ?></h1><p><?php esc_html_e( 'Choose a username, email and password to get started.', 'lavzentheme' ); ?></p></header>
				<label class="la-field">
					<span class="la-label"><?php esc_html_e( 'Username', 'lavzentheme' ); ?></span>
					<span class="la-input"><span class="la-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span><input type="text" name="user_login" autocomplete="username" required placeholder="yourname" aria-required="true"></span>
				</label>
				<label class="la-field">
					<span class="la-label"><?php esc_html_e( 'Email address', 'lavzentheme' ); ?></span>
					<span class="la-input"><span class="la-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span><input type="email" name="user_email" autocomplete="email" required placeholder="you@example.com" aria-required="true"></span>
				</label>
				<label class="la-field">
					<span class="la-label"><?php esc_html_e( 'Password', 'lavzentheme' ); ?></span>
					<span class="la-input"><span class="la-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><input type="password" name="user_pass" autocomplete="new-password" required minlength="8" placeholder="<?php esc_attr_e( 'At least 8 characters', 'lavzentheme' ); ?>" aria-required="true"></span>
				</label>
				<button type="submit" class="btn btn-primary la-submit"><span class="la-submit-t"><?php esc_html_e( 'Create account', 'lavzentheme' ); ?></span><span class="la-spin" aria-hidden="true"></span></button>
				<p class="la-fine"><?php esc_html_e( 'By creating an account you agree to our terms & privacy policy.', 'lavzentheme' ); ?></p>
				<p class="la-alt"><?php esc_html_e( 'Already have an account?', 'lavzentheme' ); ?> <button type="button" class="la-link" data-auth-tab="login"><?php esc_html_e( 'Sign in', 'lavzentheme' ); ?></button></p>
			</form>
		</div>
	</div>
</section>
