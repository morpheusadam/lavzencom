<?php
/**
 * Code Studio module.
 *
 * The in-dashboard code editor for the theme, rebuilt clean. Front-end side
 * (live override injection) is wired here via the single Injector. The admin UI
 * + unified AJAX (save/load/reset/restore/rename/reorder for global / context /
 * page scopes) and Source_Reader (file-default resolution for the editors) are
 * layered on in the following sub-phases — all on top of the one Section_Store.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Code_Studio;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Code_Studio_Module extends Abstract_Module {

	private Section_Store $store;

	public function id(): string {
		return 'code_studio';
	}

	public function boot(): void {
		$this->store = new Section_Store();

		// Front-end: one scope-aware injector (replaces the ~8 per-context clones).
		( new Injector( $this->store ) )->register();

		// Admin: the unified AJAX layer (replaces the legacy ~42 actions) + editor UI.
		if ( is_admin() ) {
			( new Ajax( $this->store, new Source_Reader() ) )->register();
			( new Admin\Editor_Page() )->register();
		}
	}

	/**
	 * Expose the store to other code (e.g. modules that read overrides).
	 */
	public function store(): Section_Store {
		return $this->store;
	}
}
