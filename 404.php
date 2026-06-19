<?php
/**
 * 404 template.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="block lavtheme-content glass" id="content" style="padding:56px 48px;text-align:center;">
	<div class="kicker"><?php esc_html_e( 'Error 404', 'lavtheme' ); ?></div>
	<h1 class="block-title" style="font-size:48px;margin:8px 0 16px;"><?php esc_html_e( 'Page not found', 'lavtheme' ); ?></h1>
	<p class="block-intro" style="margin:0 auto;"><?php esc_html_e( 'The page you are looking for moved or never existed.', 'lavtheme' ); ?></p>
	<div class="hero-cta" style="justify-content:center;margin-top:28px;">
		<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'lavtheme' ); ?></a>
	</div>
	<div style="max-width:560px;margin:28px auto 0;"><?php get_search_form(); ?></div>
</section>
<?php
get_footer();
