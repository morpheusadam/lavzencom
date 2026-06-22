<?php
/**
 * Theme Code Studio — admin panel.
 *
 * A per-section code editor (HTML/PHP, CSS, JS, Mobile CSS) plus a Global tab.
 * Two save modes: Database (default, safe live injection) and File (writes the
 * theme files directly, guarded by a wp-config constant, with backups).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Capability required to use the Code Studio.
 *
 * @return string
 */
function lavtheme_cs_cap() {
	return apply_filters( 'lavtheme_cs_capability', 'manage_options' );
}

/**
 * Menu slug.
 */
if ( ! defined( 'LAVTHEME_CS_SLUG' ) ) {
	define( 'LAVTHEME_CS_SLUG', 'lavtheme-code-studio' );
}

/**
 * Editor fields for a section, as type => label.
 *
 * @param string $section Section key.
 * @return array
 */
function lavtheme_cs_fields( $section ) {
	if ( 'global' === $section ) {
		return array(
			'root' => 'Root Variables (:root)',
			'css'  => 'Global CSS',
			'js'   => 'Global JS',
			'bg'   => 'Background CSS',
		);
	}

	$sections = lavtheme_cs_sections();
	$fields   = array();
	if ( ! empty( $sections[ $section ]['html'] ) ) {
		$fields['html'] = 'HTML / PHP';
	}
	$fields['css']  = 'CSS';
	$fields['js']   = 'JS';
	$fields['mcss'] = 'Mobile CSS';
	$fields['php']  = 'PHP';

	return $fields;
}

/**
 * CodeMirror MIME mode for an editor type.
 *
 * @param string $type Field type.
 * @return string
 */
function lavtheme_cs_mode_for( $type ) {
	switch ( $type ) {
		case 'html':
		case 'html_before':
		case 'html_after':
		case 'php':
			return 'application/x-httpd-php';
		case 'js':
			return 'text/javascript';
		case 'json':
			return 'application/json';
		case 'css':
		case 'mcss':
		case 'root':
		case 'bg':
		default:
			return 'text/css';
	}
}

/**
 * Option key for a section/type pair.
 *
 * @param string $section Section.
 * @param string $type    Type.
 * @return string
 */
function lavtheme_cs_key( $section, $type ) {
	return 'lavtheme_cs_' . sanitize_key( $section ) . '_' . sanitize_key( $type );
}

/**
 * Current save mode: 'db' (default) or 'file'.
 *
 * @return string
 */
function lavtheme_cs_mode() {
	return 'file' === get_option( 'lavtheme_cs_mode', 'db' ) ? 'file' : 'db';
}

/**
 * Is File mode unlocked via wp-config constant?
 *
 * @return bool
 */
function lavtheme_cs_file_allowed() {
	return defined( 'LAVTHEME_ALLOW_FILE_WRITE' ) && LAVTHEME_ALLOW_FILE_WRITE;
}

/**
 * Is front-end minification enabled?
 *
 * @return bool
 */
function lavtheme_cs_minify_on() {
	return '1' === get_option( 'lavtheme_cs_minify', '0' );
}

/**
 * Should the Header section render on every page (true) or front page only (false)?
 *
 * @return bool
 */
function lavtheme_cs_header_global() {
	return '1' === get_option( 'lavtheme_cs_header_global', '1' );
}

/**
 * Default JSON-LD schema (valid WebSite + Organization).
 *
 * @return string
 */
function lavtheme_cs_schema_default() {
	$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$url  = home_url( '/' );
	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type' => 'Organization',
				'@id'   => $url . '#organization',
				'name'  => $name,
				'url'   => $url,
			),
			array(
				'@type'     => 'WebSite',
				'@id'       => $url . '#website',
				'name'      => $name,
				'url'       => $url,
				'publisher' => array( '@id' => $url . '#organization' ),
			),
		),
	);
	return (string) wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/**
 * Stored JSON-LD schema, or the default when unset.
 *
 * @return string
 */
