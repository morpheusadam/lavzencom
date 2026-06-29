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
		if ( 'single' === $ctx ) {
			return 'template-parts/single-article.php';
		}
		if ( '404' === $ctx ) {
			return 'template-parts/404.php';
		}
		if ( 'account' === $ctx ) {
			return 'template-parts/account.php';
		}
		if ( 'auth' === $ctx ) {
			return 'template-parts/auth.php';
		}
		if ( 'wp-dash' === $ctx ) {
			return 'plugins/wp-dash/template.php';
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

		// WordPress template hierarchy for pages: page-{slug}.php then page-{id}.php
		// (e.g. the Contact page is really rendered by page-contact.php). Mirroring
		// the hierarchy here means the HTML/PHP tab shows the file that actually runs.
		$post = get_post( $id );
		if ( $post && '' !== $post->post_name && is_readable( get_theme_file_path( 'page-' . $post->post_name . '.php' ) ) ) {
			return 'page-' . $post->post_name . '.php';
		}
		if ( is_readable( get_theme_file_path( 'page-' . $id . '.php' ) ) ) {
			return 'page-' . $id . '.php';
		}

		return 'page.php';
	}

	/**
	 * Real front-end CSS/JS that style a given page id — used as the editor
	 * defaults for that page's Template CSS / JS / Mobile-CSS tabs so every tab
	 * shows real code (the shop page → shop assets, EDD purchase-flow pages →
	 * checkout, every other page → the theme's global main.css / main.js).
	 *
	 * These are reference defaults only: the per-page injector
	 * (lavtheme_cs_page_head/_footer) emits the STORED override, never the default,
	 * so showing a global asset here never double-loads it on the front end.
	 *
	 * @param int $id Page id.
	 * @return array { css, js } theme-relative paths.
	 */
	public static function page_assets( $id ) {
		$id = absint( $id );

		// The configured EDD Shop Page renders the shop body → shop styling.
		if ( function_exists( 'lavtheme_shop_page_id' ) && (int) lavtheme_shop_page_id() === $id ) {
			return array( 'css' => 'assets/css/shop.css', 'js' => 'assets/js/shop.js' );
		}

		// EDD purchase-flow pages (checkout / success / failed / history /
		// confirmation) are mapped onto the theme tokens by checkout.css.
		if ( self::is_edd_flow_page( $id ) ) {
			return array( 'css' => 'assets/css/checkout.css', 'js' => 'assets/js/main.js' );
		}

		// Every other page is styled by the theme's global stylesheet + script.
		return array( 'css' => 'assets/css/main.css', 'js' => 'assets/js/main.js' );
	}

	/**
	 * Is the page id one of the EDD purchase-flow pages?
	 *
	 * @param int $id Page id.
	 * @return bool
	 */
	protected static function is_edd_flow_page( $id ) {
		if ( ! function_exists( 'edd_get_option' ) ) {
			return false;
		}
		foreach ( array( 'purchase_page', 'success_page', 'failure_page', 'purchase_history_page', 'confirmation_page' ) as $opt ) {
			if ( (int) edd_get_option( $opt, 0 ) === (int) $id ) {
				return true;
			}
		}
		return false;
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
			'single'      => array( 'css' => 'assets/css/single.css', 'js' => 'assets/js/single.js' ),
			'404'         => array( 'css' => 'assets/css/404.css', 'js' => 'assets/js/404.js' ),
			'dl-template' => array( 'css' => 'assets/css/single-product.css', 'js' => 'assets/js/single-product.js' ),
			'account'     => array( 'css' => 'assets/css/account.css', 'js' => 'assets/js/account.js' ),
			'auth'        => array( 'css' => 'assets/css/auth.css', 'js' => 'assets/js/auth.js' ),
			'wp-dash'     => array( 'css' => 'plugins/wp-dash/assets/dash-skin.css', 'js' => 'plugins/wp-dash/assets/dash-skin.js' ),
		);

		if ( 'html' === $tab ) {
			return self::resolve_template( $ctx );
		}
		if ( isset( $map[ $ctx ][ $tab ] ) ) {
			return $map[ $ctx ][ $tab ];
		}
		// Page contexts resolve the REAL stylesheet / script that styles the page
		// (shop page → shop assets, EDD purchase-flow → checkout, else the theme's
		// global main.css / main.js) so the CSS / JS / Mobile-CSS tabs show real code.
		if ( 0 === strpos( (string) $ctx, 'page-' ) && in_array( $tab, array( 'css', 'js' ), true ) ) {
			$assets = self::page_assets( absint( substr( $ctx, 5 ) ) );
			return isset( $assets[ $tab ] ) ? $assets[ $tab ] : '';
		}
		// mcss is derived from the css source (extracted), not a standalone file.
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
