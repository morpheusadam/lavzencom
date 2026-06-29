<?php
/**
 * Lavzen theme bootstrap.
 *
 * Intentionally tiny: define constants, register the autoloader, boot the Theme
 * orchestrator. No business logic lives here — every feature is a module under
 * src/Modules/ discovered by the Module_Manager.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

define( 'LAVZEN_VERSION', '2.0.0' );
define( 'LAVZEN_DIR', trailingslashit( get_template_directory() ) );
define( 'LAVZEN_URI', trailingslashit( get_template_directory_uri() ) );

/*
 * Autoloader. Prefer Composer's optimized classmap (run `composer dump-autoload
 * -o` and commit vendor/). Fall back to a PSR-4 shim so the theme still boots on
 * a checkout where vendor/ has not been built yet.
 */
$lavzen_autoload = LAVZEN_DIR . 'vendor/autoload.php';
if ( is_readable( $lavzen_autoload ) ) {
	require $lavzen_autoload;
} else {
	spl_autoload_register(
		static function ( $class ) {
			if ( ! str_starts_with( $class, 'Lavzen\\' ) ) {
				return;
			}
			$relative = str_replace( array( 'Lavzen\\', '\\' ), array( '', '/' ), $class ) . '.php';
			$file     = LAVZEN_DIR . 'src/' . $relative;
			if ( is_readable( $file ) ) {
				require $file;
			}
		}
	);
}

\Lavzen\Theme::instance()->boot();
