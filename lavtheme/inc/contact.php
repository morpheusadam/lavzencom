<?php
/**
 * Contact / "Free consultation" lead capture.
 *
 * Self-contained (no form plugin): a private `lav_lead` CPT persists every
 * submission (so leads survive even if outbound mail fails on shared hosting),
 * plus a best-effort wp_mail notification. The form posts to admin-post.php and
 * is protected by a nonce + honeypot. Render the form with lavzen_contact_form().
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the private "Lead" post type (visible to admins in wp-admin).
 */
function lavzen_register_leads() {
	register_post_type(
		'lav_lead',
		array(
			'labels'            => array(
				'name'          => __( 'Leads', 'lavtheme' ),
				'singular_name' => __( 'Lead', 'lavtheme' ),
				'menu_name'     => __( 'Leads', 'lavtheme' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'menu_icon'         => 'dashicons-email-alt',
			'menu_position'     => 26,
			'capability_type'   => 'post',
			'map_meta_cap'      => true,
			'supports'          => array( 'title', 'editor' ),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'init', 'lavzen_register_leads' );

/**
 * The contact form markup (glass styling via lavzen-ui.css).
 *
 * @return string
 */
function lavzen_contact_form() {
	$action  = esc_url( admin_url( 'admin-post.php' ) );
	$nonce   = wp_nonce_field( 'lavzen_contact', 'lavzen_contact_nonce', true, false );
	$source  = esc_attr( home_url( add_query_arg( array() ) ) );
	ob_start();
	?>
	<form class="lav-form glass glass--crystal" method="post" action="<?php echo $action; ?>" novalidate>
		<input type="hidden" name="action" value="lavzen_contact">
		<input type="hidden" name="lav_source" value="<?php echo $source; ?>">
		<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nonce_field is safe. ?>
		<p class="lav-vh" aria-hidden="true">
			<label>Leave this field empty<input type="text" name="lav_hp" tabindex="-1" autocomplete="off"></label>
		</p>

		<div class="lav-form__row">
			<div class="lav-field">
				<label class="lav-label" for="lav-name">Your name <span class="lav-label__req" aria-hidden="true">*</span></label>
				<input class="lav-input" id="lav-name" name="lav_name" type="text" autocomplete="name" required maxlength="120">
			</div>
			<div class="lav-field">
				<label class="lav-label" for="lav-email">Email <span class="lav-label__req" aria-hidden="true">*</span></label>
				<input class="lav-input" id="lav-email" name="lav_email" type="email" autocomplete="email" inputmode="email" required maxlength="160">
			</div>
		</div>

		<div class="lav-form__row">
			<div class="lav-field">
				<label class="lav-label" for="lav-company">Company <span class="lav-help">(optional)</span></label>
				<input class="lav-input" id="lav-company" name="lav_company" type="text" autocomplete="organization" maxlength="120">
			</div>
			<div class="lav-field">
				<label class="lav-label" for="lav-type">Project type</label>
				<select class="lav-input" id="lav-type" name="lav_type">
					<option value="Online store">Online store</option>
					<option value="Internal system">Internal system</option>
					<option value="Mobile app">Mobile app</option>
					<option value="AI-powered tool">AI-powered tool</option>
					<option value="Not sure yet">Not sure yet</option>
				</select>
			</div>
		</div>

		<div class="lav-field">
			<label class="lav-label" for="lav-message">What do you want to build? <span class="lav-label__req" aria-hidden="true">*</span></label>
			<textarea class="lav-textarea" id="lav-message" name="lav_message" required maxlength="4000" placeholder="A few sentences about your idea, timeline, and budget if you have one."></textarea>
			<span class="lav-help">No technical jargon needed — a veteran developer will translate it into a plan.</span>
		</div>

		<div class="lav-form__foot">
			<button type="submit" class="lav-btn lav-btn--cta">
				Request my free consultation
				<svg class="lav-btn__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
			</button>
			<span class="lav-help"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-2px"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg> The first step is free. You don&rsquo;t pay until you approve each milestone.</span>
		</div>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * Handle the submission: validate → persist a lead → notify → redirect.
 */
function lavzen_handle_contact() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );

	// Honeypot: silently accept (look successful) but do nothing.
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

	// Persist the lead (survives mail failures).
	$lead_id = wp_insert_post(
		array(
			'post_type'    => 'lav_lead',
			'post_status'  => 'private',
			'post_title'   => $name . ( $company ? ' — ' . $company : '' ),
			'post_content' => $message,
		),
		true
	);
	if ( ! is_wp_error( $lead_id ) ) {
		update_post_meta( $lead_id, 'lav_email', $email );
		update_post_meta( $lead_id, 'lav_company', $company );
		update_post_meta( $lead_id, 'lav_type', $type );
	}

	// Best-effort notification.
	$to      = apply_filters( 'lavzen_lead_recipient', get_option( 'admin_email' ) );
	$subject = sprintf( '[LAVZEN] Free consultation — %s', $name );
	$body    = "New consultation request:\n\n"
		. "Name: {$name}\n"
		. "Email: {$email}\n"
		. ( $company ? "Company: {$company}\n" : '' )
		. "Project type: {$type}\n\n"
		. "Message:\n{$message}\n";
	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
	wp_mail( $to, $subject, $body, $headers );

	wp_safe_redirect( add_query_arg( 'sent', '1', $back ) );
	exit;
}
add_action( 'admin_post_lavzen_contact', 'lavzen_handle_contact' );
add_action( 'admin_post_nopriv_lavzen_contact', 'lavzen_handle_contact' );
