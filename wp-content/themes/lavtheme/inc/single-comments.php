<?php
/**
 * Single post — comments rendering + interactions.
 *
 * A Liquid-Glass comment system on top of the real WordPress comment API:
 *   - lavtheme_comment_cb()  : renders one comment (schema.org/Comment) with
 *                              avatar, author badge, relative time, body, and an
 *                              action row (like + reply + overflow menu).
 *   - sort (Newest/Oldest/Top) via ?csort= → comments_template_query_args.
 *   - AJAX like toggles for posts (rail) and comments, stored in meta and
 *     guarded by a per-browser cookie. Functional, progressive, nonce-checked.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* ============================ sort ============================ */

/**
 * Apply the ?csort= sort to the comment query (Newest / Oldest / Top by likes).
 *
 * @param array $args Comment query args.
 * @return array
 */
function lavtheme_comments_sort_args( $args ) {
	$sort = isset( $_GET['csort'] ) ? sanitize_key( wp_unslash( $_GET['csort'] ) ) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	switch ( $sort ) {
		case 'oldest':
			$args['order'] = 'ASC';
			break;
		case 'top':
			// OR EXISTS / NOT EXISTS forces a LEFT JOIN so comments with no likes
			// yet are still returned (missing meta sorts to the bottom), not hidden.
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
add_filter( 'comments_template_query_args', 'lavtheme_comments_sort_args' );

/* ============================ render ============================ */

/**
 * Relative "x ago" label for a comment.
 *
 * @param WP_Comment $comment Comment.
 * @return string
 */
function lavtheme_comment_ago( $comment ) {
	$ts = get_comment_time( 'U', true, false );
	/* translators: %s: human time difference, e.g. "2 hours". */
	return sprintf( __( '%s ago', 'lavtheme' ), human_time_diff( (int) $ts, current_time( 'timestamp' ) ) );
}

/**
 * Render one comment (opening markup; WordPress appends children + closes <li>).
 *
 * @param WP_Comment $comment Comment object.
 * @param array      $args    wp_list_comments args.
 * @param int        $depth   Nesting depth.
 */
function lavtheme_comment_cb( $comment, $args, $depth ) {
	$cid       = $comment->comment_ID;
	$likes     = (int) get_comment_meta( $cid, 'lavtheme_comment_likes', true );
	$post_aid  = (int) get_post_field( 'post_author', $comment->comment_post_ID );
	$is_author = ( $comment->user_id && (int) $comment->user_id === $post_aid );
	$tag       = ( 'div' === $args['style'] ) ? 'div' : 'li';
	?>
	<<?php echo esc_attr( $tag ); ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( 'cm-item', $comment ); ?>>
		<article class="cm-card" itemprop="comment" itemscope itemtype="https://schema.org/Comment">
			<div class="cm-av"><?php echo get_avatar( $comment, 44, '', '', array( 'class' => 'cm-av-img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?></div>
			<div class="cm-main">
				<div class="cm-meta">
					<span class="cm-author" itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name"><?php echo esc_html( get_comment_author( $comment ) ); ?></span></span>
					<?php if ( $is_author ) : ?><span class="cm-badge"><?php esc_html_e( 'Author', 'lavtheme' ); ?></span><?php endif; ?>
					<time class="cm-time" datetime="<?php echo esc_attr( get_comment_date( 'c', $comment ) ); ?>" itemprop="dateCreated"><?php echo esc_html( lavtheme_comment_ago( $comment ) ); ?></time>
				</div>

				<?php if ( '0' === $comment->comment_approved ) : ?>
					<p class="cm-hold"><?php esc_html_e( 'Your comment is awaiting moderation.', 'lavtheme' ); ?></p>
				<?php endif; ?>

				<div class="cm-body" itemprop="text"><?php comment_text(); ?></div>

				<div class="cm-actions">
					<button type="button" class="cm-like" data-comment="<?php echo esc_attr( $cid ); ?>" aria-pressed="false" aria-label="<?php esc_attr_e( 'Like this comment', 'lavtheme' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 11v9H4a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1zM7 11l4-8a2.5 2.5 0 0 1 2.5 3.5L12 11h6.5a2 2 0 0 1 2 2.4l-1.4 6A2 2 0 0 1 17 21H7"/></svg>
						<span class="cm-like-count"><?php echo esc_html( number_format_i18n( $likes ) ); ?></span>
					</button>
					<?php
					comment_reply_link(
						array_merge(
							$args,
							array(
								'add_below' => 'comment',
								'depth'     => $depth,
								'max_depth' => $args['max_depth'],
								'reply_text' => __( 'Reply', 'lavtheme' ),
								'before'    => '<span class="cm-reply">',
								'after'     => '</span>',
							)
						)
					);
					?>
					<span class="cm-more">
						<button type="button" class="cm-more-btn" aria-label="<?php esc_attr_e( 'More options', 'lavtheme' ); ?>" aria-expanded="false">
							<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
						</button>
						<span class="cm-menu" role="menu" hidden>
							<a class="cm-menu-i" role="menuitem" href="<?php echo esc_url( get_comment_link( $comment ) ); ?>"><?php esc_html_e( 'Permalink', 'lavtheme' ); ?></a>
							<a class="cm-menu-i" role="menuitem" href="<?php echo esc_url( wp_login_url( get_comment_link( $comment ) ) ); ?>"><?php esc_html_e( 'Report', 'lavtheme' ); ?></a>
						</span>
					</span>
				</div>
			</div>
		</article>
	<?php
	// NB: no closing tag — wp_list_comments() adds children then calls end-callback.
}

/**
 * Close one comment's markup (paired with lavtheme_comment_cb).
 *
 * @param WP_Comment $comment Comment.
 * @param array      $args    Args.
 * @param int        $depth   Depth.
 */
function lavtheme_comment_end_cb( $comment, $args, $depth ) {
	$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
	echo '</' . esc_attr( $tag ) . ">\n";
}

/* ============================ AJAX likes ============================ */

/**
 * Toggle a like on a post or comment (cookie-guarded, nonce-checked).
 */
function lavtheme_ajax_like() {
	check_ajax_referer( 'lavtheme_like', 'nonce' );

	$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$id   = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
	if ( ! $id || ! in_array( $type, array( 'post', 'comment' ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Bad request.', 'lavtheme' ) ), 400 );
	}

	if ( 'post' === $type ) {
		if ( 'publish' !== get_post_status( $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Not found.', 'lavtheme' ) ), 404 );
		}
		$meta_key = 'lavtheme_post_likes';
		$cookie   = 'lav_liked_p_' . $id;
		$current  = (int) get_post_meta( $id, $meta_key, true );
	} else {
		$c = get_comment( $id );
		if ( ! $c ) {
			wp_send_json_error( array( 'message' => __( 'Not found.', 'lavtheme' ) ), 404 );
		}
		$meta_key = 'lavtheme_comment_likes';
		$cookie   = 'lav_liked_c_' . $id;
		$current  = (int) get_comment_meta( $id, $meta_key, true );
	}

	$liked = isset( $_COOKIE[ $cookie ] );
	if ( $liked ) {
		$current = max( 0, $current - 1 );
		$state   = false;
		setcookie( $cookie, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
		unset( $_COOKIE[ $cookie ] );
	} else {
		$current = $current + 1;
		$state   = true;
		setcookie( $cookie, '1', time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
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
add_action( 'wp_ajax_lavtheme_like', 'lavtheme_ajax_like' );
add_action( 'wp_ajax_nopriv_lavtheme_like', 'lavtheme_ajax_like' );

// The like AJAX config (window.LavSingle) is printed in the head by the Code
// Studio "Single Post" context — lavtheme_cs_single_config_head().
