<?php
/**
 * Template-layer concerns (body classes, template-hierarchy tweaks).
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Core;

use Lavzen\Support\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Cross-cutting template behavior that is not tied to a single context.
 *
 * NOTE: filled in during Phase 1 (template port). Stub for now.
 */
final class Template {

	use Singleton;

	protected function init(): void {
		// Phase 1: register template_include / body_class / template-tag wiring.
	}
}
