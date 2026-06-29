<?php
/**
 * EDD checkout enhancements — a professional, trustworthy purchase flow.
 *
 * Adds (without touching EDD core markup): a "Secure checkout" header above the
 * cart, a trust-badge row under the purchase button, and a beautiful empty-cart
 * state. The two-column layout + styling live in assets/css/checkout.css. All
 * markup is kses-safe (the block checkout passes the empty message through
 * wp_kses_post), so icons are CSS-drawn, not inline SVG.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shop URL for CTAs (graceful fallback).
 *
 * @return string
 */
function lavtheme_checkout_shop_url() {
	if ( function_exists( 'lavtheme_shop_url' ) ) {
		return lavtheme_shop_url();
	}
	if ( function_exists( 'get_post_type_archive_link' ) ) {
		$u = get_post_type_archive_link( 'download' );
		if ( $u ) {
			return $u;
		}
	}
	return home_url( '/' );
}

/* ----------------------------------------------------- empty cart state --- */

/**
 * Replace EDD's plain "Your cart is empty." with a designed empty state.
 *
 * Returned markup is kses-safe (div/span/p/h2/a only) so it survives the block
 * checkout's wp_kses_post(). The icon is drawn in CSS via .lav-ec-ico.
 *
 * @param string $message Default EDD message.
 * @return string
 */
function lavtheme_edd_empty_cart_html( $message ) {
	$shop    = lavtheme_checkout_shop_url();
	$account = function_exists( 'lavtheme_edd_page_url' ) ? lavtheme_edd_page_url( 'purchase_history' ) : '';

	ob_start();
	?>
	<div class="lav-empty-cart">
		<span class="lav-ec-ico" aria-hidden="true"></span>
		<h2 class="lav-ec-title"><?php esc_html_e( 'Your cart is empty', 'lavtheme' ); ?></h2>
		<p class="lav-ec-text"><?php esc_html_e( 'Looks like you haven’t added anything yet. Explore our digital products, tools and templates — instant download, the moment you buy.', 'lavtheme' ); ?></p>
		<div class="lav-ec-actions">
			<a class="lav-ec-btn primary" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Browse the shop', 'lavtheme' ); ?></a>
			<?php if ( $account ) : ?>
				<a class="lav-ec-btn ghost" href="<?php echo esc_url( $account ); ?>"><?php esc_html_e( 'Your purchases', 'lavtheme' ); ?></a>
			<?php endif; ?>
		</div>

		<?php
		// Optional cross-sell: a few popular downloads.
		$popular = function_exists( 'edd_get_download' ) ? get_posts(
			array(
				'post_type'      => 'download',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_edd_download_sales',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		) : array();
		if ( $popular ) :
			?>
			<div class="lav-ec-pop">
				<span class="lav-ec-pop-label"><?php esc_html_e( 'Popular right now', 'lavtheme' ); ?></span>
				<div class="lav-ec-pop-row">
					<?php
					foreach ( $popular as $pid ) :
						$price = function_exists( 'edd_price' ) ? wp_strip_all_tags( edd_price( $pid, false ) ) : '';
						?>
						<a class="lav-ec-prod" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
							<span class="lav-ec-prod-thumb">
								<?php if ( has_post_thumbnail( $pid ) ) : ?>
									<img src="<?php echo esc_url( get_the_post_thumbnail_url( $pid, 'medium' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $pid ) ); ?>" loading="lazy">
								<?php endif; ?>
							</span>
							<span class="lav-ec-prod-name"><?php echo esc_html( get_the_title( $pid ) ); ?></span>
							<?php if ( '' !== $price ) : ?><span class="lav-ec-prod-price"><?php echo esc_html( $price ); ?></span><?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
	$html = (string) ob_get_clean();
	return $html ? $html : $message;
}
add_filter( 'edd_empty_cart_message', 'lavtheme_edd_empty_cart_html' );

/* ------------------------------------------------------- checkout header --- */

/**
 * A "Secure checkout" header with 3-step progress, above the cart. Only fires
 * when the cart has items (EDD renders the checkout form).
 */
function lavtheme_edd_checkout_header() {
	?>
	<div class="lav-co-head">
		<div class="lav-co-titles">
			<h1 class="lav-co-h1"><?php esc_html_e( 'Secure checkout', 'lavtheme' ); ?></h1>
			<span class="lav-co-secure"><span class="lav-co-lock" aria-hidden="true"></span><?php esc_html_e( 'SSL encrypted payment', 'lavtheme' ); ?></span>
		</div>
		<ol class="lav-co-steps">
			<li class="done"><span>1</span><?php esc_html_e( 'Cart', 'lavtheme' ); ?></li>
			<li class="active"><span>2</span><?php esc_html_e( 'Details &amp; payment', 'lavtheme' ); ?></li>
			<li><span>3</span><?php esc_html_e( 'Done', 'lavtheme' ); ?></li>
		</ol>
	</div>
	<?php
}
add_action( 'edd_before_checkout_cart', 'lavtheme_edd_checkout_header' );

/* --------------------------------------------------------- trust badges --- */

/**
 * Trust badges under the purchase button (instant delivery, refund, secure).
 */
function lavtheme_edd_checkout_trust() {
	$badges = array(
		array( 'bolt', __( 'Instant delivery', 'lavtheme' ), __( 'Download the moment payment clears.', 'lavtheme' ) ),
		array( 'shield', __( 'Secure payment', 'lavtheme' ), __( '256-bit SSL. We never store card data.', 'lavtheme' ) ),
		array( 'refund', __( '7-day guarantee', 'lavtheme' ), __( 'Not happy? Get a full refund.', 'lavtheme' ) ),
	);
	echo '<div class="lav-co-trust">';
	foreach ( $badges as $b ) {
		printf(
			'<div class="lav-co-badge"><span class="lav-co-bico %1$s" aria-hidden="true"></span><span class="lav-co-btext"><b>%2$s</b><i>%3$s</i></span></div>',
			esc_attr( $b[0] ),
			esc_html( $b[1] ),
			esc_html( $b[2] )
		);
	}
	echo '</div>';
}
add_action( 'edd_purchase_form_after_submit', 'lavtheme_edd_checkout_trust' );
