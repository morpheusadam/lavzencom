<?php
/**
 * Document head + opening page shell.
 *
 * One clean path for every template (no front-page branching / mid-file return).
 * Header/footer chrome is rendered by template parts so this file stays minimal.
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
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'lavzentheme' ); ?></a>
<div id="page" class="site">
	<?php get_template_part( 'template-parts/chrome/header' ); ?>
	<main id="content" class="site-main" tabindex="-1">
