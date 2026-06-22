<?php
/**
 * Easy Digital Downloads integration for the front-page products section.
 *
 * Everything is guarded so the theme never fatals when EDD is inactive;
 * the products section then falls back to its original static markup.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is Easy Digital Downloads active and ready?
 *
 * @return bool
 */
function lavtheme_edd_active() {
	return function_exists( 'EDD' ) || class_exists( 'Easy_Digital_Downloads' ) || post_type_exists( 'download' );
}

/**
 * Resolve how many products to show (datasources / legacy keys / default).
 *
 * @return int
 */
function lavtheme_products_count() {
	$count = lavtheme_option( 'products_per_page', lavtheme_option( 'products_count', 8 ) );
	$count = absint( $count );
	return $count > 0 ? $count : 8;
}

/**
 * Query EDD downloads.
 *
 * @param int $count    Number of products.
 * @param int $term_id  Optional download_category term id.
 * @return WP_Query
 */
function lavtheme_get_products( $count, $term_id = 0 ) {
	$args = array(
		'post_type'           => 'download',
		'posts_per_page'      => $count,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if ( $term_id > 0 && taxonomy_exists( 'download_category' ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'download_category',
				'field'    => 'term_id',
				'terms'    => $term_id,
			),
		);
	}

	return new WP_Query( $args );
}

/**
 * Build the products grid markup. Returns '' when nothing to show.
 *
 * @return string
 */
function lavtheme_products_grid_html() {
	if ( ! lavtheme_edd_active() ) {
		return '';
	}

	$count      = lavtheme_products_count();
	$term_id    = absint( lavtheme_option( 'default_product_category', 0 ) );
	$show_price = '' === lavtheme_option( 'show_price', '1' ) ? false : true;
	$query      = lavtheme_get_products( $count, $term_id );

	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return '';
	}

	ob_start();
	echo '<div class="lavp-grid">';
	while ( $query->have_posts() ) {
		$query->the_post();
		$id    = get_the_ID();
		$cats  = get_the_terms( $id, 'download_category' );
		$cat   = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
		$thumb = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'lavtheme-card' ) : '';
		?>
		<a class="lavp-card glass" href="<?php the_permalink(); ?>">
			<div class="lavp-thumb">
				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
				<?php endif; ?>
			</div>
			<div class="lavp-body">
				<?php if ( $cat ) : ?>
					<div class="lavp-cat"><?php echo esc_html( $cat ); ?></div>
				<?php endif; ?>
				<h3 class="lavp-title"><?php the_title(); ?></h3>
				<div class="lavp-foot">
					<span class="lavp-price">
						<?php
						if ( $show_price && function_exists( 'edd_price' ) ) {
							echo wp_kses_post( edd_price( $id, false ) );
						}
						?>
					</span>
					<span class="lavp-add"><?php esc_html_e( 'View', 'lavtheme' ); ?></span>
				</div>
			</div>
		</a>
		<?php
	}
	echo '</div>';
	wp_reset_postdata();

	return ob_get_clean();
}

/**
 * Build the iconnav category bubbles from download_category terms.
 *
 * Returns '' when EDD/terms are unavailable so the caller can fall back to
 * the original static bubbles.
 *
 * @return string
 */
function lavtheme_category_bubbles_html() {
	if ( ! lavtheme_edd_active() || ! taxonomy_exists( 'download_category' ) ) {
		return '';
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'download_category',
			'hide_empty' => true,
			'number'     => 7,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	$icons   = array_values( lavtheme_icon_presets_safe() );
	$default = '<svg viewBox="0 0 24 24" fill="none"><path d="M12 2.6l2.7 5.6 6.1.8-4.5 4.3 1.1 6.1L12 16.7 6.5 19.4l1.1-6.1L3.1 9l6.1-.8z" fill="currentColor"/></svg>';

	ob_start();
	echo '<nav class="iconnav" aria-label="' . esc_attr__( 'Product categories', 'lavtheme' ) . '"><div class="iconnav-row">';

	$i = 0;
	foreach ( $terms as $term ) {
		$svg = isset( $icons[ $i + 1 ] ) && '' !== $icons[ $i + 1 ] ? $icons[ $i + 1 ] : $default;
		printf(
			'<a class="ibubble" href="%1$s"><span class="bub" aria-hidden="true">%2$s</span><span class="ilabel">%3$s</span></a>',
			esc_url( get_term_link( $term ) ),
			wp_kses( $svg, lavtheme_svg_allowed_html() ),
			esc_html( $term->name )
		);
		$i++;
	}

	// Trailing "More" bubble linking to the shop / downloads archive.
	$more = function_exists( 'lavtheme_shop_url' ) ? lavtheme_shop_url() : get_post_type_archive_link( 'download' );
	if ( $more ) {
		printf(
			'<a class="ibubble is-more" href="%1$s" aria-label="%3$s"><span class="bub" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="5" cy="12" r="2.2" fill="currentColor"/><circle cx="12" cy="12" r="2.2" fill="currentColor"/><circle cx="19" cy="12" r="2.2" fill="currentColor"/></svg></span><span class="ilabel">%2$s</span></a>',
			esc_url( $more ),
			esc_html__( 'All products', 'lavtheme' ),
			esc_attr__( 'Browse all products', 'lavtheme' )
		);
	}

	echo '</div></nav>';

	return ob_get_clean();
}

/**
 * Safe accessor for the icon presets (Icons tab provides the canonical list).
 *
 * @return array
 */
function lavtheme_icon_presets_safe() {
	return function_exists( 'lavtheme_icon_presets' ) ? lavtheme_icon_presets() : array();
}

/**
 * Admin notice when EDD is not active.
 */
function lavtheme_edd_admin_notice() {
	if ( lavtheme_edd_active() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'lavtheme: Easy Digital Downloads is not active — the products section is showing demo categories until you activate it.', 'lavtheme' ) . '</p></div>';
}
add_action( 'admin_notices', 'lavtheme_edd_admin_notice' );
