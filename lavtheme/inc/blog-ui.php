<?php
/**
 * Blog archive — UI builders (head + stats, featured post, filter bar, post
 * cards, sidebar widgets, pagination). Split from inc/blog.php; all output is
 * escaped here. Scoped under .lav-blog by assets/css/blog.css.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/** Build a blog filter URL from args (preserves nothing the caller didn't pass). */
function lavtheme_blog_url_with( $args ) {
	$base = function_exists( 'lavtheme_blog_url' ) ? lavtheme_blog_url() : home_url( '/' );
	$base = remove_query_arg( array( 'bcat', 'bsort', 'bdate', 'bauthor', 'bq', 'bpg', 'paged' ), $base );
	return $args ? add_query_arg( $args, $base ) : $base;
}

/** Avatar initial for a display name. */
function lavtheme_blog_initial( $name ) {
	$name = (string) $name;
	return function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : strtoupper( substr( $name, 0, 1 ) );
}

/** Blog head: kicker, title, description, real stats. */
function lavtheme_blog_head_html() {
	$counts   = wp_count_posts( 'post' );
	$articles = $counts ? (int) $counts->publish : 0;
	$topics   = (int) wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => true ) );
	$authors  = count( get_users( array( 'has_published_posts' => array( 'post' ), 'fields' => 'ID' ) ) );

	$title = __( 'The Blog', 'lavtheme' );
	$desc  = __( 'Guides, deep dives and product updates on web design, development, and digital business.', 'lavtheme' );
	if ( is_category() || is_tag() || is_tax() ) {
		$title = single_term_title( '', false );
		$desc  = wp_strip_all_tags( term_description() );
	} elseif ( is_author() ) {
		$title = get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) );
		$desc  = wp_strip_all_tags( get_the_author_meta( 'description', (int) get_query_var( 'author' ) ) );
	} elseif ( is_search() ) {
		/* translators: %s: search term. */
		$title = sprintf( __( 'Search: %s', 'lavtheme' ), get_search_query() );
		$desc  = '';
	} elseif ( is_date() ) {
		$title = get_the_archive_title();
		$desc  = '';
	}

	$stats = array(
		array( $articles, __( 'Articles', 'lavtheme' ) ),
		array( $topics, __( 'Topics', 'lavtheme' ) ),
		array( $authors, __( 'Authors', 'lavtheme' ) ),
	);

	ob_start();
	?>
	<div class="blog-head">
		<div class="left">
			<span class="kicker"><?php esc_html_e( 'Insights & Tutorials', 'lavtheme' ); ?></span>
			<h1><?php echo esc_html( $title ); ?></h1>
			<?php if ( '' !== trim( (string) $desc ) ) : ?><p><?php echo esc_html( $desc ); ?></p><?php endif; ?>
		</div>
		<div class="head-stats">
			<?php foreach ( $stats as $s ) : ?>
				<div class="hs"><b><?php echo esc_html( number_format_i18n( $s[0] ) ); ?></b><span><?php echo esc_html( $s[1] ); ?></span></div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/** Featured post block (latest or sticky). Shown only on the unfiltered index, page 1. */
