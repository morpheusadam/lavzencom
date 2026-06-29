<?php
/**
 * Account + Auth module.
 *
 * Loads the account/auth subsystem (inc/account-auth.php): page routing for the
 * seeded My Account + Login pages, the login/register self-POST handler (WP core),
 * and the display helpers used by the account/auth template parts. Their CSS/JS
 * is enqueued by the Context system (ctx:account / ctx:auth).
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Account;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Account_Module extends Abstract_Module {

	public function id(): string {
		return 'account';
	}

	public function boot(): void {
		// The subsystem registers its own hooks (template_redirect, template_include,
		// login_redirect, admin_init seeding) at require time.
		require_once LAVZEN_DIR . 'inc/account-auth.php';
	}
}