function lavtheme_cs_get_schema() {
	$v = get_option( 'lavtheme_cs_schema', '' );
	return '' !== $v ? (string) $v : lavtheme_cs_schema_default();
}

/**
 * Get the stored (or default) content for an editor.
 *
 * For section HTML the default is the current template-part file contents.
 *
 * @param string $section Section.
 * @param string $type    Type.
 * @return string
 */
/**
 * Theme-relative path of the DEFAULT source for an editor (section/type).
 *
 * @param string $section Section key.
 * @param string $type    Editor type.
 * @return string Relative path, or '' if none.
 */
function lavtheme_cs_default_path( $section, $type ) {
	if ( 'global' === $section ) {
		switch ( $type ) {
			case 'root':
				return 'assets/css/sections/global.root.css';
			case 'bg':
				return 'assets/css/sections/global.bg.css';
			case 'css':
				return 'assets/css/sections/global.css';
			case 'js':
				return 'assets/js/sections/global.js';
		}
		return '';
	}

	$sections = lavtheme_cs_sections();
	switch ( $type ) {
		case 'html':
			return isset( $sections[ $section ]['file'] ) ? $sections[ $section ]['file'] : '';
		case 'css':
			return 'assets/css/sections/' . $section . '.css';
		case 'mcss':
			return 'assets/css/sections/' . $section . '.mobile.css';
		case 'js':
			return 'assets/js/sections/' . $section . '.js';
	}
	return '';
}

/**
 * Default (file) content for an editor.
 *
 * @param string $section Section key.
 * @param string $type    Editor type.
 * @return string
 */
