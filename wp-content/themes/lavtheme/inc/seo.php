<?php
/**
 * SEO + GEO (AI search) layer — head metadata only, zero visual output.
 *
 * Adds what the site lacks (no SEO plugin is active): meta description, Open
 * Graph, Twitter cards, canonical for non-singular views, rich robots previews,
 * and JSON-LD (Organization, WebSite+SearchAction, Article, BreadcrumbList).
 * Product JSON-LD for EDD downloads is already emitted by code-studio-downloads;
 * this layer deliberately skips it to avoid duplicates. Also serves /llms.txt
 * for AI crawlers. Everything is escaped; nothing renders in the <body>.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * The canonical URL for the current request.
 *
 * @return string
 */
function lavtheme_seo_current_url() {
	if ( is_singular() ) {
		$url = get_permalink();
	} elseif ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_home() ) {
		$url = get_permalink( (int) get_option( 'page_for_posts' ) );
		$url = $url ? $url : home_url( '/' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_author() ) {
		$url = get_author_posts_url( (int) get_query_var( 'author' ) );
	} elseif ( is_post_type_archive() ) {
		$url = get_post_type_archive_link( get_post_type() );
	} elseif ( is_search() ) {
		$url = get_search_link();
	} else {
		$url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ? '/' . $GLOBALS['wp']->request . '/' : '/' ) );
	}
	return is_wp_error( $url ) || ! $url ? home_url( '/' ) : $url;
}

/**
 * A clean meta description for the current context (~155 chars).
 *
 * @return string
 */
function lavtheme_seo_description() {
	$desc = '';

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$desc = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		}
	} elseif ( is_front_page() || is_home() ) {
		$desc = get_bloginfo( 'description' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		$desc = $term instanceof WP_Term ? term_description( $term ) : '';
	} elseif ( is_author() ) {
		$desc = get_the_author_meta( 'description', (int) get_query_var( 'author' ) );
	} elseif ( is_search() ) {
		/* translators: %s: search query. */
		$desc = sprintf( __( 'Search results for “%s”.', 'lavtheme' ), get_search_query() );
	}

	$desc = wp_strip_all_tags( (string) $desc );
	$desc = trim( preg_replace( '/\s+/', ' ', $desc ) );
	if ( '' === $desc ) {
		$desc = get_bloginfo( 'description' );
	}

	/** Allow overriding the computed description. */
	$desc = (string) apply_filters( 'lavtheme_seo_description', $desc );

	if ( '' === $desc ) {
		// Guarantee a meta description always renders — e.g. the front page when
		// the site tagline is empty, or archives with no description. Filterable
		// so each site can tailor the wording.
		$desc = (string) apply_filters(
			'lavtheme_seo_default_description',
			sprintf(
				/* translators: %s: site name. */
				__( '%s — premium digital downloads, automation workflows and SEO services, built to ship fast and rank.', 'lavtheme' ),
				get_bloginfo( 'name' )
			)
		);
	}

	if ( mb_strlen( $desc ) > 160 ) {
		$desc = rtrim( mb_substr( $desc, 0, 157 ), " ,.;:–-" ) . '…';
	}
	return $desc;
}

/**
 * Best representative image URL for the current view (featured image, else logo).
 *
 * @return string
 */
function lavtheme_seo_image() {
	$img = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$img = get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
	}
	if ( ! $img ) {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$img = wp_get_attachment_image_url( $logo_id, 'full' );
		}
	}
	if ( ! $img ) {
		$img = (string) apply_filters( 'lavtheme_seo_default_image', '' );
	}
	return (string) $img;
}

/**
 * Social profile URLs for sameAs — pulled from the "social_sidebar" nav menu,
 * filterable. Used in Organization JSON-LD.
 *
 * @return string[]
 */
