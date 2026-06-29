<?php
/**
 * Document head + opening of the app shell.
 *
 * One clean path for every template (no front-page branching / mid-file return).
 * The shell is .app > [icon rail] + .main > [topbar] + <main id="content">.
 * Chrome is rendered by template parts under template-parts/chrome/.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#0B0907">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'lavzentheme' ); ?></a>
<div class="app">
	<?php get_template_part( 'template-parts/chrome/sidebar' ); ?>
	<div class="main">
		<?php get_template_part( 'template-parts/chrome/topbar' ); ?>
		<main id="content" class="site-main" tabindex="-1">
