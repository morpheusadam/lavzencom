<?php
/**
 * Theme Code Studio — Source Reader.
 *
 * Single source of truth for "what real code renders a context". Given a
 * context id (front section, page-<ID>, dl-template, shop, blog, …) it resolves
 * the WordPress Template-Hierarchy file that renders it and returns the real
 * default code for each of the five uniform editor tabs:
 *
 *   HTML / PHP  → the resolved template body
 *   CSS         → the context's real stylesheet
 *   JS          → the context's real script
 *   Mobile CSS  → the @media (max-width:640px) layer of that stylesheet
 *   PHP         → context-specific server-side logic (override, default empty)
 *
 * The editors are never empty: when no stored override exists the real file is
 * shown (the `_empty` marker still lets a user intentionally clear css/js/mcss).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolves real render sources for every Code Studio context.
 */
class Lav_CS_Source_Reader {

	/**
	 * The five uniform editor tabs every "Template" section exposes,
	 * as type => label.
	 *
	 * @return array
	 */
	public static function tabs() {
		return array(
			'html' => __( 'HTML / PHP', 'lavtheme' ),
			'css'  => __( 'CSS', 'lavtheme' ),
			'js'   => __( 'JS', 'lavtheme' ),
			'mcss' => __( 'Mobile CSS', 'lavtheme' ),
			'php'  => __( 'PHP', 'lavtheme' ),
		);
	}

	/**
	 * Theme-relative path of the per-page dedicated template copy.
	 *
	 * @param int $id Page id.
	 * @return string
	 */
	public static function page_copy_rel( $id ) {
		return 'template-parts/context-page-' . absint( $id ) . '.php';
	}

	/**
	 * Resolve the template file (theme-relative) that renders a context, per the
	 * WordPress Template Hierarchy. Future pages resolve automatically.
	 *
	 * @param string $ctx Context id.
	 * @return string Theme-relative path, or '' when none applies.
	 */
	public static function resolve_template( $ctx ) {
		if ( 'shop' === $ctx ) {
			// Download archive / taxonomies render the shared shop body.
			return 'template-parts/shop.php';
		}
		if ( 'blog' === $ctx ) {
			return 'template-parts/blog.php';
		}
		if ( '404' === $ctx ) {
			return 'template-parts/404.php';
		}
		if ( 'dl-template' === $ctx || 0 === strpos( (string) $ctx, 'dl-' ) ) {
			return 'template-parts/single-download-body.php';
		}
		if ( 0 === strpos( (string) $ctx, 'page-' ) ) {
			$id = absint( substr( $ctx, 5 ) );
			return self::resolve_page_template( $id );
		}
		return '';
	}

	/**
	 * Resolve the template that renders a single page id (custom page template,
	 * the configured Shop Page wrapper, else page.php).
	 *
	 * @param int $id Page id.
	 * @return string Theme-relative path.
	 */
	public static function resolve_page_template( $id ) {
		$id = absint( $id );
		if ( ! $id ) {
			return 'page.php';
		}

		// A previously-materialised dedicated copy wins (the editable source).
		$copy = self::page_copy_rel( $id );
		if ( is_readable( get_theme_file_path( $copy ) ) ) {
			return $copy;
		}

		// The configured EDD Shop Page is routed through its own wrapper.
		if ( function_exists( 'lavtheme_shop_page_id' ) && (int) lavtheme_shop_page_id() === $id ) {
			$rel = 'template-parts/shop-page-template.php';
			if ( is_readable( get_theme_file_path( $rel ) ) ) {
				return $rel;
			}
		}

		// A page template assigned in the editor (Template: … dropdown).
		$slug = get_page_template_slug( $id );
		if ( $slug && is_readable( get_theme_file_path( $slug ) ) ) {
			return $slug;
		}

		return 'page.php';
	}

