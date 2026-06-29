<?php
/**
 * Contact / lead capture (ported from inc/contact.php; already lavzen_*-prefixed).
 *
 * Self-contained (no form plugin): a private `lav_lead` CPT persists every
 * submission, plus a best-effort wp_mail notification. The form posts to
 * admin-post.php (nonce + honeypot). Render with lavzen_contact_form().
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

/** Register the private "Lead" post type. */
if ( ! function_exists( 'lavzen_register_leads' ) ) :
function lavzen_register_leads() {
	register_post_type(
		'lav_lead',
		array(
			'labels'        => array(
				'name'          => __( 'Leads', 'lavzentheme' ),
				'singular_name' => __( 'Lead', 'lavzentheme' ),
				'menu_name'     => __( 'Leads', 'lavzentheme' ),
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'menu_icon'     => 'dashicons-email-alt',
			'menu_position' => 26,
			'capability_type' => 'post',
			'map_meta_cap'  => true,
			'supports'      => array( 'title', 'editor' ),
			'show_in_rest'  => false,
		)
	);
}
endif;
add_action( 'init', 'lavzen_register_leads' );

/** The contact form markup. */
if ( ! function_exists( 'lavzen_contact_form' ) ) :
function lavzen_contact_form(): string {
	$action = esc_url( admin_url( 'admin-post.php' ) );
	$nonce  = wp_nonce_field( 'lavzen_contact', 'lavzen_contact_nonce', true, false );
	$source = esc_attr( home_url( add_query_arg( array() ) ) );
	ob_start();
	?>
	<form class="lav-form glass glass--crystal" method="post" action="<?php echo $action; ?>" novalidate>
		<input type="hidden" name="action" value="lavzen_contact">
		<input type="hidden" name="lav_source" value="<?php echo $source; ?>">
		<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nonce_field is safe. ?>
		<p class="lav-vh" aria-hidden="true"><label>Leave this field empty<input type="text" name="lav_hp" tabindex="-1" autocomplete="off"></label></p>
		<div class="lav-form__row">
			<div class="lav-field"><label class="lav-label" for="lav-name"><?php esc_html_e( 'Your name', 'lavzentheme' ); ?> <span class="lav-label__req" aria-hidden="true">*</span></label><input class="lav-input" id="lav-name" name="lav_name" type="text" autocomplete="name" required maxlength="120"></div>
			<div class="lav-field"><label class="lav-label" for="lav-email"><?php esc_html_e( 'Email', 'lavzentheme' ); ?> <span class="lav-label__req" aria-hidden="true">*</span></label><input class="lav-input" id="lav-email" name="lav_email" type="email" autocomplete="email" inputmode="email" required maxlength="160"></div>
		</div>
		<div class="lav-form__row">
			<div class="lav-field"><label class="lav-label" for="lav-company"><?php esc_html_e( 'Company', 'lavzentheme' ); ?> <span class="lav-help"><?php esc_html_e( '(optional)', 'lavzentheme' ); ?></span></label><input class="lav-input" id="lav-company" name="lav_company" type="text" autocomplete="organization" maxlength="120"></div>
			<div class="lav-field"><label class="lav-label" for="lav-type"><?php esc_html_e( 'Project type', 'lavzentheme' ); ?></label><select class="lav-input" id="lav-type" name="lav_type"><option><?php esc_html_e( 'Online store', 'lavzentheme' ); ?></option><option><?php esc_html_e( 'Internal system', 'lavzentheme' ); ?></option><option><?php esc_html_e( 'Mobile app', 'lavzentheme' ); ?></option><option><?php esc_html_e( 'AI-powered tool', 'lavzentheme' ); ?></option><option><?php esc_html_e( 'Not sure yet', 'lavzentheme' ); ?></option></select></div>
		</div>
		<div class="lav-field"><label class="lav-label" for="lav-message"><?php esc_html_e( 'What do you want to build?', 'lavzentheme' ); ?> <span class="lav-label__req" aria-hidden="true">*</span></label><textarea class="lav-textarea" id="lav-message" name="lav_message" required maxlength="4000"></textarea></div>
		<div class="lav-form__foot"><button type="submit" class="lav-btn lav-btn--cta"><?php esc_html_e( 'Request my free consultation', 'lavzentheme' ); ?></button></div>
	</form>
	<?php
	return (string) ob_get_clean();
}
endif;

/** Handle the submission: validate → persist a lead → notify → redirect. */
if ( ! function_exists( 'lavzen_handle_contact' ) ) :
function lavzen_handle_contact(): void {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );
	if ( ! empty( $_POST['lav_hp'] ) ) {
		wp_safe_redirect( add_query_arg( 'sent', '1', $back ) );
		exit;
	}
	if ( ! isset( $_POST['lavzen_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lavzen_contact_nonce'] ) ), 'lavzen_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'err', 'nonce', $back ) );
		exit;
	}
	$name    = isset( $_POST['lav_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lav_name'] ) ) : '';
	$email   = isset( $_POST['lav_email'] ) ? sanitize_email( wp_unslash( $_POST['lav_email'] ) ) : '';
	$company = isset( $_POST['lav_company'] ) ? sanitize_text_field( wp_unslash( $_POST['lav_company'] ) ) : '';
	$type    = isset( $_POST['lav_type'] ) ? sanitize_text_field( wp_unslash( $_POST['lav_type'] ) ) : '';
	$message = isset( $_POST['lav_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lav_message'] ) ) : '';
	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		wp_safe_redirect( add_query_arg( 'err', 'fields', $back ) );
		exit;
	}
	$lead_id = wp_insert_post( array( 'post_type' => 'lav_lead', 'post_status' => 'private', 'post_title' => $name . ( $company ? ' — ' . $company : '' ), 'post_content' => $message ), true );
	if ( ! is_wp_error( $lead_id ) ) {
		update_post_meta( $lead_id, 'lav_email', $email );
		update_post_meta( $lead_id, 'lav_company', $company );
		update_post_meta( $lead_id, 'lav_type', $type );
	}
	$to      = apply_filters( 'lavzen_lead_recipient', get_option( 'admin_email' ) );
	$subject = sprintf( '[LAVZEN] Free consultation — %s', $name );
	$body    = "New consultation request:\n\nName: {$name}\nEmail: {$email}\n" . ( $company ? "Company: {$company}\n" : '' ) . "Project type: {$type}\n\nMessage:\n{$message}\n";
	wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );
	wp_safe_redirect( add_query_arg( 'sent', '1', $back ) );
	exit;
}
endif;
add_action( 'admin_post_lavzen_contact', 'lavzen_handle_contact' );
add_action( 'admin_post_nopriv_lavzen_contact', 'lavzen_handle_contact' );
