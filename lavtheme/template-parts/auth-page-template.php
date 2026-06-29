<?php
/**
 * Standalone full-screen template for the "Login" page — NO theme header/footer.
 *
 * Renders a bare HTML canvas (only wp_head()/wp_footer() + the auth body) so the
 * login / register screen fills the entire viewport with no topbar, sidebar or
 * footer. The theme tokens, base CSS and the auth context CSS/JS still load via
 * wp_head()/wp_footer(); fonts are linked here (header.php is bypassed).
 *
 * Routed here by lavtheme_cs_auth_page_template() (template_include). The page is
 * no-cache because it carries per-request nonces and error/success messages.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'DONOTCACHEPAGE' ) ) {
	define( 'DONOTCACHEPAGE', true );
}
nocache_headers();
if ( ! headers_sent() ) {
	header( 'X-Content-Type-Options: nosniff' );
}

$lav_fonts = 'https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,400;6..72,500&family=JetBrains+Mono:wght@500&display=swap';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0B0907">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="<?php echo esc_url( $lav_fonts ); ?>" onload="this.onload=null,this.rel='stylesheet'">
<link href="<?php echo esc_url( $lav_fonts ); ?>" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="<?php echo esc_url( $lav_fonts ); ?>"></noscript>
<?php wp_head(); ?>
</head>
<body <?php body_class( 'lav-auth-page' ); ?>>
<?php
wp_body_open();

if ( function_exists( 'lavtheme_cs_auth_render' ) ) {
	lavtheme_cs_auth_render();
} else {
	$lav_auth_body = get_theme_file_path( 'template-parts/auth.php' );
	if ( is_readable( $lav_auth_body ) ) {
		include $lav_auth_body;
	}
}

wp_footer();
?>
</body>
</html>
