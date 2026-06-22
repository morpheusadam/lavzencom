<?php
/**
 * Single post template — professional article reading experience.
 *
 * Liquid-Glass design, scoped under .lav-single (assets/css/single.css). Reuses
 * the blog helpers (read time, avatar initial, post card) so the article page
 * stays consistent with the blog archive. All output escaped here.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

/** Blog index URL (graceful when the blog helper is absent). */
$lav_blog_url = function_exists( 'lavtheme_blog_url' ) ? lavtheme_blog_url() : home_url( '/' );

while ( have_posts() ) :
	the_post();

	$lav_id       = get_the_ID();
	$lav_author   = get_the_author();
	$lav_author_id = (int) get_post_field( 'post_author', $lav_id );
	$lav_cats     = get_the_category();
	$lav_tags     = get_the_tags();
	$lav_excerpt  = has_excerpt() ? get_the_excerpt() : '';
	$lav_readtime = function_exists( 'lavtheme_blog_read_time' ) ? lavtheme_blog_read_time( $lav_id ) : 1;
	$lav_initial  = function_exists( 'lavtheme_blog_initial' ) ? lavtheme_blog_initial( $lav_author ) : strtoupper( substr( $lav_author, 0, 1 ) );
	$lav_comments = (int) get_comments_number( $lav_id );
	$lav_permalink = get_permalink( $lav_id );
	$lav_share_url = rawurlencode( $lav_permalink );
	$lav_share_ttl = rawurlencode( get_the_title() );
	?>
	<div class="lav-single">

		<div class="reading-progress" aria-hidden="true"><span></span></div>

		<article class="lav-article" id="content">

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

			<header class="art-head">
				<?php if ( $lav_cats && ! is_wp_error( $lav_cats ) ) : ?>
					<div class="art-cats">
						<?php foreach ( $lav_cats as $lav_c ) : ?>
							<a class="art-cpill" href="<?php echo esc_url( get_category_link( $lav_c->term_id ) ); ?>"><?php echo esc_html( $lav_c->name ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<h1 class="art-title"><?php the_title(); ?></h1>

				<?php if ( $lav_excerpt ) : ?>
					<p class="art-deck"><?php echo esc_html( $lav_excerpt ); ?></p>
				<?php endif; ?>

				<div class="art-meta">
					<a class="who" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>">
						<?php echo get_avatar( $lav_author_id, 72, '', $lav_author, array( 'class' => 'av-img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>
						<span class="who-name"><?php echo esc_html( $lav_author ); ?></span>
					</a>
					<span class="dot" aria-hidden="true">·</span>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					<span class="dot" aria-hidden="true">·</span>
					<span class="rt">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
						<?php printf( esc_html__( '%d min read', 'lavtheme' ), (int) $lav_readtime ); ?>
					</span>
					<?php if ( $lav_comments > 0 ) : ?>
						<span class="dot" aria-hidden="true">·</span>
						<a class="rt" href="#comments">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
							<?php echo esc_html( number_format_i18n( $lav_comments ) ); ?>
						</a>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="art-hero glass">
					<?php the_post_thumbnail( 'large', array( 'class' => 'art-hero-img' ) ); ?>
					<?php
					$lav_caption = get_the_post_thumbnail_caption();
					if ( $lav_caption ) :
						?>
						<figcaption><?php echo esc_html( $lav_caption ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="art-body">
				<aside class="share-rail" aria-label="<?php esc_attr_e( 'Share this article', 'lavtheme' ); ?>">
					<span class="share-lbl"><?php esc_html_e( 'Share', 'lavtheme' ); ?></span>
					<a class="srb" href="https://twitter.com/intent/tweet?url=<?php echo esc_attr( $lav_share_url ); ?>&text=<?php echo esc_attr( $lav_share_ttl ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Share on X', 'lavtheme' ); ?>">
						<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 2H22l-7.2 8.2L23.3 22h-6.6l-5.2-6.8L5.5 22H2.4l7.7-8.8L1 2h6.8l4.7 6.2zm-1.2 18h1.8L7.2 3.8H5.3z"/></svg>
					</a>
					<a class="srb" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo esc_attr( $lav_share_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Share on LinkedIn', 'lavtheme' ); ?>">
						<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zM8.3 18.3V10H5.7v8.3zM7 8.8a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m11.3 9.5v-4.6c0-2.5-1.3-3.6-3.1-3.6a2.7 2.7 0 0 0-2.4 1.3V10H10.2v8.3h2.6v-4.4c0-1.2.2-2.3 1.6-2.3s1.4 1.3 1.4 2.4v4.3z"/></svg>
					</a>
					<a class="srb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $lav_share_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Share on Facebook', 'lavtheme' ); ?>">
						<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.2c-1.2 0-1.6.8-1.6 1.6V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12"/></svg>
					</a>
					<button type="button" class="srb srb-copy" data-url="<?php echo esc_attr( $lav_permalink ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'lavtheme' ); ?>">
						<svg class="ic-link" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
						<svg class="ic-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
					</button>
				</aside>

				<div class="art-content entry-content">
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
			</div>

			<?php if ( $lav_tags && ! is_wp_error( $lav_tags ) ) : ?>
				<div class="art-tags">
					<?php foreach ( $lav_tags as $lav_t ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $lav_t ) ); ?>">#<?php echo esc_html( $lav_t->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php
			$lav_bio = get_the_author_meta( 'description', $lav_author_id );
			if ( $lav_bio ) :
				?>
				<div class="art-author glass">
					<a class="aa-av" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>">
						<?php echo get_avatar( $lav_author_id, 120, '', $lav_author ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>
					</a>
					<div class="aa-body">
						<span class="aa-kicker"><?php esc_html_e( 'Written by', 'lavtheme' ); ?></span>
						<a class="aa-name" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>"><?php echo esc_html( $lav_author ); ?></a>
						<p class="aa-bio"><?php echo esc_html( $lav_bio ); ?></p>
						<a class="aa-more" href="<?php echo esc_url( get_author_posts_url( $lav_author_id ) ); ?>"><?php esc_html_e( 'More from this author', 'lavtheme' ); ?> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></a>
					</div>
				</div>
			<?php endif; ?>

			<?php
			$lav_prev = get_previous_post();
			$lav_next = get_next_post();
			if ( $lav_prev || $lav_next ) :
				?>
				<nav class="art-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'lavtheme' ); ?>">
					<?php if ( $lav_prev ) : ?>
						<a class="an-card prev glass" href="<?php echo esc_url( get_permalink( $lav_prev ) ); ?>" rel="prev">
							<span class="an-dir"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg> <?php esc_html_e( 'Previous', 'lavtheme' ); ?></span>
							<span class="an-t"><?php echo esc_html( get_the_title( $lav_prev ) ); ?></span>
						</a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>
					<?php if ( $lav_next ) : ?>
						<a class="an-card next glass" href="<?php echo esc_url( get_permalink( $lav_next ) ); ?>" rel="next">
							<span class="an-dir"><?php esc_html_e( 'Next', 'lavtheme' ); ?> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></span>
							<span class="an-t"><?php echo esc_html( get_the_title( $lav_next ) ); ?></span>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

		</article>

		<?php
		// Related posts: same category, fallback to recent. Reuses the blog card.
		if ( function_exists( 'lavtheme_blog_card_html' ) ) {
			$lav_rel_args = array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 3,
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
				<section class="art-related" aria-label="<?php esc_attr_e( 'Related articles', 'lavtheme' ); ?>">
					<div class="ar-head">
						<span class="kicker"><?php esc_html_e( 'Keep reading', 'lavtheme' ); ?></span>
						<h2><?php esc_html_e( 'Related articles', 'lavtheme' ); ?></h2>
					</div>
					<div class="ar-grid">
						<?php
						while ( $lav_related->have_posts() ) :
							$lav_related->the_post();
							echo lavtheme_blog_card_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- builder escapes internally.
						endwhile;
						?>
					</div>
				</section>
				<?php
			endif;
			wp_reset_postdata();
		}
		?>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<section class="art-comments glass" id="comments">
				<?php comments_template(); ?>
			</section>
		<?php endif; ?>

	</div>
	<?php
endwhile;

get_footer();
