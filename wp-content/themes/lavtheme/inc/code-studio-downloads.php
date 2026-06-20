<?php
/**
 * Theme Code Studio — EDD download contexts (template + specific product).
 *
 * Two editable levels for the single-download "page type":
 *   - dl-template : applies to EVERY single download (is_singular('download')).
 *   - dl-<ID>     : applies to ONE specific product (get_the_ID() === ID).
 * On a product page BOTH layers render: template wraps the product, which wraps
 * the real content. Each context has the same full editor (sections / Global /
 * Schema / Page Content / PHP) as a page, with its own namespaced registry.
 *
 * Schema is fully editable JSON-LD with {{product_*}} placeholders that are
 * replaced with the real product's data at render time.
 *
 * Mirrors the page module but is kept separate so the tested page system is
 * untouched. Plugin templates are never overridden.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* ============================ context plumbing ============================ */

/** @return bool */
function lavtheme_cs_dl_is_template( $ctx ) {
	return 'dl-template' === $ctx;
}

/** Validate a dl context key. */
function lavtheme_cs_dl_valid( $ctx ) {
	if ( 'blog' === $ctx ) {
		return true; // the blog always exists.
	}
	if ( 'shop' === $ctx ) {
		// The shop (download archive) reuses the dl context plumbing.
		return post_type_exists( 'download' );
	}
	if ( 'dl-template' === $ctx ) {
		return post_type_exists( 'download' );
	}
	if ( 0 === strpos( $ctx, 'dl-' ) ) {
		$p = get_post( absint( substr( $ctx, 3 ) ) );
		return $p && 'download' === $p->post_type && 'publish' === $p->post_status;
	}
	return false;
}

/** Registry option name for a dl context. */
function lavtheme_cs_dl_regopt( $ctx ) {
	return 'lavtheme_cs_registry_' . str_replace( '-', '_', sanitize_key( $ctx ) );
}

/** Field option key for a dl context. */
function lavtheme_cs_dl_key( $ctx, $slug, $type ) {
	return 'lavtheme_cs_' . str_replace( '-', '_', sanitize_key( $ctx ) ) . '_' . sanitize_key( $slug ) . '_' . sanitize_key( $type );
}

/** The product id whose post_content is editable (0 for the template). */
function lavtheme_cs_dl_post_id( $ctx ) {
	return ( 0 === strpos( $ctx, 'dl-' ) && 'dl-template' !== $ctx ) ? absint( substr( $ctx, 3 ) ) : 0;
}

/** Published EDD downloads, id => title. */
function lavtheme_cs_dl_products() {
	if ( ! post_type_exists( 'download' ) ) {
		return array();
	}
	$out  = array();
	$rows = get_posts( array( 'post_type' => 'download', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'suppress_filters' => true ) );
	foreach ( $rows as $p ) {
		$out[ $p->ID ] = '' !== $p->post_title ? $p->post_title : ( '#' . $p->ID );
	}
	return $out;
}

/* ============================ registry & values =========================== */

