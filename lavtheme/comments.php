<?php
/**
 * Comments template — Liquid-Glass comment system.
 *
 * Header (count + sort) · compose form (avatar + auto-grow textarea + actions) ·
 * threaded list (lavtheme_comment_cb) · pagination · empty state. Built on the
 * real WordPress comment API. Loaded by comments_template() from the single
 * article body.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}

$lav_sort   = isset( $_GET['csort'] ) ? sanitize_key( wp_unslash( $_GET['csort'] ) ) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lav_cnum   = (int) get_comments_number();
$lav_curl   = get_permalink();
$lav_user   = wp_get_current_user();
$lav_avatar = get_avatar( $lav_user->exists() ? $lav_user->ID : 0, 44, '', '', array( 'class' => 'cm-av-img' ) );
?>
<div class="lav-comments">

	<div class="cm-head">
		<h2 class="cm-title">
			<?php
			printf(
				/* translators: %s: comment count. */
				esc_html( _n( '%s Comment', '%s Comments', $lav_cnum, 'lavtheme' ) ),
				'<span class="cm-count">' . esc_html( number_format_i18n( $lav_cnum ) ) . '</span>'
			);
			?>
		</h2>
		<?php if ( have_comments() ) : ?>
			<label class="cm-sort">
				<span class="screen-reader-text"><?php esc_html_e( 'Sort comments', 'lavtheme' ); ?></span>
				<select class="cm-sort-sel" aria-label="<?php esc_attr_e( 'Sort comments', 'lavtheme' ); ?>">
					<option value="newest" <?php selected( $lav_sort, 'newest' ); ?>><?php esc_html_e( 'Newest', 'lavtheme' ); ?></option>
					<option value="oldest" <?php selected( $lav_sort, 'oldest' ); ?>><?php esc_html_e( 'Oldest', 'lavtheme' ); ?></option>
					<option value="top" <?php selected( $lav_sort, 'top' ); ?>><?php esc_html_e( 'Top', 'lavtheme' ); ?></option>
				</select>
				<svg class="cm-sort-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
			</label>
		<?php endif; ?>
	</div>

	<?php if ( comments_open() ) : ?>
		<div class="cm-compose">
			<div class="cm-av"><?php echo $lav_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?></div>
			<?php
			comment_form(
				array(
					'class_form'           => 'cm-form',
					'title_reply'          => '',
					'title_reply_before'   => '',
					'title_reply_after'    => '',
					'comment_notes_before' => '',
					'comment_notes_after'  => '',
					'logged_in_as'         => '',
					'comment_field'        => '<div class="cm-field"><label class="screen-reader-text" for="comment">' . esc_html__( 'Comment', 'lavtheme' ) . '</label><textarea id="comment" name="comment" class="cm-textarea" rows="2" required placeholder="' . esc_attr__( 'Add to the conversation…', 'lavtheme' ) . '"></textarea></div>',
					'submit_button'        => '<button type="submit" class="btn btn-primary cm-submit" id="%2$s" name="%1$s">%4$s</button>',
					'submit_field'         => '<div class="cm-formactions"><span class="cm-tools">'
						. '<button type="button" class="cm-tool" aria-label="' . esc_attr__( 'Add emoji', 'lavtheme' ) . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg></button>'
						. '<button type="button" class="cm-tool" aria-label="' . esc_attr__( 'Attach file', 'lavtheme' ) . '"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 11-8.6 8.6a5 5 0 0 1-7-7L13 4a3.5 3.5 0 0 1 5 5l-8.6 8.6a2 2 0 0 1-3-3L14 6"/></svg></button>'
						. '</span>%1$s %2$s</div>',
					'label_submit'         => __( 'Post comment', 'lavtheme' ),
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( have_comments() ) : ?>
		<ol class="cm-list">
			<?php
			wp_list_comments(
				array(
					'callback'     => 'lavtheme_comment_cb',
					'end-callback' => 'lavtheme_comment_end_cb',
					'style'        => 'ol',
					'avatar_size'  => 44,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text'          => '<span aria-hidden="true">‹</span><span class="screen-reader-text">' . esc_html__( 'Older comments', 'lavtheme' ) . '</span>',
				'next_text'          => '<span aria-hidden="true">›</span><span class="screen-reader-text">' . esc_html__( 'Newer comments', 'lavtheme' ) . '</span>',
				'screen_reader_text' => __( 'Comments navigation', 'lavtheme' ),
				'class'              => 'cm-pagination',
			)
		);
		?>

	<?php elseif ( comments_open() ) : ?>
		<div class="cm-empty">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
			<p><?php esc_html_e( 'No comments yet — start the conversation.', 'lavtheme' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! comments_open() && $lav_cnum > 0 ) : ?>
		<p class="cm-closed"><?php esc_html_e( 'Comments are closed.', 'lavtheme' ); ?></p>
	<?php endif; ?>
</div>
