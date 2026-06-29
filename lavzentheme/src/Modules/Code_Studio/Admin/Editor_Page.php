<?php
/**
 * Code Studio admin editor page.
 *
 * Registers the "Code Studio" admin menu and renders a clean editor that drives
 * the unified Ajax layer (load/save/reset/restore). One page edits every
 * surface (Global + each context) via a scope selector — replacing the legacy
 * theme's three separate editor screens.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Code_Studio\Admin;

defined( 'ABSPATH' ) || exit;

final class Editor_Page {

	private const SLUG  = 'lavzen-code-studio';
	private const CAP   = 'edit_theme_options';
	private const NONCE = 'lavzen_cs';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * The editable surfaces: scope => { label, section, types }.
	 *
	 * @return array<string, array{label:string, section:string, types:string[]}>
	 */
	public function surfaces(): array {
		return array(
			'global'       => array( 'label' => __( 'Global', 'lavzentheme' ), 'section' => 'global', 'types' => array( 'root', 'css', 'bg', 'js' ) ),
			'ctx:single'   => array( 'label' => __( 'Single Post', 'lavzentheme' ), 'section' => 'design', 'types' => array( 'html', 'css', 'js', 'mcss' ) ),
			'ctx:404'      => array( 'label' => __( '404 / Error', 'lavzentheme' ), 'section' => 'design', 'types' => array( 'html', 'css', 'js', 'mcss' ) ),
			'ctx:shop'     => array( 'label' => __( 'Shop (archive)', 'lavzentheme' ), 'section' => 'design', 'types' => array( 'css', 'js', 'mcss' ) ),
			'ctx:blog'     => array( 'label' => __( 'Blog (archive)', 'lavzentheme' ), 'section' => 'design', 'types' => array( 'css', 'js', 'mcss' ) ),
			'ctx:account'  => array( 'label' => __( 'Account', 'lavzentheme' ), 'section' => 'design', 'types' => array( 'css', 'js', 'mcss' ) ),
			'ctx:auth'     => array( 'label' => __( 'Auth / Login', 'lavzentheme' ), 'section' => 'design', 'types' => array( 'css', 'js', 'mcss' ) ),
			'ctx:download' => array( 'label' => __( 'Single Download', 'lavzentheme' ), 'section' => 'design', 'types' => array( 'css', 'js', 'mcss' ) ),
		);
	}

	public function menu(): void {
		add_menu_page(
			__( 'Code Studio', 'lavzentheme' ),
			__( 'Code Studio', 'lavzentheme' ),
			self::CAP,
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-editor-code',
			59
		);
	}

	/**
	 * Enqueue the editor JS/CSS only on our screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function assets( string $hook ): void {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		$css = 'assets/admin/code-studio.css';
		$js  = 'assets/admin/code-studio.js';
		wp_enqueue_style( 'lavzen-cs', get_theme_file_uri( $css ), array(), (string) @filemtime( get_theme_file_path( $css ) ) );
		wp_enqueue_script( 'lavzen-cs', get_theme_file_uri( $js ), array(), (string) @filemtime( get_theme_file_path( $js ) ), true );
		wp_localize_script(
			'lavzen-cs',
			'LavzenCS',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( self::NONCE ),
				'surfaces' => $this->surfaces(),
				'i18n'     => array(
					'saved'    => __( 'Saved.', 'lavzentheme' ),
					'loading'  => __( 'Loading…', 'lavzentheme' ),
					'error'    => __( 'Something went wrong.', 'lavzentheme' ),
					'confirm'  => __( 'Reset this field to the theme default?', 'lavzentheme' ),
				),
			)
		);
	}

	public function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		?>
		<div class="wrap lavcs">
			<h1 class="lavcs-title"><?php esc_html_e( 'Code Studio', 'lavzentheme' ); ?></h1>
			<p class="lavcs-lead"><?php esc_html_e( 'Edit theme code per surface. Overrides are stored in the database (non-autoloaded) and layered over the theme files; an empty save falls back to the file (HTML) or clears the field (CSS/JS).', 'lavzentheme' ); ?></p>

			<div class="lavcs-bar">
				<label class="lavcs-ctl">
					<span><?php esc_html_e( 'Surface', 'lavzentheme' ); ?></span>
					<select id="lavcs-scope"></select>
				</label>
				<label class="lavcs-ctl">
					<span><?php esc_html_e( 'Field', 'lavzentheme' ); ?></span>
					<select id="lavcs-type"></select>
				</label>
				<span class="lavcs-flag" id="lavcs-flag"></span>
			</div>

			<textarea id="lavcs-editor" class="lavcs-editor" spellcheck="false" wrap="off" placeholder="<?php esc_attr_e( 'Loading…', 'lavzentheme' ); ?>"></textarea>

			<div class="lavcs-actions">
				<button type="button" class="button button-primary" id="lavcs-save"><?php esc_html_e( 'Save', 'lavzentheme' ); ?></button>
				<button type="button" class="button" id="lavcs-reset"><?php esc_html_e( 'Reset to default', 'lavzentheme' ); ?></button>
				<button type="button" class="button" id="lavcs-restore"><?php esc_html_e( 'Restore previous', 'lavzentheme' ); ?></button>
				<span class="lavcs-status" id="lavcs-status" role="status" aria-live="polite"></span>
			</div>
		</div>
		<?php
	}
}
