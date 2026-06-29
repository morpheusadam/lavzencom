<?php
/**
 * 404 — not found.
 *
 * Rendered inside the app shell for consistency; the Code Studio "404" context
 * (Phase 3) can replace the body via the registered context.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="lavzen-404" aria-labelledby="lavzen-404-title">
	<p class="lavzen-404__code">404</p>
	<h1 id="lavzen-404-title" class="lavzen-404__title"><?php esc_html_e( 'Page not found', 'lavzentheme' ); ?></h1>
	<p class="lavzen-404__text"><?php esc_html_e( 'The page you’re looking for doesn’t exist or has moved.', 'lavzentheme' ); ?></p>
	<div class="lavzen-404__actions">
		<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'lavzentheme' ); ?></a>
	</div>
	<div class="lavzen-404__search"><?php get_search_form(); ?></div>
</section>
<?php
get_footer();
