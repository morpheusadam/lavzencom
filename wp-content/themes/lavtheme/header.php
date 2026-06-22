<?php
/**
 * Header: document head + opening of .app / .main shell.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0b1120">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,400;6..72,500&family=JetBrains+Mono:wght@500&display=swap" onload='this.onload=null,this.rel="stylesheet"'><link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,400;6..72,500&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet" media="print" onload='this.media="all"'><noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,400;6..72,500&family=JetBrains+Mono:wght@500&display=swap"></noscript>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="app">
<?php lavtheme_render_section( 'sidebar' ); ?>
<div class="main">
<?php
// Header renders on the front page always; on inner pages only when the
// "Header on all pages" toggle is on (default on).
if ( is_front_page() || ! function_exists( 'lavtheme_cs_header_global' ) || lavtheme_cs_header_global() ) {
	lavtheme_render_section( 'header' );
}
?>
<main id="main" class="site-main" tabindex="-1">
