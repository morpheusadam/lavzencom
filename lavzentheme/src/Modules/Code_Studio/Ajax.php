<?php
/**
 * Ajax — unified Code Studio save/load/reset/restore.
 *
 * ONE handler set, scope-aware, replacing the legacy theme's ~42 AJAX actions
 * across three families (lavtheme_cs_* / lavtheme_cs_dl_* / lavtheme_cs_page_*).
 * Every call is nonce- and capability-guarded; content is sanitised by type;
 * a value identical to the file default drops the override (file = source of
 * truth) and an empty value either falls back (html) or marks an intentional
 * clear (css/js).
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Code_Studio;

defined( 'ABSPATH' ) || exit;

final class Ajax {

	private const NONCE = 'lavzen_cs';
	private const CAP   = 'edit_theme_options';

	public function __construct(
		private Section_Store $store,
		private Source_Reader $reader
	) {}

	public function register(): void {
		add_action( 'wp_ajax_lavzen_cs_load', array( $this, 'load' ) );
		add_action( 'wp_ajax_lavzen_cs_save', array( $this, 'save' ) );
		add_action( 'wp_ajax_lavzen_cs_reset', array( $this, 'reset' ) );
		add_action( 'wp_ajax_lavzen_cs_restore', array( $this, 'restore' ) );
	}

	/**
	 * Shared guard. Returns the validated [scope, section, type]; exits on failure.
	 *
	 * @return array{0:string,1:string,2:string}
	 */
	private function guard(): array {
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lavzentheme' ) ), 403 );
		}
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'lavzentheme' ) ), 400 );
		}
		$scope   = isset( $_POST['scope'] ) ? sanitize_text_field( wp_unslash( $_POST['scope'] ) ) : '';
		$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
		$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		if ( '' === $scope || '' === $section || ! in_array( $type, Section_Store::TYPES, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Bad request.', 'lavzentheme' ) ), 400 );
		}
		return array( $scope, $section, $type );
	}

	/**
	 * Return the current editor value (override if present, else the file default).
	 */
	public function load(): void {
		list( $scope, $section, $type ) = $this->guard();
		$override = $this->store->get( $scope, $section, $type );
		$default  = $this->reader->default( $scope, $section, $type );
		wp_send_json_success(
			array(
				'value'       => null === $override ? $default : $override,
				'is_override' => null !== $override,
				'default'     => $default,
			)
		);
	}

	/**
	 * Save one editor's content.
	 */
	public function save(): void {
		list( $scope, $section, $type ) = $this->guard();
		// Raw content — unslash only (sanitise by type below; never sanitize_text_field code).
		$content = isset( $_POST['content'] ) ? (string) wp_unslash( $_POST['content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$clean   = $this->sanitize( $content, $type );
		$default = $this->reader->default( $scope, $section, $type );

		if ( '' === trim( $clean ) ) {
			if ( 'html' === $type ) {
				$this->store->reset( $scope, $section, $type );
				wp_send_json_success( array( 'message' => __( 'Matches default — using the theme file.', 'lavzentheme' ) ) );
			}
			$this->store->mark_empty( $scope, $section, $type );
			wp_send_json_success( array( 'message' => __( 'Cleared — this field now outputs nothing.', 'lavzentheme' ), 'emptied' => true ) );
		}

		if ( trim( $clean ) === trim( $default ) ) {
			$this->store->reset( $scope, $section, $type );
			wp_send_json_success( array( 'message' => __( 'Matches default — using the theme file.', 'lavzentheme' ) ) );
		}

		$this->store->set( $scope, $section, $type, $clean );
		wp_send_json_success( array( 'message' => __( 'Saved.', 'lavzentheme' ) ) );
	}

	/**
	 * Reset to the file default (keeps a one-step undo).
	 */
	public function reset(): void {
		list( $scope, $section, $type ) = $this->guard();
		$this->store->reset( $scope, $section, $type );
		wp_send_json_success(
			array(
				'value'   => $this->reader->default( $scope, $section, $type ),
				'message' => __( 'Reset to the theme file default.', 'lavzentheme' ),
			)
		);
	}

	/**
	 * Restore the previous value (one-step undo).
	 */
	public function restore(): void {
		list( $scope, $section, $type ) = $this->guard();
		$value = $this->store->restore_prev( $scope, $section, $type );
		if ( null === $value ) {
			wp_send_json_error( array( 'message' => __( 'Nothing to restore.', 'lavzentheme' ) ) );
		}
		wp_send_json_success( array( 'value' => $value, 'message' => __( 'Restored.', 'lavzentheme' ) ) );
	}

	/**
	 * Sanitise editor content by type. HTML is stored raw and kses'd at output.
	 */
	private function sanitize( string $content, string $type ): string {
		return match ( $type ) {
			'css', 'mcss', 'root', 'bg' => $this->sanitize_css( $content ),
			'js'                        => str_ireplace( '</script', '<\/script', $content ),
			default                     => $content,
		};
	}

	/**
	 * Strip tags + dangerous tokens from CSS.
	 */
	private function sanitize_css( string $css ): string {
		$css = wp_strip_all_tags( $css );
		$css = (string) preg_replace( '/(expression\s*\(|javascript\s*:|behavior\s*:|@import|<\/?script)/i', '', $css );
		return trim( $css );
	}
}
