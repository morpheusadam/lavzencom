<?php
/**
 * Wrapper template for the seeded "My Account" page. Routed here by
 * lavtheme_cs_account_page_template() (template_include). Opens the theme shell
 * (get_header → .app/.main) and renders the account dashboard body — the Code
 * Studio Template override, else template-parts/account.php.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! headers_sent() ) {
	header( 'X-Content-Type-Options: nosniff' );
}

get_header();

if ( function_exists( 'lavtheme_cs_account_render' ) ) {
	lavtheme_cs_account_render();
} else {
	$lav_acct_body = get_theme_file_path( 'template-parts/account.php' );
	if ( is_readable( $lav_acct_body ) ) {
		include $lav_acct_body;
	}
}

get_footer();
