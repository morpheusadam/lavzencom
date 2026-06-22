<?php
/**
 * My Account dashboard body — editable in Code Studio ("My Account" → Template).
 *
 * Inherits the Liquid-Glass design. A glass sidebar (Dashboard / Orders /
 * Downloads / Profile / Checkout / Log out) + a content area that swaps by the
 * `?view=` param. The data tables/forms come from EDD's own shortcodes
 * ([purchase_history], [download_history], [edd_profile_editor]) so they stay
 * reliable across EDD versions; the theme only owns the design shell. Scoped
 * under .lav-account. Rendered by template-parts/account-page-template.php.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_view = function_exists( 'lavtheme_account_view' ) ? lavtheme_account_view() : 'dashboard';
$lav_acc  = function_exists( 'lavtheme_account_url' );

/* ---- guest (not signed in) ---- */
if ( ! is_user_logged_in() ) {
	$lav_login = function_exists( 'lavtheme_edd_page_url' ) ? lavtheme_edd_page_url( 'login' ) : '';
	$lav_login = $lav_login ? $lav_login : wp_login_url( get_permalink() );
	?>
	<section class="lav-account is-guest" aria-label="<?php esc_attr_e( 'My Account', 'lavtheme' ); ?>">
		<div class="glass la-guest">
			<span class="la-guest-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
			<h1><?php esc_html_e( 'Sign in to your account', 'lavtheme' ); ?></h1>
			<p><?php esc_html_e( 'Your orders, downloads and profile — all in one place.', 'lavtheme' ); ?></p>
			<div class="la-guest-cta">
				<a class="btn btn-primary" href="<?php echo esc_url( $lav_login ); ?>"><?php esc_html_e( 'Log in', 'lavtheme' ); ?></a>
				<?php if ( get_option( 'users_can_register' ) ) : ?>
					<a class="btn btn-ghost" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Create account', 'lavtheme' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return;
}

/* ---- signed in ---- */
$lav_u = wp_get_current_user();

