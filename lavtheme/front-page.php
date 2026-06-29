<?php
/**
 * Front page — the LAVZEN "everything AI" marketplace.
 *
 * Static blocks (hero, trust, department bento, collections, social proof, sell,
 * pillars) are Code-Studio-editable sections rendered via lavtheme_render_section().
 * Between them sit product rails wired to live EDD `download` queries
 * (see inc/home.php). The topbar + <main id="content"> open in header.php, the
 * footer + tab bar close in footer.php (both branch on is_front_page()).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lav_shop = lavtheme_shop_url();

// Hero + trust.
lavtheme_render_section( 'hero' );
lavtheme_render_section( 'trust' );

// Top picks — highest rated across the marketplace.
lavtheme_home_rail(
	array(
		'id'       => 'top-picks',
		'title'    => __( 'Top picks for you', 'lavtheme' ),
		'sub'      => __( 'Trending across the marketplace right now', 'lavtheme' ),
		'view_url' => add_query_arg( 'orderby', 'trending', $lav_shop ),
		'query'    => lavtheme_home_query(
			array(
				'posts_per_page' => 10,
				'meta_key'       => '_lavzen_rating',
				'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
			)
		),
	)
);

// Best sellers — most sales.
lavtheme_home_rail(
	array(
		'id'       => 'best',
		'title'    => __( 'Best sellers this week', 'lavtheme' ),
		'sub'      => __( 'What builders are buying most', 'lavtheme' ),
		'view_url' => add_query_arg( 'orderby', 'sales', $lav_shop ),
		'rank'     => '#',
		'query'    => lavtheme_home_query(
			array(
				'posts_per_page' => 10,
				'meta_key'       => '_edd_download_sales',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
			)
		),
	)
);

// Department bento.
lavtheme_render_section( 'departments' );

// Just dropped — newest listings.
lavtheme_home_rail(
	array(
		'id'       => 'new',
		'title'    => __( 'Just dropped', 'lavtheme' ),
		'sub'      => __( 'New & verified this week', 'lavtheme' ),
		'view_url' => add_query_arg( 'orderby', 'date', $lav_shop ),
		'rank'     => true,
		'query'    => lavtheme_home_query(
			array(
				'posts_per_page' => 10,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		),
	)
);

// Curated collections.
lavtheme_render_section( 'collections' );

// Top MCP servers — the Agents → Connectors & MCP Servers branch.
lavtheme_home_rail(
	array(
		'id'       => 'mcp',
		'title'    => __( 'Top MCP servers for Claude', 'lavtheme' ),
		'sub'      => __( 'Connect your stack in one click', 'lavtheme' ),
		'view_url' => lavtheme_home_term_url( 'connectors-mcp-servers' ),
		'query'    => lavtheme_home_query(
			array(
				'posts_per_page' => 10,
				'meta_key'       => '_edd_download_sales',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'tax_query'      => array(
					array( 'taxonomy' => 'download_category', 'field' => 'slug', 'terms' => 'connectors-mcp-servers' ),
				),
			)
		),
	)
);

// Best n8n automations — the Agents → Workflows & Automations branch.
lavtheme_home_rail(
	array(
		'id'       => 'n8n',
		'title'    => __( 'Best n8n automations', 'lavtheme' ),
		'sub'      => __( 'Ready-to-import flows', 'lavtheme' ),
		'view_url' => lavtheme_home_term_url( 'workflows-automations' ),
		'query'    => lavtheme_home_query(
			array(
				'posts_per_page' => 10,
				'meta_key'       => '_lavzen_rating',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'tax_query'      => array(
					array( 'taxonomy' => 'download_category', 'field' => 'slug', 'terms' => 'workflows-automations' ),
				),
			)
		),
	)
);

// Models & assets under $20.
lavtheme_home_rail(
	array(
		'id'       => 'under20',
		'title'    => __( 'Models & assets under $20', 'lavtheme' ),
		'sub'      => __( 'Big leverage, small price', 'lavtheme' ),
		'view_url' => add_query_arg( 'max', 20, $lav_shop ),
		'query'    => lavtheme_home_query(
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

// Social proof + seller band + value pillars.
lavtheme_render_section( 'sproof' );
lavtheme_render_section( 'sell' );
lavtheme_render_section( 'pillars' );

get_footer();
