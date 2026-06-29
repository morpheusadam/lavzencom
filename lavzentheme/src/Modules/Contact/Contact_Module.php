<?php
/**
 * Contact module — lead-capture form + private Lead CPT.
 *
 * Loads inc/contact.php (self-registers the lav_lead CPT, the form renderer, and
 * the nonce-guarded admin-post handler). The form is rendered by page-contact.php.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Contact;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Contact_Module extends Abstract_Module {

	public function id(): string {
		return 'contact';
	}

	public function boot(): void {
		require_once LAVZEN_DIR . 'inc/contact.php';
	}
}