$lav_nav = array(
	'dashboard' => array(
		'label' => __( 'Dashboard', 'lavtheme' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
	),
	'orders'    => array(
		'label' => __( 'My Orders', 'lavtheme' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/></svg>',
	),
	'downloads' => array(
		'label' => __( 'My Downloads', 'lavtheme' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
	),
	'profile'   => array(
		'label' => __( 'My Profile', 'lavtheme' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
	),
);
?>
<section class="lav-account" aria-label="<?php esc_attr_e( 'My Account', 'lavtheme' ); ?>">

	<aside class="glass la-side">
		<div class="la-user">
			<span class="la-av"><?php echo get_avatar( $lav_u->ID, 96, '', $lav_u->display_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="la-id">
				<b><?php echo esc_html( $lav_u->display_name ); ?></b>
				<span class="la-mail"><?php echo esc_html( $lav_u->user_email ); ?></span>
				<span class="la-status"><span class="la-dot" aria-hidden="true"></span><?php esc_html_e( 'Signed in', 'lavtheme' ); ?></span>
			</span>
		</div>

		<nav class="la-nav" aria-label="<?php esc_attr_e( 'Account navigation', 'lavtheme' ); ?>">
			<?php foreach ( $lav_nav as $lav_key => $lav_item ) : ?>
				<a class="la-navitem<?php echo $lav_view === $lav_key ? ' is-active' : ''; ?>" href="<?php echo esc_url( $lav_acc ? lavtheme_account_url( $lav_key ) : '#' ); ?>"<?php echo $lav_view === $lav_key ? ' aria-current="page"' : ''; ?>>
					<span class="la-ic" aria-hidden="true"><?php echo $lav_item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php echo esc_html( $lav_item['label'] ); ?>
				</a>
			<?php endforeach; ?>

			<?php $lav_checkout = function_exists( 'lavtheme_edd_page_url' ) ? lavtheme_edd_page_url( 'checkout' ) : ''; ?>
			<?php if ( $lav_checkout ) : ?>
				<a class="la-navitem" href="<?php echo esc_url( $lav_checkout ); ?>">
					<span class="la-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg></span>
					<?php esc_html_e( 'Checkout', 'lavtheme' ); ?>
				</a>
			<?php endif; ?>

			<a class="la-navitem la-logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
				<span class="la-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg></span>
				<?php esc_html_e( 'Log out', 'lavtheme' ); ?>
			</a>
		</nav>
	</aside>

	<div class="la-main">

		<?php if ( 'dashboard' === $lav_view ) : ?>
			<?php
			$lav_orders = function_exists( 'edd_count_purchases_of_customer' ) ? (int) edd_count_purchases_of_customer() : 0;
			$lav_dlc    = 0;
			if ( function_exists( 'edd_get_users_purchased_products' ) ) {
				$lav_p   = edd_get_users_purchased_products( $lav_u->ID );
				$lav_dlc = is_array( $lav_p ) ? count( $lav_p ) : 0;
			}
			?>
			<header class="la-head"><h1><?php printf( esc_html__( 'Welcome back, %s', 'lavtheme' ), esc_html( $lav_u->display_name ) ); ?></h1><p><?php esc_html_e( 'Here is a quick look at your account.', 'lavtheme' ); ?></p></header>

			<div class="la-stats">
				<a class="glass la-stat" href="<?php echo esc_url( $lav_acc ? lavtheme_account_url( 'orders' ) : '#' ); ?>">
					<span class="la-stat-n"><?php echo esc_html( number_format_i18n( $lav_orders ) ); ?></span>
					<span class="la-stat-l"><?php esc_html_e( 'Orders', 'lavtheme' ); ?></span>
				</a>
				<a class="glass la-stat" href="<?php echo esc_url( $lav_acc ? lavtheme_account_url( 'downloads' ) : '#' ); ?>">
					<span class="la-stat-n"><?php echo esc_html( number_format_i18n( $lav_dlc ) ); ?></span>
					<span class="la-stat-l"><?php esc_html_e( 'Products', 'lavtheme' ); ?></span>
				</a>
				<div class="glass la-stat">
					<span class="la-stat-n"><?php echo esc_html( date_i18n( 'M Y', strtotime( $lav_u->user_registered ) ) ); ?></span>
					<span class="la-stat-l"><?php esc_html_e( 'Member since', 'lavtheme' ); ?></span>
				</div>
			</div>

			<div class="glass la-panel la-welcome">
				<h2><?php esc_html_e( 'Pick up where you left off', 'lavtheme' ); ?></h2>
				<p><?php esc_html_e( 'Review your orders, grab your downloads, or update your profile details.', 'lavtheme' ); ?></p>
				<div class="la-quick">
					<a class="btn btn-primary" href="<?php echo esc_url( $lav_acc ? lavtheme_account_url( 'downloads' ) : '#' ); ?>"><?php esc_html_e( 'Go to downloads', 'lavtheme' ); ?></a>
					<a class="btn btn-ghost" href="<?php echo esc_url( $lav_acc ? lavtheme_account_url( 'orders' ) : '#' ); ?>"><?php esc_html_e( 'View orders', 'lavtheme' ); ?></a>
				</div>
			</div>

			<?php
			$lav_shop_u = function_exists( 'lavtheme_shop_url' ) ? lavtheme_shop_url() : home_url( '/' );
			$lav_qa     = array(
				array( $lav_shop_u, __( 'Browse shop', 'lavtheme' ), __( 'Discover products', 'lavtheme' ), '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/></svg>' ),
				array( $lav_acc ? lavtheme_account_url( 'downloads' ) : '#', __( 'My downloads', 'lavtheme' ), __( 'Your files', 'lavtheme' ), '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>' ),
				array( $lav_acc ? lavtheme_account_url( 'orders' ) : '#', __( 'My orders', 'lavtheme' ), __( 'Purchase history', 'lavtheme' ), '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h12M3 12h18M3 17h8"/><circle cx="18" cy="7" r="2"/><circle cx="14" cy="17" r="2"/></svg>' ),
				array( $lav_acc ? lavtheme_account_url( 'profile' ) : '#', __( 'Edit profile', 'lavtheme' ), __( 'Name, email, password', 'lavtheme' ), '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>' ),
			);
			?>
			<section class="la-block">
				<div class="la-section-h"><h2><?php esc_html_e( 'Quick actions', 'lavtheme' ); ?></h2></div>
				<div class="la-actions">
					<?php foreach ( $lav_qa as $lav_a ) : ?>
						<a class="glass la-action" href="<?php echo esc_url( $lav_a[0] ); ?>">
							<span class="la-aico" aria-hidden="true"><?php echo $lav_a[3]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<b><?php echo esc_html( $lav_a[1] ); ?></b>
							<span><?php echo esc_html( $lav_a[2] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>

			<?php
			$lav_pop = function_exists( 'edd_get_download' ) ? get_posts( array( 'post_type' => 'download', 'post_status' => 'publish', 'posts_per_page' => 3, 'orderby' => 'meta_value_num', 'meta_key' => '_edd_download_sales', 'order' => 'DESC', 'fields' => 'ids', 'no_found_rows' => true ) ) : array();
			if ( $lav_pop ) :
				?>
				<section class="la-block">
					<div class="la-section-h"><h2><?php esc_html_e( 'Picked for you', 'lavtheme' ); ?></h2><a href="<?php echo esc_url( $lav_shop_u ); ?>"><?php esc_html_e( 'See all', 'lavtheme' ); ?> &rarr;</a></div>
					<div class="la-reco">
						<?php
						foreach ( $lav_pop as $lav_pid ) :
							$lav_price = function_exists( 'edd_price' ) ? wp_strip_all_tags( edd_price( $lav_pid, false ) ) : '';
							?>
							<a class="glass la-prod" href="<?php echo esc_url( get_permalink( $lav_pid ) ); ?>">
								<span class="la-prod-thumb"><?php if ( has_post_thumbnail( $lav_pid ) ) : ?><img src="<?php echo esc_url( get_the_post_thumbnail_url( $lav_pid, 'medium' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $lav_pid ) ); ?>" loading="lazy"><?php endif; ?></span>
								<span class="la-prod-b">
									<span class="la-prod-t"><?php echo esc_html( get_the_title( $lav_pid ) ); ?></span>
									<?php if ( '' !== $lav_price ) : ?><span class="la-prod-p"><?php echo esc_html( $lav_price ); ?></span><?php endif; ?>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

		<?php elseif ( 'orders' === $lav_view ) : ?>
			<header class="la-head"><h1><?php esc_html_e( 'My Orders', 'lavtheme' ); ?></h1></header>
			<div class="glass la-panel la-edd">
				<?php
				if ( shortcode_exists( 'purchase_history' ) ) {
					echo do_shortcode( '[purchase_history]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo '<p class="la-empty">' . esc_html__( 'Your order history is not available right now.', 'lavtheme' ) . '</p>';
				}
				?>
			</div>

		<?php elseif ( 'downloads' === $lav_view ) : ?>
			<header class="la-head"><h1><?php esc_html_e( 'My Downloads', 'lavtheme' ); ?></h1></header>
			<div class="glass la-panel la-edd">
				<?php
				if ( shortcode_exists( 'download_history' ) ) {
					echo do_shortcode( '[download_history]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo '<p class="la-empty">' . esc_html__( 'You have no downloads yet.', 'lavtheme' ) . '</p>';
				}
				?>
			</div>

		<?php else : ?>
			<header class="la-head"><h1><?php esc_html_e( 'My Profile', 'lavtheme' ); ?></h1></header>
			<div class="glass la-panel la-edd">
				<?php
				if ( shortcode_exists( 'edd_profile_editor' ) ) {
					echo do_shortcode( '[edd_profile_editor]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo '<p class="la-empty">' . esc_html__( 'Profile editing is not available right now.', 'lavtheme' ) . '</p>';
				}
				?>
			</div>
		<?php endif; ?>

		<aside class="glass la-panel la-support">
			<div class="la-support-t">
				<b><?php esc_html_e( 'Need a hand?', 'lavtheme' ); ?></b>
				<span><?php esc_html_e( 'Questions about an order or a download? We are here to help.', 'lavtheme' ); ?></span>
			</div>
			<a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>"><?php esc_html_e( 'Contact support', 'lavtheme' ); ?></a>
		</aside>

	</div>

</section>