function lavtheme_seo_social_profiles() {
	$urls      = array();
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['social_sidebar'] ) ) {
		$items = wp_get_nav_menu_items( (int) $locations['social_sidebar'] );
		if ( $items ) {
			foreach ( $items as $item ) {
				if ( ! empty( $item->url ) && false !== strpos( $item->url, '://' ) ) {
					$urls[] = $item->url;
				}
			}
		}
	}
	return array_values( array_unique( (array) apply_filters( 'lavtheme_seo_social_profiles', $urls ) ) );
}

/* -------------------------------------------------------------------------
 * Robots — richer previews for Google + AI engines.
 * ---------------------------------------------------------------------- */

/**
 * Allow large image/text/video previews (helps Google Discover + AI Overviews).
 *
 * @param array $robots Robots directives.
 * @return array
 */
function lavtheme_seo_robots( $robots ) {
	if ( is_search() || is_404() ) {
		return $robots; // leave core noindex behaviour intact.
	}
	$robots['max-image-preview'] = 'large';
	$robots['max-snippet']       = -1;
	$robots['max-video-preview'] = -1;
	return $robots;
}
add_filter( 'wp_robots', 'lavtheme_seo_robots' );

/**
 * Keyword-rich front-page <title>. The default WordPress front-page title is
 * just the site name ("Lavzen Web"), which wastes the single most important
 * on-page SEO signal. Give the homepage a descriptive, ~55-char title covering
 * the three pillars (products, automation, SEO). Filterable; other views keep
 * WordPress's native title logic.
 *
 * @param string $title Short-circuit title ('' lets core compute it).
 * @return string
 */
function lavtheme_seo_front_title( $title ) {
	if ( is_front_page() ) {
		$name    = get_bloginfo( 'name' );
		$name    = $name ? $name : 'Lavzen';
		$default = sprintf(
			/* translators: %s: site name. */
			__( '%s — Digital Products, Automation & SEO Services', 'lavtheme' ),
			$name
		);
		return (string) apply_filters( 'lavtheme_seo_front_title', $default );
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'lavtheme_seo_front_title' );

/* -------------------------------------------------------------------------
 * <head> output: description, canonical, Open Graph, Twitter.
 * ---------------------------------------------------------------------- */

/**
 * Emit meta tags. Priority 2 so it sits high in <head>, before plugins.
 */
function lavtheme_seo_head_meta() {
	$desc   = lavtheme_seo_description();
	$url    = lavtheme_seo_current_url();
	$title  = wp_get_document_title();
	$image  = lavtheme_seo_image();
	$site   = get_bloginfo( 'name' );
	$is_post = is_singular( 'post' );

	echo "\n<!-- lavtheme SEO -->\n";

	if ( $desc ) {
		printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $desc ) );
	}

	// Canonical: WP core adds rel_canonical for singular; add it for the rest.
	if ( ! is_singular() ) {
		printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $url ) );
	}

	// Open Graph.
	printf( "<meta property=\"og:locale\" content=\"%s\">\n", esc_attr( get_locale() ) );
	printf( "<meta property=\"og:type\" content=\"%s\">\n", esc_attr( $is_post ? 'article' : 'website' ) );
	printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $desc ) );
	}
	printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $url ) );
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( $site ) );
	if ( $image ) {
		printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $image ) );
		printf( "<meta property=\"og:image:alt\" content=\"%s\">\n", esc_attr( $title ) );
	}

	// Article specifics.
	if ( $is_post ) {
		$pid = get_queried_object_id();
		printf( "<meta property=\"article:published_time\" content=\"%s\">\n", esc_attr( get_the_date( 'c', $pid ) ) );
		printf( "<meta property=\"article:modified_time\" content=\"%s\">\n", esc_attr( get_the_modified_date( 'c', $pid ) ) );
		$author = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $pid ) );
		if ( $author ) {
			printf( "<meta property=\"article:author\" content=\"%s\">\n", esc_attr( $author ) );
		}
		$cats = get_the_category( $pid );
		if ( $cats && ! is_wp_error( $cats ) ) {
			printf( "<meta property=\"article:section\" content=\"%s\">\n", esc_attr( $cats[0]->name ) );
		}
		$tags = get_the_tags( $pid );
		if ( $tags && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $t ) {
				printf( "<meta property=\"article:tag\" content=\"%s\">\n", esc_attr( $t->name ) );
			}
		}
	}

	// Twitter card.
	printf( "<meta name=\"twitter:card\" content=\"%s\">\n", $image ? 'summary_large_image' : 'summary' );
	printf( "<meta name=\"twitter:title\" content=\"%s\">\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( "<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr( $desc ) );
	}
	if ( $image ) {
		printf( "<meta name=\"twitter:image\" content=\"%s\">\n", esc_url( $image ) );
	}
}
add_action( 'wp_head', 'lavtheme_seo_head_meta', 2 );

