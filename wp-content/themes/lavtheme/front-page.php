<?php
/**
 * Front page — faithful rebuild of the original landing page.
 *
 * The .app / .main shell, sidebar rail and topbar live in header.php;
 * the footer content lives in footer.php. This template renders the
 * six content sections in their original order.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lavtheme_slugs = lavtheme_cs_content_slugs();

// Placement map from the registry (default 'after' = normal full-width flow).
$lavtheme_place = array();
foreach ( lavtheme_cs_registry() as $lavtheme_r ) {
	$lavtheme_place[ $lavtheme_r['slug'] ] = isset( $lavtheme_r['placement'] ) ? $lavtheme_r['placement'] : 'after';
}
$lavtheme_has_side = false;
foreach ( $lavtheme_slugs as $lavtheme_s ) {
	$lavtheme_pl = isset( $lavtheme_place[ $lavtheme_s ] ) ? $lavtheme_place[ $lavtheme_s ] : 'after';
	if ( 'sidebar-left' === $lavtheme_pl || 'sidebar-right' === $lavtheme_pl ) {
		$lavtheme_has_side = true;
		break;
	}
}

if ( ! $lavtheme_has_side ) {
	// Default: render each section in order — byte-identical to before.
	foreach ( $lavtheme_slugs as $lavtheme_s ) {
		lavtheme_render_section( $lavtheme_s );
	}
} else {
	// A section opted into a sidebar → wrap in the responsive placement grid.
	$lavtheme_left  = '';
	$lavtheme_right = '';
	$lavtheme_main  = '';
	foreach ( $lavtheme_slugs as $lavtheme_s ) {
		ob_start();
		lavtheme_render_section( $lavtheme_s );
		$lavtheme_html = ob_get_clean();
		$lavtheme_pl   = isset( $lavtheme_place[ $lavtheme_s ] ) ? $lavtheme_place[ $lavtheme_s ] : 'after';
		if ( 'sidebar-left' === $lavtheme_pl ) {
			$lavtheme_left .= $lavtheme_html;
		} elseif ( 'sidebar-right' === $lavtheme_pl ) {
			$lavtheme_right .= $lavtheme_html;
		} else {
			$lavtheme_main .= $lavtheme_html;
		}
	}
	if ( function_exists( 'lavtheme_cs_page_layout_css' ) ) {
		echo '<style>' . lavtheme_cs_page_layout_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '<div class="lavcs-pagewrap' . ( '' !== $lavtheme_left ? ' has-left' : '' ) . ( '' !== $lavtheme_right ? ' has-right' : '' ) . '">';
	if ( '' !== $lavtheme_left ) {
		echo '<aside class="lavcs-side lavcs-side-left">' . $lavtheme_left . '</aside>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '<div class="lavcs-col-main">' . $lavtheme_main . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	if ( '' !== $lavtheme_right ) {
		echo '<aside class="lavcs-side lavcs-side-right">' . $lavtheme_right . '</aside>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div>';
}

get_footer();
