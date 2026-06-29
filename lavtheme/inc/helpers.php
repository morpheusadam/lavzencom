<?php
/**
 * Helper functions shared across the theme.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a single key from the unified options array.
 *
 * All theme options live in one option: get_option('lavtheme_settings').
 *
 * @param string $key     Option key.
 * @param mixed  $default Fallback when the key is unset/empty.
 * @return mixed
 */
function lavtheme_option( $key, $default = '' ) {
	static $settings = null;
	if ( null === $settings ) {
		$settings = get_option( 'lavtheme_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
	}

	if ( ! array_key_exists( $key, $settings ) ) {
		return $default;
	}

	$value = $settings[ $key ];
	if ( '' === $value || null === $value ) {
		return $default;
	}

	return $value;
}

/**
 * Render a template part with arguments.
 *
 * Thin wrapper around get_template_part() that keeps call sites tidy.
 *
 * @param string $slug Slug under template-parts/ (without extension).
 * @param array  $args Optional args passed to the part.
 */
function lavtheme_part( $slug, $args = array() ) {
	get_template_part( 'template-parts/' . $slug, null, $args );
}

/**
 * SVG-aware kses allowlist for sanitising user-supplied inline SVG/icons.
 *
 * Used by the (Phase 2) Icons tab. Defined here so templates can rely on it.
 *
 * @return array
 */
function lavtheme_svg_allowed_html() {
	$svg = array(
		'svg'            => array(
			'xmlns'       => true,
			'viewbox'     => true,
			'viewBox'     => true,
			'fill'        => true,
			'stroke'      => true,
			'stroke-width' => true,
			'stroke-linecap' => true,
			'stroke-linejoin' => true,
			'width'       => true,
			'height'      => true,
			'class'       => true,
			'aria-label'  => true,
			'role'        => true,
		),
		'path'           => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'opacity' => true ),
		'circle'         => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'rect'           => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'line'           => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true ),
		'polyline'       => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'polygon'        => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'g'              => array( 'fill' => true, 'stroke' => true, 'transform' => true ),
		'defs'           => array(),
		'lineargradient' => array( 'id' => true, 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true ),
		'stop'           => array( 'offset' => true, 'stop-color' => true, 'stop-opacity' => true ),
		'ellipse'        => array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true ),
	);

	return $svg;
}

/**
 * Extended kses allowlist for HTML overrides (markup + inline SVG + data-*).
 *
 * @return array
 */
function lavtheme_kses_extended() {
	$allowed = wp_kses_allowed_html( 'post' );
	$svg     = lavtheme_svg_allowed_html();
	$common  = array(
		'class'        => true,
		'id'           => true,
		'style'        => true,
		'data-scroll'  => true,
		'data-label'   => true,
		'data-to'      => true,
		'data-suffix'  => true,
		'data-dec'     => true,
		'data-clicks'  => true,
		'aria-label'   => true,
		'aria-hidden'  => true,
		'aria-expanded' => true,
		'role'         => true,
		'tabindex'     => true,
		'target'       => true,
		'rel'          => true,
	);

	foreach ( array( 'div', 'span', 'a', 'p', 'ul', 'ol', 'li', 'button', 'section', 'article', 'header', 'footer', 'nav', 'aside', 'main', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'img', 'i', 'b', 'strong', 'em', 'small', 'time', 'label', 'input', 'form' ) as $tag ) {
		$allowed[ $tag ] = isset( $allowed[ $tag ] ) && is_array( $allowed[ $tag ] )
			? array_merge( $allowed[ $tag ], $common )
			: $common;
	}

	return apply_filters( 'lavtheme_kses_extended', array_merge( $allowed, $svg ) );
}

/**
 * Is custom PHP-in-section execution unlocked via wp-config?
 *
 * @return bool
 */
function lavtheme_cs_php_allowed() {
	return defined( 'LAVTHEME_ALLOW_PHP_SECTIONS' ) && LAVTHEME_ALLOW_PHP_SECTIONS;
}

/**
 * Validate PHP syntax without executing (token_get_all is shell-free).
 *
 * @param string $code  Code (may be mixed HTML/PHP).
 * @param string $error Filled with the parse error on failure.
 * @return bool
 */
function lavtheme_cs_check_php( $code, &$error ) {
	$error = '';
	$wrapped = "<?php\n" . $code; // ensure a PHP context for the parser.
	if ( function_exists( 'token_get_all' ) && defined( 'TOKEN_PARSE' ) ) {
		try {
			token_get_all( $wrapped, TOKEN_PARSE );
			return true;
		} catch ( \ParseError $e ) {
			$error = $e->getMessage() . ' (line ' . $e->getLine() . ')';
			return false;
		} catch ( \Throwable $e ) {
			$error = $e->getMessage();
			return false;
		}
	}
	return true;
}

/**
 * Execute a section's custom PHP in an isolated scope and return its output.
 *
 * Locked unless LAVTHEME_ALLOW_PHP_SECTIONS is defined. A Throwable in one
 * section is caught so it cannot break the whole page (PHP 7+ Error covers
 * undefined functions, type errors, etc.). Output is buffered per section.
 *
 * @param string $code Stored code (mixed HTML/PHP, like a template).
 * @return string
 */
function lavtheme_cs_run_php( $code ) {
	if ( '' === trim( (string) $code ) || ! lavtheme_cs_php_allowed() ) {
		return '';
	}
	// The PHP tab holds PURE PHP (use echo to output). If it embeds <?php islands
	// (template style) we run it in template mode instead.
	$tpl = ( false !== strpos( $code, '<?php' ) || false !== strpos( $code, '<?=' ) );
	$run = static function () use ( $code, $tpl ) {
		// phpcs:ignore Squiz.PHP.Eval.Discouraged -- gated by wp-config constant + manage_options + syntax check.
		eval( $tpl ? ( '?>' . $code ) : $code );
	};
	ob_start();
	try {
		$run();
	} catch ( \Throwable $e ) {
		echo '<!-- lavtheme PHP section error: ' . esc_html( $e->getMessage() ) . ' -->';
	}
	return (string) ob_get_clean();
}

/**
 * Inline-CSS mode.
 *
 * When ON (default), the theme's built-in CSS is composed from the per-section
 * split files and delivered inline under the `lavtheme-main` handle, instead of
 * enqueuing the monolithic `assets/css/main.css`. This makes every built-in CSS
 * tab (Global + sections) fully editable AND removable in Code Studio. The split
 * files reproduce main.css rule-for-rule (verified), so the rendered page is
 * unchanged.
 *
 * Instant rollback: add `define( 'LAVTHEME_DISABLE_INLINE_CSS', true );` to
 * wp-config.php and the theme re-enqueues main.css exactly as before (no upload
 * needed — main.css stays on disk).
 *
 * @return bool
 */
function lavtheme_cs_inline_css() {
	$on = ! ( defined( 'LAVTHEME_DISABLE_INLINE_CSS' ) && LAVTHEME_DISABLE_INLINE_CSS );
	return (bool) apply_filters( 'lavtheme_cs_inline_css', $on );
}

/**
 * Sanitise a CSS string: strip tags (so no </style> survives) and dangerous tokens.
 *
 * @param string $css Raw CSS.
 * @return string
 */
function lavtheme_sanitize_css( $css ) {
	$css = wp_strip_all_tags( (string) $css );
	$css = preg_replace( '/(expression\s*\(|javascript\s*:|behavior\s*:|@import|<\/?script)/i', '', $css );
	return trim( (string) $css );
}
