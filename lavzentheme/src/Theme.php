<?php
/**
 * Theme bootstrap orchestrator.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen;

use Lavzen\Support\Singleton;
use Lavzen\Core\Setup;
use Lavzen\Core\Assets;
use Lavzen\Core\Template;
use Lavzen\Context\Context_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * The single legitimate singleton. Boots cross-cutting core services, the
 * per-context CSS/JS registry, and (deferred to after_setup_theme:20, so
 * WooCommerce/EDD have registered) the feature modules.
 */
final class Theme {

	use Singleton;

	/**
	 * Wire everything up.
	 */
	public function boot(): void {
		// Cross-cutting core concerns.
		Setup::instance();
		Assets::instance();
		Template::instance();

		// Per-context asset injection (replaces the old per-context clone files).
		Context_Registry::instance()->boot();

		// Feature modules — deferred so integration modules can detect their plugins.
		add_action(
			'after_setup_theme',
			static function () {
				Module_Manager::instance()->discover()->boot_active();
			},
			20
		);
	}
}