function lavtheme_cs_dl_builtin( $ctx ) {
	if ( 'shop' === $ctx || 'blog' === $ctx ) {
		// Archive context: Global (CSS/JS/Background) + the editable Template.
		return array(
			array( 'slug' => 'global', 'label' => 'Global (this context)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false ),
			array( 'slug' => 'design', 'label' => 'Template (PHP/HTML)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false ),
		);
	}
	$tpl  = lavtheme_cs_dl_is_template( $ctx );
	$rows = array(
		array( 'slug' => 'global', 'label' => 'Global (this context)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false ),
		array( 'slug' => 'schema', 'label' => 'Schema (Product)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false ),
	);
	if ( $tpl ) {
		// The whole product template body, editable as PHP/HTML (file default + override).
		$rows[] = array( 'slug' => 'design', 'label' => 'Template (PHP/HTML)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false );
	}
	$rows[] = array( 'slug' => 'content', 'label' => $tpl ? 'Product Content (each product)' : 'Page Content', 'zone' => 'content', 'builtin' => true, 'deletable' => false, 'html' => ! $tpl, 'pagecontent' => true, 'placeholder' => $tpl, 'placement' => 'inline' );
	return $rows;
}

function lavtheme_cs_dl_registry( $ctx ) {
	$opt = lavtheme_cs_dl_regopt( $ctx );
	$reg = get_option( $opt, null );
	if ( ! is_array( $reg ) || empty( $reg ) ) {
		$reg = lavtheme_cs_dl_builtin( $ctx );
		update_option( $opt, $reg );
		return $reg;
	}

	// Migration: ensure the editable "design" (template body) section exists on
	// the template level for registries created before it was introduced.
	if ( lavtheme_cs_dl_is_template( $ctx ) && ! in_array( 'design', wp_list_pluck( $reg, 'slug' ), true ) ) {
		$design   = array( 'slug' => 'design', 'label' => 'Template (PHP/HTML)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false );
		$new      = array();
		$inserted = false;
		foreach ( $reg as $r ) {
			$new[] = $r;
			if ( ! $inserted && isset( $r['slug'] ) && 'schema' === $r['slug'] ) {
				$new[]    = $design;
				$inserted = true;
			}
		}
		if ( ! $inserted ) {
			array_unshift( $new, $design );
		}
		$reg = $new;
		update_option( $opt, $reg );
	}

	return $reg;
}

function lavtheme_cs_dl_registry_save( $ctx, $reg ) {
	update_option( lavtheme_cs_dl_regopt( $ctx ), array_values( $reg ) );
}

function lavtheme_cs_dl_fields( $rec, $ctx ) {
	if ( 'global' === $rec['slug'] ) {
		return array( 'css' => 'CSS', 'js' => 'JS', 'bg' => 'Background CSS' );
	}
	if ( 'schema' === $rec['slug'] ) {
		return array( 'json' => 'JSON-LD Schema' );
	}
	if ( 'design' === $rec['slug'] ) {
		return array( 'php' => 'Template (PHP/HTML)' );
	}
	if ( ! empty( $rec['pagecontent'] ) ) {
		// Template content is a non-editable anchor; specific edits the product post_content.
		return lavtheme_cs_dl_is_template( $ctx ) ? array() : array( 'html' => 'Page Content (post_content)' );
	}
	return array( 'html' => 'HTML', 'css' => 'CSS', 'js' => 'JS', 'php' => 'PHP' );
}

/** Default editable Product JSON-LD with {{tokens}}. */
function lavtheme_cs_dl_schema_default() {
	$data = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Product',
		'name'     => '{{product_name}}',
		'url'      => '{{product_url}}',
		'image'    => '{{product_image}}',
		'sku'      => '{{product_id}}',
		'offers'   => array(
			'@type'         => 'Offer',
			'price'         => '{{product_price}}',
			'priceCurrency' => '{{product_currency}}',
			'url'           => '{{product_url}}',
			'availability'  => 'https://schema.org/InStock',
		),
	);
	return (string) wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/**
 * Real theme file backing a dl-template editor (so editors are never empty and
 * the page renders from a single source: override-or-file). Template level only.
 *
 * @return string Relative theme path, or '' when no file backs this field.
 */
function lavtheme_cs_dl_default_path( $ctx, $slug, $type ) {
	if ( 'shop' === $ctx ) {
		if ( 'global' === $slug && 'css' === $type ) {
			return 'assets/css/shop.css';
		}
		if ( 'global' === $slug && 'js' === $type ) {
			return 'assets/js/shop.js';
		}
		if ( 'design' === $slug && 'php' === $type ) {
			return 'template-parts/shop.php';
		}
		return '';
	}
	if ( 'blog' === $ctx ) {
		if ( 'global' === $slug && 'css' === $type ) {
			return 'assets/css/blog.css';
		}
		if ( 'global' === $slug && 'js' === $type ) {
			return 'assets/js/blog.js';
		}
		if ( 'design' === $slug && 'php' === $type ) {
			return 'template-parts/blog.php';
		}
		return '';
	}
	if ( 'dl-template' !== $ctx ) {
		return '';
	}
	if ( 'global' === $slug && 'css' === $type ) {
		return 'assets/css/single-product.css';
	}
	if ( 'global' === $slug && 'js' === $type ) {
		return 'assets/js/single-product.js';
	}
	if ( 'design' === $slug && 'php' === $type ) {
		return 'template-parts/single-download-body.php';
	}
	return '';
}

/** File default contents for a dl-template editor. */
function lavtheme_cs_dl_default_value( $ctx, $slug, $type ) {
	$rel = lavtheme_cs_dl_default_path( $ctx, $slug, $type );
	if ( '' === $rel ) {
		return '';
	}
	$path = get_theme_file_path( $rel );
	return is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

function lavtheme_cs_dl_get( $ctx, $slug, $type ) {
	if ( 'content' === $slug && 'html' === $type ) {
		$pid = lavtheme_cs_dl_post_id( $ctx );
		$p   = $pid ? get_post( $pid ) : null;
		return $p ? (string) $p->post_content : '';
	}
	$key    = lavtheme_cs_dl_key( $ctx, $slug, $type );
	$stored = get_option( $key, null );
	if ( null !== $stored && '' !== $stored ) {
		return (string) $stored;
	}
	// Intentional clear (empty save) — honour it, don't fall back to the file.
	if ( '' === $stored && '' !== get_option( $key . '_empty', '' ) ) {
		return '';
	}
	$def = lavtheme_cs_dl_default_value( $ctx, $slug, $type );
	if ( '' !== $def ) {
		return $def;
	}
	if ( 'schema' === $slug && 'json' === $type ) {
		return lavtheme_cs_dl_schema_default();
	}
	return '';
}

/**
 * Run the editable Template (PHP/HTML) override for the current product, if any.
 *
 * Returns the buffered output when an override exists AND PHP sections are
 * unlocked; otherwise '' so the loader includes the real body file. A runtime
 * error is caught inside lavtheme_cs_run_php; if it yields no output we also
 * fall back to the file (never blank the product page).
 *
 * @return string
 */
function lavtheme_cs_dl_template_body() {
	if ( ! is_singular( 'download' ) || ! lavtheme_cs_php_allowed() ) {
		return '';
	}
	$override = (string) get_option( lavtheme_cs_dl_key( 'dl-template', 'design', 'php' ), '' );
	if ( '' === trim( $override ) ) {
		return '';
	}
	$out = lavtheme_cs_run_php( $override );
	return '' !== trim( $out ) ? $out : '';
}

/** Replace {{product_*}} tokens with the real product's data. */
function lavtheme_cs_dl_fill_tokens( $str, $download_id ) {
	if ( ! $download_id || '' === $str ) {
		return $str;
	}
	$price = function_exists( 'edd_price' ) ? wp_strip_all_tags( edd_price( $download_id, false ) ) : '';
	$img   = has_post_thumbnail( $download_id ) ? get_the_post_thumbnail_url( $download_id, 'large' ) : '';
	$cur   = function_exists( 'edd_get_currency' ) ? edd_get_currency() : 'USD';
	return strtr(
		$str,
		array(
			'{{product_name}}'     => get_the_title( $download_id ),
			'{{product_url}}'      => get_permalink( $download_id ),
			'{{product_price}}'    => $price,
			'{{product_image}}'    => $img,
			'{{product_currency}}' => $cur,
			'{{product_id}}'       => (string) $download_id,
		)
	);
}

/* ============================ front-end render ============================ */

/** dl contexts that apply to the current request (outer → inner), with the product id. */
function lavtheme_cs_dl_active( &$pid ) {
	$pid = 0;
	if ( is_admin() || ! is_singular( 'download' ) ) {
		return array();
	}
	$pid = (int) get_the_ID();
	return array( 'dl-template', 'dl-' . $pid );
}

function lavtheme_cs_dl_render_section( $ctx, $pid, $r ) {
	$html = (string) get_option( lavtheme_cs_dl_key( $ctx, $r['slug'], 'html' ), '' );
	$out  = '' !== trim( $html ) ? lavtheme_cs_render_html( lavtheme_cs_dl_fill_tokens( $html, $pid ) ) : '';
	$out .= lavtheme_cs_run_php( (string) get_option( lavtheme_cs_dl_key( $ctx, $r['slug'], 'php' ), '' ) );
	return $out;
}

/** Collect one context's content-zone sections into buckets (relative to its content anchor). */
function lavtheme_cs_dl_collect( $ctx, $pid, &$before, &$after, &$left, &$right, &$replace, &$has_r, &$wrap, &$has_w, &$has_custom ) {
	$before = ''; $after = ''; $left = ''; $right = ''; $replace = ''; $has_r = false; $wrap = ''; $has_w = false; $has_custom = false;
	$reg = get_option( lavtheme_cs_dl_regopt( $ctx ), null );
	if ( ! is_array( $reg ) ) {
		return;
	}
	$passed = false;
	foreach ( $reg as $r ) {
		if ( ( isset( $r['zone'] ) ? $r['zone'] : 'content' ) !== 'content' ) {
			continue;
		}
		if ( ! empty( $r['pagecontent'] ) ) {
			$passed = true; // the content anchor: sections after this go "after".
			continue;
		}
		$pl = isset( $r['placement'] ) ? $r['placement'] : 'inline';
		if ( 'replace' === $pl ) { $has_r = true; $replace .= lavtheme_cs_dl_render_section( $ctx, $pid, $r ); continue; }
		if ( 'wrap' === $pl ) { $has_w = true; $wrap .= lavtheme_cs_dl_render_section( $ctx, $pid, $r ); continue; }
		$rendered = lavtheme_cs_dl_render_section( $ctx, $pid, $r );
		if ( '' === trim( $rendered ) ) { continue; }
		$has_custom = true;
		if ( 'sidebar-left' === $pl ) { $left .= $rendered; }
		elseif ( 'sidebar-right' === $pl ) { $right .= $rendered; }
		elseif ( $passed ) { $after .= $rendered; }
		else { $before .= $rendered; }
	}
}

function lavtheme_cs_dl_the_content( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! is_singular( 'download' ) ) {
		return $content;
	}
	$pid  = 0;
	$ctxs = lavtheme_cs_dl_active( $pid );
	if ( empty( $ctxs ) ) {
		return $content;
	}

	$before = ''; $left = ''; $right = ''; $replace = ''; $wrap = ''; $has_r = false; $has_w = false; $has_custom = false;
	$after_stack = array();
	foreach ( $ctxs as $ctx ) { // template (outer) then specific (inner)
		lavtheme_cs_dl_collect( $ctx, $pid, $cb, $ca, $cl, $cr, $crep, $chr, $cwrap, $chw, $cc );
		$before        .= $cb;
		$after_stack[]  = $ca;
		$left          .= $cl;
		$right         .= $cr;
		if ( $chr ) { $has_r = true; $replace .= $crep; }
		if ( $chw ) { $has_w = true; $wrap .= $cwrap; }
		$has_custom = $has_custom || $cc;
	}
	if ( ! $has_custom && ! $has_r && ! $has_w ) {
		return $content;
	}
	$after = implode( '', array_reverse( $after_stack ) ); // specific_after then template_after

	$content_out = $has_r ? $replace : $content;
	if ( $has_w ) {
		$content_out = ( false !== strpos( $wrap, '[lavtheme_content]' ) )
			? str_replace( '[lavtheme_content]', $content_out, $wrap )
			: $wrap . $content_out;
	}
	$main = $before . $content_out . $after;

	if ( '' === $left && '' === $right ) {
		return $main;
	}
	$classes = 'lavcs-pagewrap' . ( '' !== $left ? ' has-left' : '' ) . ( '' !== $right ? ' has-right' : '' );
	$out     = '<div class="' . esc_attr( $classes ) . '">';
	if ( '' !== $left ) {
		$out .= '<aside class="lavcs-side lavcs-side-left">' . $left . '</aside>';
	}
	$out .= '<div class="lavcs-col-main">' . $main . '</div>';
	if ( '' !== $right ) {
		$out .= '<aside class="lavcs-side lavcs-side-right">' . $right . '</aside>';
	}
	$out .= '</div>';
	return $out;
}
add_filter( 'the_content', 'lavtheme_cs_dl_the_content', 21 );

function lavtheme_cs_dl_head() {
	$pid  = 0;
	$ctxs = lavtheme_cs_dl_active( $pid );
	if ( empty( $ctxs ) ) {
		return;
	}
	$css = function_exists( 'lavtheme_cs_page_layout_css' ) ? lavtheme_cs_page_layout_css() : '';
	foreach ( $ctxs as $ctx ) {
		$reg = get_option( lavtheme_cs_dl_regopt( $ctx ), null );
		if ( ! is_array( $reg ) ) {
			continue;
		}
		foreach ( $reg as $r ) {
			if ( 'schema' === $r['slug'] || 'design' === $r['slug'] ) {
				continue;
			}
			// Override-or-file (single source). Global CSS defaults to single-product.css.
			$c = (string) lavtheme_cs_dl_get( $ctx, $r['slug'], 'css' );
			if ( 'global' === $r['slug'] ) {
				$bg = (string) lavtheme_cs_dl_get( $ctx, 'global', 'bg' );
				if ( '' !== trim( $bg ) ) {
					$c .= "\n" . $bg;
				}
			}
			if ( '' !== trim( $c ) ) {
				$css .= "\n" . lavtheme_cs_dl_fill_tokens( $c, $pid );
			}
		}
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-dl-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// Schema: specific option > template option > generic Product default. Token-filled.
	$sp = (string) get_option( lavtheme_cs_dl_key( 'dl-' . $pid, 'schema', 'json' ), '' );
	$st = (string) get_option( lavtheme_cs_dl_key( 'dl-template', 'schema', 'json' ), '' );
	$schema = '' !== trim( $sp ) ? $sp : ( '' !== trim( $st ) ? $st : lavtheme_cs_dl_schema_default() );
	$schema = lavtheme_cs_dl_fill_tokens( trim( $schema ), $pid );
	if ( '' !== $schema ) {
		json_decode( $schema );
		if ( JSON_ERROR_NONE === json_last_error() ) {
			echo '<script type="application/ld+json">' . str_replace( '</', '<\/', $schema ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
add_action( 'wp_head', 'lavtheme_cs_dl_head', 6 );

function lavtheme_cs_dl_footer() {
	$pid  = 0;
	$ctxs = lavtheme_cs_dl_active( $pid );
	if ( empty( $ctxs ) ) {
		return;
	}
	$js = '';
	foreach ( $ctxs as $ctx ) {
		$reg = get_option( lavtheme_cs_dl_regopt( $ctx ), null );
		if ( ! is_array( $reg ) ) {
			continue;
		}
		foreach ( $reg as $r ) {
			if ( 'schema' === $r['slug'] || 'design' === $r['slug'] ) {
				continue;
			}
			// Override-or-file (single source). Global JS defaults to single-product.js.
			$j = (string) lavtheme_cs_dl_get( $ctx, $r['slug'], 'js' );
			if ( '' !== trim( $j ) ) {
				$js .= ';(function(){' . lavtheme_cs_dl_fill_tokens( $j, $pid ) . '})();';
			}
		}
	}
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-dl-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_footer', 'lavtheme_cs_dl_footer', 101 );

/**
 * Syntax-check TEMPLATE-style PHP (mixed HTML + <?php islands, may start with
 * <?php). Unlike lavtheme_cs_check_php() it does NOT prepend an opening tag, so
 * code that already opens with <?php (the design body) validates correctly.
 *
 * @param string $code  Template code.
 * @param string $error Filled with the parse error on failure.
 * @return bool
 */
function lavtheme_cs_dl_check_template( $code, &$error ) {
	$error = '';
	if ( function_exists( 'token_get_all' ) && defined( 'TOKEN_PARSE' ) ) {
		try {
			token_get_all( (string) $code, TOKEN_PARSE );
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

/* ================================= AJAX ================================== */

function lavtheme_cs_dl_ctx() {
	$ctx = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : '';
	if ( ! lavtheme_cs_dl_valid( $ctx ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown context.', 'lavtheme' ) ), 400 );
	}
	return $ctx;
}

function lavtheme_cs_dl_ajax_load() {
	lavtheme_cs_guard();
	$ctx = lavtheme_cs_dl_ctx();
	$pid = lavtheme_cs_dl_post_id( $ctx );
	$reg = lavtheme_cs_dl_registry( $ctx );

	$sections = array();
	$data     = array();
	foreach ( $reg as $r ) {
		$fields = lavtheme_cs_dl_fields( $r, $ctx );
		$sections[] = array(
			'slug'        => $r['slug'],
			'label'       => $r['label'],
			'zone'        => isset( $r['zone'] ) ? $r['zone'] : 'content',
			'deletable'   => ! empty( $r['deletable'] ),
			'pagecontent' => ! empty( $r['pagecontent'] ),
			'placeholder' => ! empty( $r['placeholder'] ),
			'placeable'   => ( ( isset( $r['zone'] ) ? $r['zone'] : 'content' ) === 'content' ) && empty( $r['pagecontent'] ),
			'placement'   => isset( $r['placement'] ) ? $r['placement'] : 'inline',
			'fields'      => $fields,
		);
		foreach ( $fields as $type => $label ) {
			$data[ $r['slug'] ][ $type ] = lavtheme_cs_dl_get( $ctx, $r['slug'], $type );
		}
	}
	$short = $pid ? lavtheme_cs_has_plugin_shortcode( get_post( $pid )->post_content ) : false;

	wp_send_json_success(
		array(
			'context'    => $ctx,
			'sections'   => $sections,
			'data'       => $data,
			'shortcode'  => $short,
			'placements' => function_exists( 'lavtheme_cs_placements' ) ? lavtheme_cs_placements() : array(),
			'phpAllowed' => lavtheme_cs_php_allowed(),
			'isTemplate' => lavtheme_cs_dl_is_template( $ctx ),
		)
	);
}
add_action( 'wp_ajax_lavtheme_cs_dl_load', 'lavtheme_cs_dl_ajax_load' );

function lavtheme_cs_dl_ajax_save() {
	lavtheme_cs_guard();
	$ctx     = lavtheme_cs_dl_ctx();
	$slug    = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$content = isset( $_POST['content'] ) ? (string) wp_unslash( $_POST['content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	// Page Content (specific product only) → update the real post_content.
	if ( 'content' === $slug && 'html' === $type ) {
		$pid = lavtheme_cs_dl_post_id( $ctx );
		if ( ! $pid ) {
			wp_send_json_error( array( 'message' => __( 'The template level has no editable content.', 'lavtheme' ) ) );
		}
		update_option( 'lavtheme_cs_pcbak_dl_' . $pid, get_post( $pid )->post_content );
		$res = wp_update_post( array( 'ID' => $pid, 'post_content' => $content ), true );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success( array( 'message' => __( 'Product content updated.', 'lavtheme' ) ) );
	}

	if ( 'php' === $type ) {
		$err = '';
		if ( '' !== trim( $content ) ) {
			// The "design" body is template-style (starts with <?php, has islands);
			// other PHP tabs are pure PHP. Validate each with the right checker.
			$ok = ( 'design' === $slug )
				? lavtheme_cs_dl_check_template( $content, $err )
				: lavtheme_cs_check_php( $content, $err );
			if ( ! $ok ) {
				wp_send_json_error( array( 'message' => __( 'PHP syntax error — not saved: ', 'lavtheme' ) . $err ) );
			}
		}
		$key = lavtheme_cs_dl_key( $ctx, $slug, 'php' );
		update_option( $key . '_bak', get_option( $key, '' ) );
		update_option( $key, (string) $content );
		if ( '' === trim( (string) $content ) ) {
			update_option( $key . '_empty', '1' );
		} else {
			delete_option( $key . '_empty' );
		}
		wp_send_json_success( array( 'message' => lavtheme_cs_php_allowed() ? __( 'PHP saved and active.', 'lavtheme' ) : __( 'PHP saved, but NOT running — add define(\'LAVTHEME_ALLOW_PHP_SECTIONS\', true) to wp-config.php.', 'lavtheme' ) ) );
	}

	if ( 'schema' === $slug && 'json' === $type ) {
		$trim = trim( $content );
		if ( '' !== $trim ) {
			// Validate after replacing tokens with sample values (tokens alone aren't valid JSON values everywhere).
			json_decode( $trim );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				wp_send_json_error( array( 'message' => __( 'Invalid JSON: ', 'lavtheme' ) . json_last_error_msg() ) );
			}
		}
		update_option( lavtheme_cs_dl_key( $ctx, 'schema', 'json' ), $trim );
		wp_send_json_success( array( 'message' => __( 'Schema saved.', 'lavtheme' ) ) );
	}

	if ( in_array( $type, array( 'css', 'bg' ), true ) ) {
		$clean = lavtheme_sanitize_css( $content );
	} elseif ( 'js' === $type ) {
		$clean = str_ireplace( '</script', '<\/script', $content );
	} else {
		$clean = (string) $content;
	}
	$key = lavtheme_cs_dl_key( $ctx, $slug, $type );
	update_option( $key, $clean );
	// Honour an intentional clear for css/js/bg (render injects nothing). HTML is
	// exempt so an empty HTML save still falls back to the file.
	if ( in_array( $type, array( 'css', 'bg', 'js' ), true ) ) {
		if ( '' === trim( (string) $clean ) ) {
			update_option( $key . '_empty', '1' );
		} else {
			delete_option( $key . '_empty' );
		}
	}
	wp_send_json_success( array( 'message' => __( 'Saved.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_dl_save', 'lavtheme_cs_dl_ajax_save' );

function lavtheme_cs_dl_ajax_addsection() {
	lavtheme_cs_guard();
	$ctx   = lavtheme_cs_dl_ctx();
	$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
	$label = '' !== $label ? $label : __( 'New Section', 'lavtheme' );
	$base  = preg_replace( '/[^a-z0-9_-]/', '', sanitize_title( $label ) );
	$base  = '' !== $base ? $base : 'block';
	$reg   = lavtheme_cs_dl_registry( $ctx );
	$used  = wp_list_pluck( $reg, 'slug' );
	$slug  = $base;
	$i     = 2;
	while ( in_array( $slug, $used, true ) || in_array( $slug, array( 'global', 'schema', 'content' ), true ) ) {
		$slug = $base . '-' . $i;
		$i++;
	}
	if ( function_exists( 'lavtheme_cs_starter_content' ) ) {
		$s    = lavtheme_cs_starter_content( $slug, $label );
		$html = preg_replace( '/^<\?php[^?]*\?>\s*/', '', $s['html'] );
		update_option( lavtheme_cs_dl_key( $ctx, $slug, 'html' ), $html );
		update_option( lavtheme_cs_dl_key( $ctx, $slug, 'css' ), $s['css'] );
		update_option( lavtheme_cs_dl_key( $ctx, $slug, 'js' ), $s['js'] );
	}
	$reg[] = array( 'slug' => $slug, 'label' => $label, 'zone' => 'content', 'builtin' => false, 'deletable' => true, 'html' => true, 'pagecontent' => false, 'placement' => 'inline' );
	lavtheme_cs_dl_registry_save( $ctx, $reg );
	wp_send_json_success( array( 'message' => __( 'Section added.', 'lavtheme' ), 'slug' => $slug ) );
}
add_action( 'wp_ajax_lavtheme_cs_dl_addsection', 'lavtheme_cs_dl_ajax_addsection' );

function lavtheme_cs_dl_ajax_rename() {
	lavtheme_cs_guard();
	$ctx   = lavtheme_cs_dl_ctx();
	$slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
	$reg   = lavtheme_cs_dl_registry( $ctx );
	foreach ( $reg as &$r ) {
		if ( $r['slug'] === $slug ) { $r['label'] = $label; break; }
	}
	unset( $r );
	lavtheme_cs_dl_registry_save( $ctx, $reg );
	wp_send_json_success( array( 'message' => __( 'Renamed.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_dl_rename', 'lavtheme_cs_dl_ajax_rename' );

function lavtheme_cs_dl_ajax_reorder() {
	lavtheme_cs_guard();
	$ctx   = lavtheme_cs_dl_ctx();
	$order = isset( $_POST['order'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['order'] ) ) : array();
	$reg   = lavtheme_cs_dl_registry( $ctx );
	$byid  = array();
	foreach ( $reg as $r ) { $byid[ $r['slug'] ] = $r; }
	$new = array();
	foreach ( $order as $slug ) {
		if ( isset( $byid[ $slug ] ) ) { $new[] = $byid[ $slug ]; unset( $byid[ $slug ] ); }
	}
	foreach ( $byid as $r ) { $new[] = $r; }
	lavtheme_cs_dl_registry_save( $ctx, $new );
	wp_send_json_success( array( 'message' => __( 'Order saved.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_dl_reorder', 'lavtheme_cs_dl_ajax_reorder' );

function lavtheme_cs_dl_ajax_delsection() {
	lavtheme_cs_guard();
	$ctx  = lavtheme_cs_dl_ctx();
	$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	if ( in_array( $slug, array( 'global', 'schema', 'content' ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'This section cannot be deleted.', 'lavtheme' ) ) );
	}
	foreach ( array( 'html', 'css', 'js', 'php' ) as $tp ) {
		delete_option( lavtheme_cs_dl_key( $ctx, $slug, $tp ) );
		delete_option( lavtheme_cs_dl_key( $ctx, $slug, $tp ) . '_bak' );
	}
	$reg = array_values( array_filter( lavtheme_cs_dl_registry( $ctx ), function ( $r ) use ( $slug ) { return $r['slug'] !== $slug; } ) );
	lavtheme_cs_dl_registry_save( $ctx, $reg );
	wp_send_json_success( array( 'message' => __( 'Section deleted.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_dl_delsection', 'lavtheme_cs_dl_ajax_delsection' );

function lavtheme_cs_dl_ajax_setplacement() {
	lavtheme_cs_guard();
	$ctx       = lavtheme_cs_dl_ctx();
	$slug      = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$placement = isset( $_POST['placement'] ) ? sanitize_key( wp_unslash( $_POST['placement'] ) ) : 'inline';
	if ( ! array_key_exists( $placement, lavtheme_cs_placements() ) ) {
		wp_send_json_error( array( 'message' => __( 'Bad placement.', 'lavtheme' ) ) );
	}
	$reg = lavtheme_cs_dl_registry( $ctx );
	foreach ( $reg as &$r ) {
		if ( $r['slug'] === $slug ) { $r['placement'] = $placement; break; }
	}
	unset( $r );
	lavtheme_cs_dl_registry_save( $ctx, $reg );
	wp_send_json_success( array( 'message' => __( 'Placement saved.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_dl_setplacement', 'lavtheme_cs_dl_ajax_setplacement' );

function lavtheme_cs_dl_ajax_pcrestore() {
	lavtheme_cs_guard();
	$ctx = lavtheme_cs_dl_ctx();
	$pid = lavtheme_cs_dl_post_id( $ctx );
	$bak = $pid ? get_option( 'lavtheme_cs_pcbak_dl_' . $pid, null ) : null;
	if ( ! $pid || null === $bak ) {
		wp_send_json_error( array( 'message' => __( 'Nothing to restore.', 'lavtheme' ) ) );
	}
	$cur = get_post( $pid )->post_content;
	wp_update_post( array( 'ID' => $pid, 'post_content' => $bak ) );
	update_option( 'lavtheme_cs_pcbak_dl_' . $pid, $cur );
	wp_send_json_success( array( 'content' => $bak, 'message' => __( 'Product content restored.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_dl_pcrestore', 'lavtheme_cs_dl_ajax_pcrestore' );

/**
 * On single download pages the theme provides its own editable Product schema.
 * Turn off EDD's HTML microdata to reduce overlap. (EDD 3.x may still emit its
 * own JSON-LD block; that's a plugin feature — disable it in EDD's settings if a
 * single schema is required.)
 */
function lavtheme_cs_dl_disable_edd_schema() {
	if ( is_singular( 'download' ) ) {
		add_filter( 'edd_add_schema_microdata', '__return_false' );
	}
}
add_action( 'wp', 'lavtheme_cs_dl_disable_edd_schema' );
