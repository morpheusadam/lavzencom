<?php
/**
 * Contact page template — lead-capture form.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

get_header();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lav_sent = isset( $_GET['sent'] );
?>
<section class="lav-contact">
	<header class="lav-contact__head">
		<?php the_title( '<h1 class="lav-contact__title">', '</h1>' ); ?>
		<p class="lav-contact__lead"><?php esc_html_e( 'Tell us what you want to build — a veteran developer will turn it into a plan. The first step is free.', 'lavzentheme' ); ?></p>
	</header>

	<?php if ( $lav_sent ) : ?>
		<div class="lav-alert lav-alert--success" role="status"><?php esc_html_e( 'Thanks — your request was sent. We’ll get back to you shortly.', 'lavzentheme' ); ?></div>
	<?php endif; ?>

	<?php
	while ( have_posts() ) :
		the_post();
		if ( trim( wp_strip_all_tags( get_the_content() ) ) ) :
			?>
			<div class="lav-contact__content entry-content"><?php the_content(); ?></div>
			<?php
		endif;
	endwhile;

	if ( function_exists( 'lavzen_contact_form' ) ) {
		echo lavzen_contact_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
	}
	?>
</section>
<?php
get_footer();
