<?php
/**
 * My Account dashboard body — glass sidebar + view that swaps by ?view=.
 * EDD shortcodes provide the data tables; the theme owns the shell.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_view = function_exists( 'lavzen_account_view' ) ? lavzen_account_view() : 'dashboard';

if ( ! is_user_logged_in() ) {
	$lav_login = function_exists( 'lavzen_login_url' ) ? lavzen_login_url() : wp_login_url( get_permalink() );
	?>
	<section class="lav-account is-guest" aria-label="<?php esc_attr_e( 'My Account', 'lavzentheme' ); ?>">
		<div class="glass la-guest">
			<span class="la-guest-ic" aria-hidden="true"><?php lavzen_icon( 'user' ); ?></span>
			<h1><?php esc_html_e( 'Sign in to your account', 'lavzentheme' ); ?></h1>
			<p><?php esc_html_e( 'Your orders, downloads and profile — all in one place.', 'lavzentheme' ); ?></p>
			<div class="la-guest-cta">
				<a class="btn btn-primary" href="<?php echo esc_url( $lav_login ); ?>"><?php esc_html_e( 'Log in', 'lavzentheme' ); ?></a>
				<?php if ( get_option( 'users_can_register' ) ) : ?>
					<a class="btn btn-ghost" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Create account', 'lavzentheme' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return;
}

$lav_u   = wp_get_current_user();
$lav_acc = function_exists( 'lavzen_account_url' );
$lav_nav = array(
	'dashboard' => array( 'label' => __( 'Dashboard', 'lavzentheme' ), 'icon' => lavzen_get_icon( 'grid' ) ),
	'orders'    => array( 'label' => __( 'My Orders', 'lavzentheme' ), 'icon' => lavzen_get_icon( 'bag' ) ),
	'downloads' => array( 'label' => __( 'My Downloads', 'lavzentheme' ), 'icon' => lavzen_get_icon( 'download' ) ),
	'profile'   => array( 'label' => __( 'My Profile', 'lavzentheme' ), 'icon' => lavzen_get_icon( 'user' ) ),
);
?>
<section class="lav-account" aria-label="<?php esc_attr_e( 'My Account', 'lavzentheme' ); ?>">
	<aside class="glass la-side">
		<div class="la-user">
			<span class="la-av"><?php echo get_avatar( $lav_u->ID, 96, '', $lav_u->display_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="la-id">
				<b><?php echo esc_html( $lav_u->display_name ); ?></b>
				<span class="la-mail"><?php echo esc_html( $lav_u->user_email ); ?></span>
				<span class="la-status"><span class="la-dot" aria-hidden="true"></span><?php esc_html_e( 'Signed in', 'lavzentheme' ); ?></span>
			</span>
		</div>
		<nav class="la-nav" aria-label="<?php esc_attr_e( 'Account navigation', 'lavzentheme' ); ?>">
			<?php foreach ( $lav_nav as $lav_key => $lav_item ) : ?>
				<a class="la-navitem<?php echo $lav_view === $lav_key ? ' is-active' : ''; ?>" href="<?php echo esc_url( $lav_acc ? lavzen_account_url( $lav_key ) : '#' ); ?>"<?php echo $lav_view === $lav_key ? ' aria-current="page"' : ''; ?>>
					<span class="la-ic" aria-hidden="true"><?php echo $lav_item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php echo esc_html( $lav_item['label'] ); ?>
				</a>
			<?php endforeach; ?>
			<a class="la-navitem la-logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
				<span class="la-ic" aria-hidden="true"><?php lavzen_icon( 'logout' ); ?></span>
				<?php esc_html_e( 'Log out', 'lavzentheme' ); ?>
			</a>
		</nav>
	</aside>

	<div class="la-main">
		<?php if ( 'dashboard' === $lav_view ) : ?>
			<?php
			$lav_orders = function_exists( 'edd_count_purchases_of_customer' ) ? (int) edd_count_purchases_of_customer() : 0;
			$lav_dlc    = function_exists( 'edd_get_users_purchased_products' ) ? count( (array) edd_get_users_purchased_products( $lav_u->ID ) ) : 0;
			?>
			<header class="la-head"><h1><?php printf( esc_html__( 'Welcome back, %s', 'lavzentheme' ), esc_html( $lav_u->display_name ) ); ?></h1><p><?php esc_html_e( 'Here is a quick look at your account.', 'lavzentheme' ); ?></p></header>
			<div class="la-stats">
				<a class="glass la-stat" href="<?php echo esc_url( $lav_acc ? lavzen_account_url( 'orders' ) : '#' ); ?>"><span class="la-stat-n"><?php echo esc_html( number_format_i18n( $lav_orders ) ); ?></span><span class="la-stat-l"><?php esc_html_e( 'Orders', 'lavzentheme' ); ?></span></a>
				<a class="glass la-stat" href="<?php echo esc_url( $lav_acc ? lavzen_account_url( 'downloads' ) : '#' ); ?>"><span class="la-stat-n"><?php echo esc_html( number_format_i18n( $lav_dlc ) ); ?></span><span class="la-stat-l"><?php esc_html_e( 'Products', 'lavzentheme' ); ?></span></a>
				<div class="glass la-stat"><span class="la-stat-n"><?php echo esc_html( date_i18n( 'M Y', strtotime( $lav_u->user_registered ) ) ); ?></span><span class="la-stat-l"><?php esc_html_e( 'Member since', 'lavzentheme' ); ?></span></div>
			</div>
			<div class="glass la-panel la-welcome">
				<h2><?php esc_html_e( 'Pick up where you left off', 'lavzentheme' ); ?></h2>
				<p><?php esc_html_e( 'Review your orders, grab your downloads, or update your profile details.', 'lavzentheme' ); ?></p>
				<div class="la-quick">
					<a class="btn btn-primary" href="<?php echo esc_url( $lav_acc ? lavzen_account_url( 'downloads' ) : '#' ); ?>"><?php esc_html_e( 'Go to downloads', 'lavzentheme' ); ?></a>
					<a class="btn btn-ghost" href="<?php echo esc_url( $lav_acc ? lavzen_account_url( 'orders' ) : '#' ); ?>"><?php esc_html_e( 'View orders', 'lavzentheme' ); ?></a>
				</div>
			</div>
		<?php elseif ( 'orders' === $lav_view ) : ?>
			<header class="la-head"><h1><?php esc_html_e( 'My Orders', 'lavzentheme' ); ?></h1></header>
			<div class="glass la-panel la-edd"><?php echo shortcode_exists( 'purchase_history' ) ? do_shortcode( '[purchase_history]' ) : '<p class="la-empty">' . esc_html__( 'Your order history is not available right now.', 'lavzentheme' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php elseif ( 'downloads' === $lav_view ) : ?>
			<header class="la-head"><h1><?php esc_html_e( 'My Downloads', 'lavzentheme' ); ?></h1></header>
			<div class="glass la-panel la-edd"><?php echo shortcode_exists( 'download_history' ) ? do_shortcode( '[download_history]' ) : '<p class="la-empty">' . esc_html__( 'You have no downloads yet.', 'lavzentheme' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php else : ?>
			<header class="la-head"><h1><?php esc_html_e( 'My Profile', 'lavzentheme' ); ?></h1></header>
			<div class="glass la-panel la-edd"><?php echo shortcode_exists( 'edd_profile_editor' ) ? do_shortcode( '[edd_profile_editor]' ) : '<p class="la-empty">' . esc_html__( 'Profile editing is not available right now.', 'lavzentheme' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
	</div>
</section>
