<?php
/**
 * Single-download template BODY — the product design.
 *
 * Ported from the legacy template-parts/single-download-body.php. All data is
 * real: native EDD + the lavzen_pm_* product meta (inc/product-meta.php). Loaded
 * by single-download.php inside the loop.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

while ( have_posts() ) :
	the_post();
	$id = (int) get_the_ID();

	if ( ! function_exists( 'edd_get_download_price' ) ) {
		echo '<section class="block"><p class="block-intro">' . esc_html__( 'Easy Digital Downloads is required.', 'lavzentheme' ) . '</p></section>';
		return;
	}

	/* native EDD data */
	$title      = get_the_title();
	$cats       = get_the_terms( $id, 'download_category' );
	$cat        = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0] : null;
	$price_num  = (float) edd_get_download_price( $id );
	$variable   = function_exists( 'edd_has_variable_prices' ) && edd_has_variable_prices( $id );
	$sales      = (int) edd_get_download_sales_stats( $id );
	$published  = get_the_date( 'M Y' );
	$updated    = human_time_diff( (int) get_the_modified_time( 'U' ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'lavzentheme' );
	$thumb      = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'large' ) : '';
	$is_new     = ( time() - (int) get_post_time( 'U', true, $id ) ) < 14 * DAY_IN_SECONDS;
	$currency   = function_exists( 'edd_get_currency' ) ? edd_get_currency() : 'USD';
	$price_html = edd_price( $id, false );

	/* custom meta */
	$version      = lavzen_pm_get( $id, '_lav_version' );
	$tagline      = lavzen_pm_get( $id, '_lav_tagline', get_the_excerpt() );
	$demo_url     = lavzen_pm_get( $id, '_lav_demo_url' );
	$preview_url  = lavzen_pm_get( $id, '_lav_preview_url' );
	$sub_note     = lavzen_pm_get( $id, '_lav_subscription_note' );
	$support_lbl  = lavzen_pm_get( $id, '_lav_support_label', '24/7' );
	$installment  = lavzen_pm_is_on( $id, '_lav_show_installment' ) && $price_num > 0 && ! $variable;
	$author_name  = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) );
	$seller_name  = lavzen_pm_get( $id, '_lav_seller_name', $author_name );
	$seller_verif = lavzen_pm_is_on( $id, '_lav_seller_verified' );
	$seller_resp  = lavzen_pm_get( $id, '_lav_seller_response' );
	$features     = lavzen_pm_list( $id, 'product_features' );
	$specs        = lavzen_pm_rows( $id, '_lav_spec_boxes', 2, 4 );
	$cards        = lavzen_pm_rows( $id, '_lav_feature_cards', 2, 6 );
	$highlights   = lavzen_pm_rows( $id, '_lav_highlights', 2, 3 );
	$tab_tut      = lavzen_pm_get( $id, '_lav_tab_tutorials' );
	$tab_qa       = lavzen_pm_get( $id, '_lav_tab_qa' );
	$tab_support  = lavzen_pm_get( $id, '_lav_tab_support' );
	$gallery_raw  = get_post_meta( $id, '_product_gallery_ids', true );
	$gallery_ids  = $gallery_raw ? array_values( array_filter( array_map( 'absint', explode( ',', (string) $gallery_raw ) ) ) ) : array();

	$installment_amt = $installment ? edd_currency_filter( edd_format_amount( $price_num / 4 ) ) : '';
	$seller_initial  = function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $seller_name, 0, 1 ) ) : strtoupper( substr( $seller_name, 0, 1 ) );

	$has_qa      = '' !== trim( wp_strip_all_tags( $tab_qa ) );
	$has_tut     = '' !== trim( wp_strip_all_tags( $tab_tut ) );
	$has_support = '' !== trim( wp_strip_all_tags( $tab_support ) );

	$cart_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>';
	$tick_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
	?>

	<div class="lav-product">
		<div class="crumb">
			<div class="path">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lavzentheme' ); ?></a><span class="sep">/</span>
				<?php if ( $cat ) : ?>
					<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a><span class="sep">/</span>
				<?php endif; ?>
				<span style="color:var(--text-3)"><?php echo esc_html( $title ); ?></span>
			</div>
			<?php if ( $version ) : ?>
				<span class="ver"><?php esc_html_e( 'Version', 'lavzentheme' ); ?> <b><?php echo esc_html( $version ); ?></b></span>
			<?php endif; ?>
		</div>

		<h1 class="lav-product__title"><?php echo esc_html( $title ); ?></h1>

		<div class="layout">
			<aside class="side">
				<div class="glass s-buy" id="lav-buy">
					<span class="label"><?php esc_html_e( 'Product price:', 'lavzentheme' ); ?></span>
					<div class="price"><?php echo wp_kses_post( $price_html ); ?> <small><?php echo esc_html( $currency ); ?></small></div>
					<?php if ( $installment ) : ?>
						<div class="installment">
							<span><?php esc_html_e( 'Pay in 4 interest-free parts', 'lavzentheme' ); ?></span>
							<span class="pay"><?php echo esc_html( $installment_amt ); ?> &times;4</span>
						</div>
					<?php endif; ?>
					<?php
					echo edd_get_purchase_link( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- EDD builds escaped markup.
						array(
							'download_id' => $id,
							'price'       => false,
							'text'        => __( 'Add to Cart', 'lavzentheme' ),
							'class'       => 'btn btn-primary lav-buy-btn',
						)
					);
					?>
					<?php if ( $sub_note ) : ?>
						<div class="pro"><span class="tag"><?php esc_html_e( 'PRO', 'lavzentheme' ); ?></span> <?php echo esc_html( $sub_note ); ?></div>
					<?php endif; ?>
				</div>

				<div class="s-carousel">
					<?php if ( $sales || $features ) : ?>
						<div class="glass s-card">
							<div class="s-stats">
								<div class="col"><b><?php echo esc_html( number_format_i18n( $sales ) ); ?></b><span><?php esc_html_e( 'Sales', 'lavzentheme' ); ?></span></div>
								<div class="col"><b><?php echo esc_html( $is_new ? __( 'New', 'lavzentheme' ) : __( 'Pro', 'lavzentheme' ) ); ?></b><span><?php esc_html_e( 'Product', 'lavzentheme' ); ?></span></div>
								<div class="col"><b><?php echo esc_html( $support_lbl ); ?></b><span><?php esc_html_e( 'Support', 'lavzentheme' ); ?></span></div>
							</div>
							<?php if ( $features ) : ?>
								<ul class="s-feats">
									<?php foreach ( $features as $feat ) : ?>
										<li><span class="tick"><?php echo $tick_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php echo esc_html( $feat ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $seller_name ) : ?>
						<div class="glass s-card s-seller">
							<div class="who">
								<div class="av"><?php echo esc_html( $seller_initial ); ?></div>
								<div class="info">
									<b><?php echo esc_html( $seller_name ); ?></b>
									<?php if ( $seller_verif ) : ?><span><?php esc_html_e( 'Verified author', 'lavzentheme' ); ?></span><?php endif; ?>
								</div>
							</div>
							<?php if ( $seller_resp ) : ?>
								<div class="resp"><?php esc_html_e( 'Avg. response time:', 'lavzentheme' ); ?> <b style="color:var(--text-2)"><?php echo esc_html( $seller_resp ); ?></b></div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="glass s-card s-meta">
						<?php if ( $version ) : ?><div class="m"><b><?php echo esc_html( $version ); ?></b><span><?php esc_html_e( 'Version', 'lavzentheme' ); ?></span></div><?php endif; ?>
						<div class="m"><b><?php echo esc_html( $updated ); ?></b><span><?php esc_html_e( 'Last update', 'lavzentheme' ); ?></span></div>
						<div class="m"><b><?php echo esc_html( $published ); ?></b><span><?php esc_html_e( 'Published', 'lavzentheme' ); ?></span></div>
						<?php if ( $cat ) : ?><div class="m"><b><?php echo esc_html( $cat->name ); ?></b><span><?php esc_html_e( 'Category', 'lavzentheme' ); ?></span></div><?php endif; ?>
					</div>
				</div>
			</aside>

			<div class="content">
				<div class="glass banner">
					<div class="banner-img">
						<?php if ( $thumb ) : ?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>">
						<?php else : ?>
							<span class="ph"><?php echo esc_html( $title ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $demo_url || $preview_url || $has_support ) : ?>
						<div class="banner-bar">
							<?php if ( $demo_url ) : ?>
								<a class="chip active" href="<?php echo esc_url( $demo_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Live Demo', 'lavzentheme' ); ?></a>
							<?php endif; ?>
							<?php if ( $preview_url ) : ?>
								<a class="chip" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview', 'lavzentheme' ); ?></a>
							<?php endif; ?>
							<?php if ( $has_support ) : ?>
								<a class="chip" href="#" data-lav-tab="support"><?php esc_html_e( 'Support', 'lavzentheme' ); ?></a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="glass tabs-wrap">
					<div class="tablist" role="tablist">
						<button class="tab is-active" data-panel="desc" role="tab" id="lavtab-desc" aria-controls="lavpanel-desc" aria-selected="true" tabindex="0"><?php esc_html_e( 'Description', 'lavzentheme' ); ?></button>
						<?php if ( $has_qa ) : ?><button class="tab" data-panel="faq" role="tab" id="lavtab-faq" aria-controls="lavpanel-faq" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Q & A', 'lavzentheme' ); ?></button><?php endif; ?>
						<?php if ( $has_tut ) : ?><button class="tab" data-panel="tutorials" role="tab" id="lavtab-tutorials" aria-controls="lavpanel-tutorials" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Tutorials', 'lavzentheme' ); ?></button><?php endif; ?>
						<?php if ( $has_support ) : ?><button class="tab" data-panel="support" role="tab" id="lavtab-support" aria-controls="lavpanel-support" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Support', 'lavzentheme' ); ?></button><?php endif; ?>
					</div>

					<div class="panel is-active" data-panel="desc" id="lavpanel-desc" role="tabpanel" aria-labelledby="lavtab-desc" tabindex="0">
						<div class="intro">
							<h2><?php printf( esc_html__( 'About %s', 'lavzentheme' ), esc_html( $title ) ); ?></h2>
							<?php the_content(); ?>
						</div>

						<?php if ( $tagline || $highlights ) : ?>
							<div class="inner-banner">
								<h3><?php echo esc_html( $title ); ?></h3>
								<?php if ( $tagline ) : ?><p><?php echo esc_html( $tagline ); ?></p><?php endif; ?>
								<?php if ( $highlights ) : ?>
									<div class="acts">
										<?php
										foreach ( $highlights as $h ) :
											$h_label = $h[0];
											$h_url   = isset( $h[1] ) ? $h[1] : '';
											if ( '' === $h_label ) {
												continue;
											}
											$tag = $h_url ? 'a' : 'span';
											?>
											<<?php echo esc_html( $tag ); ?> class="act"<?php echo $h_url ? ' href="' . esc_url( $h_url ) . '"' : ''; ?>><?php echo esc_html( $h_label ); ?></<?php echo esc_html( $tag ); ?>>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( $specs ) : ?>
							<div class="statrow">
								<?php foreach ( $specs as $sp ) : ?>
									<div class="glass statbox"><b><?php echo esc_html( $sp[0] ); ?></b><span><?php echo esc_html( $sp[1] ); ?></span></div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( $cards ) : ?>
							<div class="feat-head"><h2><?php esc_html_e( 'Key capabilities', 'lavzentheme' ); ?></h2></div>
							<div class="feat-grid">
								<?php foreach ( $cards as $c ) : ?>
									<div class="glass feat-card">
										<h4><?php echo esc_html( $c[0] ); ?></h4>
										<?php if ( ! empty( $c[1] ) ) : ?><p><?php echo esc_html( $c[1] ); ?></p><?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( $gallery_ids ) : ?>
							<div class="gal-head"><h2><?php esc_html_e( 'Product Gallery', 'lavzentheme' ); ?></h2></div>
							<div class="gallery">
								<?php
								foreach ( $gallery_ids as $gid ) :
									$g_full  = wp_get_attachment_image_url( $gid, 'large' );
									$g_thumb = wp_get_attachment_image_url( $gid, 'medium' );
									if ( ! $g_thumb ) {
										continue;
									}
									?>
									<a class="gal-item" href="<?php echo esc_url( $g_full ? $g_full : $g_thumb ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( $g_thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy"></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( $has_qa ) : ?>
						<div class="panel" data-panel="faq" id="lavpanel-faq" role="tabpanel" aria-labelledby="lavtab-faq" tabindex="0"><div class="intro"><h2><?php esc_html_e( 'Questions & Answers', 'lavzentheme' ); ?></h2><?php echo wp_kses_post( wpautop( $tab_qa ) ); ?></div></div>
					<?php endif; ?>
					<?php if ( $has_tut ) : ?>
						<div class="panel" data-panel="tutorials" id="lavpanel-tutorials" role="tabpanel" aria-labelledby="lavtab-tutorials" tabindex="0"><div class="intro"><h2><?php esc_html_e( 'Tutorials', 'lavzentheme' ); ?></h2><?php echo wp_kses_post( wpautop( $tab_tut ) ); ?></div></div>
					<?php endif; ?>
					<?php if ( $has_support ) : ?>
						<div class="panel" data-panel="support" id="lavpanel-support" role="tabpanel" aria-labelledby="lavtab-support" tabindex="0"><div class="intro"><h2><?php esc_html_e( 'Support', 'lavzentheme' ); ?></h2><?php echo wp_kses_post( wpautop( $tab_support ) ); ?></div></div>
					<?php endif; ?>
				</div>

				<?php
				$hot_args = array(
					'post_type'           => 'download',
					'posts_per_page'      => 3,
					'post__not_in'        => array( $id ),
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				);
				if ( $cat ) {
					$hot_args['tax_query'] = array( array( 'taxonomy' => 'download_category', 'field' => 'term_id', 'terms' => $cat->term_id ) );
				} else {
					$hot_args['meta_key'] = '_edd_download_sales';
					$hot_args['orderby']  = 'meta_value_num';
					$hot_args['order']    = 'DESC';
				}
				$hot = new WP_Query( $hot_args );
				if ( $hot->have_posts() ) :
					?>
					<div class="hot-head"><h2><?php esc_html_e( 'Hot Products', 'lavzentheme' ); ?></h2></div>
					<div class="hot-grid">
						<?php
						while ( $hot->have_posts() ) :
							$hot->the_post();
							$hid    = (int) get_the_ID();
							$h_img  = has_post_thumbnail( $hid ) ? get_the_post_thumbnail_url( $hid, 'lavzen-card' ) : '';
							$h_pric = edd_price( $hid, false );
							?>
							<a class="glass hot-card" href="<?php the_permalink(); ?>">
								<div class="hot-img"><?php if ( $h_img ) : ?><img src="<?php echo esc_url( $h_img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy"><?php endif; ?></div>
								<div class="hot-body">
									<h4><?php the_title(); ?></h4>
									<div class="hot-meta"><span class="price"><?php echo wp_kses_post( $h_pric ); ?></span></div>
								</div>
							</a>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="lav-product mobile-buybar">
		<div class="mb-price"><b><?php echo wp_kses_post( $price_html ); ?></b><span><?php echo esc_html( $currency ); ?></span></div>
		<button type="button" class="btn btn-primary" data-lav-buy-proxy>
			<?php echo $cart_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Add to Cart', 'lavzentheme' ); ?>
		</button>
	</div>

	<?php
endwhile;
