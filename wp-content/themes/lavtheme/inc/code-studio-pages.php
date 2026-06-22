<?php
/**
 * Theme Code Studio — per-PAGE contexts.
 *
 * Every published WordPress page becomes an editable context with its own
 * namespaced section system (independent of the Front Page). Each page has:
 *   - Global  (per-page CSS / JS / Background)            [fixed, non-deletable]
 *   - Schema  (per-page JSON-LD)                          [fixed, non-deletable]
 *   - Page Content (the real post_content from the DB)    [fixed, non-deletable]
 *   - any number of custom sections (DB-backed)           [add / reorder / delete]
 *
 * Custom sections render by filtering the_content (interleaved around the real
 * Page Content). This works on standard / shortcode pages but NOT on Elementor
 * canvas pages (which bypass the_content). Per-page CSS/JS/Schema/Page-Content
 * work on every page. No plugin templates are overridden.
 *
 * This module is fully separate from the Front Page system — the global
 * registry and front AJAX handlers are untouched.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * Page index
 * ====================================================================== */

/**
 * All published pages (excluding the static front page), with EDD labels.
 *
 * @return array id => array( id, title, edd )
 */
function lavtheme_cs_pages() {
	$front = (int) get_option( 'page_on_front' );
	$pages = get_posts(
		array(
			'post_type'        => 'page',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'suppress_filters' => true,
		)
	);

	$out = array();
	foreach ( $pages as $p ) {
		if ( (int) $p->ID === $front ) {
			continue;
		}
		$out[ $p->ID ] = array(
			'id'    => (int) $p->ID,
			'title' => '' !== $p->post_title ? $p->post_title : ( '#' . $p->ID ),
			'edd'   => lavtheme_cs_edd_page_label( (int) $p->ID ),
		);
	}
	return $out;
}

/**
 * If a page id is a known EDD/Woo page, return a short label, else ''.
 *
 * @param int $id Page id.
 * @return string
 */
function lavtheme_cs_edd_page_label( $id ) {
	if ( function_exists( 'edd_get_option' ) ) {
		$map = array(
			'purchase_page'        => 'EDD Checkout',
			'success_page'         => 'EDD Confirmation',
			'failure_page'         => 'EDD Failed',
			'purchase_history_page' => 'EDD Order History',
		);
		foreach ( $map as $opt => $label ) {
			if ( (int) edd_get_option( $opt ) === $id ) {
				return $label;
			}
		}
	}
	if ( function_exists( 'wc_get_page_id' ) ) {
		$wmap = array( 'shop' => 'Woo Shop', 'cart' => 'Woo Cart', 'checkout' => 'Woo Checkout' );
		foreach ( $wmap as $slug => $label ) {
			if ( (int) wc_get_page_id( $slug ) === $id ) {
				return $label;
			}
		}
	}
	return '';
}

/**
 * Does a published page id exist?
 *
 * @param int $id Page id.
 * @return bool
 */
function lavtheme_cs_page_exists( $id ) {
	$p = get_post( $id );
	return $p && 'page' === $p->post_type && 'publish' === $p->post_status;
}

/* =========================================================================
 * Per-page registry + option keys
 * ====================================================================== */

/**
 * Option name for a page registry.
 *
 * @param int $id Page id.
 * @return string
 */
function lavtheme_cs_page_registry_opt( $id ) {
	return 'lavtheme_cs_registry_page_' . absint( $id );
}

/**
 * Fixed (non-deletable) sections every page starts with.
 *
 * @return array
 */
