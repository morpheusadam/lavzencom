<?php
/**
 * Theme Code Studio — dynamic section registry.
 *
 * Sections are no longer hardcoded: they live in an ordered registry stored in
 * wp_options. Users can add / rename / delete / reorder them. Built-in sections
 * are migrated as the initial records (their files/code are untouched).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * The shipped built-in sections (used to seed the registry once).
 *
 * @return array
 */
function lavtheme_cs_builtin_registry() {
	return array(
		array( 'slug' => 'global', 'label' => 'Global', 'file' => '', 'html' => false, 'zone' => 'settings', 'builtin' => true, 'deletable' => false, 'dynamic' => false ),
		array( 'slug' => 'sidebar', 'label' => 'Sidebar (icon rail)', 'file' => 'template-parts/sidebar-rail.php', 'html' => true, 'zone' => 'top', 'builtin' => true, 'deletable' => true, 'dynamic' => false ),
		array( 'slug' => 'header', 'label' => 'Header / Topbar', 'file' => 'template-parts/header-topbar.php', 'html' => true, 'zone' => 'top', 'builtin' => true, 'deletable' => true, 'dynamic' => false ),
		array( 'slug' => 'hero', 'label' => 'Hero', 'file' => 'template-parts/section-hero.php', 'html' => true, 'zone' => 'content', 'builtin' => true, 'deletable' => true, 'dynamic' => false ),
		array( 'slug' => 'services', 'label' => 'Services', 'file' => 'template-parts/section-services.php', 'html' => true, 'zone' => 'content', 'builtin' => true, 'deletable' => true, 'dynamic' => false ),
		array( 'slug' => 'products', 'label' => 'Products (EDD)', 'file' => 'template-parts/section-products.php', 'html' => true, 'zone' => 'content', 'builtin' => true, 'deletable' => true, 'dynamic' => true ),
		array( 'slug' => 'work', 'label' => 'Case Studies', 'file' => 'template-parts/section-cases.php', 'html' => true, 'zone' => 'content', 'builtin' => true, 'deletable' => true, 'dynamic' => false ),
		array( 'slug' => 'blog', 'label' => 'Blog', 'file' => 'template-parts/section-blog.php', 'html' => true, 'zone' => 'content', 'builtin' => true, 'deletable' => true, 'dynamic' => true ),
		array( 'slug' => 'cta', 'label' => 'Call To Action', 'file' => 'template-parts/section-cta.php', 'html' => true, 'zone' => 'content', 'builtin' => true, 'deletable' => true, 'dynamic' => false ),
		array( 'slug' => 'footer', 'label' => 'Footer', 'file' => 'template-parts/footer-content.php', 'html' => true, 'zone' => 'bottom', 'builtin' => true, 'deletable' => true, 'dynamic' => false ),
	);
}

/**
 * The section registry (ordered list of records). Seeds on first use.
 *
 * @return array
 */
function lavtheme_cs_registry() {
	$reg = get_option( 'lavtheme_cs_registry', null );
	if ( ! is_array( $reg ) || empty( $reg ) ) {
		$reg = lavtheme_cs_builtin_registry();
		update_option( 'lavtheme_cs_registry', $reg );
	}
	return $reg;
}

/**
 * Persist the registry.
 *
 * @param array $reg Ordered list of records.
 */
function lavtheme_cs_registry_save( $reg ) {
	update_option( 'lavtheme_cs_registry', array_values( $reg ) );
}

/**
 * Find a registry record by slug.
 *
 * @param string $slug Slug.
 * @return array|null
 */
function lavtheme_cs_record( $slug ) {
	foreach ( lavtheme_cs_registry() as $r ) {
		if ( $r['slug'] === $slug ) {
			return $r;
		}
	}
	return null;
}

/**
 * Section map keyed by slug => [label,file,html,...] — registry-backed.
 *
 * Drop-in replacement for the old hardcoded lavtheme_cs_sections().
 *
 * @return array
 */
