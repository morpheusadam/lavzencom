<?php
/**
 * Comments — glass comment system (header · compose · threaded list · pagination).
 *
 * Built on the real WordPress comment API. Loaded by comments_template() from the
 * single article body. The custom comment-row callback (lavzen_comment_cb) ships
 * with the Blog module (Phase 4); until then WordPress's default walker is used.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}

$lav_sort   = isset( $_GET['csort'] ) ? sanitize_key( wp_unslash( $_GET['csort'] ) ) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lav_cnum   = (int) get_comments_number();
$lav_user   = wp_get_current_user();
$lav_avatar = get_avatar( $lav_user->exists() ? $lav_user->ID : 0, 44, '', '', array( 'class' => 'cm-av-img' ) );
$lav_cb     = function_exists( 'lavzen_comment_cb' ) ? 'lavzen_comment_cb' : null;
$lav_end_cb = ( $lav_cb && function_exists( 'lavzen_comment_end_cb' ) ) ? 'lavzen_comment_end_cb' : null;
?>
<div class="lav-comments">

	<div class="cm-head">
		<h2 class="cm-title">
			<?php
			printf(
				/* translators: %s: comment count. */
				esc_html( _n( '%s Comment', '%s Comments', $lav_cnum, 'lavzentheme' ) ),
				'<span class="cm-count">' . esc_html( number_format_i18n( $lav_cnum ) ) . '</span>'
			);
			?>
		</h2>
		<?php if ( have_comments() ) : ?>
			<label class="cm-sort">
				<span class="screen-reader-text"><?php esc_html_e( 'Sort comments', 'lavzentheme' ); ?></span>
				<select class="cm-sort-sel" aria-label="<?php esc_attr_e( 'Sort comments', 'lavzentheme' ); ?>">
					<option value="newest" <?php selected( $lav_sort, 'newest' ); ?>><?php esc_html_e( 'Newest', 'lavzentheme' ); ?></option>
					<option value="oldest" <?php selected( $lav_sort, 'oldest' ); ?>><?php esc_html_e( 'Oldest', 'lavzentheme' ); ?></option>
					<option value="top" <?php selected( $lav_sort, 'top' ); ?>><?php esc_html_e( 'Top', 'lavzentheme' ); ?></option>
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
					'comment_field'        => '<div class="cm-field"><label class="screen-reader-text" for="comment">' . esc_html__( 'Comment', 'lavzentheme' ) . '</label><textarea id="comment" name="comment" class="cm-textarea" rows="2" required placeholder="' . esc_attr__( 'Add to the conversation…', 'lavzentheme' ) . '"></textarea></div>',
					'submit_button'        => '<button type="submit" class="btn btn-primary cm-submit" id="%2$s" name="%1$s">%4$s</button>',
					'label_submit'         => __( 'Post comment', 'lavzentheme' ),
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
					'callback'     => $lav_cb,
					'end-callback' => $lav_end_cb,
					'style'        => 'ol',
					'avatar_size'  => 44,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text'          => '<span aria-hidden="true">‹</span><span class="screen-reader-text">' . esc_html__( 'Older comments', 'lavzentheme' ) . '</span>',
				'next_text'          => '<span aria-hidden="true">›</span><span class="screen-reader-text">' . esc_html__( 'Newer comments', 'lavzentheme' ) . '</span>',
				'screen_reader_text' => __( 'Comments navigation', 'lavzentheme' ),
				'class'              => 'cm-pagination',
			)
		);
		?>

	<?php elseif ( comments_open() ) : ?>
		<div class="cm-empty">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
			<p><?php esc_html_e( 'No comments yet — start the conversation.', 'lavzentheme' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! comments_open() && $lav_cnum > 0 ) : ?>
		<p class="cm-closed"><?php esc_html_e( 'Comments are closed.', 'lavzentheme' ); ?></p>
	<?php endif; ?>
</div>
