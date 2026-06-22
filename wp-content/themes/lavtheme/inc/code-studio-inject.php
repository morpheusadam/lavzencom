<?php
/**
 * Theme Code Studio — front-end injection.
 *
 * Renders each section (DB override → safe HTML, otherwise the template-part
 * file) and injects the section/global CSS and JS saved in the studio.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inject the JSON-LD schema into the <head> of every page.
 */
function lavtheme_cs_schema_head() {
	// Single pages and single downloads output their own (per-context) schema,
	// so skip the site-wide default there to avoid duplicate JSON-LD blocks.
	if ( is_page() || is_singular( 'download' ) || ( function_exists( 'is_product' ) && is_product() ) ) {
		return;
	}
	$schema = trim( (string) lavtheme_cs_get_schema() );
	if ( '' === $schema ) {
		return;
	}
	json_decode( $schema );
	if ( JSON_ERROR_NONE !== json_last_error() ) {
		return; // never inject broken JSON.
	}
	$safe = str_replace( '</', '<\/', $schema );
	echo '<script type="application/ld+json">' . $safe . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'lavtheme_cs_schema_head', 5 );

/**
 * Start output buffering to minify the front-end document, if enabled.
 */
function lavtheme_cs_maybe_minify() {
	if ( is_admin() || is_feed() || wp_doing_ajax() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	if ( ! lavtheme_cs_minify_on() ) {
		return;
	}
	ob_start( 'lavtheme_cs_minify_buffer' );
}
add_action( 'template_redirect', 'lavtheme_cs_maybe_minify', 0 );

/**
 * Safe CSS minifier (strips comments, collapses whitespace).
 *
 * @param string $css CSS.
 * @return string
 */
function lavtheme_cs_min_css( $css ) {
	$css = preg_replace( '#/\*(?!!).*?\*/#s', '', $css );
	$css = preg_replace( '/\s*\r?\n\s*/', ' ', $css );
	$css = preg_replace( '/\s*([{}:;,>])\s*/', '$1', $css );
	$css = str_replace( ';}', '}', $css );
	return trim( (string) $css );
}

/**
 * Conservative JS minifier — keeps newlines (ASI-safe), only drops indentation
 * and blank lines so execution order/behaviour cannot change.
 *
 * @param string $js JS.
 * @return string
 */
function lavtheme_cs_min_js_light( $js ) {
	$js = preg_replace( '/[ \t]+\r?\n/', "\n", $js );
	$js = preg_replace( '/\r?\n[ \t]+/', "\n", $js );
	$js = preg_replace( '/\n{2,}/', "\n", $js );
	return trim( (string) $js );
}

/**
 * Output-buffer callback: minify the rendered HTML document safely.
 *
 * <pre>, <textarea>, <script> and <style> are protected; only their CSS/JS
 * bodies are (separately) minified. Whitespace runs containing a newline are
 * collapsed to a single space, so inline text spacing is preserved.
 *
 * @param string $html Buffered output.
 * @return string
 */
function lavtheme_cs_minify_buffer( $html ) {
	if ( '' === $html || false === stripos( $html, '</html>' ) ) {
		return $html;
	}

	$blocks = array();
	$html   = preg_replace_callback(
		'#<(pre|textarea|script|style)(\s[^>]*)?>(.*?)</\1>#is',
		function ( $m ) use ( &$blocks ) {
			$tag   = strtolower( $m[1] );
			$attr  = isset( $m[2] ) ? $m[2] : '';
			$inner = $m[3];
			if ( 'style' === $tag ) {
				$inner = lavtheme_cs_min_css( $inner );
			} elseif ( 'script' === $tag ) {
				$inner = lavtheme_cs_min_js_light( $inner );
			}
			$key            = '<!--LAVCS' . count( $blocks ) . '-->';
			$blocks[ $key ] = '<' . $tag . $attr . '>' . $inner . '</' . $tag . '>';
			return $key;
		},
		$html
	);

	// Strip HTML comments (keep IE conditionals + our placeholders).
	$html = preg_replace( '#<!--(?!\[if)(?!LAVCS).*?-->#s', '', $html );
	// Collapse newline+indentation to a single space (preserves inline spacing).
	$html = preg_replace( '/\s*\r?\n\s*/', ' ', $html );
	$html = trim( (string) $html );

	// Restore protected blocks.
	return strtr( $html, $blocks );
}

/**
 * Render a managed section by key.
 *
 * In DB mode, a non-empty HTML override is printed as sanitised markup;
 * otherwise the template-part file is included (running its PHP, e.g. EDD).
 *
 * @param string $key Section key.
 */
function lavtheme_render_section( $key ) {
	$sections = lavtheme_cs_sections();
	if ( ! isset( $sections[ $key ] ) ) {
		return;
	}

	if ( 'db' === lavtheme_cs_mode() && ! empty( $sections[ $key ]['html'] ) ) {
		$override = get_option( lavtheme_cs_key( $key, 'html' ), '' );
		// Only use an override that is non-empty AND contains no raw PHP — PHP
		// cannot be executed safely in DB mode, so fall back to the file
		// (keeps dynamic sections like Products/EDD working). Bug 3 fix.
		if ( '' !== $override && false === strpos( $override, '<?php' ) && false === strpos( $override, '<?=' ) ) {
			echo lavtheme_cs_render_html( $override ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd inside.
			return;
		}
	}

	$file = $sections[ $key ]['file'];
	if ( $file ) {
		$path = get_theme_file_path( $file );
		if ( is_readable( $path ) ) {
			include $path;
		}
	}

	// Run the section's custom PHP (gated by LAVTHEME_ALLOW_PHP_SECTIONS).
	$php = get_option( lavtheme_cs_key( $key, 'php' ), '' );
	if ( '' !== trim( (string) $php ) ) {
		echo lavtheme_cs_run_php( $php ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Sanitise + expand a stored HTML override for safe output.
 *
 * @param string $html Raw stored markup.
 * @return string
 */
function lavtheme_cs_render_html( $html ) {
	$html = do_shortcode( (string) $html );
	return wp_kses( $html, lavtheme_kses_extended() );
}

/**
 * Compose the built-in BASE CSS from the per-section split files (inline mode).
 *
 * Reproduces what main.css used to deliver — global `:root`/CSS/background plus
 * each built-in section's CSS and Mobile CSS, in registry order — but sourced
 * from the editable split files. An explicit `_empty` marker on a tab omits its
 * default here (so an intentional clear of a built-in tab actually removes it).
 * Per-section OVERRIDES are layered afterwards by lavtheme_cs_head_css() at
 * wp_head priority 100, so they still win by cascade order — exactly as an
 * override beat main.css before. Returns '' when inline mode is rolled back.
 *
 * @return string
 */
function lavtheme_cs_builtin_base_css() {
	if ( ! lavtheme_cs_inline_css() ) {
		return '';
	}

	$css = '';

	// Global :root / shared CSS / background, in the original order.
	foreach ( array( 'root', 'css', 'bg' ) as $type ) {
		if ( '' !== get_option( lavtheme_cs_key( 'global', $type ) . '_empty', '' ) ) {
			continue; // explicitly cleared.
		}
		$css .= lavtheme_cs_default_value( 'global', $type ) . "\n";
	}

	// Each built-in section's CSS + Mobile CSS, in registry order.
	foreach ( lavtheme_cs_registry() as $r ) {
		$slug = $r['slug'];
		if ( 'global' === $slug || empty( $r['builtin'] ) ) {
			continue;
		}
		if ( '' === get_option( lavtheme_cs_key( $slug, 'css' ) . '_empty', '' ) ) {
			$css .= lavtheme_cs_default_value( $slug, 'css' ) . "\n";
		}
		if ( '' === get_option( lavtheme_cs_key( $slug, 'mcss' ) . '_empty', '' ) ) {
			$m = lavtheme_cs_default_value( $slug, 'mcss' );
			if ( '' !== trim( (string) $m ) ) {
				$css .= '@media (max-width:640px){' . $m . '}' . "\n";
			}
		}
	}

	return $css;
}

/**
 * Inject section + global CSS overrides in the head (after the base CSS).
 */
function lavtheme_cs_head_css() {
	if ( is_404() ) {
		return; // the standalone error page uses its own '404' context CSS.
	}
	$css = '';

	foreach ( array( 'root', 'css', 'bg' ) as $type ) {
		$val = get_option( lavtheme_cs_key( 'global', $type ), '' );
		if ( '' !== $val ) {
			$css .= $val . "\n";
		}
	}

	foreach ( lavtheme_cs_sections() as $key => $section ) {
		if ( 'global' === $key ) {
			continue;
		}
		$builtin = ! empty( $section['builtin'] );

		// Built-in section CSS already ships in the base layer (inline split files, or
		// main.css when rolled back), so inject only an override. Custom sections are
		// NOT in the base, so inject their file default when there is no override.
		$ckey = lavtheme_cs_key( $key, 'css' );
		$c    = get_option( $ckey, '' );
		if ( '' === $c && ! $builtin && '' === get_option( $ckey . '_empty', '' ) ) {
			$c = lavtheme_cs_default_value( $key, 'css' );
		}
		if ( '' !== $c ) {
			$css .= $c . "\n";
		}

		$mkey = lavtheme_cs_key( $key, 'mcss' );
		$m    = get_option( $mkey, '' );
		if ( '' === $m && ! $builtin && '' === get_option( $mkey . '_empty', '' ) ) {
			$m = lavtheme_cs_default_value( $key, 'mcss' );
		}
		if ( '' !== trim( (string) $m ) ) {
			$css .= '@media (max-width:640px){' . $m . '}' . "\n";
		}
	}

	if ( '' !== trim( $css ) ) {
		// All values pass through lavtheme_sanitize_css() on save.
		echo "<style id=\"lavtheme-cs-css\">\n" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'lavtheme_cs_head_css', 100 );

/**
 * Inject section + global JS in the footer.
 */
function lavtheme_cs_footer_js() {
	if ( is_404() ) {
		return; // the standalone error page uses its own '404' context JS (no header/footer DOM).
	}
	// One shared scope (exactly like the original single main.js) so helpers
	// such as go() and shared vars are visible to every section. Global runs
	// FIRST, then sections in order, so anything depending on go() works.
	// Section JS lives in ONE outer IIFE. The global block stays in that outer
	// scope so go() and shared vars are visible to everyone. Each *section*
	// block is wrapped in its OWN inner IIFE so its locals can't collide across
	// sections (important when several sections declare the same var), while
	// still closing over go(). Registry order drives section order.
	$order = array( 'global' );
	foreach ( lavtheme_cs_registry() as $r ) {
		if ( 'global' !== $r['slug'] ) {
			$order[] = $r['slug'];
		}
	}

	$global_code   = '';
	$section_codes = array();

	foreach ( $order as $key ) {
		$jkey = lavtheme_cs_key( $key, 'js' );
		$val  = get_option( $jkey, '' );
		// Fall back to the file default only when the user hasn't explicitly
		// cleared this tab (an "_empty" marker means: inject nothing).
		if ( '' === $val && '' === get_option( $jkey . '_empty', '' ) ) {
			$val = lavtheme_cs_default_value( $key, 'js' ); // assets/js/sections/<key>.js
		}
		$val = trim( (string) $val );
		if ( '' === $val ) {
			continue;
		}
		if ( 'global' === $key ) {
			$global_code = $val;
		} else {
			$section_codes[] = '/* ' . $key . " */\n(function(){\n" . $val . "\n})();";
		}
	}

	if ( '' === $global_code && empty( $section_codes ) ) {
		return;
	}

	$js = "(function(){\n/* global */\n" . $global_code . "\n" . implode( "\n", $section_codes ) . "\n})();";
	// JS is admin-authored (manage_options) and closing-tag neutralised on save.
	echo "<script id=\"lavtheme-cs-js\">\n" . $js . "\n</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'lavtheme_cs_footer_js', 100 );
