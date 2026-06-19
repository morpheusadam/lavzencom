<?php
/**
 * Single EDD Product template.
 *
 * Professional product page for digital downloads with gallery,
 * pricing, features, and related products.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$id = get_the_ID();
	
	// EDD product data.
	$price = edd_get_download_price( $id );
	$cats = get_the_terms( $id, 'download_category' );
	$cat = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
	$tags = get_the_terms( $id, 'download_tag' );
	
	// Product stats.
	$downloads = edd_get_download_sales_stats( $id ) ? edd_get_download_sales_stats( $id ) : 0;
	?>
	
	<article class="product-single">
		<!-- Product Header Section -->
		<div class="product-header glass">
			<div class="product-grid">
				<!-- Product Image/Gallery -->
				<div class="product-media">
					<?php if ( has_post_thumbnail( $id ) ) : ?>
						<div class="product-gallery">
							<?php the_post_thumbnail( 'large', array( 'class' => 'product-image' ) ); ?>
						</div>
					<?php else : ?>
						<div class="product-gallery product-gallery-empty">
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
				
				<!-- Product Info -->
				<div class="product-info">
					<?php if ( $cat ) : ?>
						<div class="product-category"><?php echo esc_html( $cat ); ?></div>
					<?php endif; ?>
					
					<h1 class="product-title"><?php the_title(); ?></h1>
					
					<!-- Rating & Stats -->
					<div class="product-stats">
						<div class="stat-item">
							<span class="stat-label">Downloads</span>
							<span class="stat-value"><?php echo esc_html( number_format( $downloads ) ); ?></span>
						</div>
						<div class="stat-divider"></div>
						<div class="stat-item">
							<span class="stat-label">Updated</span>
							<span class="stat-value"><?php echo esc_html( get_the_modified_date( 'M d, Y' ) ); ?></span>
						</div>
					</div>
					
					<!-- Price Section -->
					<div class="product-pricing">
						<?php if ( $price ) : ?>
							<div class="price-display">
								<span class="currency">$</span>
								<span class="amount"><?php echo esc_html( number_format( $price, 2 ) ); ?></span>
							</div>
						<?php else : ?>
							<div class="price-free">Free</div>
						<?php endif; ?>
					</div>
					
					<!-- Download Button -->
					<div class="product-actions">
						<?php
						if ( class_exists( 'Easy_Digital_Downloads' ) ) {
							echo edd_get_purchase_link( array( 'download_id' => $id, 'class' => 'btn btn-primary' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							?>
							<button class="btn btn-primary" disabled>Purchase</button>
							<?php
						}
						?>
					</div>
					
					<!-- Tags -->
					<?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
						<div class="product-tags">
							<?php foreach ( $tags as $tag ) : ?>
								<a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="tag">
									<?php echo esc_html( $tag->name ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<!-- Product Description & Content -->
		<div class="product-content glass">
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</div>
		
		<!-- Features Section (if custom field exists) -->
		<?php
		$features = get_post_meta( $id, 'product_features', true );
		if ( $features ) :
			$features_array = explode( "\n", $features );
			?>
			<div class="product-features glass">
				<div class="section-head">
					<h2>Key Features</h2>
				</div>
				<ul class="features-list">
					<?php foreach ( $features_array as $feature ) : ?>
						<?php if ( trim( $feature ) ) : ?>
							<li class="feature-item">
								<svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<polyline points="20 6 9 17 4 12"/>
								</svg>
								<span><?php echo esc_html( $feature ); ?></span>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		
		<!-- Related Products -->
		<?php
		if ( $cat && taxonomy_exists( 'download_category' ) ) :
			$related = new WP_Query( array(
				'post_type'           => 'download',
				'posts_per_page'      => 3,
				'post__not_in'        => array( $id ),
				'tax_query'           => array(
					array(
						'taxonomy' => 'download_category',
						'field'    => 'term_id',
						'terms'    => $cat->term_id,
					),
				),
				'ignore_sticky_posts' => true,
			) );
			
			if ( $related->have_posts() ) :
				?>
				<div class="product-related">
					<div class="section-head">
						<h2>Related Products</h2>
					</div>
					<div class="related-grid">
						<?php
						while ( $related->have_posts() ) :
							$related->the_post();
							$rel_id = get_the_ID();
							$rel_thumb = has_post_thumbnail( $rel_id ) ? get_the_post_thumbnail_url( $rel_id, 'lavtheme-card' ) : '';
							$rel_price = edd_get_download_price( $rel_id );
							?>
							<a class="related-card glass" href="<?php the_permalink(); ?>">
								<?php if ( $rel_thumb ) : ?>
									<div class="related-thumb">
										<img src="<?php echo esc_url( $rel_thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
									</div>
								<?php endif; ?>
								<div class="related-body">
									<h3 class="related-title"><?php the_title(); ?></h3>
									<?php if ( $rel_price ) : ?>
										<div class="related-price">$<?php echo esc_html( number_format( $rel_price, 2 ) ); ?></div>
									<?php endif; ?>
								</div>
							</a>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</div>
				<?php
			endif;
		endif;
		?>
	</article>
	
	<?php
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
endwhile;

get_footer();
