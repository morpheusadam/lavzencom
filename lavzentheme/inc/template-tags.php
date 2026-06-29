<?php
/**
 * Template tags — thin procedural wrappers used inside template files.
 *
 * Templates stay readable (lavzen_topnav()) while the logic lives in OOP services.
 * This file is procedural by design (WordPress template-tag convention) and is
 * required by the Theme bootstrap; it is intentionally outside the autoloaded
 * Lavzen\ namespace.
 *
 * @package Lavzen
 */

use Lavzen\Core\Navigation;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lavzen_topnav' ) ) {
	/**
	 * Echo the desktop topnav.
	 */
	function lavzen_topnav(): void {
		Navigation::instance()->topnav();
	}
}

if ( ! function_exists( 'lavzen_account_popover' ) ) {
	/**
	 * Return the account-popover markup.
	 */
	function lavzen_account_popover(): string {
		return Navigation::instance()->account_popover();
	}
}

if ( ! function_exists( 'lavzen_shop_categories_nav' ) ) {
	/**
	 * Return the shop-categories menu markup.
	 *
	 * @param int $limit Max terms (0 = all).
	 */
	function lavzen_shop_categories_nav( int $limit = 0 ): string {
		return Navigation::instance()->shop_categories_nav( $limit );
	}
}

if ( ! function_exists( 'lavzen_blog_url' ) ) {
	/**
	 * The blog landing URL.
	 */
	function lavzen_blog_url(): string {
		return Navigation::instance()->blog_url();
	}
}

if ( ! function_exists( 'lavzen_read_time' ) ) {
	/**
	 * Estimated read time (minutes) from word count.
	 *
	 * @param int $id Post id.
	 */
	function lavzen_read_time( int $id ): int {
		$words = str_word_count( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $id ) ) ) );
		return max( 1, (int) ceil( $words / 200 ) );
	}
}

if ( ! function_exists( 'lavzen_comment_ago' ) ) {
	/**
	 * Relative "x ago" label for a comment.
	 *
	 * @param \WP_Comment $comment Comment.
	 */
	function lavzen_comment_ago( $comment ): string {
		$ts = get_comment_time( 'U', true, false );
		/* translators: %s: human time difference, e.g. "2 hours". */
		return sprintf( __( '%s ago', 'lavzentheme' ), human_time_diff( (int) $ts, current_time( 'timestamp' ) ) );
	}
}

if ( ! function_exists( 'lavzen_comment_cb' ) ) {
	/**
	 * Render one comment (opening markup; WordPress appends children + closes).
	 *
	 * @param \WP_Comment $comment Comment object.
	 * @param array       $args    wp_list_comments args.
	 * @param int         $depth   Nesting depth.
	 */
	function lavzen_comment_cb( $comment, $args, $depth ): void {
		$cid       = $comment->comment_ID;
		$likes     = (int) get_comment_meta( $cid, 'lavtheme_comment_likes', true ); // legacy key (data continuity).
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
						<?php if ( $is_author ) : ?><span class="cm-badge"><?php esc_html_e( 'Author', 'lavzentheme' ); ?></span><?php endif; ?>
						<time class="cm-time" datetime="<?php echo esc_attr( get_comment_date( 'c', $comment ) ); ?>" itemprop="dateCreated"><?php echo esc_html( lavzen_comment_ago( $comment ) ); ?></time>
					</div>
					<?php if ( '0' === $comment->comment_approved ) : ?>
						<p class="cm-hold"><?php esc_html_e( 'Your comment is awaiting moderation.', 'lavzentheme' ); ?></p>
					<?php endif; ?>
					<div class="cm-body" itemprop="text"><?php comment_text(); ?></div>
					<div class="cm-actions">
						<button type="button" class="cm-like" data-comment="<?php echo esc_attr( $cid ); ?>" aria-pressed="false" aria-label="<?php esc_attr_e( 'Like this comment', 'lavzentheme' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 11v9H4a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1zM7 11l4-8a2.5 2.5 0 0 1 2.5 3.5L12 11h6.5a2 2 0 0 1 2 2.4l-1.4 6A2 2 0 0 1 17 21H7"/></svg>
							<span class="cm-like-count"><?php echo esc_html( number_format_i18n( $likes ) ); ?></span>
						</button>
						<?php
						comment_reply_link(
							array_merge(
								$args,
								array(
									'add_below'  => 'comment',
									'depth'      => $depth,
									'max_depth'  => $args['max_depth'],
									'reply_text' => __( 'Reply', 'lavzentheme' ),
									'before'     => '<span class="cm-reply">',
									'after'      => '</span>',
								)
							)
						);
						?>
					</div>
				</div>
			</article>
		<?php
		// No closing tag — wp_list_comments() adds children, then the end-callback closes.
	}
}

if ( ! function_exists( 'lavzen_comment_end_cb' ) ) {
	/**
	 * Close one comment's markup (paired with lavzen_comment_cb).
	 *
	 * @param \WP_Comment $comment Comment.
	 * @param array       $args    Args.
	 * @param int         $depth   Depth.
	 */
	function lavzen_comment_end_cb( $comment, $args, $depth ): void {
		$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
		echo '</' . esc_attr( $tag ) . ">\n";
	}
}
