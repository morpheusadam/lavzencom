<?php
/**
 * Shop layout — download archive + download taxonomy archives.
 * Reads the already-filtered main query; builders live in inc/shop.php.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_state = function_exists( 'lavzen_shop_filter_state' ) ? lavzen_shop_filter_state() : array( 'view' => 'grid' );
$lav_view  = isset( $lav_state['view'] ) ? $lav_state['view'] : 'grid';
?>
<section class="lav-shop">
	<div class="shell">
		<?php echo lavzen_shop_hero_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>

		<button class="filter-toggle" id="filterToggle" aria-controls="filters" aria-expanded="false">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
			<?php esc_html_e( 'Filters', 'lavzentheme' ); ?>
		</button>

		<div class="layout">
			<?php echo lavzen_shop_sidebar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>

			<div class="main">
				<?php echo lavzen_shop_toolbar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>

				<?php if ( have_posts() ) : ?>
					<div class="grid<?php echo 'list' === $lav_view ? ' is-list' : ''; ?>" id="grid">
						<?php
						while ( have_posts() ) :
							the_post();
							echo lavzen_shop_card_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
						endwhile;
						?>
					</div>
				<?php else : ?>
					<div class="shop-empty glass">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
						<h3><?php esc_html_e( 'No products match your filters', 'lavzentheme' ); ?></h3>
						<p><?php esc_html_e( 'Try widening the price range or clearing a filter.', 'lavzentheme' ); ?></p>
						<a class="apply-btn" href="<?php echo esc_url( function_exists( 'lavzen_shop_base_url' ) ? lavzen_shop_base_url() : get_post_type_archive_link( 'download' ) ); ?>"><?php esc_html_e( 'Clear all filters', 'lavzentheme' ); ?></a>
					</div>
				<?php endif; ?>

				<?php echo lavzen_shop_pagination_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
			</div>
		</div>

		<div class="lav-shop-overlay" data-lav-close hidden></div>
	</div>
</section>
