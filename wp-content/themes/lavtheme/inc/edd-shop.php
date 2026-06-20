<?php
/**
 * Easy Digital Downloads — Shop archive (the download post-type archive).
 *
 * The shop.html design wired to real EDD data. Filtering is server-side via
 * read-only GET params applied to the REAL main query in pre_get_posts (no AJAX:
 * SEO-friendly, works with JS off; JS only enhances UX). These are THEME
 * templates + a query filter — no EDD /edd/ internal template is overridden, so
 * nothing breaks on an EDD update.
 *
 * Params: pq (search), pcat[] (download_category slugs), min/max (edd_price),
 * flt[] (sale|new|best), rating (45|40|30, needs a reviews add-on), orderby
 * (relevance|sales|date|rating|trending|price-asc|price-desc), paged.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/** Products per shop page. */
function lavtheme_shop_per_page() {
	$n = absint( lavtheme_option( 'shop_per_page', 9 ) );
	return $n > 0 ? $n : 9;
}

/** "Best seller" sales threshold (filterable). */
function lavtheme_shop_bestseller_threshold() {
	return (int) apply_filters( 'lavtheme_shop_bestseller_threshold', 5 );
}

/**
 * Optional custom shop PAGE id (0 = use the download archive). Lets the shop be
 * moved later with no code change — set the `lavtheme_shop_page_id` filter or a
 * `shop_page_id` theme option. EDD core has no shop-page setting (the listing is
 * the `download` archive), so this is the theme's single extension point.
 *
 * @return int
 */
function lavtheme_shop_page_id() {
	$id = (int) lavtheme_option( 'shop_page_id', 0 );
	return (int) apply_filters( 'lavtheme_shop_page_id', $id );
}

/**
 * The shop URL — read DYNAMICALLY from EDD, never hardcoded. A configured shop
 * page wins; otherwise the `download` post-type archive link, which follows
 * EDD's slug (`EDD_SLUG`/rewrite) automatically. Filterable. This is the single
 * source every shop link/redirect should use.
 *
 * @return string
 */
function lavtheme_shop_url() {
	$pid = lavtheme_shop_page_id();
	$url = ( $pid && get_post( $pid ) ) ? get_permalink( $pid ) : get_post_type_archive_link( 'download' );
	if ( ! $url ) {
		$url = home_url( '/' );
	}
	return apply_filters( 'lavtheme_shop_url', $url, $pid );
}

/**
 * Is the given (or current) query the shop — the `download` post-type archive or
 * a download taxonomy? **Slug-agnostic**: uses `is_post_type_archive`/`is_tax`,
 * so it follows whatever archive slug EDD is configured with — change the slug
 * and detection (and thus the design, filters and CSS/JS injection) follows with
 * zero code change. Filterable (`lavtheme_is_shop`) for advanced custom-page
 * setups; the default stays archive/tax only so `pre_get_posts` can rely on it.
 *
 * @param WP_Query|null $query Optional query; defaults to the main query.
 * @return bool
 */
function lavtheme_is_shop( $query = null ) {
	if ( $query instanceof WP_Query ) {
		$is = $query->is_post_type_archive( 'download' ) || $query->is_tax( array( 'download_category', 'download_tag' ) );
	} else {
		$is = is_post_type_archive( 'download' ) || is_tax( array( 'download_category', 'download_tag' ) );
	}
	return (bool) apply_filters( 'lavtheme_is_shop', $is, $query );
}

/**
 * Is a reviews add-on active that exposes a per-download average rating?
 * Filterable so any review plugin can opt in.
 *
 * @return bool
 */
function lavtheme_shop_has_reviews() {
	$has = class_exists( 'EDD_Reviews' ) || function_exists( 'edd_reviews' );
	return (bool) apply_filters( 'lavtheme_shop_has_reviews', $has );
}

/** Postmeta key holding a download's average rating (filterable). */
function lavtheme_shop_rating_meta_key() {
	return (string) apply_filters( 'lavtheme_shop_rating_meta_key', 'edd_reviews_average_rating' );
}