function lavtheme_cs_default_value( $section, $type ) {
	$rel = lavtheme_cs_default_path( $section, $type );
	if ( ! $rel ) {
		return '';
	}
	$path = get_theme_file_path( $rel );
	return is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/**
 * Get the stored value, falling back to the real theme file default.
 *
 * @param string $section Section.
 * @param string $type    Type.
 * @return string
 */
function lavtheme_cs_get_value( $section, $type ) {
	$key    = lavtheme_cs_key( $section, $type );
	$stored = get_option( $key, null );
	if ( null !== $stored && '' !== $stored ) {
		return (string) $stored;
	}
	// Explicitly cleared by the user (Save on an empty editor): honour the empty
	// value instead of falling back to the file default.
	if ( '' === $stored && '' !== get_option( $key . '_empty', '' ) ) {
		return '';
	}
	return lavtheme_cs_default_value( $section, $type );
}

/**
 * Register the top-level admin menu.
 */
function lavtheme_cs_admin_menu() {
	add_menu_page(
		__( 'Theme Code Studio', 'lavtheme' ),
		__( 'Code Studio', 'lavtheme' ),
		lavtheme_cs_cap(),
		LAVTHEME_CS_SLUG,
		'lavtheme_cs_render_page',
		'dashicons-editor-code',
		59
	);
}
add_action( 'admin_menu', 'lavtheme_cs_admin_menu' );

/**
 * Enqueue editor + admin assets on the Code Studio page only.
 *
 * @param string $hook Current admin hook.
 */
function lavtheme_cs_admin_assets( $hook ) {
	if ( false === strpos( (string) $hook, LAVTHEME_CS_SLUG ) ) {
		return;
	}

	// Enhanced CodeMirror options (VS Code-like): folding, bracket matching,
	// auto-close, active line, 2-space tabs. The dark skin is applied via CSS.
	$cm_opts = array(
		'lineNumbers'      => true,
		'styleActiveLine'  => true,
		'matchBrackets'    => true,
		'autoCloseBrackets' => true,
		'autoCloseTags'    => true,
		'foldGutter'       => true,
		'gutters'          => array( 'CodeMirror-linenumbers', 'CodeMirror-foldgutter' ),
		'indentUnit'       => 2,
		'tabSize'          => 2,
		'indentWithTabs'   => false,
	);

	$cm = array();
	foreach ( array( 'text/css', 'text/javascript', 'application/x-httpd-php', 'application/json' ) as $mime ) {
		$settings = wp_enqueue_code_editor(
			array(
				'type'       => $mime,
				'codemirror' => $cm_opts,
			)
		);
		if ( false !== $settings ) {
			$cm[ $mime ] = $settings;
		}
	}

	$ver = function_exists( 'lavtheme_asset_ver' ) ? lavtheme_asset_ver( 'assets/admin/code-studio.css' ) : LAVTHEME_VERSION;
	wp_enqueue_style( 'lavtheme-cs', LAVTHEME_URI . 'assets/admin/code-studio.css', array( 'code-editor' ), $ver );

	$verjs = function_exists( 'lavtheme_asset_ver' ) ? lavtheme_asset_ver( 'assets/admin/code-studio.js' ) : LAVTHEME_VERSION;
	wp_enqueue_script( 'lavtheme-cs', LAVTHEME_URI . 'assets/admin/code-studio.js', array( 'jquery', 'jquery-ui-sortable' ), $verjs, true );

	// Build the full content map for the editors + a section metadata list.
	$data  = array();
	$metas = array();
	foreach ( lavtheme_cs_sections() as $skey => $section ) {
		foreach ( lavtheme_cs_fields( $skey ) as $type => $label ) {
			$data[ $skey ][ $type ] = lavtheme_cs_get_value( $skey, $type );
		}
		$metas[ $skey ] = array(
			'deletable' => ( 'global' !== $skey ),
			'dynamic'   => ! empty( $section['dynamic'] ),
			'label'     => $section['label'],
		);
	}
	$trash = get_option( 'lavtheme_cs_trash', array() );
	$trash = is_array( $trash ) ? $trash : array();

	// Schema editor content (special, non-registry section).
	$data['schema']['json'] = lavtheme_cs_get_schema();

	// Per-context (EDD/Woo page) editor content.
	if ( function_exists( 'lavtheme_cs_contexts' ) ) {
		foreach ( lavtheme_cs_contexts() as $ckey => $c ) {
			$cd                       = lavtheme_cs_ctx_get( $ckey );
			$data[ 'ctx-' . $ckey ]   = array(
				'css'         => $cd['css'],
				'js'          => $cd['js'],
				'html_before' => $cd['html_before'],
				'html_after'  => $cd['html_after'],
			);
		}
	}

	wp_localize_script(
		'lavtheme-cs',
		'LavthemeCS',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'lavtheme_cs' ),
			'cm'           => $cm,
			'content'      => $data,
			'sections'     => $metas,
			'trashCount'   => count( $trash ),
			'mode'         => lavtheme_cs_mode(),
			'fileAllowed'  => lavtheme_cs_file_allowed(),
			'minify'       => lavtheme_cs_minify_on(),
			'headerGlobal' => lavtheme_cs_header_global(),
			'exportFormat' => defined( 'LAVTHEME_CS_EXPORT_FORMAT' ) ? LAVTHEME_CS_EXPORT_FORMAT : 1,
			'i18n'        => array(
				'saved'          => __( 'Saved ✓', 'lavtheme' ),
				'saving'         => __( 'Saving…', 'lavtheme' ),
				'error'          => __( 'Error', 'lavtheme' ),
				'restored'       => __( 'Restored ✓', 'lavtheme' ),
				'confirmRestore' => __( 'Restore this backup? Current content will be backed up first.', 'lavtheme' ),
				'noBackups'      => __( 'No backups yet for this file.', 'lavtheme' ),
				'fnConfirm'      => __( 'You are writing a theme FILE on disk. A backup is taken first. Continue?', 'lavtheme' ),
				'addPrompt'      => __( 'Name for the new section:', 'lavtheme' ),
				'renamePrompt'   => __( 'New display name:', 'lavtheme' ),
				'confirmDelete'  => __( 'Delete this section? It will be moved to the restorable trash.', 'lavtheme' ),
				'confirmDynamic' => __( 'WARNING: this section renders live data (EDD products / blog). Deleting it removes that from the site. Are you absolutely sure?', 'lavtheme' ),
				'noTrash'        => __( 'Trash is empty.', 'lavtheme' ),
				'badJson'        => __( 'Invalid JSON — fix it before saving.', 'lavtheme' ),
				'pcEddWarn'      => __( 'This page contains plugin shortcodes (EDD/Woo). Removing them will break the page. Save anyway?', 'lavtheme' ),
				'pcRestore'      => __( 'Restore the previous page content from backup?', 'lavtheme' ),
				'delSection'     => __( 'Delete this section?', 'lavtheme' ),
					'unsavedExport'  => __( 'You have unsaved changes. The export will contain the last SAVED version of this section, not your current edits. Continue?', 'lavtheme' ),
					'importBadJson'  => __( 'Could not read the file — it is not valid JSON. Nothing was imported.', 'lavtheme' ),
					'importBadFile'  => __( 'This is not a lavtheme export file (missing the lavtheme_export marker). Nothing was imported.', 'lavtheme' ),
					'importBadVer'   => __( 'This file was made by a newer export format. Update the theme to import it.', 'lavtheme' ),
					'importNoMatch'  => __( 'None of the tabs in this file match this section, so there is nothing to import here.', 'lavtheme' ),
					'importConfirm'  => __( 'Import will REPLACE this section\'s saved content with the file. A backup of the current content is taken first (use Restore… to undo). Continue?', 'lavtheme' ),
					'importSkipped'  => __( 'These tabs are not part of this section and were skipped:', 'lavtheme' ),
					'importCancel'   => __( 'Import cancelled — nothing changed.', 'lavtheme' ),
					'imported'       => __( 'Imported ✓', 'lavtheme' ),
					'confirmReset'   => __( 'Reset this tab to the theme file default? Your current content for this tab is replaced (you can undo via Restore…).', 'lavtheme' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'lavtheme_cs_admin_assets' );

/**
 * Render the Code Studio admin page.
 */
function lavtheme_cs_render_page() {
	if ( ! current_user_can( lavtheme_cs_cap() ) ) {
		return;
	}
	$sections = lavtheme_cs_sections();
	$sections = is_array( $sections ) ? $sections : array();
	$mode     = lavtheme_cs_mode();
	$file_ok  = lavtheme_cs_file_allowed();
	$trash    = get_option( 'lavtheme_cs_trash', array() );
	$trash    = is_array( $trash ) ? $trash : array();
	?>
	<div class="wrap lavcs-wrap">
		<div class="lavcs-topbar">
			<h1><?php esc_html_e( 'Theme Code Studio', 'lavtheme' ); ?></h1>
			<div class="lavcs-modebox">
				<label for="lavcs-mode"><?php esc_html_e( 'Save mode:', 'lavtheme' ); ?></label>
				<select id="lavcs-mode">
					<option value="db" <?php selected( $mode, 'db' ); ?>><?php esc_html_e( 'Database (safe, live inject)', 'lavtheme' ); ?></option>
					<option value="file" <?php selected( $mode, 'file' ); ?> <?php disabled( ! $file_ok ); ?>><?php esc_html_e( 'File (writes theme files)', 'lavtheme' ); ?></option>
				</select>
				<span class="lavcs-mode-state <?php echo esc_attr( $mode ); ?>"><?php echo esc_html( strtoupper( $mode ) ); ?></span>
				<label class="lavcs-switch" title="<?php esc_attr_e( 'Minify the rendered front-end HTML/CSS (source files stay readable).', 'lavtheme' ); ?>">
					<input type="checkbox" id="lavcs-minify" <?php checked( lavtheme_cs_minify_on() ); ?>>
					<?php esc_html_e( 'Minify front-end', 'lavtheme' ); ?>
				</label>
				<label class="lavcs-switch" title="<?php esc_attr_e( 'Render the Header section on every page (off = front page only).', 'lavtheme' ); ?>">
					<input type="checkbox" id="lavcs-headerglobal" <?php checked( lavtheme_cs_header_global() ); ?>>
					<?php esc_html_e( 'Header on all pages', 'lavtheme' ); ?>
				</label>
			</div>
		</div>

		<?php if ( ! $file_ok ) : ?>
			<div class="notice notice-info inline"><p>
				<?php
				printf(
					/* translators: %s: code constant. */
					esc_html__( 'File mode is locked. To enable it, add %s to wp-config.php.', 'lavtheme' ),
					'<code>define( \'LAVTHEME_ALLOW_FILE_WRITE\', true );</code>'
				);
				?>
			</p></div>
		<?php endif; ?>

		<?php $lav_pages = function_exists( 'lavtheme_cs_pages' ) ? lavtheme_cs_pages() : array(); ?>
		<div class="lavcs-context-bar">
			<label for="lavcs-context"><?php esc_html_e( 'Editing context:', 'lavtheme' ); ?></label>
			<select id="lavcs-context">
				<option value="front">★ <?php esc_html_e( 'Front Page (full section builder)', 'lavtheme' ); ?></option>
				<?php if ( ! empty( $lav_pages ) ) : ?>
					<optgroup label="<?php esc_attr_e( 'Pages', 'lavtheme' ); ?>">
						<?php foreach ( $lav_pages as $pid => $pg ) : ?>
							<option value="page-<?php echo (int) $pid; ?>" data-view="<?php echo esc_url( get_permalink( $pid ) ); ?>">
								<?php
								echo esc_html( $pg['title'] );
								echo '' !== $pg['edd'] ? ' — ' . esc_html( $pg['edd'] ) : '';
								?>
							</option>
						<?php endforeach; ?>
					</optgroup>
				<?php endif; ?>
				<?php if ( post_type_exists( 'download' ) ) : ?>
					<?php
					// One single editable level for ALL products (applies via is_singular('download')).
					// Per-product overrides (dl-<ID>) are intentionally not listed — the template level
					// covers every product, and its Schema is filled with each product's real data.
					$lav_dl_view = '';
					if ( function_exists( 'lavtheme_cs_dl_products' ) ) {
						$lav_dl_ids = array_keys( lavtheme_cs_dl_products() );
						if ( ! empty( $lav_dl_ids ) ) {
							$lav_dl_view = (string) get_permalink( $lav_dl_ids[0] );
						}
					}
					?>
					<optgroup label="<?php esc_attr_e( 'Downloads (EDD)', 'lavtheme' ); ?>">
						<option value="dl-template" data-view="<?php echo esc_url( $lav_dl_view ); ?>"><?php esc_html_e( 'Single Download (template)', 'lavtheme' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Shop (EDD)', 'lavtheme' ); ?>">
						<option value="shop" data-view="<?php echo esc_url( function_exists( 'lavtheme_shop_url' ) ? lavtheme_shop_url() : get_post_type_archive_link( 'download' ) ); ?>"><?php esc_html_e( 'Shop (archive)', 'lavtheme' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Account', 'lavtheme' ); ?>">
						<option value="account" data-view="<?php echo esc_url( function_exists( 'lavtheme_account_url' ) ? lavtheme_account_url() : home_url( '/' ) ); ?>"><?php esc_html_e( 'My Account (dashboard)', 'lavtheme' ); ?></option>
						<option value="auth" data-view="<?php echo esc_url( function_exists( 'lavtheme_login_url' ) ? lavtheme_login_url() : wp_login_url() ); ?>"><?php esc_html_e( 'Login / Register', 'lavtheme' ); ?></option>
					</optgroup>
				<?php endif; ?>
				<optgroup label="<?php esc_attr_e( 'Blog', 'lavtheme' ); ?>">
					<option value="blog" data-view="<?php echo esc_url( function_exists( 'lavtheme_blog_url' ) ? lavtheme_blog_url() : home_url( '/' ) ); ?>"><?php esc_html_e( 'Blog (archive)', 'lavtheme' ); ?></option>
					<?php
					$lav_post_view = '';
					$lav_recent    = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true ) );
					if ( ! empty( $lav_recent ) ) {
						$lav_post_view = (string) get_permalink( $lav_recent[0] );
					}
					?>
					<option value="single" data-view="<?php echo esc_url( $lav_post_view ); ?>"><?php esc_html_e( 'Single Post (template)', 'lavtheme' ); ?></option>
				</optgroup>
				<optgroup label="<?php esc_attr_e( 'Error pages', 'lavtheme' ); ?>">
					<option value="404" data-view="<?php echo esc_url( home_url( '/404-page-not-found-preview/' ) ); ?>"><?php esc_html_e( '404 / Error page', 'lavtheme' ); ?></option>
				</optgroup>
					<optgroup label="<?php esc_attr_e( 'Admin', 'lavtheme' ); ?>">
						<option value="wp-dash" data-view="<?php echo esc_url( admin_url( 'index.php' ) ); ?>"><?php esc_html_e( 'WP Dash (dashboard)', 'lavtheme' ); ?></option>
					</optgroup>
			</select>
			<?php if ( function_exists( 'lavtheme_cs_edd' ) && lavtheme_cs_edd() ) : ?>
				<span class="lavcs-badge edd"><?php esc_html_e( 'EDD detected', 'lavtheme' ); ?></span>
			<?php endif; ?>
			<?php if ( function_exists( 'lavtheme_cs_woo' ) && lavtheme_cs_woo() ) : ?>
				<span class="lavcs-badge woo"><?php esc_html_e( 'WooCommerce detected', 'lavtheme' ); ?></span>
			<?php endif; ?>
		</div>

		<div class="lavcs-front-area">
		<div class="lavcs-layout">
			<div class="lavcs-sidebar">
				<div class="lavcs-managebar">
					<button type="button" class="button button-primary lavcs-add">+ <?php esc_html_e( 'Add Section', 'lavtheme' ); ?></button>
					<button type="button" class="button lavcs-trash-btn"><?php esc_html_e( 'Trash', 'lavtheme' ); ?> (<span class="lavcs-trash-count"><?php echo (int) count( $trash ); ?></span>)</button>
				</div>
				<ul class="lavcs-nav">
					<?php
					$first = true;
					foreach ( $sections as $skey => $section ) :
						$is_global = ( 'global' === $skey );
						?>
						<li class="lavcs-navli" data-section="<?php echo esc_attr( $skey ); ?>">
							<span class="lavcs-drag" title="<?php esc_attr_e( 'Drag to reorder', 'lavtheme' ); ?>" aria-hidden="true">⋮⋮</span>
							<button type="button" class="lavcs-navitem<?php echo $first ? ' is-active' : ''; ?>" data-section="<?php echo esc_attr( $skey ); ?>">
								<span class="lavcs-navlabel"><?php echo esc_html( $section['label'] ); ?></span>
							</button>
							<span class="lavcs-rowtools">
								<button type="button" class="lavcs-rename" data-section="<?php echo esc_attr( $skey ); ?>" title="<?php esc_attr_e( 'Rename', 'lavtheme' ); ?>">✎</button>
								<?php if ( ! $is_global ) : ?>
									<button type="button" class="lavcs-del" data-section="<?php echo esc_attr( $skey ); ?>" title="<?php esc_attr_e( 'Delete', 'lavtheme' ); ?>">✕</button>
								<?php endif; ?>
							</span>
						</li>
						<?php
						$first = false;
					endforeach;
					?>
					<li class="lavcs-navli lavcs-pinned" data-section="schema">
						<span class="lavcs-drag lavcs-drag-off" aria-hidden="true">★</span>
						<button type="button" class="lavcs-navitem" data-section="schema">
							<span class="lavcs-navlabel"><?php esc_html_e( 'Schema', 'lavtheme' ); ?></span>
						</button>
					</li>
				</ul>
			</div>

			<div class="lavcs-main">
				<?php
				$first = true;
				foreach ( $sections as $skey => $section ) :
					$fields = lavtheme_cs_fields( $skey );
					?>
					<div class="lavcs-panel<?php echo $first ? ' is-active' : ''; ?>" data-section="<?php echo esc_attr( $skey ); ?>">
						<div class="lavcs-tabs">
							<?php
							$ftab = true;
							foreach ( $fields as $type => $label ) :
								?>
								<button type="button" class="lavcs-tab<?php echo $ftab ? ' is-active' : ''; ?>" data-type="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></button>
								<?php
								$ftab = false;
							endforeach;
							?>
							<span class="lavcs-spacer"></span>
							<button type="button" class="button lavcs-fullscreen" title="<?php esc_attr_e( 'Toggle fullscreen', 'lavtheme' ); ?>">⤢</button>
						</div>

						<?php
						$ftab = true;
						foreach ( $fields as $type => $label ) :
							?>
							<div class="lavcs-editorwrap<?php echo $ftab ? ' is-active' : ''; ?>" data-type="<?php echo esc_attr( $type ); ?>">
								<?php if ( 'php' === $type ) : ?>
									<p class="lavcs-php-warn">⚠ <?php esc_html_e( 'Custom PHP runs on the server. Only enter trusted code.', 'lavtheme' ); ?>
										<?php if ( ! lavtheme_cs_php_allowed() ) : ?>
											<strong><?php esc_html_e( 'LOCKED', 'lavtheme' ); ?></strong> — <?php esc_html_e( 'add', 'lavtheme' ); ?> <code>define( 'LAVTHEME_ALLOW_PHP_SECTIONS', true );</code> <?php esc_html_e( 'to wp-config.php to run it.', 'lavtheme' ); ?>
										<?php endif; ?>
									</p>
								<?php endif; ?>
								<textarea class="lavcs-editor" data-section="<?php echo esc_attr( $skey ); ?>" data-type="<?php echo esc_attr( $type ); ?>" data-mode="<?php echo esc_attr( lavtheme_cs_mode_for( $type ) ); ?>"></textarea>
							</div>
							<?php
							$ftab = false;
						endforeach;
						?>

						<div class="lavcs-actions">
							<button type="button" class="button button-primary lavcs-save" data-section="<?php echo esc_attr( $skey ); ?>"><?php esc_html_e( 'Save', 'lavtheme' ); ?></button>
							<button type="button" class="button lavcs-restore" data-section="<?php echo esc_attr( $skey ); ?>"><?php esc_html_e( 'Restore…', 'lavtheme' ); ?></button>
								<button type="button" class="button lavcs-reset" data-section="<?php echo esc_attr( $skey ); ?>" title="<?php esc_attr_e( 'Reset the ACTIVE tab to the theme file default', 'lavtheme' ); ?>"><?php esc_html_e( 'Reset to default', 'lavtheme' ); ?></button>
								<button type="button" class="button lavcs-export" data-section="<?php echo esc_attr( $skey ); ?>" title="<?php esc_attr_e( 'Download every tab of this section as one JSON file', 'lavtheme' ); ?>"><?php esc_html_e( 'Export', 'lavtheme' ); ?></button>
								<button type="button" class="button lavcs-import" data-section="<?php echo esc_attr( $skey ); ?>" title="<?php esc_attr_e( 'Import tabs from a lavtheme JSON export into this section', 'lavtheme' ); ?>"><?php esc_html_e( 'Import', 'lavtheme' ); ?></button>
							<?php if ( 'settings' !== ( isset( $section['zone'] ) ? $section['zone'] : 'content' ) && function_exists( 'lavtheme_cs_placements' ) ) : ?>
								<label class="lavcs-placement"><?php esc_html_e( 'Placement:', 'lavtheme' ); ?>
									<select class="lavcs-placement-sel" data-front="1" data-slug="<?php echo esc_attr( $skey ); ?>">
										<?php
										$cur_pl = isset( $section['placement'] ) ? $section['placement'] : 'after';
										foreach ( lavtheme_cs_placements() as $pk => $plabel ) {
											printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $pk ), selected( $cur_pl, $pk, false ), esc_html( $plabel ) );
										}
										?>
									</select>
								</label>
							<?php endif; ?>
							<span class="lavcs-status" aria-live="polite"></span>
						</div>
					</div>
					<?php
					$first = false;
				endforeach;
				?>

					<div class="lavcs-panel" data-section="schema">
						<div class="lavcs-tabs">
							<button type="button" class="lavcs-tab is-active" data-type="json"><?php esc_html_e( 'JSON-LD Schema', 'lavtheme' ); ?></button>
							<span class="lavcs-spacer"></span>
							<button type="button" class="button lavcs-fullscreen" title="<?php esc_attr_e( 'Toggle fullscreen', 'lavtheme' ); ?>">⤢</button>
						</div>
						<p class="description" style="margin:8px 0;"><?php esc_html_e( 'Structured data (Schema.org / JSON-LD) injected into the <head> of every page. JSON is validated before saving.', 'lavtheme' ); ?></p>
						<div class="lavcs-editorwrap is-active" data-type="json">
							<textarea class="lavcs-editor" data-section="schema" data-type="json" data-mode="application/json"></textarea>
						</div>
						<div class="lavcs-actions">
							<button type="button" class="button button-primary lavcs-save" data-section="schema"><?php esc_html_e( 'Save', 'lavtheme' ); ?></button>
								<button type="button" class="button lavcs-reset" data-section="schema" title="<?php esc_attr_e( 'Reset the schema to the default', 'lavtheme' ); ?>"><?php esc_html_e( 'Reset to default', 'lavtheme' ); ?></button>
								<button type="button" class="button lavcs-export" data-section="schema" title="<?php esc_attr_e( 'Download the saved JSON-LD schema', 'lavtheme' ); ?>"><?php esc_html_e( 'Export', 'lavtheme' ); ?></button>
								<button type="button" class="button lavcs-import" data-section="schema" title="<?php esc_attr_e( 'Import a lavtheme JSON export into the schema', 'lavtheme' ); ?>"><?php esc_html_e( 'Import', 'lavtheme' ); ?></button>
							<span class="lavcs-status" aria-live="polite"></span>
						</div>
					</div>
			</div>
		</div>

		</div><!-- .lavcs-front-area -->

		<div class="lavcs-page-area" hidden>
			<div class="lavcs-page-toolbar">
				<button type="button" class="button button-primary lavcs-page-add">+ <?php esc_html_e( 'Add Section', 'lavtheme' ); ?></button>
				<strong class="lavcs-page-title"></strong>
				<a class="lavcs-page-view button" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'View page ↗', 'lavtheme' ); ?></a>
				<span class="lavcs-page-loading" hidden><?php esc_html_e( 'Loading…', 'lavtheme' ); ?></span>
			</div>
			<p class="description"><?php esc_html_e( 'Per-page sections render via the_content (standard / shortcode pages). Global CSS/JS, Schema and Page Content apply on every page. Note: pages built with Elementor (canvas) bypass the_content, so custom visual sections won\'t show there.', 'lavtheme' ); ?></p>
			<div class="lavcs-layout">
				<div class="lavcs-sidebar">
					<ul class="lavcs-page-nav"></ul>
				</div>
				<div class="lavcs-main lavcs-page-main"></div>
			</div>
		</div>

		<input type="file" class="lavcs-import-file" accept="application/json,.json" hidden>

		<div class="lavcs-modal" hidden>
			<div class="lavcs-modal-box">
				<h2><?php esc_html_e( 'Backups', 'lavtheme' ); ?></h2>
				<ul class="lavcs-backups"></ul>
				<button type="button" class="button lavcs-modal-close"><?php esc_html_e( 'Close', 'lavtheme' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
