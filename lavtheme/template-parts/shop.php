<?php
/**
 * Shop layout — EDD download archive + download taxonomy archives (shop.html
 * design). Shared by archive-download.php and taxonomy-download_*.php. Reads the
 * already-filtered main query; all builders live in inc/edd-shop*.php.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_state = function_exists( 'lavtheme_shop_filter_state' ) ? lavtheme_shop_filter_state() : array( 'view' => 'grid' );
$lav_view  = isset( $lav_state['view'] ) ? $lav_state['view'] : 'grid';
?>
<section class="lav-shop">
	<div class="shell">

		<?php echo lavtheme_shop_hero_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>

		<button class="filter-toggle" id="filterToggle" aria-controls="filters" aria-expanded="false">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
			<?php esc_html_e( 'Filters', 'lavtheme' ); ?>
		</button>

		<div class="layout">
			<?php echo lavtheme_shop_sidebar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>

			<div class="main">
				<?php echo lavtheme_shop_toolbar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
				<?php echo lavtheme_shop_chips_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>

				<?php if ( have_posts() ) : ?>
					<div class="grid<?php echo 'list' === $lav_view ? ' is-list' : ''; ?>" id="grid">
						<?php
						while ( have_posts() ) :
							the_post();
							echo lavtheme_shop_card_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
						endwhile;
						?>
					</div>
				<?php else : ?>
					<div class="shop-empty glass">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
						<h3><?php esc_html_e( 'No products match your filters', 'lavtheme' ); ?></h3>
						<p><?php esc_html_e( 'Try widening the price range or clearing a filter.', 'lavtheme' ); ?></p>
						<a class="apply-btn" href="<?php echo esc_url( function_exists( 'lavtheme_shop_base_url' ) ? lavtheme_shop_base_url() : get_post_type_archive_link( 'download' ) ); ?>"><?php esc_html_e( 'Clear all filters', 'lavtheme' ); ?></a>
					</div>
				<?php endif; ?>

				<?php
				// Newsletter / promo widget.
				?>
				<div class="promo glass">
					<div class="pt">
						<h3><?php esc_html_e( 'Get 20% off your first order', 'lavtheme' ); ?></h3>
						<p><?php esc_html_e( 'Join the newsletter for fresh drops, exclusive deals, and free resources — straight to your inbox.', 'lavtheme' ); ?></p>
					</div>
					<form class="promo-form" method="post" action="#" onsubmit="return false">
						<input type="email" name="lav_news_email" placeholder="<?php esc_attr_e( 'you@email.com', 'lavtheme' ); ?>" aria-label="<?php esc_attr_e( 'Email address', 'lavtheme' ); ?>">
						<button type="submit"><?php esc_html_e( 'Subscribe', 'lavtheme' ); ?></button>
					</form>
				</div>

				<?php echo lavtheme_shop_pagination_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
			</div>
		</div>

		<div class="lav-shop-overlay" data-lav-close hidden></div>
	</div>
</section>
<?php echo lavtheme_shop_quickview_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