/* -------------------------------------------------------------------------
 * JSON-LD structured data (search engines + AI).
 * ---------------------------------------------------------------------- */

/**
 * Print one JSON-LD block.
 *
 * @param array $data Schema graph node.
 */
function lavtheme_seo_print_jsonld( $data ) {
	$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( $json ) {
		echo '<script type="application/ld+json">' . str_replace( '</', '<\/', $json ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded.
	}
}

/**
 * Sitewide Organization + WebSite (with SearchAction) + per-view Article and
 * BreadcrumbList. Hooked late so it comes after meta tags.
 */
function lavtheme_seo_jsonld() {
	$home = home_url( '/' );
	$name = get_bloginfo( 'name' );

	// Organization (publisher identity for rich results + AI grounding).
	$org = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'@id'      => $home . '#organization',
		'name'     => $name,
		'url'      => $home,
	);
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
		if ( $logo_url ) {
			$org['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo_url,
			);
		}
	}
	$social = lavtheme_seo_social_profiles();
	if ( $social ) {
		$org['sameAs'] = $social;
	}
	lavtheme_seo_print_jsonld( $org );

	// WebSite + SearchAction (enables sitelinks search box + AI query routing).
	lavtheme_seo_print_jsonld(
		array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'@id'             => $home . '#website',
			'url'             => $home,
			'name'            => $name,
			'publisher'       => array( '@id' => $home . '#organization' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => $home . '?s={search_term_string}',
				),
				'query-input' => 'required name=search_term_string',
			),
		)
	);

	// Article + Breadcrumb on single blog posts (downloads already get Product).
	if ( is_singular( 'post' ) ) {
		$pid       = get_queried_object_id();
		$author_id = (int) get_post_field( 'post_author', $pid );
		$article   = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'@id'              => get_permalink( $pid ) . '#article',
			'mainEntityOfPage' => get_permalink( $pid ),
			'headline'         => wp_strip_all_tags( get_the_title( $pid ) ),
			'description'      => lavtheme_seo_description(),
			'datePublished'    => get_the_date( 'c', $pid ),
			'dateModified'     => get_the_modified_date( 'c', $pid ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $author_id ),
				'url'   => get_author_posts_url( $author_id ),
			),
			'publisher'        => array( '@id' => $home . '#organization' ),
		);
		$img = lavtheme_seo_image();
		if ( $img ) {
			$article['image'] = $img;
		}
		$cats = get_the_category( $pid );
		if ( $cats && ! is_wp_error( $cats ) ) {
			$article['articleSection'] = $cats[0]->name;
		}
		$tags = get_the_tags( $pid );
		if ( $tags && ! is_wp_error( $tags ) ) {
			$article['keywords'] = implode( ', ', wp_list_pluck( $tags, 'name' ) );
		}
		$words = str_word_count( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $pid ) ) ) );
		if ( $words ) {
			$article['wordCount'] = $words;
		}
		lavtheme_seo_print_jsonld( $article );

		// BreadcrumbList: Home > Blog > Category > Post.
		$crumbs = array();
		$pos    = 1;
		$crumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => __( 'Home', 'lavtheme' ), 'item' => $home );
		if ( function_exists( 'lavtheme_blog_url' ) ) {
			$crumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => __( 'Blog', 'lavtheme' ), 'item' => lavtheme_blog_url() );
		}
		if ( $cats && ! is_wp_error( $cats ) ) {
			$crumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => $cats[0]->name, 'item' => get_category_link( $cats[0]->term_id ) );
		}
		$crumbs[] = array( '@type' => 'ListItem', 'position' => $pos, 'name' => wp_strip_all_tags( get_the_title( $pid ) ), 'item' => get_permalink( $pid ) );
		lavtheme_seo_print_jsonld(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $crumbs,
			)
		);
	}
}
add_action( 'wp_head', 'lavtheme_seo_jsonld', 20 );

