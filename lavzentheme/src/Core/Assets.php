<?php
/**
 * Global (site-wide) asset enqueue.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Core;

use Lavzen\Support\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the shared, every-page CSS/JS (design system + chrome). Per-context
 * assets are handled by Lavzen\Context\Context_Registry, not here.
 *
 * NOTE: filled in during Phase 5 (asset consolidation — merging the legacy
 * /css and /assets/css trees into assets/dist). Stub for now so boot is clean.
 */
final class Assets {

	use Singleton;

	protected function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue global front-end assets.
	 */
	public function enqueue(): void {
		// Phase 5: enqueue the consolidated design-system stylesheet + chrome here,
		// with filemtime() versioning. Intentionally empty during scaffold.
	}
}
