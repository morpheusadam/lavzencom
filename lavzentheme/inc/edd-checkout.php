<?php
/**
 * EDD checkout enhancements (ported from inc/edd-checkout.php).
 *
 * Adds, without touching EDD core markup: a designed empty-cart state, a "Secure
 * checkout" header, and a trust-badge row under the purchase button. Styling lives
 * in assets/dist/css/checkout.css (enqueued on EDD flow pages by Core\Assets).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

function lavzen_checkout_shop_url(): string {
	if ( function_exists( 'lavzen_shop_url' ) ) {
		return lavzen_shop_url();
	}
	$u = get_post_type_archive_link( 'download' );
	return $u ? $u : home_url( '/' );
}

/** Replace EDD's plain empty-cart message with a designed state (kses-safe). */
function lavzen_edd_empty_cart_html( $message ) {
	$shop    = lavzen_checkout_shop_url();
	$account = function_exists( 'lavzen_edd_page_url' ) ? lavzen_edd_page_url( 'purchase_history' ) : '';
	ob_start();
	?>
	<div class="lav-empty-cart">
		<span class="lav-ec-ico" aria-hidden="true"></span>
		<h2 class="lav-ec-title"><?php esc_html_e( 'Your cart is empty', 'lavzentheme' ); ?></h2>
		<p class="lav-ec-text"><?php esc_html_e( 'Looks like you haven’t added anything yet. Explore our digital products, tools and templates — instant download, the moment you buy.', 'lavzentheme' ); ?></p>
		<div class="lav-ec-actions">
			<a class="lav-ec-btn primary" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Browse the shop', 'lavzentheme' ); ?></a>
			<?php if ( $account ) : ?>
				<a class="lav-ec-btn ghost" href="<?php echo esc_url( $account ); ?>"><?php esc_html_e( 'Your purchases', 'lavzentheme' ); ?></a>
			<?php endif; ?>
		</div>
		<?php
		$popular = function_exists( 'edd_get_download' ) ? get_posts( array( 'post_type' => 'download', 'post_status' => 'publish', 'posts_per_page' => 3, 'orderby' => 'meta_value_num', 'meta_key' => '_edd_download_sales', 'order' => 'DESC', 'fields' => 'ids', 'no_found_rows' => true ) ) : array();
		if ( $popular ) :
			?>
			<div class="lav-ec-pop">
				<span class="lav-ec-pop-label"><?php esc_html_e( 'Popular right now', 'lavzentheme' ); ?></span>
				<div class="lav-ec-pop-row">
					<?php foreach ( $popular as $pid ) : $price = function_exists( 'edd_price' ) ? wp_strip_all_tags( edd_price( $pid, false ) ) : ''; ?>
						<a class="lav-ec-prod" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
							<span class="lav-ec-prod-thumb"><?php if ( has_post_thumbnail( $pid ) ) : ?><img src="<?php echo esc_url( get_the_post_thumbnail_url( $pid, 'medium' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $pid ) ); ?>" loading="lazy"><?php endif; ?></span>
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
add_filter( 'edd_empty_cart_message', 'lavzen_edd_empty_cart_html' );

function lavzen_edd_checkout_header(): void {
	?>
	<div class="lav-co-head">
		<div class="lav-co-titles">
			<h1 class="lav-co-h1"><?php esc_html_e( 'Secure checkout', 'lavzentheme' ); ?></h1>
			<span class="lav-co-secure"><span class="lav-co-lock" aria-hidden="true"></span><?php esc_html_e( 'SSL encrypted payment', 'lavzentheme' ); ?></span>
		</div>
		<ol class="lav-co-steps">
			<li class="done"><span>1</span><?php esc_html_e( 'Cart', 'lavzentheme' ); ?></li>
			<li class="active"><span>2</span><?php esc_html_e( 'Details & payment', 'lavzentheme' ); ?></li>
			<li><span>3</span><?php esc_html_e( 'Done', 'lavzentheme' ); ?></li>
		</ol>
	</div>
	<?php
}
add_action( 'edd_before_checkout_cart', 'lavzen_edd_checkout_header' );

function lavzen_edd_checkout_trust(): void {
	$badges = array(
		array( 'bolt', __( 'Instant delivery', 'lavzentheme' ), __( 'Download the moment payment clears.', 'lavzentheme' ) ),
		array( 'shield', __( 'Secure payment', 'lavzentheme' ), __( '256-bit SSL. We never store card data.', 'lavzentheme' ) ),
		array( 'refund', __( '7-day guarantee', 'lavzentheme' ), __( 'Not happy? Get a full refund.', 'lavzentheme' ) ),
	);
	echo '<div class="lav-co-trust">';
	foreach ( $badges as $b ) {
		printf( '<div class="lav-co-badge"><span class="lav-co-bico %1$s" aria-hidden="true"></span><span class="lav-co-btext"><b>%2$s</b><i>%3$s</i></span></div>', esc_attr( $b[0] ), esc_html( $b[1] ), esc_html( $b[2] ) );
	}
	echo '</div>';
}
add_action( 'edd_purchase_form_after_submit', 'lavzen_edd_checkout_trust' );
