<?php
/**
 * Icons — one source of truth for inline SVG icons.
 *
 * Replaces the dozens of duplicated inline <svg> blocks scattered across the
 * legacy templates with a single named registry. Every icon is a 24×24,
 * currentColor, stroke-based glyph (consistent weight/caps), so a call like
 * lavzen_icon( 'cart' ) renders the same mark everywhere and an icon is changed
 * in exactly one place.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Support;

defined( 'ABSPATH' ) || exit;

final class Icons {

	/**
	 * Inner SVG path markup per icon name (no <svg> wrapper — that's added by render()).
	 *
	 * @return array<string,string>
	 */
	private static function paths(): array {
		return array(
			'search'      => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
			'cart'        => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
			'heart'       => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/>',
			'star'        => '<path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 19l-4.8 2.5.9-5.4L4.2 12.3l5.4-.8z"/>',
			'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
			'lock'        => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
			'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
			'check'       => '<path d="M20 6 9 17l-5-5"/>',
			'eye'         => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
			'download'    => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
			'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
			'login'       => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/>',
			'bag'         => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/>',
			'orders'      => '<path d="M3 7h12M3 12h18M3 17h8"/><circle cx="18" cy="7" r="2"/><circle cx="14" cy="17" r="2"/>',
			'comment'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
			'reply'       => '<polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>',
			'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'calendar'    => '<rect x="3" y="4.5" width="18" height="17" rx="2.5"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/>',
			'bell'        => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
			'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
			'arrow-left'  => '<path d="M15 5l-7 7 7 7"/>',
			'arrow-right' => '<path d="M9 5l7 7-7 7"/>',
			'arrow-cta'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'close'       => '<path d="M18 6 6 18M6 6l12 12"/>',
			'grid'        => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
			'list'        => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
			'shield'      => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
			'doc'         => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
			'home'        => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/>',
			'menu'        => '<path d="M4 7h16M4 12h16M4 17h16"/>',
			'filter'      => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		);
	}

	/**
	 * Render a named icon, or '' for an unknown name.
	 *
	 * @param string $name  Icon key.
	 * @param string $class Extra class on the <svg>.
	 * @param array  $attrs Extra attributes (e.g. ['width'=>'14']).
	 */
	public static function render( string $name, string $class = '', array $attrs = array() ): string {
		$paths = self::paths();
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}
		$fill    = in_array( $name, array( 'star', 'shield' ), true ); // solid glyphs.
		$attrs   = array_merge(
			array(
				'viewBox'        => '0 0 24 24',
				'fill'           => $fill ? 'currentColor' : 'none',
				'stroke'         => $fill ? '' : 'currentColor',
				'stroke-width'   => $fill ? '' : '1.8',
				'stroke-linecap' => $fill ? '' : 'round',
				'stroke-linejoin' => $fill ? '' : 'round',
				'aria-hidden'    => 'true',
				'class'          => trim( 'lav-icon ' . $class ),
			),
			$attrs
		);
		$out = '<svg';
		foreach ( $attrs as $k => $v ) {
			if ( '' !== $v ) {
				$out .= ' ' . $k . '="' . esc_attr( $v ) . '"';
			}
		}
		$out .= '>' . $paths[ $name ] . '</svg>';
		return $out;
	}
}