function lavtheme_cs_sections() {
	$out = array();
	foreach ( lavtheme_cs_registry() as $r ) {
		$out[ $r['slug'] ] = array(
			'label'     => $r['label'],
			'file'      => $r['file'],
			'html'      => ! empty( $r['html'] ),
			'zone'      => isset( $r['zone'] ) ? $r['zone'] : 'content',
			'builtin'   => ! empty( $r['builtin'] ),
			'dynamic'   => ! empty( $r['dynamic'] ),
			'placement' => isset( $r['placement'] ) ? $r['placement'] : 'after',
		);
	}
	return apply_filters( 'lavtheme_cs_sections', $out );
}

/**
 * Ordered slugs of the front-page content sections.
 *
 * @return array
 */
function lavtheme_cs_content_slugs() {
	$slugs = array();
	foreach ( lavtheme_cs_registry() as $r ) {
		if ( ( isset( $r['zone'] ) ? $r['zone'] : 'content' ) === 'content' ) {
			$slugs[] = $r['slug'];
		}
	}
	return $slugs;
}

/* --------------------------------------------------------------------------
 * Hello World starter content for a new section.
 * ------------------------------------------------------------------------ */

/**
 * Starter content (html/css/mcss/js) for a new section, scoped to its slug.
 *
 * @param string $slug  Section slug.
 * @param string $label Display label.
 * @return array type => content
 */
function lavtheme_cs_starter_content( $slug, $label ) {
	$s = preg_replace( '/[^a-z0-9_-]/', '', $slug );

	$html = '<?php defined( \'ABSPATH\' ) || exit; ?>'
		. "\n" . '<section class="block lav-hw lav-hw-' . $s . '" id="' . $s . '">'
		. "\n\t" . '<div class="lav-hw-card">'
		. "\n\t\t" . '<span class="lav-hw-badge">New Section</span>'
		. "\n\t\t" . '<h2 class="lav-hw-title">Hello World &#128075;</h2>'
		. "\n\t\t" . '<p class="lav-hw-sub">This is your new &ldquo;' . esc_html( $label ) . '&rdquo; section. Edit its HTML, CSS, JS &amp; Mobile CSS right here in Code Studio.</p>'
		. "\n\t\t" . '<button class="lav-hw-btn" type="button" data-clicks="0">Click me</button>'
		. "\n\t" . '</div>'
		. "\n" . '</section>' . "\n";

	$css = ".lav-hw-{$s}{display:flex;justify-content:center;padding:48px 0}\n"
		. ".lav-hw-{$s} .lav-hw-card{position:relative;max-width:560px;width:100%;padding:44px;border-radius:24px;text-align:center;color:#fff;background:linear-gradient(135deg,#7c83ff,#22d3ee);box-shadow:0 30px 70px rgba(0,0,0,.4);overflow:hidden;animation:lavhw_{$s}_float 6s ease-in-out infinite}\n"
		. "@keyframes lavhw_{$s}_float{50%{transform:translateY(-8px)}}\n"
		. ".lav-hw-{$s} .lav-hw-badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 12px;border-radius:999px;background:rgba(255,255,255,.22);margin-bottom:16px}\n"
		. ".lav-hw-{$s} .lav-hw-title{font-size:34px;margin:0 0 10px;font-weight:800}\n"
		. ".lav-hw-{$s} .lav-hw-sub{opacity:.92;margin:0 auto 24px;max-width:420px;line-height:1.55}\n"
		. ".lav-hw-{$s} .lav-hw-btn{cursor:pointer;font-weight:700;font-size:15px;color:#1a1530;background:#fff;border:none;padding:13px 28px;border-radius:14px;box-shadow:0 10px 24px rgba(0,0,0,.25);transition:transform .15s ease,background .2s ease}\n"
		. ".lav-hw-{$s} .lav-hw-btn:hover{transform:translateY(-2px)}\n"
		. ".lav-hw-{$s} .lav-hw-btn.is-on{background:#a3e635;color:#14210a}\n";

	$mcss = ".lav-hw-{$s} .lav-hw-card{padding:30px}\n"
		. ".lav-hw-{$s} .lav-hw-title{font-size:26px}\n";

	$js = "var card=document.querySelector('.lav-hw-{$s}');\n"
		. "if(card){var btn=card.querySelector('.lav-hw-btn'),title=card.querySelector('.lav-hw-title');\n"
		. "btn.addEventListener('click',function(){\n"
		. "  var n=(parseInt(btn.dataset.clicks,10)||0)+1;btn.dataset.clicks=n;\n"
		. "  btn.classList.toggle('is-on');\n"
		. "  title.textContent= n%2 ? 'You clicked '+n+' time'+(n>1?'s':'')+' \\u2728' : 'Hello World \\ud83d\\udc4b';\n"
		. "});}\n";

	return array(
		'html' => $html,
		'css'  => $css,
		'mcss' => $mcss,
		'js'   => $js,
	);
}

