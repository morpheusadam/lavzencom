<?php
/**
 * Source_Reader — resolves the FILE default for a (scope, section, type).
 *
 * When there is no DB override, the editor (and the front end) fall back to the
 * theme's own files. This maps a scope/section/type to its theme-relative file
 * and reads it. Missing files resolve to '' (graceful) — the asset paths firm up
 * in Phase 5 when the /css + /assets/css trees are consolidated into assets/dist.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Code_Studio;

defined( 'ABSPATH' ) || exit;

final class Source_Reader {

	/**
	 * Theme-relative default file for a triple, or '' when none applies.
	 */
	public function file( string $scope, string $section, string $type ): string {
		// HTML/template defaults are real template files.
		if ( 'html' === $type ) {
			return match ( $scope ) {
				'ctx:single' => 'template-parts/single-article.php',
				'ctx:404'    => '404.php',
				default      => '',
			};
		}

		// CSS/JS defaults live under assets/dist, keyed by context (global => main).
		$ctx = '';
		if ( 'global' === $scope ) {
			$ctx = 'main';
		} elseif ( str_starts_with( $scope, 'ctx:' ) ) {
			$ctx = substr( $scope, 4 );
		}
		if ( '' === $ctx ) {
			return '';
		}

		return match ( $type ) {
			'css'  => "assets/dist/css/{$ctx}.css",
			'js'   => "assets/dist/js/{$ctx}.js",
			'mcss' => "assets/dist/css/{$ctx}.mobile.css",
			default => '',
		};
	}

	/**
	 * The file default contents (or '' when there is no readable default).
	 */
	public function default( string $scope, string $section, string $type ): string {
		$relative = $this->file( $scope, $section, $type );
		if ( '' === $relative ) {
			return '';
		}
		$path = get_theme_file_path( $relative );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return is_readable( $path ) ? (string) file_get_contents( $path ) : '';
	}
}
