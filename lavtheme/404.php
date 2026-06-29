<?php
/**
 * 404 / error template — standalone, full-viewport, no site header/footer.
 *
 * Renders its own minimal document so the error experience is immersive and
 * chrome-free. Still calls wp_head()/wp_footer() so the theme stylesheet and the
 * Code Studio "404 / Error" CSS+JS inject normally. The body is the editable
 * Template (override-or-file: template-parts/404.php) via lavtheme_cs_404_render().
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

// Make sure the correct status is sent even if reached directly.
if ( ! headers_sent() ) {
	status_header( 404 );
	nocache_headers();
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0B0907">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&display=swap" onload='this.onload=null,this.rel="stylesheet"'><link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload='this.media="all"'><noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&display=swap"></noscript>
<?php wp_head(); ?>
</head>
<body <?php body_class( 'lav-error-page' ); ?>>
<?php
wp_body_open();

// Editable body (Code Studio "404 / Error") with a guaranteed file fallback.
if ( function_exists( 'lavtheme_cs_404_render' ) ) {
	lavtheme_cs_404_render();
} elseif ( function_exists( 'lavtheme_part' ) ) {
	lavtheme_part( '404' );
} else {
	get_template_part( 'template-parts/404' );
}

wp_footer();
?>
</body>
</html>