function lavtheme_cs_page_builtin() {
	return array(
		array( 'slug' => 'global', 'label' => 'Global (this page)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false ),
		array( 'slug' => 'schema', 'label' => 'Schema (this page)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false ),
		array( 'slug' => 'design', 'label' => 'Template (this page)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false ),
		array( 'slug' => 'content', 'label' => 'Page Content', 'zone' => 'content', 'builtin' => true, 'deletable' => false, 'html' => true, 'pagecontent' => true ),
	);
}

/**
 * The registry for a page (seeded on first use).
 *
 * @param int $id Page id.
 * @return array
 */
function lavtheme_cs_page_registry( $id ) {
	$opt = lavtheme_cs_page_registry_opt( $id );
	$reg = get_option( $opt, null );
	if ( ! is_array( $reg ) || empty( $reg ) ) {
		$reg = lavtheme_cs_page_builtin();
		update_option( $opt, $reg );
		return $reg;
	}

	// Migration: add the unified "Template (this page)" section (after Schema) to
	// registries created before it existed.
	if ( ! in_array( 'design', wp_list_pluck( $reg, 'slug' ), true ) ) {
		$design   = array( 'slug' => 'design', 'label' => 'Template (this page)', 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'html' => false, 'pagecontent' => false );
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

/**
 * Save a page registry.
 *
 * @param int   $id  Page id.
 * @param array $reg Registry.
 */
function lavtheme_cs_page_registry_save( $id, $reg ) {
	update_option( lavtheme_cs_page_registry_opt( $id ), array_values( $reg ) );
}

/**
 * Namespaced option key for a page section field.
 *
 * @param int    $id   Page id.
 * @param string $slug Section slug.
 * @param string $type Field type.
 * @return string
 */
function lavtheme_cs_page_key( $id, $slug, $type ) {
	return 'lavtheme_cs_page_' . absint( $id ) . '_' . sanitize_key( $slug ) . '_' . sanitize_key( $type );
}

/**
 * Get a page section field value (with sensible defaults).
 *
 * @param int    $id   Page id.
 * @param string $slug Section slug.
 * @param string $type Field type.
 * @return string
 */
function lavtheme_cs_page_get( $id, $slug, $type ) {
	// Page Content reads the live post_content.
	if ( 'content' === $slug && 'html' === $type ) {
		$p = get_post( $id );
		return $p ? (string) $p->post_content : '';
	}

	$key    = lavtheme_cs_page_key( $id, $slug, $type );
	$stored = get_option( $key, null );
	if ( null !== $stored && '' !== $stored ) {
		return (string) $stored;
	}

	// The Template (design) section is pre-filled from the real render chain:
	// HTML/PHP ← the resolved page template, CSS/JS ← per-page layers (empty by
	// default), Mobile CSS ← extracted @640 layer. The body (html) always shows
	// the real template; css/js/mcss honour an intentional clear.
	if ( 'design' === $slug ) {
		if ( '' === $stored && '' !== get_option( $key . '_empty', '' ) && 'html' !== $type ) {
			return '';
		}
		return Lav_CS_Source_Reader::default_value( 'page-' . absint( $id ), $type );
	}

	return ( null !== $stored ) ? (string) $stored : '';
}

/**
 * Field map (type => label) for a page section.
 *
 * @param array $rec Registry record.
 * @return array
 */
function lavtheme_cs_page_fields( $rec ) {
	if ( 'global' === $rec['slug'] ) {
		return array( 'css' => 'CSS', 'js' => 'JS', 'bg' => 'Background CSS' );
	}
	if ( 'schema' === $rec['slug'] ) {
		return array( 'json' => 'JSON-LD Schema' );
	}
	if ( 'design' === $rec['slug'] ) {
		// Unified five tabs (HTML/PHP, CSS, JS, Mobile CSS, PHP) — the real code
		// that renders this page, resolved by Lav_CS_Source_Reader.
		return Lav_CS_Source_Reader::tabs();
	}
	if ( ! empty( $rec['pagecontent'] ) ) {
		return array( 'html' => 'Page Content (post_content)' );
	}
	return array( 'html' => 'HTML', 'css' => 'CSS', 'js' => 'JS', 'php' => 'PHP' );
}

/**
 * Placement choices for a content section.
 *
 * @return array
 */
function lavtheme_cs_placements() {
	return array(
		'inline'        => __( 'In content flow (drag to position)', 'lavtheme' ),
		'sidebar-left'  => __( 'Sidebar (left)', 'lavtheme' ),
		'sidebar-right' => __( 'Sidebar (right)', 'lavtheme' ),
		'replace'       => __( 'Replace content', 'lavtheme' ),
		'wrap'          => __( 'Wrap content', 'lavtheme' ),
	);
}

/**
 * Is a placement value "in the content flow" (rendered in registry order)?
 *
 * @param string $pl Placement.
 * @return bool
 */
function lavtheme_cs_is_inline_placement( $pl ) {
	return ! in_array( $pl, array( 'sidebar-left', 'sidebar-right', 'replace', 'wrap' ), true );
}

/* =========================================================================
 * Front-end rendering (only on the matching page)
 * ====================================================================== */

/**
 * Current page id when viewing a single page on the front end, else 0.
 *
 * @return int
 */
function lavtheme_cs_page_current_id() {
	if ( is_admin() || ! is_page() ) {
		return 0;
	}
	return (int) get_queried_object_id();
}

/**
 * Inject per-page Global CSS/Background + Schema into the head.
 */
function lavtheme_cs_page_head() {
	$id = lavtheme_cs_page_current_id();
	if ( ! $id ) {
		return;
	}
	$reg = get_option( lavtheme_cs_page_registry_opt( $id ), null );
	if ( ! is_array( $reg ) ) {
		$reg = array();
	}

	// Inject EVERY section's CSS (global bg too), not just the global section.
	$css = lavtheme_cs_page_layout_css();
	foreach ( $reg as $r ) {
		if ( 'schema' === $r['slug'] ) {
			continue;
		}
		$c = (string) get_option( lavtheme_cs_page_key( $id, $r['slug'], 'css' ), '' );
		if ( 'global' === $r['slug'] ) {
			$c .= "\n" . (string) get_option( lavtheme_cs_page_key( $id, 'global', 'bg' ), '' );
		}
		if ( 'design' === $r['slug'] ) {
			$c .= "\n" . (string) get_option( lavtheme_cs_page_key( $id, 'design', 'mcss' ), '' );
		}
		if ( '' !== trim( $c ) ) {
			$css .= "\n" . $c;
		}
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-page-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// Per-page schema; falls back to the site default when not set.
	$schema = trim( (string) get_option( lavtheme_cs_page_key( $id, 'schema', 'json' ), '' ) );
	if ( '' === $schema && function_exists( 'lavtheme_cs_get_schema' ) ) {
		$schema = trim( (string) lavtheme_cs_get_schema() );
	}
	if ( '' !== $schema ) {
		json_decode( $schema );
		if ( JSON_ERROR_NONE === json_last_error() ) {
			echo '<script type="application/ld+json">' . str_replace( '</', '<\/', $schema ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
add_action( 'wp_head', 'lavtheme_cs_page_head', 6 );

/**
 * Base CSS for the placement grid wrapper (responsive).
 *
 * @return string
 */
function lavtheme_cs_page_layout_css() {
	return '.lavcs-pagewrap{display:grid;gap:var(--lavcs-gap,28px);align-items:start}'
		. '.lavcs-pagewrap.has-right{grid-template-columns:minmax(0,1fr) var(--lavcs-side-w,300px)}'
		. '.lavcs-pagewrap.has-left{grid-template-columns:var(--lavcs-side-w,300px) minmax(0,1fr)}'
		. '.lavcs-pagewrap.has-left.has-right{grid-template-columns:var(--lavcs-side-w,300px) minmax(0,1fr) var(--lavcs-side-w,300px)}'
		. '@media(max-width:782px){.lavcs-pagewrap{display:flex;flex-direction:column}.lavcs-pagewrap .lavcs-col-main{order:0}.lavcs-pagewrap .lavcs-side{order:1}}';
}

/**
 * Inject EVERY section's JS into the footer (not just the global section).
 */
function lavtheme_cs_page_footer() {
	$id = lavtheme_cs_page_current_id();
	if ( ! $id ) {
		return;
	}
	$reg = get_option( lavtheme_cs_page_registry_opt( $id ), null );
	if ( ! is_array( $reg ) ) {
		return;
	}
	$js = '';
	foreach ( $reg as $r ) {
		$j = (string) get_option( lavtheme_cs_page_key( $id, $r['slug'], 'js' ), '' );
		if ( '' !== trim( $j ) ) {
			$js .= ';(function(){' . $j . '})();';
		}
	}
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-page-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_footer', 'lavtheme_cs_page_footer', 101 );

/* =========================================================================
 * Editable per-page Template (design/html) — render chain
 * ====================================================================== */

/**
 * Compose the editable Template body for a page: optional extra-PHP output
 * (design/php) + the HTML/PHP override (design/html), with a guaranteed
 * fall-through to the real resolved template file. Returns '' to let the
 * caller include the file directly (also when PHP sections are locked).
 *
 * @param int $id Page id.
 * @return string
 */
function lavtheme_cs_page_compose_body( $id ) {
	$id = absint( $id );
	if ( ! $id || ! lavtheme_cs_php_allowed() ) {
		return '';
	}
	$body_override = (string) get_option( lavtheme_cs_page_key( $id, 'design', 'html' ), '' );
	$php_extra     = (string) get_option( lavtheme_cs_page_key( $id, 'design', 'php' ), '' );
	if ( '' === trim( $body_override ) && '' === trim( $php_extra ) ) {
		return '';
	}
	$pre  = '' !== trim( $php_extra ) ? lavtheme_cs_run_php( $php_extra ) : '';
	$body = '' !== trim( $body_override ) ? lavtheme_cs_run_php( $body_override ) : '';
	if ( '' === trim( $body ) ) {
		ob_start();
		$path = get_theme_file_path( Lav_CS_Source_Reader::resolve_page_template( $id ) );
		if ( is_readable( $path ) ) {
			include $path;
		}
		$body = (string) ob_get_clean();
	}
	return $pre . $body;
}

/**
 * Materialise the dedicated per-page template copy (File mode only). In DB mode
 * the override is stored as an option and rendered live; no file is written.
 *
 * @param int    $id      Page id.
 * @param string $content Template body.
 */
function lavtheme_cs_page_materialise_copy( $id, $content ) {
	if ( ! lavtheme_cs_file_allowed() || ! function_exists( 'lavtheme_cs_fs_write' ) ) {
		return;
	}
	$rel  = Lav_CS_Source_Reader::page_copy_rel( $id );
	$path = get_theme_file_path( $rel );
	if ( '' === trim( $content ) ) {
		if ( file_exists( $path ) && function_exists( 'wp_delete_file' ) ) {
			wp_delete_file( $path );
		}
		return;
	}
	$err = '';
	lavtheme_cs_fs_write( $rel, $content, $err );
}

/**
 * Route a single page through the editable Template loader, but ONLY when it
 * has a non-empty Template (design/html) override AND PHP sections are unlocked
 * AND it is not an Elementor canvas page. With no override the template is
 * returned untouched — default page rendering is unchanged (zero risk).
 *
 * @param string $template Resolved template path.
 * @return string
 */
function lavtheme_cs_page_template_include( $template ) {
	if ( is_admin() || ! is_page() ) {
		return $template;
	}
	$id = (int) get_queried_object_id();
	if ( ! $id || ! lavtheme_cs_php_allowed() ) {
		return $template;
	}
	if ( Lav_CS_Source_Reader::is_elementor( $id ) ) {
		return $template; // builder owns the markup; don't fight it.
	}
	if ( function_exists( 'lavtheme_shop_page_id' ) && (int) lavtheme_shop_page_id() === $id ) {
		return $template; // the Shop Page has its own dedicated route.
	}
	$override = (string) get_option( lavtheme_cs_page_key( $id, 'design', 'html' ), '' );
	if ( '' === trim( $override ) ) {
		return $template;
	}
	$loader = get_theme_file_path( 'template-parts/context-page-loader.php' );
	return is_readable( $loader ) ? $loader : $template;
}
add_filter( 'template_include', 'lavtheme_cs_page_template_include', 95 );

/**
 * Render one custom section: sanitised HTML + (gated) PHP output.
 *
 * @param int   $id  Page id.
 * @param array $r   Registry record.
 * @return string
 */
function lavtheme_cs_page_render_section( $id, $r ) {
	$html = (string) get_option( lavtheme_cs_page_key( $id, $r['slug'], 'html' ), '' );
	$out  = '' !== trim( $html ) ? lavtheme_cs_render_html( $html ) : '';
	$php  = (string) get_option( lavtheme_cs_page_key( $id, $r['slug'], 'php' ), '' );
	$out .= lavtheme_cs_run_php( $php );
	return $out;
}

/**
 * Assemble the page: real content + custom sections positioned by placement.
 *
 * Priority 20 = after do_shortcode (11), so $content is already rendered.
 *
 * @param string $content Post content.
 * @return string
 */
function lavtheme_cs_page_the_content( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! is_page() ) {
		return $content;
	}
	$id  = (int) get_the_ID();
	$reg = get_option( lavtheme_cs_page_registry_opt( $id ), null );
	if ( ! is_array( $reg ) ) {
		return $content; // never configured → untouched (zero risk).
	}

	// First pass: collect replace / wrap content (special placements).
	$replace     = '';
	$wrap        = '';
	$has_replace = false;
	$has_wrap    = false;
	foreach ( $reg as $r ) {
		if ( ( isset( $r['zone'] ) ? $r['zone'] : 'content' ) !== 'content' || ! empty( $r['pagecontent'] ) ) {
			continue;
		}
		$pl = isset( $r['placement'] ) ? $r['placement'] : 'inline';
		if ( 'replace' === $pl ) {
			$has_replace = true;
			$replace    .= lavtheme_cs_page_render_section( $id, $r );
		} elseif ( 'wrap' === $pl ) {
			$has_wrap = true;
			$wrap    .= lavtheme_cs_page_render_section( $id, $r );
		}
	}

	// The real content (possibly replaced and/or wrapped).
	$content_out = $has_replace ? $replace : $content;
	if ( $has_wrap ) {
		$content_out = ( false !== strpos( $wrap, '[lavtheme_content]' ) )
			? str_replace( '[lavtheme_content]', $content_out, $wrap )
			: $wrap . $content_out;
	}

	// Second pass: render content-zone sections in REGISTRY ORDER. The Page
	// Content item sits at its own position, so dragging sections above/below it
	// places them before/after the content. Sidebars are pulled into columns.
	$flow       = '';
	$left       = '';
	$right      = '';
	$has_custom = false;
	foreach ( $reg as $r ) {
		if ( ( isset( $r['zone'] ) ? $r['zone'] : 'content' ) !== 'content' ) {
			continue;
		}
		if ( ! empty( $r['pagecontent'] ) ) {
			$flow .= $content_out; // the content anchor, at its registry position.
			continue;
		}
		$pl = isset( $r['placement'] ) ? $r['placement'] : 'inline';
		if ( 'replace' === $pl || 'wrap' === $pl ) {
			continue; // already consumed above.
		}
		$rendered = lavtheme_cs_page_render_section( $id, $r );
		if ( '' === trim( $rendered ) ) {
			continue;
		}
		$has_custom = true;
		if ( 'sidebar-left' === $pl ) {
			$left .= $rendered;
		} elseif ( 'sidebar-right' === $pl ) {
			$right .= $rendered;
		} else {
			$flow .= $rendered; // in-flow, in registry order.
		}
	}

	if ( ! $has_custom && ! $has_replace && ! $has_wrap ) {
		return $content;
	}

	if ( '' === $left && '' === $right ) {
		return $flow;
	}

	$classes = 'lavcs-pagewrap' . ( '' !== $left ? ' has-left' : '' ) . ( '' !== $right ? ' has-right' : '' );
	$out     = '<div class="' . esc_attr( $classes ) . '">';
	if ( '' !== $left ) {
		$out .= '<aside class="lavcs-side lavcs-side-left">' . $left . '</aside>';
	}
	$out .= '<div class="lavcs-col-main">' . $flow . '</div>';
	if ( '' !== $right ) {
		$out .= '<aside class="lavcs-side lavcs-side-right">' . $right . '</aside>';
	}
	$out .= '</div>';
	return $out;
}
add_filter( 'the_content', 'lavtheme_cs_page_the_content', 20 );

/* =========================================================================
 * AJAX
 * ====================================================================== */

/**
 * AJAX: load a page's full editor payload.
 */
function lavtheme_cs_page_ajax_load() {
	lavtheme_cs_guard();
	$id = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	if ( ! lavtheme_cs_page_exists( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'Page not found.', 'lavtheme' ) ), 404 );
	}

	$reg          = lavtheme_cs_page_registry( $id );
	$is_elementor = Lav_CS_Source_Reader::is_elementor( $id );
	$sections     = array();
	$data         = array();
	foreach ( $reg as $r ) {
		$fields    = lavtheme_cs_page_fields( $r );
		$is_custom = empty( $r['builtin'] ) || ( 'content' === $r['slug'] );
		$sections[] = array(
			'slug'        => $r['slug'],
			'label'       => $r['label'],
			'zone'        => isset( $r['zone'] ) ? $r['zone'] : 'content',
			'deletable'   => ! empty( $r['deletable'] ),
			'pagecontent' => ! empty( $r['pagecontent'] ),
			'placeable'   => ( ( isset( $r['zone'] ) ? $r['zone'] : 'content' ) === 'content' ) && empty( $r['pagecontent'] ),
			'placement'   => isset( $r['placement'] ) ? $r['placement'] : 'after',
			'fields'      => $fields,
			'elementor'   => ( 'design' === $r['slug'] ) ? $is_elementor : false,
			'template'    => ( 'design' === $r['slug'] ) ? Lav_CS_Source_Reader::resolve_template( 'page-' . $id ) : '',
		);
		foreach ( $fields as $type => $label ) {
			$val = lavtheme_cs_page_get( $id, $r['slug'], $type );
			if ( 'schema' === $r['slug'] && 'json' === $type && '' === $val && function_exists( 'lavtheme_cs_get_schema' ) ) {
				$val = lavtheme_cs_get_schema();
			}
			$data[ $r['slug'] ][ $type ] = $val;
		}
	}

	$pc        = get_post( $id );
	$has_short = $pc ? lavtheme_cs_has_plugin_shortcode( $pc->post_content ) : false;

	wp_send_json_success(
		array(
			'page_id'    => $id,
			'sections'   => $sections,
			'data'       => $data,
			'shortcode'  => $has_short,
			'placements' => lavtheme_cs_placements(),
			'phpAllowed' => lavtheme_cs_php_allowed(),
			'isElementor' => $is_elementor,
		)
	);
}
add_action( 'wp_ajax_lavtheme_cs_page_load', 'lavtheme_cs_page_ajax_load' );

/**
 * AJAX: set a section's placement.
 */
function lavtheme_cs_page_ajax_setplacement() {
	lavtheme_cs_guard();
	$id        = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$slug      = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$placement = isset( $_POST['placement'] ) ? sanitize_key( wp_unslash( $_POST['placement'] ) ) : 'after';
	if ( ! lavtheme_cs_page_exists( $id ) || ! array_key_exists( $placement, lavtheme_cs_placements() ) ) {
		wp_send_json_error( array( 'message' => __( 'Bad placement.', 'lavtheme' ) ) );
	}
	$reg = lavtheme_cs_page_registry( $id );
	foreach ( $reg as &$r ) {
		if ( $r['slug'] === $slug ) {
			$r['placement'] = $placement;
			break;
		}
	}
	unset( $r );
	lavtheme_cs_page_registry_save( $id, $reg );
	wp_send_json_success( array( 'message' => __( 'Placement saved.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_setplacement', 'lavtheme_cs_page_ajax_setplacement' );

/**
 * Detect EDD/Woo shortcodes in page content (for the warning).
 *
 * @param string $content Content.
 * @return bool
 */
function lavtheme_cs_has_plugin_shortcode( $content ) {
	foreach ( array( 'download_checkout', 'edd_receipt', 'edd_profile_editor', 'purchase_history', 'download_history', 'downloads', 'edd_login', 'edd_register', 'woocommerce_checkout', 'woocommerce_cart', 'product_page', 'products' ) as $sc ) {
		if ( has_shortcode( $content, $sc ) ) {
			return true;
		}
	}
	return false;
}

/**
 * AJAX: save one editor field for a page section.
 */
function lavtheme_cs_page_ajax_save() {
	lavtheme_cs_guard();
	$id      = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$slug    = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$content = isset( $_POST['content'] ) ? (string) wp_unslash( $_POST['content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( ! lavtheme_cs_page_exists( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'Page not found.', 'lavtheme' ) ), 404 );
	}

	// Page Content → update the real post_content (with a restorable backup).
	if ( 'content' === $slug && 'html' === $type ) {
		$prev = get_post( $id )->post_content;
		update_option( 'lavtheme_cs_pcbak_' . $id, $prev );
		$res = wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => $content, // wp_update_post sanitises/kses per user caps.
			),
			true
		);
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success( array( 'message' => __( 'Page content updated.', 'lavtheme' ) ) );
	}

	// Template body (design/html) → executable template PHP: syntax-check, store raw.
	if ( 'design' === $slug && 'html' === $type ) {
		$err = '';
		if ( '' !== trim( $content ) && function_exists( 'lavtheme_cs_dl_check_template' ) && ! lavtheme_cs_dl_check_template( $content, $err ) ) {
			wp_send_json_error( array( 'message' => __( 'PHP syntax error — not saved: ', 'lavtheme' ) . $err ) );
		}
		$hkey = lavtheme_cs_page_key( $id, 'design', 'html' );
		update_option( $hkey . '_prev', get_option( $hkey, '' ) );
		update_option( $hkey, (string) $content );
		if ( '' === trim( (string) $content ) ) {
			update_option( $hkey . '_empty', '1' );
		} else {
			delete_option( $hkey . '_empty' );
		}
		// Materialise the dedicated per-page template copy so the source is real and
		// future loads resolve to it (file mode only; DB mode renders the override live).
		if ( function_exists( 'lavtheme_cs_page_materialise_copy' ) ) {
			lavtheme_cs_page_materialise_copy( $id, (string) $content );
		}
		wp_send_json_success( array( 'message' => lavtheme_cs_php_allowed() ? __( 'Template saved and active.', 'lavtheme' ) : __( 'Template saved, but NOT running — add define(\'LAVTHEME_ALLOW_PHP_SECTIONS\', true) to wp-config.php.', 'lavtheme' ) ) );
	}

	// PHP tab → syntax-check + backup; stored even when locked, run only if unlocked.
	if ( 'php' === $type ) {
		$err = '';
		if ( '' !== trim( $content ) && ! lavtheme_cs_check_php( $content, $err ) ) {
			wp_send_json_error( array( 'message' => __( 'PHP syntax error — not saved: ', 'lavtheme' ) . $err ) );
		}
		$key = lavtheme_cs_page_key( $id, $slug, 'php' );
		update_option( $key . '_bak', get_option( $key, '' ) );
			update_option( $key . '_prev', get_option( $key, '' ) );
		update_option( $key, (string) $content );
		$msg = lavtheme_cs_php_allowed()
			? __( 'PHP saved and active.', 'lavtheme' )
			: __( 'PHP saved, but NOT running — add define(\'LAVTHEME_ALLOW_PHP_SECTIONS\', true) to wp-config.php to enable it.', 'lavtheme' );
		wp_send_json_success( array( 'message' => $msg ) );
	}

	// Schema → validate JSON.
	if ( 'schema' === $slug && 'json' === $type ) {
		$trim = trim( $content );
		if ( '' !== $trim ) {
			json_decode( $trim );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				wp_send_json_error( array( 'message' => __( 'Invalid JSON: ', 'lavtheme' ) . json_last_error_msg() ) );
			}
		}
		$pskey = lavtheme_cs_page_key( $id, 'schema', 'json' );
		update_option( $pskey . '_prev', get_option( $pskey, '' ) );
		update_option( $pskey, $trim );
		wp_send_json_success( array( 'message' => __( 'Schema saved.', 'lavtheme' ) ) );
	}

	// CSS / Background / Mobile CSS → sanitise; JS → neutralise; HTML → raw.
	if ( in_array( $type, array( 'css', 'bg', 'mcss' ), true ) ) {
		$clean = lavtheme_sanitize_css( $content );
	} elseif ( 'js' === $type ) {
		$clean = str_ireplace( '</script', '<\/script', $content );
	} else {
		$clean = (string) $content;
	}
	$pfkey = lavtheme_cs_page_key( $id, $slug, $type );
		update_option( $pfkey . '_prev', get_option( $pfkey, '' ) );
		update_option( $pfkey, $clean );
	wp_send_json_success( array( 'message' => __( 'Saved.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_save', 'lavtheme_cs_page_ajax_save' );

/**
 * AJAX: restore the previous Page Content from backup.
 */
function lavtheme_cs_page_ajax_pcrestore() {
	lavtheme_cs_guard();
	$id   = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$prev = get_option( 'lavtheme_cs_pcbak_' . $id, null );
	if ( ! lavtheme_cs_page_exists( $id ) || null === $prev ) {
		wp_send_json_error( array( 'message' => __( 'Nothing to restore.', 'lavtheme' ) ) );
	}
	$cur = get_post( $id )->post_content;
	wp_update_post( array( 'ID' => $id, 'post_content' => $prev ) );
	update_option( 'lavtheme_cs_pcbak_' . $id, $cur );
	wp_send_json_success( array( 'content' => $prev, 'message' => __( 'Page content restored.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_pcrestore', 'lavtheme_cs_page_ajax_pcrestore' );

/**
 * AJAX: add a custom section to a page.
 */
function lavtheme_cs_page_ajax_addsection() {
	lavtheme_cs_guard();
	$id    = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
	$label = '' !== $label ? $label : __( 'New Section', 'lavtheme' );
	if ( ! lavtheme_cs_page_exists( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'Page not found.', 'lavtheme' ) ), 404 );
	}

	$base     = preg_replace( '/[^a-z0-9_-]/', '', sanitize_title( $label ) );
	$base     = '' !== $base ? $base : 'block';
	$reg      = lavtheme_cs_page_registry( $id );
	$existing = wp_list_pluck( $reg, 'slug' );
	$slug     = $base;
	$i        = 2;
	while ( in_array( $slug, $existing, true ) || in_array( $slug, array( 'global', 'schema', 'content' ), true ) ) {
		$slug = $base . '-' . $i;
		$i++;
	}

	// Seed Hello-World content (DB-backed; strip the PHP guard line).
	if ( function_exists( 'lavtheme_cs_starter_content' ) ) {
		$starter = lavtheme_cs_starter_content( $slug, $label );
		$html    = preg_replace( '/^<\?php[^?]*\?>\s*/', '', $starter['html'] );
		update_option( lavtheme_cs_page_key( $id, $slug, 'html' ), $html );
		update_option( lavtheme_cs_page_key( $id, $slug, 'css' ), $starter['css'] );
		update_option( lavtheme_cs_page_key( $id, $slug, 'js' ), $starter['js'] );
	}

	$reg[] = array(
		'slug'        => $slug,
		'label'       => $label,
		'zone'        => 'content',
		'builtin'     => false,
		'deletable'   => true,
		'html'        => true,
		'pagecontent' => false,
		'placement'   => 'inline',
	);
	lavtheme_cs_page_registry_save( $id, $reg );
	wp_send_json_success( array( 'message' => __( 'Section added.', 'lavtheme' ), 'slug' => $slug ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_addsection', 'lavtheme_cs_page_ajax_addsection' );

/**
 * AJAX: rename a page section.
 */
function lavtheme_cs_page_ajax_rename() {
	lavtheme_cs_guard();
	$id    = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
	if ( ! lavtheme_cs_page_exists( $id ) || '' === $slug || '' === $label ) {
		wp_send_json_error( array( 'message' => __( 'Missing data.', 'lavtheme' ) ) );
	}
	$reg = lavtheme_cs_page_registry( $id );
	foreach ( $reg as &$r ) {
		if ( $r['slug'] === $slug ) {
			$r['label'] = $label;
			break;
		}
	}
	unset( $r );
	lavtheme_cs_page_registry_save( $id, $reg );
	wp_send_json_success( array( 'message' => __( 'Renamed.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_rename', 'lavtheme_cs_page_ajax_rename' );

/**
 * AJAX: reorder page sections.
 */
function lavtheme_cs_page_ajax_reorder() {
	lavtheme_cs_guard();
	$id    = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$order = isset( $_POST['order'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['order'] ) ) : array();
	if ( ! lavtheme_cs_page_exists( $id ) || empty( $order ) ) {
		wp_send_json_error( array( 'message' => __( 'Bad order.', 'lavtheme' ) ) );
	}
	$reg  = lavtheme_cs_page_registry( $id );
	$byid = array();
	foreach ( $reg as $r ) {
		$byid[ $r['slug'] ] = $r;
	}
	$new = array();
	foreach ( $order as $slug ) {
		if ( isset( $byid[ $slug ] ) ) {
			$new[] = $byid[ $slug ];
			unset( $byid[ $slug ] );
		}
	}
	foreach ( $byid as $r ) {
		$new[] = $r;
	}
	lavtheme_cs_page_registry_save( $id, $new );
	wp_send_json_success( array( 'message' => __( 'Order saved.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_reorder', 'lavtheme_cs_page_ajax_reorder' );

/**
 * AJAX: delete a custom page section (global/schema/content cannot be deleted).
 */
function lavtheme_cs_page_ajax_delsection() {
	lavtheme_cs_guard();
	$id   = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	if ( ! lavtheme_cs_page_exists( $id ) || in_array( $slug, array( 'global', 'schema', 'content' ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'This section cannot be deleted.', 'lavtheme' ) ) );
	}
	foreach ( array( 'html', 'css', 'js', 'php' ) as $type ) {
		delete_option( lavtheme_cs_page_key( $id, $slug, $type ) );
		delete_option( lavtheme_cs_page_key( $id, $slug, $type ) . '_bak' );
	}
	$reg = array_values(
		array_filter(
			lavtheme_cs_page_registry( $id ),
			function ( $r ) use ( $slug ) {
				return $r['slug'] !== $slug;
			}
		)
	);
	lavtheme_cs_page_registry_save( $id, $reg );
	wp_send_json_success( array( 'message' => __( 'Section deleted.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_delsection', 'lavtheme_cs_page_ajax_delsection' );

/**
 * AJAX: reset a page section field to its default (clears the override).
 */
function lavtheme_cs_page_ajax_reset() {
	lavtheme_cs_guard();
	$id   = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	if ( ! lavtheme_cs_page_exists( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'Page not found.', 'lavtheme' ) ), 404 );
	}
	// Page Content is the real post; it has no file default to reset to.
	if ( 'content' === $slug && 'html' === $type ) {
		wp_send_json_error( array( 'message' => __( 'Page Content has no file default. Use Restore… to undo.', 'lavtheme' ) ) );
	}
	$key = lavtheme_cs_page_key( $id, $slug, $type );
	update_option( $key . '_prev', (string) get_option( $key, '' ) );
	delete_option( $key );
	$def = lavtheme_cs_page_get( $id, $slug, $type );
	if ( 'schema' === $slug && 'json' === $type && '' === $def && function_exists( 'lavtheme_cs_get_schema' ) ) {
		$def = lavtheme_cs_get_schema();
	}
	wp_send_json_success( array( 'content' => $def, 'message' => __( 'Reset to default.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_reset', 'lavtheme_cs_page_ajax_reset' );

/**
 * AJAX: list available backups (the previous saved version) for a page field.
 */
function lavtheme_cs_page_ajax_backups() {
	lavtheme_cs_guard();
	$id   = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	if ( ! lavtheme_cs_page_exists( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'Page not found.', 'lavtheme' ) ), 404 );
	}
	$items = array();
	$prev  = get_option( lavtheme_cs_page_key( $id, $slug, $type ) . '_prev', null );
	if ( null !== $prev ) {
		$items[] = array( 'stamp' => 'prev', 'label' => __( 'Previous saved version', 'lavtheme' ) );
	}
	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_backups', 'lavtheme_cs_page_ajax_backups' );

/**
 * AJAX: restore a page field's previous saved version (reversible swap).
 */
function lavtheme_cs_page_ajax_restore() {
	lavtheme_cs_guard();
	$id   = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	if ( ! lavtheme_cs_page_exists( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'Page not found.', 'lavtheme' ) ), 404 );
	}
	$key  = lavtheme_cs_page_key( $id, $slug, $type );
	$prev = get_option( $key . '_prev', null );
	if ( null === $prev ) {
		wp_send_json_error( array( 'message' => __( 'Nothing to restore.', 'lavtheme' ) ) );
	}
	$cur = (string) get_option( $key, '' );
	update_option( $key, (string) $prev );
	update_option( $key . '_prev', $cur ); // swap so Restore is reversible.
	wp_send_json_success( array( 'content' => (string) $prev, 'message' => __( 'Restored.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_page_restore', 'lavtheme_cs_page_ajax_restore' );
