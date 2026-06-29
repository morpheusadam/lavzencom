<?php
/**
 * Front page — the LAVZEN "everything AI" marketplace.
 *
 * Static sections (hero, trust, departments, collections, social proof, sell,
 * pillars) are template parts; between them sit product rails wired to live EDD
 * `download` queries via the marketplace helpers (inc/marketplace.php, loaded by
 * the EDD module). The topbar + chrome open in header.php and close in footer.php.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lav_shop      = function_exists( 'lavzen_shop_url' ) ? lavzen_shop_url() : home_url( '/' );
$lav_has_rails = function_exists( 'lavzen_home_rail' ) && function_exists( 'lavzen_home_query' );

get_template_part( 'template-parts/section-hero' );
get_template_part( 'template-parts/section-trust' );

if ( $lav_has_rails ) {
	lavzen_home_rail(
		array(
			'id'       => 'top-picks',
			'title'    => __( 'Top picks for you', 'lavzentheme' ),
			'sub'      => __( 'Trending across the marketplace right now', 'lavzentheme' ),
			'view_url' => add_query_arg( 'orderby', 'trending', $lav_shop ),
			'query'    => lavzen_home_query( array( 'posts_per_page' => 10, 'meta_key' => '_lavzen_rating', 'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) ) ),
		)
	);
	lavzen_home_rail(
		array(
			'id'       => 'best',
			'title'    => __( 'Best sellers this week', 'lavzentheme' ),
			'sub'      => __( 'What builders are buying most', 'lavzentheme' ),
			'view_url' => add_query_arg( 'orderby', 'sales', $lav_shop ),
			'rank'     => '#',
			'query'    => lavzen_home_query( array( 'posts_per_page' => 10, 'meta_key' => '_edd_download_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC' ) ),
		)
	);
}

get_template_part( 'template-parts/section-departments' );

if ( $lav_has_rails ) {
	lavzen_home_rail(
		array(
			'id'       => 'new',
			'title'    => __( 'Just dropped', 'lavzentheme' ),
			'sub'      => __( 'New & verified this week', 'lavzentheme' ),
			'view_url' => add_query_arg( 'orderby', 'date', $lav_shop ),
			'rank'     => true,
			'query'    => lavzen_home_query( array( 'posts_per_page' => 10, 'orderby' => 'date', 'order' => 'DESC' ) ),
		)
	);
}

get_template_part( 'template-parts/section-collections' );

if ( $lav_has_rails ) {
	lavzen_home_rail(
		array(
			'id'       => 'mcp',
			'title'    => __( 'Top MCP servers for Claude', 'lavzentheme' ),
			'sub'      => __( 'Connect your stack in one click', 'lavzentheme' ),
			'view_url' => lavzen_home_term_url( 'connectors-mcp-servers' ),
			'query'    => lavzen_home_query( array( 'posts_per_page' => 10, 'meta_key' => '_edd_download_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'tax_query' => array( array( 'taxonomy' => 'download_category', 'field' => 'slug', 'terms' => 'connectors-mcp-servers' ) ) ) ),
		)
	);
	lavzen_home_rail(
		array(
			'id'       => 'n8n',
			'title'    => __( 'Best n8n automations', 'lavzentheme' ),
			'sub'      => __( 'Ready-to-import flows', 'lavzentheme' ),
			'view_url' => lavzen_home_term_url( 'workflows-automations' ),
			'query'    => lavzen_home_query( array( 'posts_per_page' => 10, 'meta_key' => '_lavzen_rating', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'tax_query' => array( array( 'taxonomy' => 'download_category', 'field' => 'slug', 'terms' => 'workflows-automations' ) ) ) ),
		)
	);
	lavzen_home_rail(
		array(
			'id'       => 'under20',
			'title'    => __( 'Models & assets under $20', 'lavzentheme' ),
			'sub'      => __( 'Big leverage, small price', 'lavzentheme' ),
			'view_url' => add_query_arg( 'max', 20, $lav_shop ),
			'query'    => lavzen_home_query(
				array(
					'posts_per_page' => 10,
					'meta_key'       => 'edd_price',
					'orderby'        => 'meta_value_num',
					'order'          => 'ASC',
					'meta_query'     => array(
						'relation' => 'AND',
						array( 'key' => 'edd_price', 'value' => 0, 'type' => 'NUMERIC', 'compare' => '>' ),
						array( 'key' => 'edd_price', 'value' => 20, 'type' => 'NUMERIC', 'compare' => '<=' ),
					),
				)
			),
		)
	);
}

get_template_part( 'template-parts/section-sproof' );
get_template_part( 'template-parts/section-sell' );
get_template_part( 'template-parts/section-pillars' );

get_footer();
