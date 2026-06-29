<?php
/**
 * SEO + GEO module — head metadata only, zero visual output.
 *
 * Ported from the legacy inc/seo.php: meta description, canonical (non-singular),
 * Open Graph, Twitter cards, rich robots, JSON-LD (Organization, WebSite+Search,
 * BlogPosting + Breadcrumb), /llms.txt and robots.txt augmentation.
 *
 * AUDIT FIX: the legacy theme emitted all of this WHILE Rank Math Pro was active,
 * producing duplicate canonical / OG / Organization / WebSite / Article on every
 * page (measured live). This module is therefore INACTIVE by default whenever a
 * dedicated SEO plugin (Rank Math / Yoast) is detected — single-source ownership.
 * Force it on/off with the `lavzen/module/seo/active` filter.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Seo;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Seo_Module extends Abstract_Module {

	public function id(): string {
		return 'seo';
	}

	/**
	 * Active only when NO dedicated SEO plugin owns the metadata.
	 */
	public function is_active(): bool {
		$seo_plugin = defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| class_exists( 'RankMath' )
			|| function_exists( 'rank_math' );
		return (bool) apply_filters( 'lavzen/module/seo/active', ! $seo_plugin );
	}

	public function boot(): void {
		add_filter( 'wp_robots', array( $this, 'robots' ) );
		add_filter( 'pre_get_document_title', array( $this, 'front_title' ) );
		add_action( 'wp_head', array( $this, 'head_meta' ), 2 );
		add_action( 'wp_head', array( $this, 'jsonld' ), 20 );
		add_action( 'template_redirect', array( $this, 'llms_txt' ) );
		add_filter( 'robots_txt', array( $this, 'robots_txt' ), 10, 2 );
	}

	/* ------------------------------ helpers ------------------------------ */

	private function current_url(): string {
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

	private function description(): string {
		$desc = '';
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$desc = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			}
		} elseif ( is_front_page() || is_home() ) {
			$desc = get_bloginfo( 'description' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			$desc = $term instanceof \WP_Term ? term_description( $term ) : '';
		} elseif ( is_author() ) {
			$desc = get_the_author_meta( 'description', (int) get_query_var( 'author' ) );
		} elseif ( is_search() ) {
			/* translators: %s: search query. */
			$desc = sprintf( __( 'Search results for “%s”.', 'lavzentheme' ), get_search_query() );
		}

		$desc = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $desc ) ) );
		if ( '' === $desc ) {
			$desc = get_bloginfo( 'description' );
		}
		$desc = (string) apply_filters( 'lavzen/seo/description', $desc );
		if ( '' === $desc ) {
			$desc = (string) apply_filters(
				'lavzen/seo/default_description',
				sprintf(
					/* translators: %s: site name. */
					__( '%s — premium digital downloads, automation workflows and SEO services, built to ship fast and rank.', 'lavzentheme' ),
					get_bloginfo( 'name' )
				)
			);
		}
		if ( mb_strlen( $desc ) > 160 ) {
			$desc = rtrim( mb_substr( $desc, 0, 157 ), ' ,.;:–-' ) . '…';
		}
		return $desc;
	}

	private function image(): string {
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
			$img = (string) apply_filters( 'lavzen/seo/default_image', '' );
		}
		return (string) $img;
	}

	private function social_profiles(): array {
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
		return array_values( array_unique( (array) apply_filters( 'lavzen/seo/social_profiles', $urls ) ) );
	}

	private function blog_url(): string {
		return function_exists( 'lavzen_blog_url' ) ? lavzen_blog_url() : home_url( '/' );
	}

	/* ------------------------------ robots / title ------------------------------ */

	/**
	 * @param array $robots Robots directives.
	 * @return array
	 */
	public function robots( $robots ) {
		if ( is_search() || is_404() ) {
			return $robots;
		}
		$robots['max-image-preview'] = 'large';
		$robots['max-snippet']       = -1;
		$robots['max-video-preview']  = -1;
		return $robots;
	}

	/**
	 * @param string $title Short-circuit title.
	 * @return string
	 */
	public function front_title( $title ) {
		if ( is_front_page() ) {
			$name = get_bloginfo( 'name' );
			$name = $name ? $name : 'Lavzen';
			return (string) apply_filters(
				'lavzen/seo/front_title',
				sprintf(
					/* translators: %s: site name. */
					__( '%s — Digital Products, Automation & SEO Services', 'lavzentheme' ),
					$name
				)
			);
		}
		return $title;
	}

	/* ------------------------------ head meta ------------------------------ */

	public function head_meta(): void {
		$desc    = $this->description();
		$url     = $this->current_url();
		$title   = wp_get_document_title();
		$image   = $this->image();
		$site    = get_bloginfo( 'name' );
		$is_post = is_singular( 'post' );

		echo "\n<!-- lavzen SEO -->\n";

		if ( $desc ) {
			printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $desc ) );
		}
		if ( ! is_singular() ) {
			printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $url ) );
		}
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
		if ( $is_post ) {
			$pid = get_queried_object_id();
			printf( "<meta property=\"article:published_time\" content=\"%s\">\n", esc_attr( get_the_date( 'c', $pid ) ) );
			printf( "<meta property=\"article:modified_time\" content=\"%s\">\n", esc_attr( get_the_modified_date( 'c', $pid ) ) );
			$author = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $pid ) );
			if ( $author ) {
				printf( "<meta property=\"article:author\" content=\"%s\">\n", esc_attr( $author ) );
			}
		}
		printf( "<meta name=\"twitter:card\" content=\"%s\">\n", $image ? 'summary_large_image' : 'summary' );
		printf( "<meta name=\"twitter:title\" content=\"%s\">\n", esc_attr( $title ) );
		if ( $desc ) {
			printf( "<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr( $desc ) );
		}
		if ( $image ) {
			printf( "<meta name=\"twitter:image\" content=\"%s\">\n", esc_url( $image ) );
		}
	}

	/* ------------------------------ JSON-LD ------------------------------ */

	private function print_jsonld( array $data ): void {
		$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( $json ) {
			echo '<script type="application/ld+json">' . str_replace( '</', '<\/', $json ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function jsonld(): void {
		$home = home_url( '/' );
		$name = get_bloginfo( 'name' );

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
				$org['logo'] = array( '@type' => 'ImageObject', 'url' => $logo_url );
			}
		}
		$social = $this->social_profiles();
		if ( $social ) {
			$org['sameAs'] = $social;
		}
		$this->print_jsonld( $org );

		$this->print_jsonld(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'WebSite',
				'@id'             => $home . '#website',
				'url'             => $home,
				'name'            => $name,
				'publisher'       => array( '@id' => $home . '#organization' ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array( '@type' => 'EntryPoint', 'urlTemplate' => $home . '?s={search_term_string}' ),
					'query-input' => 'required name=search_term_string',
				),
			)
		);

		if ( is_singular( 'post' ) ) {
			$pid       = get_queried_object_id();
			$author_id = (int) get_post_field( 'post_author', $pid );
			$article   = array(
				'@context'         => 'https://schema.org',
				'@type'            => 'BlogPosting',
				'@id'              => get_permalink( $pid ) . '#article',
				'mainEntityOfPage' => get_permalink( $pid ),
				'headline'         => wp_strip_all_tags( get_the_title( $pid ) ),
				'description'      => $this->description(),
				'datePublished'    => get_the_date( 'c', $pid ),
				'dateModified'     => get_the_modified_date( 'c', $pid ),
				'author'           => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $author_id ), 'url' => get_author_posts_url( $author_id ) ),
				'publisher'        => array( '@id' => $home . '#organization' ),
			);
			$img = $this->image();
			if ( $img ) {
				$article['image'] = $img;
			}
			$this->print_jsonld( $article );

			$crumbs   = array();
			$pos      = 1;
			$crumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => __( 'Home', 'lavzentheme' ), 'item' => $home );
			$crumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => __( 'Blog', 'lavzentheme' ), 'item' => $this->blog_url() );
			$crumbs[] = array( '@type' => 'ListItem', 'position' => $pos, 'name' => wp_strip_all_tags( get_the_title( $pid ) ), 'item' => get_permalink( $pid ) );
			$this->print_jsonld(
				array(
					'@context'        => 'https://schema.org',
					'@type'           => 'BreadcrumbList',
					'itemListElement' => $crumbs,
				)
			);
		}
	}

	/* ------------------------------ llms.txt / robots.txt ------------------------------ */

	public function llms_txt(): void {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		if ( '/llms.txt' !== untrailingslashit( (string) $path ) ) {
			return;
		}
		$name  = get_bloginfo( 'name' );
		$desc  = get_bloginfo( 'description' );
		$home  = home_url( '/' );
		$lines = array( '# ' . $name );
		if ( $desc ) {
			$lines[] = '';
			$lines[] = '> ' . $desc;
		}
		$lines[] = '';
		$lines[] = '## About';
		$lines[] = '';
		$lines[] = $name . ' — ' . $home;
		$lines[] = '';
		$posts   = get_posts( array( 'numberposts' => 20, 'post_status' => 'publish', 'orderby' => 'modified', 'suppress_filters' => false ) );
		if ( $posts ) {
			$lines[] = '## Articles';
			$lines[] = '';
			foreach ( $posts as $p ) {
				$ex      = has_excerpt( $p ) ? wp_strip_all_tags( get_the_excerpt( $p ) ) : '';
				$ex      = $ex ? ': ' . wp_trim_words( $ex, 18, '…' ) : '';
				$lines[] = '- [' . wp_strip_all_tags( get_the_title( $p ) ) . '](' . get_permalink( $p ) . ')' . $ex;
			}
			$lines[] = '';
		}
		$lines[] = '## Sitemaps';
		$lines[] = '';
		$lines[] = '- ' . home_url( '/wp-sitemap.xml' );
		$lines[] = '';
		$body    = (string) apply_filters( 'lavzen/seo/llms_txt', implode( "\n", $lines ) );

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * @param string $output Robots.txt contents.
	 * @param bool   $public Whether the site is public.
	 * @return string
	 */
	public function robots_txt( $output, $public ) {
		if ( ! $public ) {
			return $output;
		}
		$extra  = "\n# AI crawlers welcome\n";
		$extra .= "User-agent: GPTBot\nAllow: /\n\n";
		$extra .= "User-agent: PerplexityBot\nAllow: /\n\n";
		$extra .= "User-agent: ClaudeBot\nAllow: /\n\n";
		$extra .= "User-agent: Google-Extended\nAllow: /\n\n";
		$extra .= 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
		$extra .= '# LLM: ' . home_url( '/llms.txt' ) . "\n";
		return $output . $extra;
	}
}