	/**
	 * Theme-relative source file for a given (context, tab). '' when no file
	 * backs that tab (e.g. the per-page CSS/JS layers, or the extra PHP tab).
	 *
	 * @param string $ctx Context id.
	 * @param string $tab Tab type (html|css|js|mcss|php).
	 * @return string
	 */
	public static function source_path( $ctx, $tab ) {
		$map = array(
			'shop'        => array( 'css' => 'assets/css/shop.css', 'js' => 'assets/js/shop.js' ),
			'blog'        => array( 'css' => 'assets/css/blog.css', 'js' => 'assets/js/blog.js' ),
			'404'         => array( 'css' => 'assets/css/404.css', 'js' => 'assets/js/404.js' ),
			'dl-template' => array( 'css' => 'assets/css/single-product.css', 'js' => 'assets/js/single-product.js' ),
		);

		if ( 'html' === $tab ) {
			return self::resolve_template( $ctx );
		}
		if ( isset( $map[ $ctx ][ $tab ] ) ) {
			return $map[ $ctx ][ $tab ];
		}
		// mcss is derived from the css source (extracted), not a standalone file;
		// pages have no dedicated css/js file (per-page layers start empty).
		return '';
	}

	/**
	 * Default (real-file) value for a (context, tab). Mobile CSS is extracted
	 * from the context stylesheet; the HTML/PHP tab is the resolved template.
	 *
	 * @param string $ctx Context id.
	 * @param string $tab Tab type.
	 * @return string
	 */
	public static function default_value( $ctx, $tab ) {
		if ( 'mcss' === $tab ) {
			$css_rel = self::source_path( $ctx, 'css' );
			$css     = $css_rel ? self::read( $css_rel ) : '';
			return self::mobile_extract( $css );
		}

		$rel = self::source_path( $ctx, $tab );
		if ( '' === $rel ) {
			return '';
		}
		return self::read( $rel );
	}

	/**
	 * Read a theme-relative file, or '' when unreadable.
	 *
	 * @param string $rel Theme-relative path.
	 * @return string
	 */
	public static function read( $rel ) {
		if ( '' === $rel || false !== strpos( $rel, '..' ) ) {
			return '';
		}
		$path = get_theme_file_path( $rel );
		return is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/** Widest max-width (px) treated as a "mobile" @media query. */
	const MOBILE_MAX = 768;

	/**
	 * Extract the mobile layer of a stylesheet: every @media block whose query
	 * targets a phone-width viewport (max-width <= MOBILE_MAX, e.g. the theme's
	 * 640 / 680 / 380px breakpoints), excluding tablet/desktop (980 / 1024px).
	 * Brace-aware (handles nested rules).
	 *
	 * @param string $css Full stylesheet.
	 * @return string Concatenated @media blocks, or ''.
	 */
	public static function mobile_extract( $css ) {
		$css = (string) $css;
		$len = strlen( $css );
		$out = array();
		$i   = 0;

		while ( false !== ( $at = strpos( $css, '@media', $i ) ) ) {
			// Prelude is everything from @media up to the first '{'.
			$brace = strpos( $css, '{', $at );
			if ( false === $brace ) {
				break;
			}
			$prelude = substr( $css, $at, $brace - $at );

			// Walk braces to find the block's matching close.
			$depth = 0;
			$end   = $brace;
			for ( $j = $brace; $j < $len; $j++ ) {
				if ( '{' === $css[ $j ] ) {
					$depth++;
				} elseif ( '}' === $css[ $j ] ) {
					$depth--;
					if ( 0 === $depth ) {
						$end = $j;
						break;
					}
				}
			}

			$block = substr( $css, $at, $end - $at + 1 );
			if ( self::is_mobile_query( $prelude ) ) {
				$out[] = trim( $block );
			}
			$i = $end + 1;
		}

		return $out ? implode( "\n\n", $out ) . "\n" : '';
	}

	/**
	 * Does an @media prelude target a <=640px viewport?
	 *
	 * @param string $prelude e.g. "@media (max-width:640px)".
	 * @return bool
	 */
	protected static function is_mobile_query( $prelude ) {
		if ( preg_match( '/max-width\s*:\s*(\d+)\s*px/i', $prelude, $m ) ) {
			return (int) $m[1] <= self::MOBILE_MAX;
		}
		return false;
	}

	/**
	 * Is a page built with Elementor in a canvas/header-footer template that
	 * bypasses the_content (so Code Studio markup cannot apply there)?
	 *
	 * @param int $id Page id.
	 * @return bool
	 */
	public static function is_elementor( $id ) {
		$id = absint( $id );
		if ( ! $id ) {
			return false;
		}
		$built = 'builder' === get_post_meta( $id, '_elementor_edit_mode', true );
		if ( ! $built ) {
			return false;
		}
		$tpl = (string) get_post_meta( $id, '_wp_page_template', true );
		return in_array( $tpl, array( 'elementor_canvas', 'elementor_header_footer', 'elementor_theme' ), true );
	}
}
