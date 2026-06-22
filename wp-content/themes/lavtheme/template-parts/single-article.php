<?php
/**
 * Single post — article body (editorial 3-column reading experience).
 *
 * Layout: sticky action rail (left) · article (center) · table of contents
 * (right), collapsing to one column on tablet/mobile. Built on the theme's
 * Liquid-Glass tokens; scoped under .lav-single (assets/css/single.css) with
 * progressive enhancement in assets/js/single.js (reading progress, TOC
 * scroll-spy via IntersectionObserver, rail/dock actions, related carousel).
 *
 * Rendered inside the loop by single.php (or as the editable Code Studio
 * "Single Post" Template override). All output escaped here. Semantic HTML +
 * schema.org BlogPosting microdata for GEO/SEO.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_id        = get_the_ID();
$lav_author    = get_the_author();
$lav_author_id = (int) get_post_field( 'post_author', $lav_id );
$lav_cats      = get_the_category();
$lav_tags      = get_the_tags();
$lav_excerpt   = has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_excerpt(), 34, '…' );
$lav_readtime  = function_exists( 'lavtheme_blog_read_time' ) ? (int) lavtheme_blog_read_time( $lav_id ) : 1;
$lav_comments  = (int) get_comments_number( $lav_id );
$lav_permalink = get_permalink( $lav_id );
$lav_blog_url  = function_exists( 'lavtheme_blog_url' ) ? lavtheme_blog_url() : home_url( '/' );
$lav_share_x   = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $lav_permalink ) . '&text=' . rawurlencode( get_the_title() );
$lav_thumb_id  = get_post_thumbnail_id( $lav_id );
?>
<div class="lav-progress" id="lav-progress" aria-hidden="true"><span></span></div>

<div class="lav-single" id="content">
	<div class="article-shell">

		<!-- LEFT: sticky action rail -->
		<aside class="rail" aria-label="<?php esc_attr_e( 'Article actions', 'lavtheme' ); ?>">
			<div class="rail-track">
				<button type="button" class="rail-btn lav-like" data-comment="0" data-post="<?php echo esc_attr( $lav_id ); ?>" aria-pressed="false" aria-label="<?php esc_attr_e( 'Like this article', 'lavtheme' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.5-1.5 3-3.6 3-5.5A3.5 3.5 0 0 0 12 6 3.5 3.5 0 0 0 2 8.5C2 12 7 16 12 20c2-1.6 4.5-3.6 7-6"/></svg>
				</button>
				<span class="rail-count lav-like-count"><?php echo esc_html( number_format_i18n( (int) get_post_meta( $lav_id, 'lavtheme_post_likes', true ) ) ); ?></span>
				<button type="button" class="rail-btn lav-save" aria-pressed="false" aria-label="<?php esc_attr_e( 'Save for later', 'lavtheme' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
				</button>
				<button type="button" class="rail-btn lav-copy" data-url="<?php echo esc_url( $lav_permalink ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'lavtheme' ); ?>">
					<svg class="ic-link" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/></svg>
					<svg class="ic-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
				</button>
				<a class="rail-btn" href="<?php echo esc_url( $lav_share_x ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Share on X', 'lavtheme' ); ?>">
					<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 2H22l-7.6 8.7L23.3 22h-7l-5.5-7.2L4.5 22H1.4l8.1-9.3L.9 2h7.2l5 6.6zm-1.2 18h1.7L7.1 3.8H5.3z"/></svg>
				</a>
				<a class="rail-btn" href="#comments" aria-label="<?php esc_attr_e( 'Jump to comments', 'lavtheme' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				</a>
			</div>
		</aside>

		<!-- CENTER: the article -->
		<article class="post-col" itemscope itemtype="https://schema.org/BlogPosting">
			<meta itemprop="datePublished" content="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
			<meta itemprop="dateModified" content="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
			<meta itemprop="mainEntityOfPage" content="<?php echo esc_url( $lav_permalink ); ?>">
			<meta itemprop="commentCount" content="<?php echo esc_attr( $lav_comments ); ?>">

			<header class="post-head">
				<nav class="crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'lavtheme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lavtheme' ); ?></a>
					<span class="sep" aria-hidden="true">/</span>
					<a href="<?php echo esc_url( $lav_blog_url ); ?>"><?php esc_html_e( 'Blog', 'lavtheme' ); ?></a>
					<?php if ( $lav_cats && ! is_wp_error( $lav_cats ) ) : ?>
						<span class="sep" aria-hidden="true">/</span>
						<a href="<?php echo esc_url( get_category_link( $lav_cats[0]->term_id ) ); ?>"><?php echo esc_html( $lav_cats[0]->name ); ?></a>
					<?php endif; ?>
					<span class="sep" aria-hidden="true">/</span>
					<span class="current" aria-current="page"><?php echo esc_html( wp_trim_words( get_the_title(), 8, '…' ) ); ?></span>
				</nav>

				<?php if ( $lav_cats && ! is_wp_error( $lav_cats ) ) : ?>
					<a class="kicker" href="<?php echo esc_url( get_category_link( $lav_cats[0]->term_id ) ); ?>"><?php echo esc_html( $lav_cats[0]->name ); ?></a>
				<?php endif; ?>

				<h1 class="post-title" itemprop="headline"><span class="grad"><?php the_title(); ?></span></h1>

				<?php if ( $lav_excerpt ) : ?>
					<p class="dek" itemprop="description"><?php echo esc_html( $lav_excerpt ); ?></p>
				<?php endif; ?>

				<div class="post-meta">
					<a class="pm-who" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>" itemprop="author" itemscope itemtype="https://schema.org/Person">
						<?php echo get_avatar( $lav_author_id, 80, '', $lav_author, array( 'class' => 'pm-av' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>
						<span class="pm-name" itemprop="name"><?php echo esc_html( $lav_author ); ?></span>
					</a>
					<span class="pm-dot" aria-hidden="true"></span>
					<time class="pm-item" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="17" rx="2.5"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg>
						<?php echo esc_html( get_the_date() ); ?>
					</time>
					<span class="pm-dot" aria-hidden="true"></span>
					<span class="pm-item">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
						<?php printf( esc_html__( '%d min read', 'lavtheme' ), $lav_readtime ); ?>
					</span>
					<?php if ( $lav_tags && ! is_wp_error( $lav_tags ) ) : ?>
						<span class="tag-row">
							<?php foreach ( array_slice( $lav_tags, 0, 3 ) as $lav_t ) : ?>
								<a class="tag" href="<?php echo esc_url( get_tag_link( $lav_t ) ); ?>"><?php echo esc_html( $lav_t->name ); ?></a>
							<?php endforeach; ?>
						</span>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( $lav_thumb_id ) : ?>
				<figure class="cover">
					<?php
					echo wp_get_attachment_image(
						$lav_thumb_id,
						'large',
						false,
						array(
							'class'         => 'cover-img',
							'itemprop'      => 'image',
							'loading'       => 'eager',
							'fetchpriority' => 'high',
							'decoding'      => 'async',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped.
					$lav_cap = get_the_post_thumbnail_caption();
					if ( $lav_cap ) :
						?>
						<figcaption class="cap"><?php echo esc_html( $lav_cap ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="prose" id="lav-prose" itemprop="articleBody">
				<?php
				the_content();
				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'lavtheme' ),
						'after'  => '</div>',
					)
				);
				?>
			</div>

			<?php
			$lav_bio = get_the_author_meta( 'description', $lav_author_id );
			if ( $lav_bio ) :
				?>
				<footer class="author-box">
					<a class="bigav" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>" aria-label="<?php echo esc_attr( $lav_author ); ?>">
						<?php echo get_avatar( $lav_author_id, 124, '', $lav_author ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>
					</a>
					<div class="ab-body">
						<span class="ab-kicker"><?php esc_html_e( 'Written by', 'lavtheme' ); ?></span>
						<a class="ab-name" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>"><?php echo esc_html( $lav_author ); ?></a>
						<p class="ab-bio"><?php echo esc_html( $lav_bio ); ?></p>
					</div>
					<a class="ab-follow" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>"><?php esc_html_e( 'More posts', 'lavtheme' ); ?></a>
				</footer>
			<?php endif; ?>
		</article>

		<!-- RIGHT: table of contents (built by JS from the article headings) -->
		<nav class="toc" id="lav-toc" aria-label="<?php esc_attr_e( 'On this page', 'lavtheme' ); ?>" hidden>
			<h2 class="toc-h"><?php esc_html_e( 'On this page', 'lavtheme' ); ?></h2>
			<ul class="toc-list"></ul>
			<p class="toc-read">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
				<span class="toc-left" data-total="<?php echo esc_attr( $lav_readtime ); ?>"><?php printf( esc_html__( '%d min left', 'lavtheme' ), $lav_readtime ); ?></span>
			</p>
		</nav>
	</div>

	<?php
	// Related: same category, fallback to recent.
	$lav_rel_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 6,
		'post__not_in'        => array( $lav_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $lav_cats && ! is_wp_error( $lav_cats ) ) {
		$lav_rel_args['category__in'] = wp_list_pluck( $lav_cats, 'term_id' );
	}
	$lav_related = new WP_Query( $lav_rel_args );
	if ( $lav_related->have_posts() ) :
		?>
		<section class="related" aria-labelledby="lav-rel-h">
			<div class="rel-head">
				<h2 id="lav-rel-h"><?php esc_html_e( 'Keep reading', 'lavtheme' ); ?></h2>
				<div class="rel-nav">
					<button type="button" class="rel-arrow" data-dir="prev" aria-label="<?php esc_attr_e( 'Previous related articles', 'lavtheme' ); ?>" disabled>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
					</button>
					<button type="button" class="rel-arrow" data-dir="next" aria-label="<?php esc_attr_e( 'Next related articles', 'lavtheme' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
					</button>
				</div>
			</div>
			<div class="rel-viewport">
				<ul class="rel-track" tabindex="0" aria-label="<?php esc_attr_e( 'Related articles', 'lavtheme' ); ?>">
					<?php
					while ( $lav_related->have_posts() ) :
						$lav_related->the_post();
						$lav_rid   = get_the_ID();
						$lav_rthmb = has_post_thumbnail( $lav_rid ) ? get_the_post_thumbnail_url( $lav_rid, 'lavtheme-card' ) : '';
						$lav_rcats = get_the_category( $lav_rid );
						$lav_rcat  = ( $lav_rcats && ! is_wp_error( $lav_rcats ) ) ? $lav_rcats[0]->name : '';
						?>
						<li class="rel-item">
							<a class="rel" href="<?php the_permalink(); ?>">
								<span class="rel-thumb">
									<?php if ( $lav_rthmb ) : ?>
										<img src="<?php echo esc_url( $lav_rthmb ); ?>" alt="<?php echo esc_attr( get_the_title( $lav_rid ) ); ?>" loading="lazy" decoding="async">
									<?php endif; ?>
								</span>
								<span class="rel-b">
									<?php if ( $lav_rcat ) : ?><span class="rel-pill"><?php echo esc_html( $lav_rcat ); ?></span><?php endif; ?>
									<span class="rel-t"><?php echo esc_html( get_the_title( $lav_rid ) ); ?></span>
									<span class="rel-m"><?php printf( esc_html__( '%d min read', 'lavtheme' ), function_exists( 'lavtheme_blog_read_time' ) ? (int) lavtheme_blog_read_time( $lav_rid ) : 1 ); ?> · <?php echo esc_html( get_the_date( '', $lav_rid ) ); ?></span>
								</span>
							</a>
						</li>
						<?php
					endwhile;
					?>
				</ul>
			</div>
			<div class="rel-dots" role="tablist" aria-label="<?php esc_attr_e( 'Related pagination', 'lavtheme' ); ?>"></div>
		</section>
		<?php
		wp_reset_postdata();
	endif;
	?>

	<?php if ( comments_open( $lav_id ) || $lav_comments > 0 ) : ?>
		<section class="comments-section" id="comments" aria-label="<?php esc_attr_e( 'Comments', 'lavtheme' ); ?>">
			<?php comments_template(); ?>
		</section>
	<?php endif; ?>
</div><!-- #content -->

<!-- mobile bottom action dock (mirrors the rail) -->
<div class="dock" aria-label="<?php esc_attr_e( 'Article actions', 'lavtheme' ); ?>">
	<button type="button" class="rail-btn lav-like" data-post="<?php echo esc_attr( $lav_id ); ?>" aria-pressed="false" aria-label="<?php esc_attr_e( 'Like this article', 'lavtheme' ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.5-1.5 3-3.6 3-5.5A3.5 3.5 0 0 0 12 6 3.5 3.5 0 0 0 2 8.5C2 12 7 16 12 20c2-1.6 4.5-3.6 7-6"/></svg>
	</button>
	<button type="button" class="rail-btn lav-save" aria-pressed="false" aria-label="<?php esc_attr_e( 'Save for later', 'lavtheme' ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
	</button>
	<a class="rail-btn" href="#comments" aria-label="<?php esc_attr_e( 'Jump to comments', 'lavtheme' ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
	</a>
	<button type="button" class="rail-btn lav-copy" data-url="<?php echo esc_url( $lav_permalink ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'lavtheme' ); ?>">
		<svg class="ic-link" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/></svg>
		<svg class="ic-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
	</button>
</div>
