<?php
/**
 * Shop layout — EDD download archive + download taxonomy archives.
 *
 * Shared by archive-download.php, taxonomy-download_category.php and
 * taxonomy-download_tag.php. Reads the (already-filtered) main query.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_total = isset( $GLOBALS['wp_query']->found_posts ) ? (int) $GLOBALS['wp_query']->found_posts : 0;
$lav_state = function_exists( 'lavtheme_shop_filter_state' ) ? lavtheme_shop_filter_state() : array( 'orderby' => 'date' );
$lav_is_tax = is_tax();
?>
<section class="block lav-shop-wrap" id="shop">
	<div class="block-head">
		<div>
			<div class="kicker"><?php echo esc_html( $lav_is_tax ? get_the_archive_title() : __( 'Shop', 'lavtheme' ) ); ?></div>
			<h2 class="block-title"><?php echo esc_html( $lav_is_tax ? single_term_title( '', false ) : __( 'Digital Downloads', 'lavtheme' ) ); ?></h2>
			<?php
			if ( $lav_is_tax ) {
				the_archive_description( '<p class="block-intro">', '</p>' );
			}
			?>
			<p class="block-intro lav-shop__count">
				<?php
				/* translators: %s: number of products. */
				printf( esc_html( _n( '%s product', '%s products', $lav_total, 'lavtheme' ) ), esc_html( number_format_i18n( $lav_total ) ) );
				?>
			</p>
		</div>
		<div class="lav-shop__bar">
			<button type="button" class="btn btn-ghost lav-filters-toggle" aria-expanded="false" aria-controls="lav-filters">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
				<?php esc_html_e( 'Filters', 'lavtheme' ); ?>
			</button>
			<?php
			if ( function_exists( 'lavtheme_shop_sort_html' ) ) {
				echo lavtheme_shop_sort_html( $lav_state ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
			}
			?>
		</div>
	</div>

	<div class="lav-shop">
		<aside class="lav-shop__filters glass" id="lav-filters" aria-label="<?php esc_attr_e( 'Product filters', 'lavtheme' ); ?>">
			<?php
			if ( function_exists( 'lavtheme_shop_sidebar_html' ) ) {
				echo lavtheme_shop_sidebar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
			}
			?>
		</aside>

		<div class="lav-shop__main">
			<?php if ( have_posts() ) : ?>
				<div class="lav-shop__grid">
					<?php
					while ( have_posts() ) :
						the_post();
						echo lavtheme_shop_card_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
					endwhile;
					?>
				</div>
				<div class="lav-shop__nav">
					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 1,
							'prev_text' => __( 'Prev', 'lavtheme' ),
							'next_text' => __( 'Next', 'lavtheme' ),
						)
					);
					?>
				</div>
			<?php else : ?>
				<div class="lav-shop__empty glass">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
					<h3><?php esc_html_e( 'No products match your filters', 'lavtheme' ); ?></h3>
					<p><?php esc_html_e( 'Try widening your price range or clearing a category.', 'lavtheme' ); ?></p>
					<a class="btn btn-ghost" href="<?php echo esc_url( get_post_type_archive_link( 'download' ) ); ?>"><?php esc_html_e( 'Clear filters', 'lavtheme' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="lav-shop-overlay" data-lav-close hidden></div>
</section>
