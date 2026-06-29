<?php
/**
 * Conditional tags + page-id helpers used by the Context system and navigation.
 *
 * These are the predicates referenced by config/contexts.php. They read the
 * relevant EDD state / option-stored page ids (with filters so the EDD and Auth
 * modules in Phase 4 can refine them). Legacy option keys are read for data
 * continuity at cutover. Procedural by design (conditional-tag convention) and
 * required by the Theme bootstrap, outside the autoloaded namespace.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lavzen_blog_page_id' ) ) {
	/**
	 * The posts/blog page id.
	 */
	function lavzen_blog_page_id(): int {
		return (int) apply_filters( 'lavzen_blog_page_id', (int) get_option( 'page_for_posts' ) );
	}
}

if ( ! function_exists( 'lavzen_account_page_id' ) ) {
	/**
	 * The "My Account" page id (legacy key read for continuity).
	 */
	function lavzen_account_page_id(): int {
		return (int) apply_filters( 'lavzen_account_page_id', (int) get_option( 'lavtheme_account_page_id', 0 ) );
	}
}

if ( ! function_exists( 'lavzen_auth_page_id' ) ) {
	/**
	 * The login/auth page id (legacy key read for continuity).
	 */
	function lavzen_auth_page_id(): int {
		return (int) apply_filters( 'lavzen_auth_page_id', (int) get_option( 'lavtheme_login_page_id', 0 ) );
	}
}

if ( ! function_exists( 'lavzen_shop_url' ) ) {
	/**
	 * The shop landing URL: the EDD download archive, else home. Filterable.
	 */
	function lavzen_shop_url(): string {
		$url = get_post_type_archive_link( 'download' );
		$url = $url ? $url : home_url( '/' );
		return (string) apply_filters( 'lavzen_shop_url', $url );
	}
}

if ( ! function_exists( 'lavzen_is_download' ) ) {
	/**
	 * Single EDD download.
	 */
	function lavzen_is_download(): bool {
		return is_singular( 'download' );
	}
}

if ( ! function_exists( 'lavzen_is_shop' ) ) {
	/**
	 * Shop archive: download post-type archive or its taxonomies.
	 */
	function lavzen_is_shop(): bool {
		return is_post_type_archive( 'download' ) || is_tax( array( 'download_category', 'download_tag' ) );
	}
}

if ( ! function_exists( 'lavzen_is_account' ) ) {
	/**
	 * The My Account page.
	 */
	function lavzen_is_account(): bool {
		$id = lavzen_account_page_id();
		return $id > 0 && is_page( $id );
	}
}

if ( ! function_exists( 'lavzen_is_auth' ) ) {
	/**
	 * The login/auth page.
	 */
	function lavzen_is_auth(): bool {
		$id = lavzen_auth_page_id();
		return $id > 0 && is_page( $id );
	}
}