function lavtheme_blog_featured_html() {
	if ( lavtheme_blog_has_active_filters() || lavtheme_blog_paged() > 1 ) {
		return '';
	}
	$on_index = ( is_home() && ! is_front_page() ) || lavtheme_is_blog_page_request();
	if ( ! $on_index ) {
		return ''; // featured only on the blog index, not term/search archives.
	}
	$id = lavtheme_blog_featured_id();
	if ( ! $id ) {
		return '';
	}
	$thumb  = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'large' ) : '';
	$author = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) );
	$excerpt = wp_trim_words( get_the_excerpt( $id ), 32, '…' );

	ob_start();
	?>
	<article class="featured glass">
		<a class="fimg" href="<?php echo esc_url( get_permalink( $id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( $thumb ) : ?><img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $id ) ); ?>" fetchpriority="high" decoding="async"><?php endif; ?>
		</a>
		<div class="fbody">
			<span class="fbadge"><?php esc_html_e( '★ Featured', 'lavtheme' ); ?></span>
			<h2><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></h2>
			<?php if ( $excerpt ) : ?><p class="fexc"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
			<div class="fmeta">
				<span class="who"><span class="av"><?php echo esc_html( lavtheme_blog_initial( $author ) ); ?></span> <?php echo esc_html( $author ); ?></span>
				<span>·</span><span><?php echo esc_html( get_the_date( '', $id ) ); ?></span>
				<span>·</span><span><?php printf( esc_html__( '%d min read', 'lavtheme' ), lavtheme_blog_read_time( $id ) ); ?></span>
			</div>
			<a class="fread" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php esc_html_e( 'Read article', 'lavtheme' ); ?> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></a>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/** Filter bar: category pills + dropdown, sort/date/author selects, count, search. */
