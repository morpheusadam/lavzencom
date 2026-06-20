<?php
/**
 * Advanced EDD Single Product Hooks & Callbacks.
 *
 * Implements default hooks for the single product page template.
 * All hooks are filterable and can be customized via child themes.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook: lavtheme_product_gallery
 * Default gallery implementation.
 *
 * @param int $id Post ID.
 */
function lavtheme_product_gallery_default( $id ) {
	$thumbnail = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'large' ) : '';
	$gallery_ids = get_post_meta( $id, '_product_gallery_ids', true );
	
	?>
	<div class="product-gallery">
		<?php if ( $thumbnail ) : ?>
			<div class="gallery-main">
				<img 
					src="<?php echo esc_url( $thumbnail ); ?>" 
					alt="<?php echo esc_attr( get_the_title( $id ) ); ?>"
					class="product-image"
					loading="lazy"
				>
			</div>
		<?php else : ?>
			<div class="product-gallery-empty">
				<div class="placeholder-icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="3" y="3" width="18" height="18" rx="2"/>
						<circle cx="8.5" cy="8.5" r="1.5"/>
						<path d="m21 15-5-5L5 21"/>
					</svg>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'lavtheme_product_gallery', 'lavtheme_product_gallery_default' );

/**
 * Hook: lavtheme_product_category
 * Display category badge.
 *
 * @param array $data Product data.
 */
function lavtheme_product_category( $data ) {
	if ( ! $data['category'] ) {
		return;
	}
	
	$cat = $data['category'];
	?>
	<div class="product-category">
		<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
			<?php echo esc_html( $cat->name ); ?>
		</a>
	</div>
	<?php
}
add_action( 'lavtheme_product_meta', 'lavtheme_product_category', 10 );

/**
 * Hook: lavtheme_product_title
 * Display product title.
 *
 * @param array $data Product data.
 */
function lavtheme_product_title( $data ) {
	?>
	<h1 class="product-title"><?php the_title(); ?></h1>
	<?php
}
add_action( 'lavtheme_product_meta', 'lavtheme_product_title', 20 );

/**
 * Hook: lavtheme_product_stats
 * Display product statistics.
 *
 * @param array $data Product data.
 */
function lavtheme_product_stats( $data ) {
	?>
	<div class="product-stats">
		<div class="stat-item">
			<span class="stat-label"><?php esc_html_e( 'Downloads', 'lavtheme' ); ?></span>
			<span class="stat-value"><?php echo esc_html( number_format( absint( $data['downloads'] ) ) ); ?></span>
		</div>
		<div class="stat-divider"></div>
		<div class="stat-item">
			<span class="stat-label"><?php esc_html_e( 'Updated', 'lavtheme' ); ?></span>
			<span class="stat-value"><?php echo esc_html( $data['updated'] ); ?></span>
		</div>
	</div>
	<?php
}
add_action( 'lavtheme_product_meta', 'lavtheme_product_stats', 30 );

/**
 * Hook: lavtheme_product_price_display
 * Display pricing.
 *
 * @param array $data Product data.
 */
function lavtheme_product_price_display( $data ) {
	$price = floatval( $data['price'] );
	
	if ( $price > 0 ) {
		?>
		<div class="price-display">
			<span class="currency">$</span>
			<span class="amount"><?php echo esc_html( number_format( $price, 2 ) ); ?></span>
		</div>
		<?php
	} else {
		?>
		<div class="price-free"><?php esc_html_e( 'Free', 'lavtheme' ); ?></div>
		<?php
	}
}
add_action( 'lavtheme_product_price', 'lavtheme_product_price_display', 10 );

/**
 * Hook: lavtheme_product_purchase_button
 * Display EDD purchase button.
 *
 * @param array $data Product data.
 */
function lavtheme_product_purchase_button( $data ) {
	if ( ! function_exists( 'edd_get_purchase_link' ) ) {
		return;
	}
	
	?>
	<div class="product-actions">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo edd_get_purchase_link( array(
			'download_id' => absint( $data['id'] ),
			'class'       => 'btn btn-primary',
		) );
		?>
	</div>
	<?php
}
add_action( 'lavtheme_product_price', 'lavtheme_product_purchase_button', 20 );

/**
 * Hook: lavtheme_product_tags
 * Display product tags.
 *
 * @param array $tags Tag objects.
 */
function lavtheme_product_tags_display( $tags ) {
	if ( ! is_array( $tags ) || is_wp_error( $tags ) ) {
		return;
	}
	
	foreach ( $tags as $tag ) {
		$tag_url = get_term_link( $tag );
		if ( ! is_wp_error( $tag_url ) ) {
			?>
			<a href="<?php echo esc_url( $tag_url ); ?>" class="tag">
				<?php echo esc_html( $tag->name ); ?>
			</a>
			<?php
		}
	}
}
add_action( 'lavtheme_product_tags', 'lavtheme_product_tags_display' );

