<?php
/**
 * Template tags — thin procedural wrappers used inside template files.
 *
 * Templates stay readable (lavzen_topnav()) while the logic lives in OOP services.
 * This file is procedural by design (WordPress template-tag convention) and is
 * required by the Theme bootstrap; it is intentionally outside the autoloaded
 * Lavzen\ namespace.
 *
 * @package Lavzen
 */

use Lavzen\Core\Navigation;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lavzen_topnav' ) ) {
	/**
	 * Echo the desktop topnav.
	 */
	function lavzen_topnav(): void {
		Navigation::instance()->topnav();
	}
}

if ( ! function_exists( 'lavzen_account_popover' ) ) {
	/**
	 * Return the account-popover markup.
	 */
	function lavzen_account_popover(): string {
		return Navigation::instance()->account_popover();
	}
}

if ( ! function_exists( 'lavzen_shop_categories_nav' ) ) {
	/**
	 * Return the shop-categories menu markup.
	 *
	 * @param int $limit Max terms (0 = all).
	 */
	function lavzen_shop_categories_nav( int $limit = 0 ): string {
		return Navigation::instance()->shop_categories_nav( $limit );
	}
}

if ( ! function_exists( 'lavzen_blog_url' ) ) {
	/**
	 * The blog landing URL.
	 */
	function lavzen_blog_url(): string {
		return Navigation::instance()->blog_url();
	}
}
