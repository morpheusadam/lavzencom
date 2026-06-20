<?php
/**
 * Blog archive engine (blog.html design wired to real posts).
 *
 * Mirrors the shop architecture. Server-side query-string filtering applied to
 * the real main query via pre_get_posts (SEO/no-JS safe; JS only enhances). The
 * blog index renders on a dedicated "Blog" page (this site shows posts on the
 * front via front-page.php and has no posts page), plus the standard
 * category/tag/author/date/search archives — all with the same design.
 *
 * Params: bcat (category slug), bsort (latest|oldest|popular|readtime|az),
 * bdate (7|30|90|365), bauthor (author slug), bq (search), bpg (page paging).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/** Posts per blog page. */
function lavtheme_blog_per_page() {
	$n = absint( lavtheme_option( 'blog_per_page', 6 ) );
	return $n > 0 ? $n : 6;
}

/**
 * The blog index page id — the standard posts page if set, else the theme's
 * seeded "Blog" page. Filterable; never hardcoded.
 *
 * @return int
 */
function lavtheme_blog_page_id() {
	$id = (int) get_option( 'page_for_posts' );
	if ( ! $id ) {
		$id = (int) get_option( 'lavtheme_blog_page_id', 0 );
	}
	return (int) apply_filters( 'lavtheme_blog_page_id', $id );
}

/**
 * Is this the blog — posts index (not the front page), a post taxonomy/author/
 * date archive, search, or the dedicated Blog page? In QUERY context it is
 * archive/home/search only, so pre_get_posts never touches the Blog page query
 * (that page renders via a secondary query).
 *
 * @param WP_Query|null $query Optional query.
 * @return bool
 */
function lavtheme_is_blog( $query = null ) {
	if ( $query instanceof WP_Query ) {
		$is = ( $query->is_home() && ! $query->is_front_page() )
			|| $query->is_category() || $query->is_tag() || $query->is_author()
			|| $query->is_date() || $query->is_search();
	} else {
		$is = ( is_home() && ! is_front_page() )
			|| is_category() || is_tag() || is_author() || is_date() || is_search();
		if ( ! $is ) {
			$pid = lavtheme_blog_page_id();
			if ( $pid && is_page( $pid ) ) {
				$is = true;
			}
		}
	}
	return (bool) apply_filters( 'lavtheme_is_blog', $is, $query );
}

/** Estimated read time (minutes) from word count. */
function lavtheme_blog_read_time( $id ) {
	$content = get_post_field( 'post_content', $id );
	$words   = str_word_count( wp_strip_all_tags( strip_shortcodes( (string) $content ) ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/** The featured post id — first published sticky, else the latest post. */
function lavtheme_blog_featured_id() {
	$st = get_option( 'sticky_posts' );
	if ( is_array( $st ) ) {
		foreach ( $st as $sid ) {
			if ( 'publish' === get_post_status( $sid ) ) {
				return (int) $sid;
			}
		}
	}
	$latest = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids', 'ignore_sticky_posts' => true ) );
	return $latest ? (int) $latest[0] : 0;
}

/** Does a view-count plugin expose a per-post count meta? (filterable) */
function lavtheme_blog_views_meta_key() {
	$candidates = array( 'post_views_count', '_post_views', 'views', 'wpb_post_views_count', 'pvc_views' );
	foreach ( $candidates as $k ) {
		if ( metadata_exists( 'post', lavtheme_blog_featured_id(), $k ) ) {
			return $k;
		}
	}
	return (string) apply_filters( 'lavtheme_blog_views_meta_key', '' );
}

/** Current blog filter state from the request. */
function lavtheme_blog_filter_state() {
	return array(
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'bcat'    => isset( $_GET['bcat'] ) ? sanitize_title( wp_unslash( $_GET['bcat'] ) ) : '',
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'bsort'   => isset( $_GET['bsort'] ) ? sanitize_key( wp_unslash( $_GET['bsort'] ) ) : 'latest',
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'bdate'   => isset( $_GET['bdate'] ) ? absint( $_GET['bdate'] ) : 0,
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'bauthor' => isset( $_GET['bauthor'] ) ? sanitize_title( wp_unslash( $_GET['bauthor'] ) ) : '',
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'bq'      => isset( $_GET['bq'] ) ? sanitize_text_field( wp_unslash( $_GET['bq'] ) ) : '',
	);
}

/** Are any blog filters active (used to hide the featured block when filtering)? */
function lavtheme_blog_has_active_filters() {
	$s = lavtheme_blog_filter_state();
	return ( '' !== $s['bcat'] || $s['bdate'] || '' !== $s['bauthor'] || '' !== $s['bq'] || ( '' !== $s['bsort'] && 'latest' !== $s['bsort'] ) );
}

/**
 * Build the blog filter/sort query vars from the request.
 *
 * @param bool $apply_term Apply category/author terms (true on the index; false
 *                         on a term archive where the term is already the context).
 * @return array
 */
function lavtheme_blog_filter_vars( $apply_term = true ) {
	$s    = lavtheme_blog_filter_state();
	$vars = array();

	if ( '' !== $s['bq'] ) {
		$vars['s'] = $s['bq'];
	}
	if ( $apply_term && '' !== $s['bcat'] ) {
		$vars['category_name'] = $s['bcat'];
	}
	if ( $apply_term && '' !== $s['bauthor'] ) {
		$vars['author_name'] = $s['bauthor'];
	}
	if ( $s['bdate'] ) {
		$vars['date_query'] = array( array( 'after' => $s['bdate'] . ' days ago' ) );
	}

	switch ( $s['bsort'] ) {
		case 'oldest':
			$vars['orderby'] = 'date';
			$vars['order']   = 'ASC';
			break;
		case 'popular':
			$vk = lavtheme_blog_views_meta_key();
			if ( $vk ) {
				$vars['meta_key'] = $vk;
				$vars['orderby']  = 'meta_value_num';
			} else {
				$vars['orderby'] = 'comment_count'; // graceful proxy when no view counter.
			}
			$vars['order'] = 'DESC';
			break;
		case 'az':
			$vars['orderby'] = 'title';
			$vars['order']   = 'ASC';
			break;
		case 'readtime':
			$vars['orderby']                 = 'date';
			$vars['order']                   = 'DESC';
			$vars['lavtheme_readtime_sort']  = 1; // handled in posts_clauses.
			break;
		case 'latest':
		default:
			$vars['orderby'] = '' !== $s['bq'] ? 'relevance' : 'date';
			$vars['order']   = 'DESC';
			break;
	}
	return $vars;
}

/** Order by content length for the "Read time" sort. */
function lavtheme_blog_readtime_clauses( $clauses, $query ) {
	if ( $query->get( 'lavtheme_readtime_sort' ) ) {
		global $wpdb;
		$clauses['orderby'] = "CHAR_LENGTH({$wpdb->posts}.post_content) DESC";
	}
	return $clauses;
}
add_filter( 'posts_clauses', 'lavtheme_blog_readtime_clauses', 10, 2 );

/** Apply the blog filters/sort to the real main query (archives + posts index). */
function lavtheme_blog_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! lavtheme_is_blog( $query ) ) {
		return;
	}
	$query->set( 'posts_per_page', lavtheme_blog_per_page() );
	$query->set( 'ignore_sticky_posts', true );

	$is_index = $query->is_home();
	foreach ( lavtheme_blog_filter_vars( $is_index ) as $k => $v ) {
		$query->set( $k, $v );
	}

	// Exclude the featured post from the grid on the unfiltered index, page 1.
	if ( $is_index && ! $query->get( 'paged' ) && ! lavtheme_blog_has_active_filters() ) {
		$fid = lavtheme_blog_featured_id();
		if ( $fid ) {
			$query->set( 'post__not_in', array( $fid ) );
		}
	}
}
add_action( 'pre_get_posts', 'lavtheme_blog_pre_get_posts' );

/** Resolve the blog page number (archive paged, or ?bpg on the Blog page). */
function lavtheme_blog_paged() {
	$p = (int) get_query_var( 'paged' );
	if ( ! $p ) {
		$p = (int) get_query_var( 'page' );
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $p && isset( $_GET['bpg'] ) ) {
		$p = (int) $_GET['bpg'];
	}
	return max( 1, $p );
}

/** The secondary WP_Query that powers the dedicated Blog page. */
function lavtheme_blog_page_query() {
	$paged = lavtheme_blog_paged();
	$args  = array_merge(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => lavtheme_blog_per_page(),
			'paged'               => $paged,
			'ignore_sticky_posts' => true,
		),
		lavtheme_blog_filter_vars( true )
	);
	if ( $paged < 2 && ! lavtheme_blog_has_active_filters() ) {
		$fid = lavtheme_blog_featured_id();
		if ( $fid ) {
			$args['post__not_in'] = array( $fid );
		}
	}
	return new WP_Query( $args );
}

