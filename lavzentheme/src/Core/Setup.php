<?php
/**
 * Theme setup: supports, menus, image sizes, content width.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Core;

use Lavzen\Support\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Registers theme feature support, navigation menus and image sizes. Ported
 * 1:1 from the legacy theme's inc/setup.php (behavior preserved, re-namespaced).
 */
final class Setup {

	use Singleton;

	protected function init(): void {
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_action( 'after_setup_theme', array( $this, 'content_width' ), 0 );
	}

	/**
	 * Theme supports, menus, image sizes.
	 */
	public function theme_supports(): void {
		load_theme_textdomain( 'lavzentheme', LAVZEN_DIR . 'languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
		);
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 52,
				'width'       => 320,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);

		register_nav_menus(
			array(
				'primary'         => __( 'Desktop Navbar (.topnav)', 'lavzentheme' ),
				'mobile'          => __( 'Mobile Navbar (bottom)', 'lavzentheme' ),
				'social_sidebar'  => __( 'Social Sidebar (desktop rail)', 'lavzentheme' ),
				'account'         => __( 'Account (avatar popover)', 'lavzentheme' ),
				'shop_categories' => __( 'Shop Categories', 'lavzentheme' ),
			)
		);

		// Blog card thumbnail aspect (16:10).
		add_image_size( 'lavzen-card', 640, 400, true );
	}

	/**
	 * Content width.
	 */
	public function content_width(): void {
		$GLOBALS['content_width'] = apply_filters( 'lavzen_content_width', 1440 );
	}
}
