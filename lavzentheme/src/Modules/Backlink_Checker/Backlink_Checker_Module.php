<?php
/**
 * Backlink Spam Checker module — standalone admin tool under Tools.
 *
 * Loads inc/backlink-checker.php (self-contained: admin_menu + wp_ajax_* only,
 * unique lavzen_blc_ prefix, inlined CSS/JS). Live SSE results with an automatic
 * AJAX-polling fallback. Admin-only; no front-end footprint.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Backlink_Checker;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Backlink_Checker_Module extends Abstract_Module {

	public function id(): string {
		return 'backlink_checker';
	}

	public function is_active(): bool {
		// Admin utility only — no need to load it on front-end requests.
		return (bool) apply_filters( 'lavzen/module/backlink_checker/active', is_admin() );
	}

	public function boot(): void {
		require_once LAVZEN_DIR . 'inc/backlink-checker.php';
	}
}
