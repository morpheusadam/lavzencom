<?php
/**
 * Nav walker that emits bare <a> items (no <ul>/<li>) for the flex topnav.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Matches the design's flat .topnav: a row of plain anchors, current item gets
 * the `active` class. Ported from the legacy Lavtheme_Topnav_Walker.
 */
final class Topnav_Walker extends \Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$active  = in_array( 'current-menu-item', $classes, true ) || in_array( 'current_page_item', $classes, true );
		$url     = ! empty( $item->url ) ? $item->url : '#';
		$output .= '<a href="' . esc_url( $url ) . '"' . ( $active ? ' class="active"' : '' ) . '>' . esc_html( $item->title ) . '</a>';
	}
}