function lavtheme_blog_filterbar_html() {
	$s      = lavtheme_blog_filter_state();
	$action = function_exists( 'lavtheme_blog_url' ) ? lavtheme_blog_url() : home_url( '/' );
	$cats   = get_categories( array( 'hide_empty' => true ) );
	$total  = isset( $GLOBALS['wp_query']->found_posts ) ? (int) $GLOBALS['wp_query']->found_posts : 0;

	// On a category/author archive the term is the context — reflect it in state.
	$active_cat = $s['bcat'];
	if ( is_category() ) {
		$qo = get_queried_object();
		if ( $qo && isset( $qo->slug ) ) {
			$active_cat = $qo->slug;
		}
	}

	$authors = get_users( array( 'has_published_posts' => array( 'post' ), 'orderby' => 'display_name' ) );

	$sorts = array(
		'latest'   => __( 'Latest', 'lavtheme' ),
		'oldest'   => __( 'Oldest', 'lavtheme' ),
		'popular'  => __( 'Most popular', 'lavtheme' ),
		'readtime' => __( 'Read time', 'lavtheme' ),
		'az'       => __( 'A → Z', 'lavtheme' ),
	);
	$dates = array(
		0   => __( 'All time', 'lavtheme' ),
		7   => __( 'Past 7 days', 'lavtheme' ),
		30  => __( 'Past 30 days', 'lavtheme' ),
		90  => __( 'Past 3 months', 'lavtheme' ),
		365 => __( 'Past year', 'lavtheme' ),
	);

	ob_start();
	?>
	<div class="filter-bar glass">
		<?php if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : ?>
			<div class="fb-cats" id="cats">
				<a class="cpill<?php echo '' === $active_cat ? ' active' : ''; ?>" href="<?php echo esc_url( lavtheme_blog_url_with( array() ) ); ?>"><?php esc_html_e( 'All', 'lavtheme' ); ?> <span class="n"><?php echo esc_html( number_format_i18n( array_sum( wp_list_pluck( $cats, 'count' ) ) ) ); ?></span></a>
				<?php foreach ( $cats as $c ) : ?>
					<a class="cpill<?php echo $active_cat === $c->slug ? ' active' : ''; ?>" href="<?php echo esc_url( lavtheme_blog_url_with( array( 'bcat' => $c->slug ) ) ); ?>" data-cat="<?php echo esc_attr( $c->slug ); ?>"><?php echo esc_html( $c->name ); ?> <span class="n"><?php echo esc_html( number_format_i18n( (int) $c->count ) ); ?></span></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<form class="fb-controls" id="lav-blog-form" method="get" action="<?php echo esc_url( $action ); ?>" role="search">
			<div class="fb-left">
				<?php if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : ?>
					<div class="ctrl"><span class="lbl"><?php esc_html_e( 'Category', 'lavtheme' ); ?></span>
						<select name="bcat" onchange="if(this.form){this.form.submit();}">
							<option value=""><?php esc_html_e( 'All categories', 'lavtheme' ); ?></option>
							<?php foreach ( $cats as $c ) : ?>
								<option value="<?php echo esc_attr( $c->slug ); ?>" <?php selected( $active_cat, $c->slug ); ?>><?php echo esc_html( $c->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
				<div class="ctrl"><span class="lbl"><?php esc_html_e( 'Sort by', 'lavtheme' ); ?></span>
					<select name="bsort" onchange="if(this.form){this.form.submit();}">
						<?php foreach ( $sorts as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['bsort'], $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?>
					</select>
				</div>
				<div class="ctrl"><span class="lbl"><?php esc_html_e( 'Date', 'lavtheme' ); ?></span>
					<select name="bdate" onchange="if(this.form){this.form.submit();}">
						<?php foreach ( $dates as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['bdate'], $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?>
					</select>
				</div>
				<?php if ( count( $authors ) > 1 ) : ?>
					<div class="ctrl"><span class="lbl"><?php esc_html_e( 'Author', 'lavtheme' ); ?></span>
						<select name="bauthor" onchange="if(this.form){this.form.submit();}">
							<option value=""><?php esc_html_e( 'All authors', 'lavtheme' ); ?></option>
							<?php foreach ( $authors as $a ) : ?><option value="<?php echo esc_attr( $a->user_nicename ); ?>" <?php selected( $s['bauthor'], $a->user_nicename ); ?>><?php echo esc_html( $a->display_name ); ?></option><?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
			</div>
			<div class="fb-right">
				<span class="result-n"><b><?php echo esc_html( number_format_i18n( $total ) ); ?></b> <?php esc_html_e( 'articles', 'lavtheme' ); ?></span>
				<label class="search-mini">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					<input type="search" name="bq" value="<?php echo esc_attr( $s['bq'] ); ?>" placeholder="<?php esc_attr_e( 'Search articles…', 'lavtheme' ); ?>" aria-label="<?php esc_attr_e( 'Search articles', 'lavtheme' ); ?>">
				</label>
			</div>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

/** One post card. */
function lavtheme_blog_card_html( $id ) {
	$id      = (int) $id;
	$link    = get_permalink( $id );
	$thumb   = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'lavtheme-card' ) : '';
	$cats    = get_the_category( $id );
	$cat     = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
	$author  = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) );
	$excerpt = wp_trim_words( get_the_excerpt( $id ), 22, '…' );

	ob_start();
	?>
	<article class="bpost glass">
		<a class="pthumb" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( $thumb ) : ?><img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $id ) ); ?>" loading="lazy"><?php endif; ?>
			<?php if ( $cat ) : ?><span class="ppill"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
		</a>
		<div class="pbody">
			<h3 class="ptitle"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></h3>
			<?php if ( $excerpt ) : ?><p class="pexc"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
			<div class="pfoot">
				<span class="who"><span class="av"><?php echo esc_html( lavtheme_blog_initial( $author ) ); ?></span> <?php echo esc_html( $author ); ?></span>
				<span class="read"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?php printf( esc_html__( '%d min', 'lavtheme' ), lavtheme_blog_read_time( $id ) ); ?></span>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/** Sidebar widgets: About, Popular Posts, Newsletter, Popular Tags. */
