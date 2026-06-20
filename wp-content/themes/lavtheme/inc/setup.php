<?php
/**
 * Theme setup: supports, menus, image sizes.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports and navigation menus.
 */
function lavtheme_setup() {
	load_theme_textdomain( 'lavtheme', LAVTHEME_DIR . 'languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
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
			'primary'        => __( 'Desktop Navbar (.topnav)', 'lavtheme' ),
			'mobile'         => __( 'Mobile Navbar (bottom)', 'lavtheme' ),
			'social_sidebar' => __( 'Social Sidebar (desktop rail)', 'lavtheme' ),
			'account'        => __( 'Account (avatar popover)', 'lavtheme' ),
			'shop_categories' => __( 'Shop Categories', 'lavtheme' ),
		)
	);

	// Image size matching the blog card thumbnail aspect (16:10).
	add_image_size( 'lavtheme-card', 640, 400, true );
}
add_action( 'after_setup_theme', 'lavtheme_setup' );

/**
 * Content width.
 */
function lavtheme_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'lavtheme_content_width', 1440 );
}
add_action( 'after_setup_theme', 'lavtheme_content_width', 0 );
