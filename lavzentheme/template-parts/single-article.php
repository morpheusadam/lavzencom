<?php
/**
 * Single post — article body (editorial 3-column reading experience).
 *
 * Sticky action rail · article · table of contents (built by JS). Semantic HTML
 * + schema.org BlogPosting microdata. Rendered in the loop by single.php; the
 * Code Studio "Single Post" context (Phase 3) can override it. The like AJAX +
 * read-time helper arrive with the Blog module (Phase 4); both degrade safely.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_id        = get_the_ID();
$lav_author    = get_the_author();
$lav_author_id = (int) get_post_field( 'post_author', $lav_id );
$lav_cats      = get_the_category();
$lav_tags      = get_the_tags();
$lav_excerpt   = has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_excerpt(), 34, '…' );
$lav_readtime  = function_exists( 'lavzen_read_time' )
	? (int) lavzen_read_time( $lav_id )
	: max( 1, (int) ceil( str_word_count( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $lav_id ) ) ) ) / 200 ) );
$lav_comments  = (int) get_comments_number( $lav_id );
$lav_permalink = get_permalink( $lav_id );
$lav_blog_url  = function_exists( 'lavzen_blog_url' ) ? lavzen_blog_url() : home_url( '/' );
$lav_share_x   = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $lav_permalink ) . '&text=' . rawurlencode( get_the_title() );
$lav_thumb_id  = get_post_thumbnail_id( $lav_id );
// Like count: read the legacy meta key to preserve counts across cutover.
$lav_likes     = (int) get_post_meta( $lav_id, 'lavtheme_post_likes', true );
?>
<div class="lav-progress" id="lav-progress" aria-hidden="true"><span></span></div>

<div class="lav-single" id="content">
	<div class="article-shell">

		<aside class="rail" aria-label="<?php esc_attr_e( 'Article actions', 'lavzentheme' ); ?>">
			<div class="rail-track">
				<button type="button" class="rail-btn lav-like" data-post="<?php echo esc_attr( $lav_id ); ?>" aria-pressed="false" aria-label="<?php esc_attr_e( 'Like this article', 'lavzentheme' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.5-1.5 3-3.6 3-5.5A3.5 3.5 0 0 0 12 6 3.5 3.5 0 0 0 2 8.5C2 12 7 16 12 20c2-1.6 4.5-3.6 7-6"/></svg>
				</button>
				<span class="rail-count lav-like-count"><?php echo esc_html( number_format_i18n( $lav_likes ) ); ?></span>
				<button type="button" class="rail-btn lav-copy" data-url="<?php echo esc_url( $lav_permalink ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'lavzentheme' ); ?>">
					<svg class="ic-link" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/></svg>
					<svg class="ic-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
				</button>
				<a class="rail-btn" href="<?php echo esc_url( $lav_share_x ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Share on X', 'lavzentheme' ); ?>">
					<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 2H22l-7.6 8.7L23.3 22h-7l-5.5-7.2L4.5 22H1.4l8.1-9.3L.9 2h7.2l5 6.6zm-1.2 18h1.7L7.1 3.8H5.3z"/></svg>
				</a>
				<a class="rail-btn" href="#comments" aria-label="<?php esc_attr_e( 'Jump to comments', 'lavzentheme' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				</a>
			</div>
		</aside>

		<article class="post-col" itemscope itemtype="https://schema.org/BlogPosting">
			<meta itemprop="datePublished" content="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
			<meta itemprop="dateModified" content="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
			<meta itemprop="mainEntityOfPage" content="<?php echo esc_url( $lav_permalink ); ?>">
			<meta itemprop="commentCount" content="<?php echo esc_attr( $lav_comments ); ?>">

			<header class="post-head">
				<nav class="crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'lavzentheme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lavzentheme' ); ?></a>
					<span class="sep" aria-hidden="true">/</span>
					<a href="<?php echo esc_url( $lav_blog_url ); ?>"><?php esc_html_e( 'Blog', 'lavzentheme' ); ?></a>
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
						<?php
						/* translators: %d: estimated reading time in minutes. */
						printf( esc_html__( '%d min read', 'lavzentheme' ), $lav_readtime );
						?>
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
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'lavzentheme' ),
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
						<span class="ab-kicker"><?php esc_html_e( 'Written by', 'lavzentheme' ); ?></span>
						<a class="ab-name" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>"><?php echo esc_html( $lav_author ); ?></a>
						<p class="ab-bio"><?php echo esc_html( $lav_bio ); ?></p>
					</div>
					<a class="ab-follow" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>"><?php esc_html_e( 'More posts', 'lavzentheme' ); ?></a>
				</footer>
			<?php endif; ?>
		</article>

		<nav class="toc" id="lav-toc" aria-label="<?php esc_attr_e( 'On this page', 'lavzentheme' ); ?>" hidden>
			<h2 class="toc-h"><?php esc_html_e( 'On this page', 'lavzentheme' ); ?></h2>
			<ul class="toc-list"></ul>
		</nav>
	</div>

	<?php
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
			<div class="rel-head"><h2 id="lav-rel-h"><?php esc_html_e( 'Keep reading', 'lavzentheme' ); ?></h2></div>
			<div class="rel-viewport">
				<ul class="rel-track" tabindex="0" aria-label="<?php esc_attr_e( 'Related articles', 'lavzentheme' ); ?>">
					<?php
					while ( $lav_related->have_posts() ) :
						$lav_related->the_post();
						$lav_rid   = get_the_ID();
						$lav_rthmb = has_post_thumbnail( $lav_rid ) ? get_the_post_thumbnail_url( $lav_rid, 'lavzen-card' ) : '';
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
									<span class="rel-m"><?php echo esc_html( get_the_date( '', $lav_rid ) ); ?></span>
								</span>
							</a>
						</li>
						<?php
					endwhile;
					?>
				</ul>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	endif;
	?>

	<?php if ( comments_open( $lav_id ) || $lav_comments > 0 ) : ?>
		<section class="comments-section" id="comments" aria-label="<?php esc_attr_e( 'Comments', 'lavzentheme' ); ?>">
			<?php comments_template(); ?>
		</section>
	<?php endif; ?>
</div><!-- #content -->