/**
 * Hook: lavtheme_product_content
 * Display main product content.
 *
 * @param int $id Post ID.
 */
function lavtheme_product_content_default( $id ) {
	?>
	<div class="entry-content">
		<?php
		the_content();
		wp_link_pages( array(
			'before' => '<div class="page-links"><span class="page-links--label">' . esc_html__( 'Pages:', 'lavtheme' ) . '</span>',
			'after'  => '</div>',
		) );
		?>
	</div>
	<?php
}
add_action( 'lavtheme_product_content', 'lavtheme_product_content_default' );

/**
 * Hook: lavtheme_product_features
 * Display product features list.
 *
 * @param int    $id       Post ID.
 * @param string $features Features string (one per line).
 */
function lavtheme_product_features_default( $id, $features ) {
	if ( ! $features ) {
		return;
	}
	
	$features_array = array_filter( array_map( 'trim', explode( "\n", $features ) ) );
	if ( empty( $features_array ) ) {
		return;
	}
	
	?>
	<div class="section-head">
		<h2><?php esc_html_e( 'Key Features', 'lavtheme' ); ?></h2>
	</div>
	<ul class="features-list">
		<?php foreach ( $features_array as $feature ) : ?>
			<li class="feature-item">
				<svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="20 6 9 17 4 12"/>
				</svg>
				<span><?php echo esc_html( $feature ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}
add_action( 'lavtheme_product_features', 'lavtheme_product_features_default', 10, 2 );

/**
 * Hook: lavtheme_product_changelog
 * Display version history/changelog.
 *
 * @param int    $id        Post ID.
 * @param string $changelog Changelog content.
 */
function lavtheme_product_changelog_default( $id, $changelog ) {
	if ( ! $changelog ) {
		return;
	}
	
	?>
	<div class="section-head">
		<h2><?php esc_html_e( 'Version History', 'lavtheme' ); ?></h2>
	</div>
	<div class="changelog-content">
		<?php echo wp_kses_post( wpautop( $changelog ) ); ?>
	</div>
	<?php
}
add_action( 'lavtheme_product_changelog', 'lavtheme_product_changelog_default', 10, 2 );

/**
 * Hook: lavtheme_product_related
 * Display related products in same category.
 *
 * @param array $data Product data.
 */
function lavtheme_product_related_default( $data ) {
	if ( ! $data['category'] || ! taxonomy_exists( 'download_category' ) ) {
		return;
	}
	
	$related = new WP_Query( array(
		'post_type'           => 'download',
		'posts_per_page'      => 3,
		'post__not_in'        => array( absint( $data['id'] ) ),
		'tax_query'           => array(
			array(
				'taxonomy' => 'download_category',
				'field'    => 'term_id',
				'terms'    => absint( $data['category']->term_id ),
			),
		),
		'ignore_sticky_posts' => true,
	) );
	
	if ( ! $related->have_posts() ) {
		wp_reset_postdata();
		return;
	}
	
	?>
	<div class="section-head">
		<h2><?php esc_html_e( 'Related Products', 'lavtheme' ); ?></h2>
	</div>
	<div class="related-grid">
		<?php
		while ( $related->have_posts() ) {
			$related->the_post();
			$rel_id    = get_the_ID();
			$rel_thumb = has_post_thumbnail( $rel_id ) ? get_the_post_thumbnail_url( $rel_id, 'lavtheme-card' ) : '';
			$rel_price = edd_get_download_price( $rel_id );
			?>
			<a class="related-card glass" href="<?php the_permalink(); ?>">
				<?php if ( $rel_thumb ) : ?>
					<div class="related-thumb">
						<img 
							src="<?php echo esc_url( $rel_thumb ); ?>" 
							alt="<?php echo esc_attr( get_the_title() ); ?>"
							loading="lazy"
						>
					</div>
				<?php endif; ?>
				<div class="related-body">
					<h3 class="related-title"><?php the_title(); ?></h3>
					<?php if ( $rel_price ) : ?>
						<div class="related-price">
							<?php echo '$' . esc_html( number_format( floatval( $rel_price ), 2 ) ); ?>
						</div>
					<?php endif; ?>
				</div>
			</a>
			<?php
		}
		wp_reset_postdata();
		?>
	</div>
	<?php
}
add_action( 'lavtheme_product_related', 'lavtheme_product_related_default' );

/**
 * Register product image size for gallery.
 */
function lavtheme_register_product_image_sizes() {
	add_image_size( 'lavtheme-product-gallery', 600, 600, true );
	add_image_size( 'lavtheme-product-thumb', 80, 80, true );
}
add_action( 'after_setup_theme', 'lavtheme_register_product_image_sizes' );