/** True while the dedicated Blog page is rendering (pagination context). */
function lavtheme_is_blog_page_request() {
	return ! empty( $GLOBALS['lavtheme_blog_page_active'] );
}

/** Render the shared blog layout (Phase B swaps this for the Code Studio override). */
function lavtheme_blog_render() {
	if ( function_exists( 'lavtheme_cs_blog_render' ) ) {
		lavtheme_cs_blog_render();
	} else {
		lavtheme_part( 'blog' );
	}
}

/** Render the blog on the dedicated Blog page: swap in a posts query, render, restore. */
function lavtheme_blog_render_page() {
	global $wp_query, $post;
	$orig_query = $wp_query;
	$orig_post  = $post;

	$wp_query = lavtheme_blog_page_query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['lavtheme_blog_page_active'] = true;

	lavtheme_blog_render();

	$GLOBALS['lavtheme_blog_page_active'] = false;
	wp_reset_postdata();
	$wp_query = $orig_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$post     = $orig_post;  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}

/** Route ONLY the dedicated Blog page through the blog template. */
function lavtheme_blog_page_template( $template ) {
	if ( is_admin() ) {
		return $template;
	}
	$pid = lavtheme_blog_page_id();
	// Only when it's a real PAGE acting as the blog (not the standard posts page,
	// which already uses home.php).
	if ( $pid && is_page( $pid ) && (int) get_option( 'page_for_posts' ) !== $pid ) {
		$custom = get_theme_file_path( 'template-parts/blog-page-template.php' );
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'lavtheme_blog_page_template', 98 );

/** Run-once: create a "Blog" page if there's no posts page (additive, reversible). */
function lavtheme_blog_seed_page() {
	if ( get_option( 'lavtheme_blog_seeded' ) ) {
		return;
	}
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	update_option( 'lavtheme_blog_seeded', 1 );

	if ( (int) get_option( 'page_for_posts' ) ) {
		return; // a real posts page exists — use it.
	}
	$existing = get_page_by_path( 'blog' );
	if ( $existing ) {
		update_option( 'lavtheme_blog_page_id', (int) $existing->ID );
		return;
	}
	$id = wp_insert_post(
		array(
			'post_title'   => __( 'Blog', 'lavtheme' ),
			'post_name'    => 'blog',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_option( 'lavtheme_blog_page_id', (int) $id );
	}
}
add_action( 'admin_init', 'lavtheme_blog_seed_page' );