/* --------------------------------------------------------------------------
 * File helpers for custom sections (guarded by LAVTHEME_ALLOW_FILE_WRITE).
 * ------------------------------------------------------------------------ */

/**
 * Relative file paths for a section's four assets.
 *
 * @param string $slug Section slug.
 * @return array type => relative path
 */
function lavtheme_cs_section_paths( $slug ) {
	$s = preg_replace( '/[^a-z0-9_-]/', '', $slug );
	return array(
		'html' => 'template-parts/section-' . $s . '.php',
		'css'  => 'assets/css/sections/' . $s . '.css',
		'mcss' => 'assets/css/sections/' . $s . '.mobile.css',
		'js'   => 'assets/js/sections/' . $s . '.js',
	);
}

/**
 * Write one section asset file (guarded; backs up an existing file first).
 *
 * @param string $relpath Theme-relative path (already slug-derived & safe).
 * @param string $content Content.
 * @param string $error   Filled on failure.
 * @return bool
 */
function lavtheme_cs_fs_write( $relpath, $content, &$error ) {
	$error = '';
	if ( ! lavtheme_cs_file_allowed() ) {
		$error = __( 'File mode is locked.', 'lavtheme' );
		return false;
	}
	if ( false !== strpos( $relpath, '..' ) ) {
		$error = 'bad path';
		return false;
	}
	$path = get_theme_file_path( $relpath );
	if ( file_exists( $path ) ) {
		lavtheme_cs_backup_file( $relpath );
	} else {
		wp_mkdir_p( dirname( $path ) );
	}
	if ( false === file_put_contents( $path, $content ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents
		$error = __( 'Write failed (permissions).', 'lavtheme' );
		return false;
	}
	return true;
}

/**
 * Materialise a section's content: to files (file mode) or DB options (else).
 *
 * @param string $slug     Slug.
 * @param array  $content  type => content.
 * @param bool   $to_files Whether to write files.
 * @return string The php file path stored on the record ('' if DB-backed).
 */
function lavtheme_cs_materialise( $slug, $content, $to_files ) {
	if ( $to_files ) {
		$paths = lavtheme_cs_section_paths( $slug );
		$err   = '';
		foreach ( $paths as $type => $rel ) {
			lavtheme_cs_fs_write( $rel, isset( $content[ $type ] ) ? $content[ $type ] : '', $err );
		}
		return $paths['html'];
	}

	// DB-backed: store content as options (html sans the PHP guard line).
	foreach ( array( 'html', 'css', 'mcss', 'js' ) as $type ) {
		$val = isset( $content[ $type ] ) ? $content[ $type ] : '';
		if ( 'html' === $type ) {
			$val = preg_replace( '/^<\?php[^?]*\?>\s*/', '', $val ); // strip guard for DB HTML override
		}
		update_option( lavtheme_cs_key( $slug, $type ), $val );
	}
	return '';
}

/* --------------------------------------------------------------------------
 * AJAX: add / rename / reorder / delete / restore.
 * ------------------------------------------------------------------------ */

/**
 * AJAX: add a new section.
 */
function lavtheme_cs_ajax_add() {
	lavtheme_cs_guard();
	$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
	$label = '' !== $label ? $label : __( 'New Section', 'lavtheme' );

	$base = sanitize_title( $label );
	$base = preg_replace( '/[^a-z0-9_-]/', '', $base );
	if ( '' === $base ) {
		$base = 'section';
	}
	// Ensure unique, non-reserved slug.
	$existing = wp_list_pluck( lavtheme_cs_registry(), 'slug' );
	$slug     = $base;
	$i        = 2;
	while ( in_array( $slug, $existing, true ) || 'global' === $slug ) {
		$slug = $base . '-' . $i;
		$i++;
	}

	$content  = lavtheme_cs_starter_content( $slug, $label );
	$to_files = lavtheme_cs_file_allowed();
	$file     = lavtheme_cs_materialise( $slug, $content, $to_files );

	$reg   = lavtheme_cs_registry();
	$reg[] = array(
		'slug'      => $slug,
		'label'     => $label,
		'file'      => $file,
		'html'      => true,
		'zone'      => 'content',
		'builtin'   => false,
		'deletable' => true,
		'dynamic'   => false,
	);
	lavtheme_cs_registry_save( $reg );

	wp_send_json_success(
		array(
			'slug'    => $slug,
			'label'   => $label,
			'toFiles' => $to_files,
			'message' => __( 'Section added.', 'lavtheme' ),
		)
	);
}
add_action( 'wp_ajax_lavtheme_cs_add', 'lavtheme_cs_ajax_add' );

/**
 * AJAX: set a front-page section's placement.
 */
function lavtheme_cs_ajax_setplacement() {
	lavtheme_cs_guard();
	$slug      = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$placement = isset( $_POST['placement'] ) ? sanitize_key( wp_unslash( $_POST['placement'] ) ) : 'after';
	$allowed   = function_exists( 'lavtheme_cs_placements' ) ? lavtheme_cs_placements() : array( 'after' => 1 );
	if ( '' === $slug || ! array_key_exists( $placement, $allowed ) ) {
		wp_send_json_error( array( 'message' => __( 'Bad placement.', 'lavtheme' ) ) );
	}
	$reg = lavtheme_cs_registry();
	foreach ( $reg as &$r ) {
		if ( $r['slug'] === $slug ) {
			$r['placement'] = $placement;
			break;
		}
	}
	unset( $r );
	lavtheme_cs_registry_save( $reg );
	wp_send_json_success( array( 'message' => __( 'Placement saved.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_setplacement', 'lavtheme_cs_ajax_setplacement' );

/**
 * AJAX: rename a section's display label.
 */
function lavtheme_cs_ajax_rename() {
	lavtheme_cs_guard();
	$slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
	if ( '' === $slug || '' === $label ) {
		wp_send_json_error( array( 'message' => __( 'Missing data.', 'lavtheme' ) ) );
	}
	$reg   = lavtheme_cs_registry();
	$found = false;
	foreach ( $reg as &$r ) {
		if ( $r['slug'] === $slug ) {
			$r['label'] = $label;
			$found      = true;
			break;
		}
	}
	unset( $r );
	if ( ! $found ) {
		wp_send_json_error( array( 'message' => __( 'Not found.', 'lavtheme' ) ) );
	}
	lavtheme_cs_registry_save( $reg );
	wp_send_json_success( array( 'message' => __( 'Renamed.', 'lavtheme' ), 'label' => $label ) );
}
add_action( 'wp_ajax_lavtheme_cs_rename', 'lavtheme_cs_ajax_rename' );

/**
 * AJAX: reorder sections.
 */
function lavtheme_cs_ajax_reorder() {
	lavtheme_cs_guard();
	$order = isset( $_POST['order'] ) ? (array) wp_unslash( $_POST['order'] ) : array();
	$order = array_map( 'sanitize_key', $order );
	if ( empty( $order ) ) {
		wp_send_json_error( array( 'message' => __( 'Empty order.', 'lavtheme' ) ) );
	}
	$reg   = lavtheme_cs_registry();
	$byid  = array();
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
	foreach ( $byid as $r ) { // any not listed keep at end
		$new[] = $r;
	}
	lavtheme_cs_registry_save( $new );
	wp_send_json_success( array( 'message' => __( 'Order saved.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_reorder', 'lavtheme_cs_ajax_reorder' );

/**
 * AJAX: delete a section (to the restorable trash).
 */
function lavtheme_cs_ajax_delete() {
	lavtheme_cs_guard();
	$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$rec  = lavtheme_cs_record( $slug );
	if ( ! $rec ) {
		wp_send_json_error( array( 'message' => __( 'Not found.', 'lavtheme' ) ) );
	}
	if ( 'global' === $slug || empty( $rec['deletable'] ) ) {
		wp_send_json_error( array( 'message' => __( 'This section cannot be deleted.', 'lavtheme' ) ) );
	}

	// Snapshot current content (files or options) into the trash for restore.
	$snapshot = array();
	foreach ( array( 'html', 'css', 'mcss', 'js' ) as $type ) {
		$snapshot[ $type ] = lavtheme_cs_get_value( $slug, $type );
	}

	// Back up + remove files for custom sections.
	if ( ! empty( $rec['file'] ) && ! $rec['builtin'] ) {
		foreach ( lavtheme_cs_section_paths( $slug ) as $rel ) {
			$path = get_theme_file_path( $rel );
			if ( file_exists( $path ) ) {
				lavtheme_cs_backup_file( $rel );
				wp_delete_file( $path );
			}
		}
	}

	// Remove DB options.
	foreach ( array( 'html', 'css', 'mcss', 'js' ) as $type ) {
		delete_option( lavtheme_cs_key( $slug, $type ) );
		delete_option( lavtheme_cs_key( $slug, $type ) . '_prev' );
			delete_option( lavtheme_cs_key( $slug, $type ) . '_empty' );
	}

	// Push to trash.
	$trash   = get_option( 'lavtheme_cs_trash', array() );
	$trash   = is_array( $trash ) ? $trash : array();
	$trash[] = array( 'record' => $rec, 'content' => $snapshot );
	update_option( 'lavtheme_cs_trash', $trash );

	// Remove from registry.
	$reg = array_values(
		array_filter(
			lavtheme_cs_registry(),
			function ( $r ) use ( $slug ) {
				return $r['slug'] !== $slug;
			}
		)
	);
	lavtheme_cs_registry_save( $reg );

	wp_send_json_success( array( 'message' => __( 'Section deleted (restorable).', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_delete', 'lavtheme_cs_ajax_delete' );

/**
 * AJAX: list the trash.
 */
function lavtheme_cs_ajax_trash() {
	lavtheme_cs_guard();
	$trash = get_option( 'lavtheme_cs_trash', array() );
	$trash = is_array( $trash ) ? $trash : array();
	$items = array();
	foreach ( $trash as $i => $t ) {
		$items[] = array( 'i' => $i, 'slug' => $t['record']['slug'], 'label' => $t['record']['label'] );
	}
	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_lavtheme_cs_trash', 'lavtheme_cs_ajax_trash' );

/**
 * AJAX: restore a section from the trash.
 */
function lavtheme_cs_ajax_restore_section() {
	lavtheme_cs_guard();
	$idx   = isset( $_POST['i'] ) ? absint( wp_unslash( $_POST['i'] ) ) : -1;
	$trash = get_option( 'lavtheme_cs_trash', array() );
	$trash = is_array( $trash ) ? $trash : array();
	if ( ! isset( $trash[ $idx ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Trash item not found.', 'lavtheme' ) ) );
	}
	$rec     = $trash[ $idx ]['record'];
	$content = $trash[ $idx ]['content'];
	$slug    = $rec['slug'];

	// Avoid clashing with a live slug.
	if ( lavtheme_cs_record( $slug ) ) {
		wp_send_json_error( array( 'message' => __( 'A section with this slug already exists.', 'lavtheme' ) ) );
	}

	if ( empty( $rec['builtin'] ) ) {
		// Custom section: recreate its content (files when possible, else DB).
		$to_files    = lavtheme_cs_file_allowed() && ! empty( $rec['file'] );
		$file        = lavtheme_cs_materialise( $slug, $content, $to_files );
		$rec['file'] = $to_files ? $file : '';
	}
	// Built-in: its theme files were never deleted, so just re-add the record
	// with its original file path intact.

	$reg   = lavtheme_cs_registry();
	$reg[] = $rec;
	lavtheme_cs_registry_save( $reg );

	// Remove from trash.
	unset( $trash[ $idx ] );
	update_option( 'lavtheme_cs_trash', array_values( $trash ) );

	wp_send_json_success( array( 'message' => __( 'Section restored.', 'lavtheme' ), 'slug' => $slug ) );
}
add_action( 'wp_ajax_lavtheme_cs_restore_section', 'lavtheme_cs_ajax_restore_section' );
