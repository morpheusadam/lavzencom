<?php
/**
 * LAVZEN marketplace home page — data + rendering layer.
 *
 * The front page is a self-contained "everything AI" marketplace. This file:
 *   1. Swaps the theme's legacy CSS/JS for the isolated home assets on the front
 *      page only (inner pages keep their existing design, untouched).
 *   2. Exposes the ten departments from the real `download_category` taxonomy
 *      (hero marquee, department bento and footer all read from these terms).
 *   3. Renders product rails wired to real EDD `download` queries, and a single
 *      product-card builder shared by every rail.
 *
 * All links are resolved dynamically (get_term_link / get_permalink); nothing is
 * hard-coded. Section template-parts stay thin — they call the helpers here.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* ==========================================================================
 * 1. Front-page asset swap.
 * ======================================================================== */

/**
 * On the front page, replace the legacy theme styling with the isolated home
 * assets so the marketplace design renders on a clean slate. Runs after the
 * main enqueue (priority 100) so the dequeues take effect, and removes the Code
 * Studio head/footer code injections (which target the legacy sections) before
 * wp_head / wp_footer fire.
 */
function lavtheme_home_assets() {
	if ( ! is_front_page() ) {
		return;
	}

	// Drop the legacy base + unified glass layers + their behaviour script.
	foreach ( array( 'lavtheme-main', 'lavtheme-products', 'lavzen-glass', 'lavzen-ui', 'lavzen-bg', 'lavzen-fonts' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
	wp_dequeue_script( 'lavzen-js' );

	// The Code Studio CSS/JS injectors emit the *legacy* sections' code; the home
	// template ships its own, so suppress them here (front page only).
	remove_action( 'wp_head', 'lavtheme_cs_head_css', 100 );
	remove_action( 'wp_footer', 'lavtheme_cs_footer_js', 100 );

	// Display fonts for the marketplace design.
	wp_enqueue_style( 'lavzen-home-fonts', 'https://api.fontshare.com/v2/css?f[]=clash-display@600,700&f[]=satoshi@400,500,700&display=swap', array(), null );

	// The single token source (dequeued with the composed base above) — re-add it
	// so the home references the same --lav-*/legacy variables as every other page.
	wp_enqueue_style( 'lavtheme-tokens', LAVTHEME_URI . 'assets/css/sections/global.root.css', array(), lavtheme_asset_ver( 'assets/css/sections/global.root.css' ) );

	wp_enqueue_style( 'lavzen-home', LAVTHEME_URI . 'assets/css/home.css', array( 'lavtheme-tokens', 'lavzen-home-fonts' ), lavtheme_asset_ver( 'assets/css/home.css' ) );
	wp_enqueue_script( 'lavzen-home', LAVTHEME_URI . 'assets/js/home.js', array(), lavtheme_asset_ver( 'assets/js/home.js' ), true );

	// Chrome behaviour (topbar popovers + mobile menu). The chrome CSS is the
	// site-wide assets/css/chrome.css (enqueued globally); the home suppresses the
	// legacy footer JS, so deliver the chrome script here.
	wp_enqueue_script( 'lavzen-chrome', LAVTHEME_URI . 'assets/js/chrome.js', array(), lavtheme_asset_ver( 'assets/js/chrome.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'lavtheme_home_assets', 100 );

/* ==========================================================================
 * 2. Departments (the ten top-level download_category terms).
 * ======================================================================== */

/**
 * Canonical department map: slug => [ name, glyph ], in homepage display order.
 * The glyph is a monochrome geometric mark used across the hero, bento and cards.
 *
 * @return array
 */
function lavtheme_home_dept_map() {
	return array(
		'models'         => array( 'Models', '◇' ),
		'agents'         => array( 'Agents', '⬡' ),
		'assets'         => array( 'Assets', '◈' ),
		'apps'           => array( 'Apps', '❖' ),
		'datasets'       => array( 'Datasets', '▦' ),
		'hardware'       => array( 'Hardware', '▢' ),
		'infrastructure' => array( 'Infrastructure', '⛁' ),
		'services'       => array( 'Services', '✦' ),
		'learning'       => array( 'Learning', '✺' ),
		'frontier'       => array( 'Frontier', '◉' ),
	);
}

/**
 * The ten departments, resolved against the live taxonomy.
 *
 * Each entry: [ slug, name, glyph, url, count ]. A term that does not exist yet
 * still appears (with a search/archive fallback URL and a zero count) so the
 * design never shows gaps.
 *
 * @return array
 */
function lavtheme_home_departments() {
	$cache = get_transient( 'lavtheme_home_depts' );
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$out = array();
	foreach ( lavtheme_home_dept_map() as $slug => $meta ) {
		$term  = get_term_by( 'slug', $slug, 'download_category' );
		$url   = '';
		$count = 0;
		if ( $term && ! is_wp_error( $term ) ) {
			$link  = get_term_link( $term );
			$url   = is_wp_error( $link ) ? '' : $link;
			$count = lavtheme_home_dept_count( (int) $term->term_id );
		}
		if ( '' === $url ) {
			$url = lavtheme_shop_url();
		}
		$out[] = array(
			'slug'  => $slug,
			'name'  => $meta[0],
			'glyph' => $meta[1],
			'url'   => $url,
			'count' => $count,
		);
	}

	set_transient( 'lavtheme_home_depts', $out, HOUR_IN_SECONDS );
	return $out;
}

/**
 * Count published downloads in a department, including every descendant
 * category (a parent term's own `count` only tallies directly-assigned posts).
 *
 * @param int $term_id Top-level department term id.
 * @return int
 */
function lavtheme_home_dept_count( $term_id ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'download',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => false,
			'tax_query'      => array(
				array(
					'taxonomy'         => 'download_category',
					'field'            => 'term_id',
					'terms'            => $term_id,
					'include_children' => true,
				),
			),
		)
	);
	return (int) $q->found_posts;
}

/**
 * A download_category term archive URL by slug, falling back to the shop URL so
 * "View all" links always resolve even before the term has listings.
 *
 * @param string $slug Term slug.
 * @return string
 */
function lavtheme_home_term_url( $slug ) {
	$term = get_term_by( 'slug', $slug, 'download_category' );
	if ( $term && ! is_wp_error( $term ) ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	return lavtheme_shop_url();
}

/**
 * The display glyph for a download's primary department (falls back to a mark).
 *
 * @param int $id Download ID.
 * @return string
 */
function lavtheme_home_post_glyph( $id ) {
	$map  = lavtheme_home_dept_map();
	$cats = get_the_terms( $id, 'download_category' );
	if ( $cats && ! is_wp_error( $cats ) ) {
		foreach ( $cats as $c ) {
			// Walk up to the top-level ancestor slug to match the map.
			$top = $c;
			while ( $top->parent ) {
				$parent = get_term( $top->parent, 'download_category' );
				if ( ! $parent || is_wp_error( $parent ) ) {
					break;
				}
				$top = $parent;
			}
			if ( isset( $map[ $top->slug ] ) ) {
				return $map[ $top->slug ][1];
			}
		}
	}
	return '◇';
}

/**
 * The primary department label for a download (top-level term name).
 *
 * @param int $id Download ID.
 * @return string
 */
function lavtheme_home_post_dept( $id ) {
	$cats = get_the_terms( $id, 'download_category' );
	if ( ! $cats || is_wp_error( $cats ) ) {
		return '';
	}
	$map = lavtheme_home_dept_map();
	foreach ( $cats as $c ) {
		$top = $c;
		while ( $top->parent ) {
			$parent = get_term( $top->parent, 'download_category' );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			$top = $parent;
		}
		if ( isset( $map[ $top->slug ] ) ) {
			return $map[ $top->slug ][0];
		}
	}
	return $cats[0]->name;
}

/* ==========================================================================
 * 3. Product card data helpers (read real meta; degrade gracefully).
 * ======================================================================== */

/** Formatted price markup for a card: "Free", "$49" or "from $19". */
function lavtheme_home_price_html( $id ) {
	if ( function_exists( 'edd_is_free_download' ) && edd_is_free_download( $id ) ) {
		return '<span class="card__free">' . esc_html__( 'Free', 'lavtheme' ) . '</span>';
	}
	$price = function_exists( 'edd_get_download_price' ) ? (float) edd_get_download_price( $id ) : 0.0;
	if ( $price <= 0 ) {
		return '<span class="card__free">' . esc_html__( 'Free', 'lavtheme' ) . '</span>';
	}
	// Whole prices render clean ("$49"); fractional keep two decimals ("$12.50").
	$show_decimals = ( (float) $price !== (float) (int) $price );
	$amount        = function_exists( 'edd_currency_filter' ) && function_exists( 'edd_format_amount' )
		? wp_strip_all_tags( edd_currency_filter( edd_format_amount( $price, $show_decimals ) ) )
		: '$' . number_format_i18n( $price, $show_decimals ? 2 : 0 );
	$prefix = ( function_exists( 'edd_has_variable_prices' ) && edd_has_variable_prices( $id ) ) ? esc_html__( 'from ', 'lavtheme' ) : '';
	return '<span class="card__price">' . esc_html( $prefix . $amount ) . '</span>';
}

/** Seller/vendor name for a card (meta, else post author display name). */
function lavtheme_home_vendor( $id ) {
	$vendor = (string) get_post_meta( $id, '_lavzen_vendor', true );
	if ( '' !== $vendor ) {
		return $vendor;
	}
	$author = (int) get_post_field( 'post_author', $id );
	$name   = $author ? get_the_author_meta( 'display_name', $author ) : '';
	return $name ? $name : __( 'LAVZEN', 'lavtheme' );
}

/** Rating tuple [ avg(float), count(int) ] for a card, or array() when unset. */
function lavtheme_home_rating( $id ) {
	$avg = (float) get_post_meta( $id, '_lavzen_rating', true );
	if ( $avg <= 0 ) {
		return array();
	}
	return array( round( $avg, 1 ), (int) get_post_meta( $id, '_lavzen_rating_count', true ) );
}

/** Compatibility chips for a card (from comma-separated meta). */
function lavtheme_home_chips( $id ) {
	$raw = (string) get_post_meta( $id, '_lavzen_chips', true );
	if ( '' === $raw ) {
		return array();
	}
	return array_slice( array_filter( array_map( 'trim', explode( ',', $raw ) ) ), 0, 2 );
}

/** Compact count (1.2k). */
function lavtheme_home_kfmt( $n ) {
	$n = (int) $n;
	if ( $n >= 1000 ) {
		return rtrim( rtrim( number_format( $n / 1000, 1 ), '0' ), '.' ) . 'k';
	}
	return number_format_i18n( $n );
}

/**
 * Store-wide stats for the social-proof band. Real where EDD/meta expose them,
 * with sensible fallbacks so the design never shows a zero.
 *
 * @return array{listings:string,sellers:string,downloads:string,rating:string}
 */
function lavtheme_home_stats() {
	$cache = get_transient( 'lavtheme_home_stats' );
	if ( is_array( $cache ) ) {
		return $cache;
	}

	global $wpdb;
	$listings  = (int) wp_count_posts( 'download' )->publish;
	$sellers   = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_lavzen_vendor' AND meta_value <> ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$downloads = (int) $wpdb->get_var( "SELECT SUM(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key = '_edd_download_sales'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$rating    = (float) $wpdb->get_var( "SELECT AVG(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key = '_lavzen_rating' AND meta_value+0 > 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	$stats = array(
		'listings'  => $listings > 0 ? number_format_i18n( $listings ) : '1,240',
		'sellers'   => $sellers > 0 ? number_format_i18n( $sellers ) : '380',
		'downloads' => $downloads > 0 ? lavtheme_home_kfmt( $downloads ) : '92k',
		'rating'    => $rating > 0 ? number_format_i18n( round( $rating, 1 ), 1 ) : '4.8',
	);

	set_transient( 'lavtheme_home_stats', $stats, 6 * HOUR_IN_SECONDS );
	return $stats;
}

/* Bust the cached home data whenever a download changes. */
add_action( 'save_post_download', static function () {
	delete_transient( 'lavtheme_home_stats' );
	delete_transient( 'lavtheme_home_depts' );
} );

/* ==========================================================================
 * 4. Card + rail renderers.
 * ======================================================================== */

/** The shared "save" (wishlist) heart button markup. */
function lavtheme_home_save_btn( $title ) {
	return '<button class="card__save" type="button" aria-pressed="false" aria-label="'
		. esc_attr( sprintf( __( 'Save %s', 'lavtheme' ), $title ) ) . '" data-save>'
		. '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.6-9.2-9C1.3 7.7 3 5 6 5c1.9 0 3.2 1.1 4 2.3C10.8 6.1 12.1 5 14 5c3 0 4.7 2.7 3.2 6C19 15.4 12 20 12 20z"/></svg>'
		. '</button>';
}

/**
 * Render one product card (the design's `.card` <li>) wired to real EDD data.
 *
 * @param int    $id   Download ID.
 * @param string $rank Optional corner badge text (e.g. "New", "#1").
 * @return string
 */
function lavtheme_home_product_card( $id, $rank = '' ) {
	$id        = absint( $id );
	$permalink = get_permalink( $id );
	$title     = get_the_title( $id );
	$dept      = lavtheme_home_post_dept( $id );
	$glyph     = lavtheme_home_post_glyph( $id );
	$vendor    = lavtheme_home_vendor( $id );
	$rating    = lavtheme_home_rating( $id );
	$chips     = lavtheme_home_chips( $id );
	$verified  = (bool) get_post_meta( $id, '_lavzen_verified', true );
	$media     = 'm' . ( $id % 6 );

	ob_start();
	?>
	<li class="card <?php echo esc_attr( $media ); ?>">
		<div class="card__media" data-glyph="<?php echo esc_attr( $glyph ); ?>" aria-hidden="true"><?php
			if ( '' !== $rank ) {
				echo '<span class="card__rank">' . esc_html( $rank ) . '</span>';
			}
		?></div>
		<?php echo lavtheme_home_save_btn( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaping above. ?>
		<div class="card__body">
			<p class="card__meta">
				<?php if ( '' !== $dept ) : ?><span class="card__dept"><?php echo esc_html( $dept ); ?></span><?php endif; ?>
				<?php if ( $verified ) : ?><span class="card__verif" title="<?php esc_attr_e( 'Verified', 'lavtheme' ); ?>">✓ <?php esc_html_e( 'Verified', 'lavtheme' ); ?></span><?php endif; ?>
			</p>
			<h3 class="card__title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<p class="card__by">
				<?php echo esc_html( sprintf( __( 'by %s', 'lavtheme' ), $vendor ) ); ?>
				<?php if ( $rating ) : ?>
					· <span class="card__rate" aria-hidden="true">★ <?php echo esc_html( number_format_i18n( $rating[0], 1 ) ); ?></span>
					<?php if ( $rating[1] > 0 ) : ?><span class="card__count">(<?php echo esc_html( lavtheme_home_kfmt( $rating[1] ) ); ?>)</span><?php endif; ?>
				<?php endif; ?>
			</p>
			<?php if ( $chips ) : ?>
				<p class="card__chips"><?php foreach ( $chips as $chip ) : ?><span class="cchip"><?php echo esc_html( $chip ); ?></span><?php endforeach; ?></p>
			<?php endif; ?>
			<div class="card__foot">
				<?php echo lavtheme_home_price_html( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
				<a class="card__get" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Get %s', 'lavtheme' ), $title ) ); ?>"><?php esc_html_e( 'Get', 'lavtheme' ); ?></a>
			</div>
		</div>
	</li>
	<?php
	return ob_get_clean();
}

/** Rail navigation arrow SVGs. */
function lavtheme_home_arrow( $dir ) {
	$path = 'prev' === $dir ? 'M15 5l-7 7 7 7' : 'M9 5l7 7-7 7';
	return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . $path . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Query downloads for a rail.
 *
 * @param array $args WP_Query overrides (post_type/status/ignore_sticky fixed).
 * @return WP_Query
 */
function lavtheme_home_query( $args ) {
	$defaults = array(
		'post_type'           => 'download',
		'post_status'         => 'publish',
		'posts_per_page'      => 10,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	return new WP_Query( array_merge( $defaults, $args ) );
}

/**
 * Render a full product rail section.
 *
 * @param array $cfg id, title, sub, view_all, view_url, query, rank (bool|string).
 */
function lavtheme_home_rail( $cfg ) {
	$query = isset( $cfg['query'] ) ? $cfg['query'] : null;
	if ( ! $query instanceof WP_Query || ! $query->have_posts() ) {
		wp_reset_postdata();
		return; // never print an empty rail.
	}
	$hid   = 'rail-' . sanitize_html_class( $cfg['id'] );
	$title = $cfg['title'];
	$rank  = isset( $cfg['rank'] ) ? $cfg['rank'] : false;
	?>
	<section class="sec rail" data-rail aria-labelledby="<?php echo esc_attr( $hid ); ?>">
		<div class="wrap rail__top">
			<div class="head">
				<h2 class="head__title" id="<?php echo esc_attr( $hid ); ?>"><?php echo esc_html( $title ); ?></h2>
				<?php if ( ! empty( $cfg['sub'] ) ) : ?><p class="head__sub"><?php echo esc_html( $cfg['sub'] ); ?></p><?php endif; ?>
			</div>
			<div class="rail__nav" data-nav>
				<?php if ( ! empty( $cfg['view_url'] ) ) : ?>
					<a class="head__link" href="<?php echo esc_url( $cfg['view_url'] ); ?>"><?php echo esc_html( ! empty( $cfg['view_all'] ) ? $cfg['view_all'] : __( 'View all', 'lavtheme' ) ); ?></a>
				<?php endif; ?>
				<button class="rail__btn" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Scroll %s left', 'lavtheme' ), $title ) ); ?>" data-prev><?php echo lavtheme_home_arrow( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<button class="rail__btn" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Scroll %s right', 'lavtheme' ), $title ) ); ?>" data-next><?php echo lavtheme_home_arrow( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
		</div>
		<div class="rail__viewport" data-viewport tabindex="0" role="group" aria-label="<?php echo esc_attr( sprintf( __( '%s — scrollable', 'lavtheme' ), $title ) ); ?>">
			<ul class="rail__track">
				<?php
				$i = 0;
				while ( $query->have_posts() ) {
					$query->the_post();
					$i++;
					$badge = '';
					if ( true === $rank ) {
						$badge = 'New';
					} elseif ( is_string( $rank ) && '#' === $rank ) {
						$badge = '#' . $i;
					}
					echo lavtheme_home_product_card( get_the_ID(), $badge ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
				}
				wp_reset_postdata();
				?>
			</ul>
		</div>
	</section>
	<?php
}
