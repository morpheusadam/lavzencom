<?php
/**
 * Front-end performance hardening — non-visual only.
 *
 * Trims render-blocking and unused <head> output that ships with WordPress by
 * default (emoji polyfill, RSD/WLW/shortlink/generator/adjacency links) and
 * defers theme-owned, dependency-free scripts. None of this changes a single
 * pixel: native emoji still render, feeds stay enabled (SEO), and deferred
 * progressive scripts run after parse. Admin and the block editor are untouched.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove the emoji polyfill (wp-emoji-release.min.js + inline detection script
 * and CSS). Modern browsers render emoji natively, so this is purely dead weight.
 */
function lavtheme_perf_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'lavtheme_perf_disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'lavtheme_perf_remove_emoji_dns', 10, 2 );
}
add_action( 'init', 'lavtheme_perf_disable_emojis' );

/**
 * Drop the emoji TinyMCE plugin.
 *
 * @param array $plugins TinyMCE plugins.
 * @return array
 */
function lavtheme_perf_disable_emojis_tinymce( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
}

/**
 * Remove the s.w.org emoji DNS-prefetch hint.
 *
 * @param array  $urls          Resource hints.
 * @param string $relation_type Hint type.
 * @return array
 */
function lavtheme_perf_remove_emoji_dns( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$emoji = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/' );
		$urls  = array_filter(
			$urls,
			function ( $url ) use ( $emoji ) {
				$u = is_array( $url ) && isset( $url['href'] ) ? $url['href'] : $url;
				return is_string( $u ) ? false === strpos( $u, $emoji ) : true;
			}
		);
	}
	return $urls;
}

/**
 * Strip legacy/unused <head> link tags. SEO-relevant tags (canonical, feeds,
 * pingback) are intentionally kept.
 */
function lavtheme_perf_clean_head() {
	remove_action( 'wp_head', 'rsd_link' );                      // Really Simple Discovery.
	remove_action( 'wp_head', 'wlwmanifest_link' );              // Windows Live Writer.
	remove_action( 'wp_head', 'wp_generator' );                  // WordPress version (also small hardening).
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );          // ?p= shortlink.
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' ); // prev/next rel (noise for non-paginated singles).
}
add_action( 'init', 'lavtheme_perf_clean_head' );

/**
 * Add defer to theme-owned, dependency-free front-end scripts so they never
 * block the parser. Scoped to known handles to avoid touching jQuery/Elementor.
 *
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return string
 */
function lavtheme_perf_defer_scripts( $tag, $handle ) {
	if ( is_admin() ) {
		return $tag;
	}
	$defer = (array) apply_filters( 'lavtheme_perf_defer_handles', array( 'lavtheme-single' ) );
	if ( in_array( $handle, $defer, true ) && false === strpos( $tag, ' defer' ) && false === strpos( $tag, ' async' ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'lavtheme_perf_defer_scripts', 10, 2 );