function lavtheme_blog_sidebar_html() {
	// Popular posts: view-count meta if present, else comment_count.
	$vk   = lavtheme_blog_views_meta_key();
	$args = array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true );
	if ( $vk ) {
		$args['meta_key'] = $vk;
		$args['orderby']  = 'meta_value_num';
	} else {
		$args['orderby'] = 'comment_count';
	}
	$args['order'] = 'DESC';
	$popular       = new WP_Query( $args );

	$tags  = get_tags( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hide_empty' => true ) );
	$about = wp_kses_post( apply_filters( 'lavtheme_blog_about_text', __( 'Practical, no-fluff writing on building modern digital products — design systems, performance, SEO and the business behind them.', 'lavtheme' ) ) );

	ob_start();
	?>
	<aside class="side">
		<div class="swidget about-w glass">
			<div class="wtitle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg> <?php esc_html_e( 'About the Blog', 'lavtheme' ); ?></div>
			<p><?php echo $about; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd. ?></p>
		</div>

		<?php if ( $popular->have_posts() ) : ?>
			<div class="swidget glass">
				<div class="wtitle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg> <?php esc_html_e( 'Popular Posts', 'lavtheme' ); ?></div>
				<div class="pop">
					<?php
					while ( $popular->have_posts() ) :
						$popular->the_post();
						$pthumb = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ) : '';
						?>
						<a class="pop-item" href="<?php the_permalink(); ?>">
							<span class="pi-img"><?php if ( $pthumb ) : ?><img src="<?php echo esc_url( $pthumb ); ?>" alt="" loading="lazy"><?php endif; ?></span>
							<span><span class="pi-t"><?php echo esc_html( get_the_title() ); ?></span><br><span class="pi-m"><?php echo esc_html( get_the_date() ); ?> · <?php printf( esc_html__( '%d min', 'lavtheme' ), lavtheme_blog_read_time( get_the_ID() ) ); ?></span></span>
						</a>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		<?php endif; ?>

		<div class="swidget nl glass">
			<div class="wtitle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="m4 6 8 6 8-6"/></svg> <?php esc_html_e( 'Newsletter', 'lavtheme' ); ?></div>
			<p><?php esc_html_e( 'Get new articles and updates in your inbox. No spam, unsubscribe anytime.', 'lavtheme' ); ?></p>
			<form class="nl-form" method="post" action="<?php echo esc_url( apply_filters( 'lavtheme_newsletter_action', '#' ) ); ?>" onsubmit="<?php echo esc_attr( apply_filters( 'lavtheme_newsletter_action', '#' ) ) === '#' ? 'return false' : ''; ?>">
				<input type="email" name="lav_news_email" placeholder="<?php esc_attr_e( 'you@email.com', 'lavtheme' ); ?>" aria-label="<?php esc_attr_e( 'Email', 'lavtheme' ); ?>">
				<button type="submit"><?php esc_html_e( 'Subscribe', 'lavtheme' ); ?></button>
			</form>
		</div>

		<?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
			<div class="swidget glass">
				<div class="wtitle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.6 13.4 12 22l-9-9V3h10z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg> <?php esc_html_e( 'Popular Tags', 'lavtheme' ); ?></div>
				<div class="tagcloud">
					<?php foreach ( $tags as $t ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $t ) ); ?>">#<?php echo esc_html( $t->name ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</aside>
	<?php
	return ob_get_clean();
}

/** Pagination (Blog-page-aware: ?bpg arg; archives use pretty /page/N/). */
function lavtheme_blog_pagination_html() {
	global $wp_query;
	$total = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 0;
	if ( $total < 2 ) {
		return '';
	}
	$current = lavtheme_blog_paged();
	$pargs   = array(
		'type'      => 'array',
		'mid_size'  => 1,
		'prev_text' => '‹',
		'next_text' => '›',
		'current'   => $current,
		'total'     => $total,
	);
	if ( function_exists( 'lavtheme_is_blog_page_request' ) && lavtheme_is_blog_page_request() ) {
		$big             = 999999999;
		$pargs['base']   = str_replace( $big, '%#%', esc_url_raw( add_query_arg( 'bpg', $big ) ) );
		$pargs['format'] = '';
	}
	$links = paginate_links( $pargs );
	if ( empty( $links ) ) {
		return '';
	}
	return '<nav class="pagination" aria-label="' . esc_attr__( 'Pagination', 'lavtheme' ) . '">' . implode( '', $links ) . '</nav>';
}