/**
 * A download's rating: array( 'avg' => float, 'count' => int ) or array() when
 * reviews are unavailable / none yet.
 *
 * @param int $id Download ID.
 * @return array
 */
function lavtheme_shop_rating( $id ) {
	if ( ! lavtheme_shop_has_reviews() ) {
		return array();
	}
	$avg = (float) get_post_meta( $id, lavtheme_shop_rating_meta_key(), true );
	if ( $avg <= 0 ) {
		return array();
	}
	$count = (int) get_post_meta( $id, (string) apply_filters( 'lavtheme_shop_rating_count_meta_key', 'edd_reviews_count' ), true );
	return array( 'avg' => round( $avg, 1 ), 'count' => $count );
}

/**
 * Apply the shop filters/sort to the real EDD main query.
 *
 * @param WP_Query $query The query.
 */
function lavtheme_shop_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! lavtheme_is_shop( $query ) ) {
		return;
	}

	$query->set( 'posts_per_page', lavtheme_shop_per_page() );

	$meta = (array) $query->get( 'meta_query' );
	$tax  = (array) $query->get( 'tax_query' );

	// Keyword search (custom param so the archive template stays selected).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$pq = isset( $_GET['pq'] ) ? sanitize_text_field( wp_unslash( $_GET['pq'] ) ) : '';
	if ( '' !== $pq ) {
		$query->set( 's', $pq );
	}

	// Categories (multi). Skipped on a category term archive (already filtered).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$raw_cat = isset( $_GET['pcat'] ) ? wp_unslash( $_GET['pcat'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$slugs   = is_array( $raw_cat ) ? $raw_cat : explode( ',', (string) $raw_cat );
	$slugs   = array_values( array_filter( array_map( 'sanitize_title', $slugs ) ) );
	if ( $slugs && ! $query->is_tax( 'download_category' ) && taxonomy_exists( 'download_category' ) ) {
		$tax[] = array(
			'taxonomy' => 'download_category',
			'field'    => 'slug',
			'terms'    => $slugs,
		);
	}

	// Price range (numeric edd_price).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$min = isset( $_GET['min'] ) && '' !== $_GET['min'] ? (float) $_GET['min'] : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max = isset( $_GET['max'] ) && '' !== $_GET['max'] ? (float) $_GET['max'] : 0;
	if ( $min > 0 || $max > 0 ) {
		if ( $min > 0 && $max > 0 ) {
			$meta[] = array( 'key' => 'edd_price', 'value' => array( min( $min, $max ), max( $min, $max ) ), 'type' => 'NUMERIC', 'compare' => 'BETWEEN' );
		} elseif ( $min > 0 ) {
			$meta[] = array( 'key' => 'edd_price', 'value' => $min, 'type' => 'NUMERIC', 'compare' => '>=' );
		} else {
			$meta[] = array( 'key' => 'edd_price', 'value' => $max, 'type' => 'NUMERIC', 'compare' => '<=' );
		}
	}

	// Tag filters.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$flt = isset( $_GET['flt'] ) ? (array) wp_unslash( $_GET['flt'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$flt = array_map( 'sanitize_key', $flt );
	if ( in_array( 'sale', $flt, true ) ) {
		$meta[] = array( 'key' => '_lavtheme_compare_price', 'value' => 0, 'type' => 'NUMERIC', 'compare' => '>' );
	}
	if ( in_array( 'new', $flt, true ) ) {
		$query->set( 'date_query', array( array( 'after' => '14 days ago' ) ) );
	}
	if ( in_array( 'best', $flt, true ) ) {
		$meta[] = array( 'key' => '_edd_download_sales', 'value' => lavtheme_shop_bestseller_threshold(), 'type' => 'NUMERIC', 'compare' => '>=' );
	}

	// Rating filter (only when a reviews add-on is present).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$rating = isset( $_GET['rating'] ) ? (int) $_GET['rating'] : 0;
	if ( $rating > 0 && lavtheme_shop_has_reviews() ) {
		$meta[] = array( 'key' => lavtheme_shop_rating_meta_key(), 'value' => $rating / 10, 'type' => 'DECIMAL(3,1)', 'compare' => '>=' );
	}

	if ( $meta ) {
		$query->set( 'meta_query', $meta );
	}
	if ( $tax ) {
		$query->set( 'tax_query', $tax );
	}

	// Sort.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
	switch ( $orderby ) {
		case 'sales':
		case 'trending': // recent-sales proxy → best sellers (graceful fallback).
			$query->set( 'meta_key', '_edd_download_sales' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
		case 'rating':
			if ( lavtheme_shop_has_reviews() ) {
				$query->set( 'meta_key', lavtheme_shop_rating_meta_key() );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
			} else {
				$query->set( 'meta_key', '_edd_download_sales' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
			}
			break;
		case 'price_asc':
		case 'price-asc':
			$query->set( 'meta_key', 'edd_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'ASC' );
			break;
		case 'price_desc':
		case 'price-desc':
			$query->set( 'meta_key', 'edd_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
		case 'date':
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'DESC' );
			break;
		case 'relevance':
		default:
			// Relevance: search relevance when searching, else newest.
			$query->set( 'orderby', '' !== $pq ? 'relevance' : 'date' );
			$query->set( 'order', 'DESC' );
			break;
	}
}
add_action( 'pre_get_posts', 'lavtheme_shop_pre_get_posts' );

/**
 * Min/max edd_price across the catalogue (cached) — slider bounds.
 *
 * @return array{min:float,max:float}
 */
function lavtheme_shop_price_bounds() {
	$cached = get_transient( 'lavtheme_shop_price_bounds' );
	if ( is_array( $cached ) && isset( $cached['min'], $cached['max'] ) ) {
		return $cached;
	}
	global $wpdb;
	$row    = $wpdb->get_row( "SELECT MIN(meta_value+0) AS min_v, MAX(meta_value+0) AS max_v FROM {$wpdb->postmeta} WHERE meta_key = 'edd_price'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$bounds = array(
		'min' => $row ? (float) $row->min_v : 0,
		'max' => $row ? (float) $row->max_v : 200,
	);
	if ( $bounds['max'] <= $bounds['min'] ) {
		$bounds['max'] = $bounds['min'] + 200;
	}
	set_transient( 'lavtheme_shop_price_bounds', $bounds, 12 * HOUR_IN_SECONDS );
	return $bounds;
}

/** Clear cached price bounds when a download changes. */
function lavtheme_shop_flush_bounds() {
	delete_transient( 'lavtheme_shop_price_bounds' );
}
add_action( 'save_post_download', 'lavtheme_shop_flush_bounds' );
add_action( 'deleted_post', 'lavtheme_shop_flush_bounds' );

/** Current filter state read from the request. */
function lavtheme_shop_filter_state() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$raw_cat = isset( $_GET['pcat'] ) ? wp_unslash( $_GET['pcat'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$slugs   = is_array( $raw_cat ) ? $raw_cat : explode( ',', (string) $raw_cat );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$flt = isset( $_GET['flt'] ) ? (array) wp_unslash( $_GET['flt'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	return array(
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'pq'      => isset( $_GET['pq'] ) ? sanitize_text_field( wp_unslash( $_GET['pq'] ) ) : '',
		'pcat'    => array_values( array_filter( array_map( 'sanitize_title', $slugs ) ) ),
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'min'     => isset( $_GET['min'] ) && '' !== $_GET['min'] ? (float) $_GET['min'] : '',
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'max'     => isset( $_GET['max'] ) && '' !== $_GET['max'] ? (float) $_GET['max'] : '',
		'flt'     => array_map( 'sanitize_key', $flt ),
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'rating'  => isset( $_GET['rating'] ) ? (int) $_GET['rating'] : 0,
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'orderby' => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'relevance',
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'view'    => ( isset( $_GET['view'] ) && 'list' === $_GET['view'] ) ? 'list' : 'grid',
	);
}

/** The shop base URL (current taxonomy term, else the download archive). */
function lavtheme_shop_base_url() {
	if ( is_tax() ) {
		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) ) {
			$u = get_term_link( $t );
			if ( ! is_wp_error( $u ) ) {
				return $u;
			}
		}
	}
	return lavtheme_shop_url();
}

/** A cyclic category icon (inline SVG) by index. */
function lavtheme_shop_cat_icon( $i ) {
	$icons = array(
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v6H4zM4 14h16v6H4z"/></svg>',
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 2 7 4v6c0 5-3.5 8-7 10-3.5-2-7-5-7-10V6z"/></svg>',
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>',
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>',
		'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 1v3M12 20v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M1 12h3M20 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>',
	);
	$n = count( $icons );
	return $icons[ ( (int) $i ) % $n ];
}

/**
 * Hero stats (real where EDD exposes them; gracefully omitted otherwise).
 *
 * @return array list of array( 'b' => value, 'span' => label )
 */
function lavtheme_shop_hero_stats() {
	$stats = array();

	$counts = wp_count_posts( 'download' );
	$total  = $counts ? (int) $counts->publish : 0;
	if ( $total > 0 ) {
		$stats[] = array( 'b' => number_format_i18n( $total ) . '+', 'span' => __( 'Products', 'lavtheme' ) );
	}

	if ( function_exists( 'edd_count_total_customers' ) ) {
		$cust = (int) edd_count_total_customers();
		if ( $cust > 0 ) {
			$stats[] = array( 'b' => lavtheme_shop_kfmt( $cust ), 'span' => __( 'Customers', 'lavtheme' ) );
		}
	}

	if ( lavtheme_shop_has_reviews() ) {
		$avg = lavtheme_shop_store_avg_rating();
		if ( $avg > 0 ) {
			$stats[] = array( 'b' => number_format_i18n( $avg, 1 ) . '★', 'span' => __( 'Avg rating', 'lavtheme' ) );
		}
	}

	return $stats;
}

/** Compact number (1.8k). */
function lavtheme_shop_kfmt( $n ) {
	$n = (int) $n;
	if ( $n >= 1000 ) {
		return rtrim( rtrim( number_format( $n / 1000, 1 ), '0' ), '.' ) . 'k';
	}
	return number_format_i18n( $n );
}

/** Store-wide average rating (cached) when a reviews add-on is present. */
function lavtheme_shop_store_avg_rating() {
	if ( ! lavtheme_shop_has_reviews() ) {
		return 0;
	}
	$cached = get_transient( 'lavtheme_shop_avg_rating' );
	if ( false !== $cached ) {
		return (float) $cached;
	}
	global $wpdb;
	$key = lavtheme_shop_rating_meta_key();
	$avg = (float) $wpdb->get_var( $wpdb->prepare( "SELECT AVG(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value+0 > 0", $key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$avg = round( $avg, 1 );
	set_transient( 'lavtheme_shop_avg_rating', $avg, 6 * HOUR_IN_SECONDS );
	return $avg;
}

/** Discount badge data for a download: array( text, class ) or array(). */
function lavtheme_shop_badge( $id ) {
	$price   = function_exists( 'edd_get_download_price' ) ? (float) edd_get_download_price( $id ) : 0.0;
	$compare = (float) get_post_meta( $id, '_lavtheme_compare_price', true );
	if ( $compare > 0 && $compare > $price ) {
		return array( 'text' => __( 'Sale', 'lavtheme' ), 'class' => 'sale' );
	}
	if ( ( time() - (int) get_post_time( 'U', true, $id ) ) < 14 * DAY_IN_SECONDS ) {
		return array( 'text' => __( 'New', 'lavtheme' ), 'class' => 'new' );
	}
	if ( (int) get_post_meta( $id, '_edd_download_sales', true ) >= lavtheme_shop_bestseller_threshold() * 4 ) {
		return array( 'text' => __( 'Hot', 'lavtheme' ), 'class' => 'hot' );
	}
	return array();
}

/**
 * Render one product card (shop.html .pcard) wired to real data.
 *
 * @param int $id Download ID.
 * @return string
 */
function lavtheme_shop_card_html( $id ) {
	$id        = absint( $id );
	$permalink = get_permalink( $id );
	$title     = get_the_title( $id );
	$cats      = get_the_terms( $id, 'download_category' );
	$cat       = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
	$thumb     = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'lavtheme-card' ) : '';
	$show_price = '' !== (string) lavtheme_option( 'show_price', '1' );
	$badge     = lavtheme_shop_badge( $id );
	$variable  = function_exists( 'edd_has_variable_prices' ) && edd_has_variable_prices( $id );
	$price_num = function_exists( 'edd_get_download_price' ) ? (float) edd_get_download_price( $id ) : 0.0;
	$compare   = (float) get_post_meta( $id, '_lavtheme_compare_price', true );
	$sales     = (int) edd_get_download_sales_stats( $id );
	$rating    = lavtheme_shop_rating( $id );
	$excerpt   = wp_trim_words( get_the_excerpt( $id ), 26, '…' );

	$heart = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>';
	$cart  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>';
	$star  = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 19l-4.8 2.5.9-5.4L4.2 12.3l5.4-.8z"/></svg>';

	ob_start();
	?>
	<article class="pcard glass" data-id="<?php echo esc_attr( $id ); ?>" data-title="<?php echo esc_attr( $title ); ?>" data-cat="<?php echo esc_attr( $cat ); ?>" data-img="<?php echo esc_attr( $thumb ); ?>" data-url="<?php echo esc_url( $permalink ); ?>" data-excerpt="<?php echo esc_attr( $excerpt ); ?>" data-price="<?php echo esc_attr( $show_price && function_exists( 'edd_price' ) ? wp_strip_all_tags( edd_price( $id, false ) ) : '' ); ?>">
		<div class="pthumb">
			<a href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php endif; ?>
			</a>
			<?php if ( ! empty( $badge ) ) : ?>
				<span class="pbadge <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['text'] ); ?></span>
			<?php endif; ?>
			<button type="button" class="pfav" data-fav="<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Add to wishlist', 'lavtheme' ); ?>" aria-pressed="false"><?php echo $heart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			<button type="button" class="pquick" data-quick="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Quick view', 'lavtheme' ); ?></button>
		</div>
		<div class="pbody">
			<?php if ( $cat ) : ?><span class="pcat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
			<h3 class="ptitle"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<div class="prate">
				<?php if ( ! empty( $rating ) ) : ?>
					<?php echo $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( number_format_i18n( $rating['avg'], 1 ) ); ?>
					<?php if ( $sales > 0 ) : ?>&nbsp;·&nbsp;<?php echo esc_html( number_format_i18n( $sales ) . ' ' . __( 'sales', 'lavtheme' ) ); ?><?php endif; ?>
				<?php elseif ( $sales > 0 ) : ?>
					<?php echo esc_html( number_format_i18n( $sales ) . ' ' . __( 'sales', 'lavtheme' ) ); ?>
				<?php endif; ?>
			</div>
			<div class="pfoot">
				<div class="pprice">
					<?php if ( $show_price && function_exists( 'edd_price' ) ) : ?>
						<span class="now"><?php echo wp_kses_post( edd_price( $id, false ) ); ?></span>
						<?php if ( $compare > 0 && $compare > $price_num ) : ?>
							<span class="was"><?php echo function_exists( 'edd_currency_filter' ) ? esc_html( edd_currency_filter( edd_format_amount( $compare ) ) ) : esc_html( $compare ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<?php
				if ( $variable || ! function_exists( 'edd_get_purchase_link' ) ) :
					?>
					<a class="pbuy" href="<?php echo esc_url( $permalink ); ?>"><?php echo $cart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'View', 'lavtheme' ); ?></a>
					<?php
				else :
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- EDD builds escaped markup.
					echo edd_get_purchase_link(
						array(
							'download_id' => $id,
							'price'       => false,
							'text'        => __( 'Buy', 'lavtheme' ),
							'class'       => 'pbuy',
						)
					);
				endif;
				?>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}
