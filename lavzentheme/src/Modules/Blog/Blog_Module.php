<?php
/**
 * Blog module — query filtering/sort, comment sort, and like AJAX.
 *
 * Ports the design-independent engine from the legacy inc/blog.php +
 * inc/single-comments.php: server-side query-string filtering of the main query
 * (bcat/bsort/bdate/bauthor/bq, SEO/no-JS safe), the "read time" sort, comment
 * sort (?csort=), and the cookie-guarded like toggle for posts/comments.
 *
 * The rich blog INDEX design (featured block + filter UI in template-parts/blog.php)
 * and the dedicated Blog-page routing are design-coupled and land with the asset
 * phase; the featured-post exclusion is therefore intentionally NOT applied here
 * (it would hide the latest post without the featured block to show it).
 * Comment rendering callbacks + read-time live in inc/template-tags.php.
 *
 * Like meta keys are kept as the legacy `lavtheme_post_likes` /
 * `lavtheme_comment_likes` for data continuity across cutover.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Blog;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Blog_Module extends Abstract_Module {

	public function id(): string {
		return 'blog';
	}

	public function boot(): void {
		add_filter( 'comments_template_query_args', array( $this, 'comments_sort_args' ) );
		add_action( 'wp_ajax_lavzen_like', array( $this, 'ajax_like' ) );
		add_action( 'wp_ajax_nopriv_lavzen_like', array( $this, 'ajax_like' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_filter( 'posts_clauses', array( $this, 'readtime_clauses' ), 10, 2 );
	}

	private function per_page(): int {
		$n = (int) apply_filters( 'lavzen/blog/per_page', 6 );
		return $n > 0 ? $n : 6;
	}

	/**
	 * Is this a post-archive/search context (query-safe; excludes the front page)?
	 *
	 * @param \WP_Query|null $query Optional query.
	 */
	private function is_blog( $query = null ): bool {
		if ( $query instanceof \WP_Query ) {
			$is = ( $query->is_home() && ! $query->is_front_page() )
				|| $query->is_category() || $query->is_tag() || $query->is_author()
				|| $query->is_date() || $query->is_search();
		} else {
			$is = ( is_home() && ! is_front_page() )
				|| is_category() || is_tag() || is_author() || is_date() || is_search();
		}
		return (bool) apply_filters( 'lavzen/blog/is_blog', $is, $query );
	}

	/** A view-count plugin's per-post meta key, if any. */
	private function views_meta_key(): string {
		$latest = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids', 'ignore_sticky_posts' => true ) );
		$pid    = $latest ? (int) $latest[0] : 0;
		foreach ( array( 'post_views_count', '_post_views', 'views', 'wpb_post_views_count', 'pvc_views' ) as $k ) {
			if ( $pid && metadata_exists( 'post', $pid, $k ) ) {
				return $k;
			}
		}
		return (string) apply_filters( 'lavzen/blog/views_meta_key', '' );
	}

	/** Current filter state from the request. */
	private function filter_state(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return array(
			'bcat'    => isset( $_GET['bcat'] ) ? sanitize_title( wp_unslash( $_GET['bcat'] ) ) : '',
			'bsort'   => isset( $_GET['bsort'] ) ? sanitize_key( wp_unslash( $_GET['bsort'] ) ) : 'latest',
			'bdate'   => isset( $_GET['bdate'] ) ? absint( $_GET['bdate'] ) : 0,
			'bauthor' => isset( $_GET['bauthor'] ) ? sanitize_title( wp_unslash( $_GET['bauthor'] ) ) : '',
			'bq'      => isset( $_GET['bq'] ) ? sanitize_text_field( wp_unslash( $_GET['bq'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/** Build the query vars from the filter state. */
	private function filter_vars( bool $apply_term = true ): array {
		$s    = $this->filter_state();
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
				$vk = $this->views_meta_key();
				if ( $vk ) {
					$vars['meta_key'] = $vk;
					$vars['orderby']  = 'meta_value_num';
				} else {
					$vars['orderby'] = 'comment_count';
				}
				$vars['order'] = 'DESC';
				break;
			case 'az':
				$vars['orderby'] = 'title';
				$vars['order']   = 'ASC';
				break;
			case 'readtime':
				$vars['orderby']                = 'date';
				$vars['order']                  = 'DESC';
				$vars['lavzen_readtime_sort']   = 1;
				break;
			case 'latest':
			default:
				$vars['orderby'] = '' !== $s['bq'] ? 'relevance' : 'date';
				$vars['order']   = 'DESC';
				break;
		}
		return $vars;
	}

	/**
	 * Order by content length for the "Read time" sort.
	 *
	 * @param array     $clauses Query clauses.
	 * @param \WP_Query $query   Query.
	 * @return array
	 */
	public function readtime_clauses( $clauses, $query ) {
		if ( $query->get( 'lavzen_readtime_sort' ) ) {
			global $wpdb;
			$clauses['orderby'] = "CHAR_LENGTH({$wpdb->posts}.post_content) DESC";
		}
		return $clauses;
	}

	/**
	 * Apply filters/sort to the real main query for post archives.
	 *
	 * @param \WP_Query $query Query.
	 */
	public function pre_get_posts( $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $this->is_blog( $query ) ) {
			return;
		}
		$query->set( 'posts_per_page', $this->per_page() );
		$query->set( 'ignore_sticky_posts', true );
		foreach ( $this->filter_vars( $query->is_home() ) as $k => $v ) {
			$query->set( $k, $v );
		}
	}

	/**
	 * Apply the ?csort= sort to the comment query.
	 *
	 * @param array $args Comment query args.
	 * @return array
	 */
	public function comments_sort_args( $args ) {
		$sort = isset( $_GET['csort'] ) ? sanitize_key( wp_unslash( $_GET['csort'] ) ) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		switch ( $sort ) {
			case 'oldest':
				$args['order'] = 'ASC';
				break;
			case 'top':
				$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array( 'key' => 'lavtheme_comment_likes', 'compare' => 'EXISTS' ),
					array( 'key' => 'lavtheme_comment_likes', 'compare' => 'NOT EXISTS' ),
				);
				$args['orderby'] = 'meta_value_num';
				$args['order']   = 'DESC';
				break;
			case 'newest':
			default:
				$args['order'] = 'DESC';
				break;
		}
		return $args;
	}

	/**
	 * Toggle a like on a post or comment (cookie-guarded, nonce-checked).
	 */
	public function ajax_like(): void {
		check_ajax_referer( 'lavzen_like', 'nonce' );

		$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$id   = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id || ! in_array( $type, array( 'post', 'comment' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Bad request.', 'lavzentheme' ) ), 400 );
		}

		if ( 'post' === $type ) {
			if ( 'publish' !== get_post_status( $id ) ) {
				wp_send_json_error( array( 'message' => __( 'Not found.', 'lavzentheme' ) ), 404 );
			}
			$meta_key = 'lavtheme_post_likes';
			$cookie   = 'lav_liked_p_' . $id;
			$current  = (int) get_post_meta( $id, $meta_key, true );
		} else {
			if ( ! get_comment( $id ) ) {
				wp_send_json_error( array( 'message' => __( 'Not found.', 'lavzentheme' ) ), 404 );
			}
			$meta_key = 'lavtheme_comment_likes';
			$cookie   = 'lav_liked_c_' . $id;
			$current  = (int) get_comment_meta( $id, $meta_key, true );
		}

		$path = COOKIEPATH ? COOKIEPATH : '/';
		if ( isset( $_COOKIE[ $cookie ] ) ) {
			$current = max( 0, $current - 1 );
			$state   = false;
			setcookie( $cookie, '', time() - 3600, $path, COOKIE_DOMAIN );
			unset( $_COOKIE[ $cookie ] );
		} else {
			$current = $current + 1;
			$state   = true;
			setcookie( $cookie, '1', time() + YEAR_IN_SECONDS, $path, COOKIE_DOMAIN );
			$_COOKIE[ $cookie ] = '1';
		}

		if ( 'post' === $type ) {
			update_post_meta( $id, $meta_key, $current );
		} else {
			update_comment_meta( $id, $meta_key, $current );
		}

		wp_send_json_success(
			array(
				'count'  => $current,
				'countF' => number_format_i18n( $current ),
				'liked'  => $state,
			)
		);
	}
}
