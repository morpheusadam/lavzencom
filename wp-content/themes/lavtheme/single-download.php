<?php
/**
 * Single EDD Product template.
 *
 * Professional, advanced product page for Easy Digital Downloads with:
 * - Gallery & media showcase
 * - Multi-tier pricing & bundles
 * - Features & benefits
 * - Version history
 * - Related products
 * - Security headers & caching
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

// Set proper headers for product pages.
if ( ! headers_sent() ) {
	header( 'Cache-Control: public, max-age=3600' );
	header( 'X-Content-Type-Options: nosniff' );
}

get_header();

while ( have_posts() ) :
	the_post();
	$id = get_the_ID();
	
	// Sanitize and get EDD download data.
	if ( ! function_exists( 'edd_get_download_price' ) ) {
		wp_die( esc_html__( 'Easy Digital Downloads plugin is required.', 'lavtheme' ) );
	}
	
	$price        = edd_get_download_price( absint( $id ) );
	$cats         = get_the_terms( absint( $id ), 'download_category' );
	$cat          = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0] : null;
	$tags         = get_the_terms( absint( $id ), 'download_tag' );
	$downloads    = edd_get_download_sales_stats( absint( $id ) ) ?: 0;
	$updated_date = get_the_modified_date( 'M d, Y' );
	
	/**
	 * Filter product data before rendering.
	 *
	 * @param array $data Product data.
	 * @param int   $id   Post ID.
	 */
	$product_data = apply_filters( 'lavtheme_product_data', [
		'id'       => $id,
		'price'    => $price,
		'category' => $cat,
		'tags'     => $tags,
		'downloads' => $downloads,
		'updated'  => $updated_date,
	], $id );
	
	<article class="product-single">
		<!-- Product Header Section -->
		<div class="product-header glass">
			<div class="product-grid">
				<!-- Product Media / Gallery -->
				<div class="product-media">
					<?php
					/**
					 * Hook: lavtheme_product_gallery
					 * Display product gallery/media section.
					 *
					 * @hooked lavtheme_product_gallery_default - 10
					 */
					do_action( 'lavtheme_product_gallery', $id );
					?>
				</div>
				
				<!-- Product Info Panel -->
				<div class="product-info">
					<?php
					/**
					 * Hook: lavtheme_product_meta
					 * Display category, title, and stats.
					 *
					 * @hooked lavtheme_product_category - 10
					 * @hooked lavtheme_product_title - 20
					 * @hooked lavtheme_product_stats - 30
					 */
					do_action( 'lavtheme_product_meta', $product_data );
					?>
					
					<!-- Pricing Section -->
					<div class="product-pricing">
						<?php
						/**
						 * Hook: lavtheme_product_price
						 * Display pricing and purchase button.
						 *
						 * @hooked lavtheme_product_price_display - 10
						 * @hooked lavtheme_product_purchase_button - 20
						 */
						do_action( 'lavtheme_product_price', $product_data );
						?>
					</div>
					
					<!-- Tags Section -->
					<?php if ( $product_data['tags'] && ! is_wp_error( $product_data['tags'] ) ) : ?>
						<div class="product-tags">
							<?php
							/**
							 * Hook: lavtheme_product_tags
							 * Display product tags.
							 *
							 * @hooked lavtheme_product_tags_display - 10
							 */
							do_action( 'lavtheme_product_tags', $product_data['tags'] );
							?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<!-- Product Description & Content -->
		<div class="product-content glass">
			<?php
			/**
			 * Hook: lavtheme_product_content
			 * Display main product description.
			 *
			 * @hooked lavtheme_product_content_default - 10
			 */
			do_action( 'lavtheme_product_content', $id );
			?>
		</div>
		
		<!-- Features Section -->
		<?php
		$features = get_post_meta( $id, 'product_features', true );
		if ( $features ) :
			?>
			<div class="product-features glass">
				<?php
				/**
				 * Hook: lavtheme_product_features
				 * Display product features list.
				 *
				 * @hooked lavtheme_product_features_default - 10
				 */
				do_action( 'lavtheme_product_features', $id, $features );
				?>
			</div>
		<?php endif; ?>
		
		<!-- System Requirements Section (optional) -->
		<?php
		$requirements = get_post_meta( $id, 'product_requirements', true );
		if ( $requirements ) :
			?>
			<div class="product-requirements glass">
				<div class="section-head">
					<h2><?php esc_html_e( 'System Requirements', 'lavtheme' ); ?></h2>
				</div>
				<div class="requirements-content">
					<?php echo wp_kses_post( wpautop( $requirements ) ); ?>
				</div>
			</div>
		<?php endif; ?>
		
		<!-- Version History (optional) -->
		<?php
		$changelog = get_post_meta( $id, 'product_changelog', true );
		if ( $changelog ) :
			?>
			<div class="product-changelog glass">
				<?php
				/**
				 * Hook: lavtheme_product_changelog
				 * Display version history/changelog.
				 *
				 * @hooked lavtheme_product_changelog_default - 10
				 */
				do_action( 'lavtheme_product_changelog', $id, $changelog );
				?>
			</div>
		<?php endif; ?>
		
		<!-- Related Products Section -->
		<?php
		if ( $product_data['category'] ) :
			?>
			<div class="product-related glass">
				<?php
				/**
				 * Hook: lavtheme_product_related
				 * Display related products.
				 *
				 * @hooked lavtheme_product_related_default - 10
				 */
				do_action( 'lavtheme_product_related', $product_data );
				?>
			</div>
		<?php endif; ?>
	</article>
	
	<?php
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
endwhile;

get_footer();