/* -------------------------------------------------------------------------
 * llms.txt — guidance file for AI crawlers (served at /llms.txt).
 * ---------------------------------------------------------------------- */

/**
 * Serve a dynamic /llms.txt without rewrite-rule flushing.
 */
function lavtheme_seo_llms_txt() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	if ( '/llms.txt' !== untrailingslashit( (string) $path ) ) {
		return;
	}

	$name = get_bloginfo( 'name' );
	$desc = get_bloginfo( 'description' );
	$home = home_url( '/' );

	$lines   = array();
	$lines[] = '# ' . $name;
	if ( $desc ) {
		$lines[] = '';
		$lines[] = '> ' . $desc;
	}
	$lines[] = '';
	$lines[] = '## About';
	$lines[] = '';
	$lines[] = $name . ' — ' . $home;
	$lines[] = '';

	// Key blog posts.
	$posts = get_posts(
		array(
			'numberposts'      => 20,
			'post_status'      => 'publish',
			'orderby'          => 'modified',
			'suppress_filters' => false,
		)
	);
	if ( $posts ) {
		$lines[] = '## Articles';
		$lines[] = '';
		foreach ( $posts as $p ) {
			$ex = has_excerpt( $p ) ? wp_strip_all_tags( get_the_excerpt( $p ) ) : '';
			$ex = $ex ? ': ' . wp_trim_words( $ex, 18, '…' ) : '';
			$lines[] = '- [' . wp_strip_all_tags( get_the_title( $p ) ) . '](' . get_permalink( $p ) . ')' . $ex;
		}
		$lines[] = '';
	}

	// Sitemap pointer.
	$lines[] = '## Sitemaps';
	$lines[] = '';
	$lines[] = '- ' . home_url( '/wp-sitemap.xml' );
	$lines[] = '';

	$body = (string) apply_filters( 'lavtheme_seo_llms_txt', implode( "\n", $lines ) );

	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text.
	exit;
}
add_action( 'template_redirect', 'lavtheme_seo_llms_txt' );

/* -------------------------------------------------------------------------
 * robots.txt — point crawlers at the sitemap + allow AI bots explicitly.
 * ---------------------------------------------------------------------- */

/**
 * Augment the virtual robots.txt (only used when no static robots.txt exists).
 *
 * @param string $output Robots.txt contents.
 * @param bool   $public Whether the site is public.
 * @return string
 */
function lavtheme_seo_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	$extra  = "\n# AI crawlers welcome\n";
	$extra .= "User-agent: GPTBot\nAllow: /\n\n";
	$extra .= "User-agent: PerplexityBot\nAllow: /\n\n";
	$extra .= "User-agent: ClaudeBot\nAllow: /\n\n";
	$extra .= "User-agent: Google-Extended\nAllow: /\n\n";
	$extra .= 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
	$extra .= 'LLM: ' . home_url( '/llms.txt' ) . "\n";
	return $output . $extra;
}
add_filter( 'robots_txt', 'lavtheme_seo_robots_txt', 10, 2 );
