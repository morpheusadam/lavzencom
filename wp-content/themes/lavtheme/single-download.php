<?php
/**
 * Single EDD download — the "product.html" design wired to real data.
 *
 * A theme template (NOT an EDD /edd/ override — update-safe). Every datapoint is
 * real: native EDD fields (title, price, image, content, category, sales, dates,
 * purchase button, related products) + lavtheme product meta from the metaboxes
 * in inc/edd-product-meta.php. Any empty field hides its section gracefully.
 *
 * Markup is wrapped in .lav-product and styled by assets/css/single-product.css
 * (scoped, token-based). The dl-template Code Studio context can still layer
 * CSS/JS on top.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! headers_sent() ) {
	header( 'X-Content-Type-Options: nosniff' );
}

get_header();

// Don't let EDD auto-append a second purchase button inside the content.
remove_filter( 'the_content', 'edd_after_download_content' );

while ( have_posts() ) :
	the_post();
	$id = (int) get_the_ID();

	if ( ! function_exists( 'edd_get_download_price' ) ) {
		echo '<section class="block"><p class="block-intro">' . esc_html__( 'Easy Digital Downloads is required.', 'lavtheme' ) . '</p></section>';
		get_footer();
		return;
	}

	/* -------------------- native EDD data -------------------- */
	$title     = get_the_title();
	$cats      = get_the_terms( $id, 'download_category' );
	$cat       = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0] : null;
	$price_num = (float) edd_get_download_price( $id );
	$variable  = function_exists( 'edd_has_variable_prices' ) && edd_has_variable_prices( $id );
	$sales     = (int) edd_get_download_sales_stats( $id );
	$published = get_the_date( 'M Y' );
	$updated   = human_time_diff( (int) get_the_modified_time( 'U' ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'lavtheme' );
	$thumb     = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'large' ) : '';
	$is_new    = ( time() - (int) get_post_time( 'U', true, $id ) ) < 14 * DAY_IN_SECONDS;
	$currency  = function_exists( 'edd_get_currency' ) ? edd_get_currency() : 'USD';
	$price_html = edd_price( $id, false );

	/* -------------------- custom meta data -------------------- */
	$version      = lavtheme_pm_get( $id, '_lav_version' );
	$tagline      = lavtheme_pm_get( $id, '_lav_tagline', get_the_excerpt() );
	$demo_url     = lavtheme_pm_get( $id, '_lav_demo_url' );
	$preview_url  = lavtheme_pm_get( $id, '_lav_preview_url' );
	$sub_note     = lavtheme_pm_get( $id, '_lav_subscription_note' );
	$support_lbl  = lavtheme_pm_get( $id, '_lav_support_label', '24/7' );
	$installment  = lavtheme_pm_is_on( $id, '_lav_show_installment' ) && $price_num > 0 && ! $variable;
	$author_name  = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) );
	$seller_name  = lavtheme_pm_get( $id, '_lav_seller_name', $author_name );
	$seller_verif = lavtheme_pm_is_on( $id, '_lav_seller_verified' );
	$seller_resp  = lavtheme_pm_get( $id, '_lav_seller_response' );
	$features     = lavtheme_pm_list( $id, 'product_features' );
	$specs        = lavtheme_pm_rows( $id, '_lav_spec_boxes', 2, 4 );
	$cards        = lavtheme_pm_rows( $id, '_lav_feature_cards', 2, 6 );
	$highlights   = lavtheme_pm_rows( $id, '_lav_highlights', 2, 3 );
	$tab_tut      = lavtheme_pm_get( $id, '_lav_tab_tutorials' );
	$tab_qa       = lavtheme_pm_get( $id, '_lav_tab_qa' );
	$tab_support  = lavtheme_pm_get( $id, '_lav_tab_support' );
	$gallery_raw  = get_post_meta( $id, '_product_gallery_ids', true );
	$gallery_ids  = $gallery_raw ? array_values( array_filter( array_map( 'absint', explode( ',', (string) $gallery_raw ) ) ) ) : array();

	$installment_amt = $installment ? edd_currency_filter( edd_format_amount( $price_num / 4 ) ) : '';
	$seller_initial  = function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $seller_name, 0, 1 ) ) : strtoupper( substr( $seller_name, 0, 1 ) );

	// Which tabs have content (Description is always present).
	$has_qa      = '' !== trim( wp_strip_all_tags( $tab_qa ) );
	$has_tut     = '' !== trim( wp_strip_all_tags( $tab_tut ) );
	$has_support = '' !== trim( wp_strip_all_tags( $tab_support ) );

	$cart_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>';
	$tick_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
	?>

	<div class="lav-product">

		<!-- breadcrumb + version -->
		<div class="crumb">
			<div class="path">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lavtheme' ); ?></a><span class="sep">/</span>
				<?php if ( $cat ) : ?>
					<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a><span class="sep">/</span>
				<?php endif; ?>
				<span style="color:var(--text-3)"><?php echo esc_html( $title ); ?></span>
			</div>
			<?php if ( $version ) : ?>
				<span class="ver"><?php esc_html_e( 'Version', 'lavtheme' ); ?> <b><?php echo esc_html( $version ); ?></b></span>
			<?php endif; ?>
		</div>

		<h1 class="lav-product__title"><?php echo esc_html( $title ); ?></h1>

		<div class="layout">

			<!-- ===================== SIDEBAR ===================== -->
			<aside class="side">

				<!-- buy card -->
				<div class="glass s-buy" id="lav-buy">
					<span class="label"><?php esc_html_e( 'Product price:', 'lavtheme' ); ?></span>
					<div class="price"><?php echo wp_kses_post( $price_html ); ?> <small><?php echo esc_html( $currency ); ?></small></div>
					<?php if ( $installment ) : ?>
						<div class="installment">
							<span><?php esc_html_e( 'Pay in 4 interest-free parts', 'lavtheme' ); ?></span>
							<span class="pay"><?php echo esc_html( $installment_amt ); ?> &times;4</span>
						</div>
					<?php endif; ?>
					<?php
					echo edd_get_purchase_link( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- EDD builds escaped markup.
						array(
							'download_id' => $id,
							'price'       => false,
							'text'        => __( 'Add to Cart', 'lavtheme' ),
							'class'       => 'btn btn-primary lav-buy-btn',
						)
					);
					?>
					<?php if ( $sub_note ) : ?>
						<div class="pro"><span class="tag"><?php esc_html_e( 'PRO', 'lavtheme' ); ?></span> <?php echo esc_html( $sub_note ); ?></div>
					<?php endif; ?>
				</div>

				<div class="s-carousel">

					<!-- stats + features -->
					<?php if ( $sales || $features ) : ?>
						<div class="glass s-card">
							<div class="s-stats">
								<div class="col"><b><?php echo esc_html( number_format_i18n( $sales ) ); ?></b><span><?php esc_html_e( 'Sales', 'lavtheme' ); ?></span></div>
								<div class="col"><b><?php echo esc_html( $is_new ? __( 'New', 'lavtheme' ) : __( 'Pro', 'lavtheme' ) ); ?></b><span><?php esc_html_e( 'Product', 'lavtheme' ); ?></span></div>
								<div class="col"><b><?php echo esc_html( $support_lbl ); ?></b><span><?php esc_html_e( 'Support', 'lavtheme' ); ?></span></div>
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

					<!-- seller -->
					<?php if ( $seller_name ) : ?>
						<div class="glass s-card s-seller">
							<div class="who">
								<div class="av"><?php echo esc_html( $seller_initial ); ?></div>
								<div class="info">
									<b><?php echo esc_html( $seller_name ); ?></b>
									<?php if ( $seller_verif ) : ?><span><?php esc_html_e( 'Verified author', 'lavtheme' ); ?></span><?php endif; ?>
								</div>
							</div>
							<?php if ( $seller_resp ) : ?>
								<div class="resp"><?php esc_html_e( 'Avg. response time:', 'lavtheme' ); ?> <b style="color:var(--text-2)"><?php echo esc_html( $seller_resp ); ?></b></div>
							<?php endif; ?>
							<div class="badges">
								<span class="b"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 19l-4.8 2.5.9-5.4L4.2 12.3l5.4-.8z"/></svg></span>
								<span class="b"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M9 14l-1 8 4-2 4 2-1-8"/></svg></span>
								<span class="b"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
							</div>
						</div>
					<?php endif; ?>

					<!-- version / date meta -->
					<div class="glass s-card s-meta">
						<?php if ( $version ) : ?><div class="m"><b><?php echo esc_html( $version ); ?></b><span><?php esc_html_e( 'Version', 'lavtheme' ); ?></span></div><?php endif; ?>
						<div class="m"><b><?php echo esc_html( $updated ); ?></b><span><?php esc_html_e( 'Last update', 'lavtheme' ); ?></span></div>
						<div class="m"><b><?php echo esc_html( $published ); ?></b><span><?php esc_html_e( 'Published', 'lavtheme' ); ?></span></div>
						<?php if ( $cat ) : ?><div class="m"><b><?php echo esc_html( $cat->name ); ?></b><span><?php esc_html_e( 'Category', 'lavtheme' ); ?></span></div><?php endif; ?>
					</div>

				</div>
			</aside>

			<!-- ===================== CONTENT ===================== -->
			<div class="content">

				<!-- product banner -->
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
								<a class="chip active" href="<?php echo esc_url( $demo_url ); ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg><?php esc_html_e( 'Live Demo', 'lavtheme' ); ?></a>
							<?php endif; ?>
							<?php if ( $preview_url ) : ?>
								<a class="chip" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="3"/><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/></svg><?php esc_html_e( 'Preview', 'lavtheme' ); ?></a>
							<?php endif; ?>
							<?php if ( $has_support ) : ?>
								<a class="chip" href="#" data-lav-tab="support"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><?php esc_html_e( 'Support', 'lavtheme' ); ?></a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<!-- tabs -->
				<div class="glass tabs-wrap">
					<div class="tablist" role="tablist">
						<button class="tab is-active" data-panel="desc" role="tab" aria-selected="true"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span><?php esc_html_e( 'Description', 'lavtheme' ); ?></button>
						<?php if ( $has_qa ) : ?><button class="tab" data-panel="faq" role="tab" aria-selected="false"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg></span><?php esc_html_e( 'Q & A', 'lavtheme' ); ?></button><?php endif; ?>
						<?php if ( $has_tut ) : ?><button class="tab" data-panel="tutorials" role="tab" aria-selected="false"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 3h20v14H2z"/><path d="m10 8 5 3-5 3V8z"/></svg></span><?php esc_html_e( 'Tutorials', 'lavtheme' ); ?></button><?php endif; ?>
						<?php if ( $has_support ) : ?><button class="tab" data-panel="support" role="tab" aria-selected="false"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><?php esc_html_e( 'Support', 'lavtheme' ); ?></button><?php endif; ?>
					</div>

					<!-- DESCRIPTION -->
					<div class="panel is-active" data-panel="desc">
						<div class="intro">
							<h2><?php printf( esc_html__( 'About %s', 'lavtheme' ), esc_html( $title ) ); ?></h2>
							<?php the_content(); ?>
						</div>

						<?php if ( $tagline || $highlights ) : ?>
							<div class="inner-banner">
								<div class="badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
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
											<<?php echo esc_html( $tag ); ?> class="act"<?php echo $h_url ? ' href="' . esc_url( $h_url ) . '"' : ''; ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg><?php echo esc_html( $h_label ); ?></<?php echo esc_html( $tag ); ?>>
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
							<div class="feat-head"><h2><?php esc_html_e( 'Key capabilities', 'lavtheme' ); ?></h2></div>
							<div class="feat-grid">
								<?php foreach ( $cards as $c ) : ?>
									<div class="glass feat-card">
										<span class="fi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
										<h4><?php echo esc_html( $c[0] ); ?></h4>
										<?php if ( ! empty( $c[1] ) ) : ?><p><?php echo esc_html( $c[1] ); ?></p><?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( $gallery_ids ) : ?>
							<div class="gal-head">
								<h2><?php esc_html_e( 'Product Gallery', 'lavtheme' ); ?></h2>
								<div class="gal-nav">
									<button type="button" data-gal="prev" aria-label="<?php esc_attr_e( 'Previous', 'lavtheme' ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></button>
									<button type="button" data-gal="next" aria-label="<?php esc_attr_e( 'Next', 'lavtheme' ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></button>
								</div>
							</div>
							<div class="gallery">
								<?php
								foreach ( $gallery_ids as $gid ) :
									$g_full  = wp_get_attachment_image_url( $gid, 'large' );
									$g_thumb = wp_get_attachment_image_url( $gid, 'medium' );
									if ( ! $g_thumb ) {
										continue;
									}
									?>
									<a class="gal-item" href="<?php echo esc_url( $g_full ? $g_full : $g_thumb ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( $g_thumb ); ?>" alt="" loading="lazy"></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<!-- Q&A -->
					<?php if ( $has_qa ) : ?>
						<div class="panel" data-panel="faq">
							<div class="intro"><h2><?php esc_html_e( 'Questions & Answers', 'lavtheme' ); ?></h2><?php echo wp_kses_post( wpautop( $tab_qa ) ); ?></div>
						</div>
					<?php endif; ?>
					<!-- TUTORIALS -->
					<?php if ( $has_tut ) : ?>
						<div class="panel" data-panel="tutorials">
							<div class="intro"><h2><?php esc_html_e( 'Tutorials', 'lavtheme' ); ?></h2><?php echo wp_kses_post( wpautop( $tab_tut ) ); ?></div>
						</div>
					<?php endif; ?>
					<!-- SUPPORT -->
					<?php if ( $has_support ) : ?>
						<div class="panel" data-panel="support">
							<div class="intro"><h2><?php esc_html_e( 'Support', 'lavtheme' ); ?></h2><?php echo wp_kses_post( wpautop( $tab_support ) ); ?></div>
						</div>
					<?php endif; ?>
				</div>

				<!-- hot / related products -->
				<?php
				$hot_args = array(
					'post_type'           => 'download',
					'posts_per_page'      => 3,
					'post__not_in'        => array( $id ),
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				);
				if ( $cat ) {
					$hot_args['tax_query'] = array(
						array(
							'taxonomy' => 'download_category',
							'field'    => 'term_id',
							'terms'    => $cat->term_id,
						),
					);
				} else {
					$hot_args['meta_key'] = '_edd_download_sales';
					$hot_args['orderby']  = 'meta_value_num';
					$hot_args['order']    = 'DESC';
				}
				$hot = new WP_Query( $hot_args );
				if ( $hot->have_posts() ) :
					?>
					<div class="hot-head"><h2><?php esc_html_e( 'Hot Products', 'lavtheme' ); ?></h2></div>
					<div class="hot-grid">
						<?php
						while ( $hot->have_posts() ) :
							$hot->the_post();
							$hid    = (int) get_the_ID();
							$h_img  = has_post_thumbnail( $hid ) ? get_the_post_thumbnail_url( $hid, 'lavtheme-card' ) : '';
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

				<!-- trust row (static signals) -->
				<div class="glass trust">
					<div class="trust-item"><span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><b><?php esc_html_e( 'Support', 'lavtheme' ); ?></b><span><?php esc_html_e( 'Included', 'lavtheme' ); ?></span></div>
					<div class="trust-item"><span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 4v6h6"/><path d="M3.5 10a9 9 0 1 1 .5 5"/></svg></span><b><?php esc_html_e( 'Money-back', 'lavtheme' ); ?></b><span><?php esc_html_e( 'Guaranteed', 'lavtheme' ); ?></span></div>
					<div class="trust-item"><span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg></span><b><?php esc_html_e( 'Documentation', 'lavtheme' ); ?></b><span><?php esc_html_e( 'Full guide', 'lavtheme' ); ?></span></div>
					<div class="trust-item"><span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg></span><b><?php esc_html_e( 'Easy install', 'lavtheme' ); ?></b><span><?php esc_html_e( 'One-click', 'lavtheme' ); ?></span></div>
					<div class="trust-item"><span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 19l-4.8 2.5.9-5.4L4.2 12.3l5.4-.8z"/></svg></span><b><?php esc_html_e( 'Original', 'lavtheme' ); ?></b><span><?php esc_html_e( '100% authentic', 'lavtheme' ); ?></span></div>
				</div>

			</div>
		</div>
	</div>

	<!-- sticky mobile buy bar -->
	<div class="lav-product mobile-buybar">
		<div class="mb-price"><b><?php echo wp_kses_post( $price_html ); ?></b><span><?php echo esc_html( $currency ); ?><?php echo $installment ? ' · 4&times; ' . esc_html( $installment_amt ) : ''; ?></span></div>
		<button type="button" class="btn btn-primary" data-lav-buy-proxy>
			<?php echo $cart_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Add to Cart', 'lavtheme' ); ?>
		</button>
	</div>

	<?php
endwhile;

get_footer();
