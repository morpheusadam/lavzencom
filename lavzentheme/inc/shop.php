<?php
/**
 * Shop archive — query engine + UI builders + product card.
 *
 * Ported from the legacy inc/edd-shop.php + inc/edd-shop-ui.php (renamed
 * lavzen_shop_*). Server-side GET filtering of the real download archive query
 * (pq/pcat/min/max/flt/rating/orderby) — SEO-friendly, JS only enhances. Uses
 * lavzen_shop_url()/lavzen_is_shop() from inc/conditionals.php; the compare-price
 * meta key (_lavtheme_compare_price) is kept for data continuity.
 *
 * Loaded by the EDD module (outside the autoloaded namespace).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

function lavzen_shop_per_page(): int {
	$n = (int) apply_filters( 'lavzen/shop/per_page', 9 );
	return $n > 0 ? $n : 9;
}

function lavzen_shop_bestseller_threshold(): int {
	return (int) apply_filters( 'lavzen/shop/bestseller_threshold', 5 );
}

function lavzen_shop_has_reviews(): bool {
	$has = class_exists( 'EDD_Reviews' ) || function_exists( 'edd_reviews' );
	return (bool) apply_filters( 'lavzen/shop/has_reviews', $has );
}

function lavzen_shop_rating_meta_key(): string {
	return (string) apply_filters( 'lavzen/shop/rating_meta_key', 'edd_reviews_average_rating' );
}

function lavzen_shop_rating( $id ): array {
	if ( ! lavzen_shop_has_reviews() ) {
		return array();
	}
	$avg = (float) get_post_meta( $id, lavzen_shop_rating_meta_key(), true );
	if ( $avg <= 0 ) {
		return array();
	}
	$count = (int) get_post_meta( $id, (string) apply_filters( 'lavzen/shop/rating_count_meta_key', 'edd_reviews_count' ), true );
	return array( 'avg' => round( $avg, 1 ), 'count' => $count );
}

/** Query-aware shop check for pre_get_posts (archive / download taxonomy). */
function lavzen_shop_is_archive_query( $query ): bool {
	return $query->is_post_type_archive( 'download' ) || $query->is_tax( array( 'download_category', 'download_tag' ) );
}

/** Apply the shop filters/sort to the real EDD main query. */
function lavzen_shop_pre_get_posts( $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! lavzen_shop_is_archive_query( $query ) ) {
		return;
	}
	$query->set( 'posts_per_page', lavzen_shop_per_page() );
	$meta = (array) $query->get( 'meta_query' );
	$tax  = (array) $query->get( 'tax_query' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$pq = isset( $_GET['pq'] ) ? sanitize_text_field( wp_unslash( $_GET['pq'] ) ) : '';
	if ( '' !== $pq ) {
		$query->set( 's', $pq );
	}
	$raw_cat = isset( $_GET['pcat'] ) ? wp_unslash( $_GET['pcat'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$slugs   = is_array( $raw_cat ) ? $raw_cat : explode( ',', (string) $raw_cat );
	$slugs   = array_values( array_filter( array_map( 'sanitize_title', $slugs ) ) );
	if ( $slugs && ! $query->is_tax( 'download_category' ) && taxonomy_exists( 'download_category' ) ) {
		$tax[] = array( 'taxonomy' => 'download_category', 'field' => 'slug', 'terms' => $slugs );
	}
	$min = isset( $_GET['min'] ) && '' !== $_GET['min'] ? (float) $_GET['min'] : 0;
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
	$flt = isset( $_GET['flt'] ) ? (array) wp_unslash( $_GET['flt'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$flt = array_map( 'sanitize_key', $flt );
	if ( in_array( 'sale', $flt, true ) ) {
		$meta[] = array( 'key' => '_lavtheme_compare_price', 'value' => 0, 'type' => 'NUMERIC', 'compare' => '>' );
	}
	if ( in_array( 'new', $flt, true ) ) {
		$query->set( 'date_query', array( array( 'after' => '14 days ago' ) ) );
	}
	if ( in_array( 'best', $flt, true ) ) {
		$meta[] = array( 'key' => '_edd_download_sales', 'value' => lavzen_shop_bestseller_threshold(), 'type' => 'NUMERIC', 'compare' => '>=' );
	}
	$rating = isset( $_GET['rating'] ) ? (int) $_GET['rating'] : 0;
	if ( $rating > 0 && lavzen_shop_has_reviews() ) {
		$meta[] = array( 'key' => lavzen_shop_rating_meta_key(), 'value' => $rating / 10, 'type' => 'DECIMAL(3,1)', 'compare' => '>=' );
	}
	if ( $meta ) {
		$query->set( 'meta_query', $meta );
	}
	if ( $tax ) {
		$query->set( 'tax_query', $tax );
	}
	$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	switch ( $orderby ) {
		case 'sales':
		case 'trending':
			$query->set( 'meta_key', '_edd_download_sales' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
		case 'rating':
			$query->set( 'meta_key', lavzen_shop_has_reviews() ? lavzen_shop_rating_meta_key() : '_edd_download_sales' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
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
		default:
			$query->set( 'orderby', '' !== $pq ? 'relevance' : 'date' );
			$query->set( 'order', 'DESC' );
			break;
	}
}
add_action( 'pre_get_posts', 'lavzen_shop_pre_get_posts' );

/** Min/max edd_price across the catalogue (cached) — slider bounds. */
function lavzen_shop_price_bounds(): array {
	$cached = get_transient( 'lavzen_shop_price_bounds' );
	if ( is_array( $cached ) && isset( $cached['min'], $cached['max'] ) ) {
		return $cached;
	}
	global $wpdb;
	$row    = $wpdb->get_row( "SELECT MIN(meta_value+0) AS min_v, MAX(meta_value+0) AS max_v FROM {$wpdb->postmeta} WHERE meta_key = 'edd_price'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$bounds = array( 'min' => $row ? (float) $row->min_v : 0, 'max' => $row ? (float) $row->max_v : 200 );
	if ( $bounds['max'] <= $bounds['min'] ) {
		$bounds['max'] = $bounds['min'] + 200;
	}
	set_transient( 'lavzen_shop_price_bounds', $bounds, 12 * HOUR_IN_SECONDS );
	return $bounds;
}
add_action( 'save_post_download', static function () { delete_transient( 'lavzen_shop_price_bounds' ); } );

/** Current filter state read from the request. */
function lavzen_shop_filter_state(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$raw_cat = isset( $_GET['pcat'] ) ? wp_unslash( $_GET['pcat'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$slugs   = is_array( $raw_cat ) ? $raw_cat : explode( ',', (string) $raw_cat );
	$flt     = isset( $_GET['flt'] ) ? (array) wp_unslash( $_GET['flt'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$state   = array(
		'pq'      => isset( $_GET['pq'] ) ? sanitize_text_field( wp_unslash( $_GET['pq'] ) ) : '',
		'pcat'    => array_values( array_filter( array_map( 'sanitize_title', $slugs ) ) ),
		'min'     => isset( $_GET['min'] ) && '' !== $_GET['min'] ? (float) $_GET['min'] : '',
		'max'     => isset( $_GET['max'] ) && '' !== $_GET['max'] ? (float) $_GET['max'] : '',
		'flt'     => array_map( 'sanitize_key', $flt ),
		'rating'  => isset( $_GET['rating'] ) ? (int) $_GET['rating'] : 0,
		'orderby' => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'relevance',
		'view'    => ( isset( $_GET['view'] ) && 'list' === $_GET['view'] ) ? 'list' : 'grid',
	);
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	return $state;
}

/** The shop base URL (current taxonomy term, else the download archive). */
function lavzen_shop_base_url(): string {
	if ( is_tax() ) {
		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) ) {
			$u = get_term_link( $t );
			if ( ! is_wp_error( $u ) ) {
				return $u;
			}
		}
	}
	return function_exists( 'lavzen_shop_url' ) ? lavzen_shop_url() : (string) get_post_type_archive_link( 'download' );
}

function lavzen_shop_kfmt( $n ): string {
	$n = (int) $n;
	if ( $n >= 1000 ) {
		return rtrim( rtrim( number_format( $n / 1000, 1 ), '0' ), '.' ) . 'k';
	}
	return number_format_i18n( $n );
}

function lavzen_shop_money( $n ): string {
	$n = (float) $n;
	if ( function_exists( 'edd_currency_filter' ) && function_exists( 'edd_format_amount' ) ) {
		return wp_strip_all_tags( edd_currency_filter( edd_format_amount( round( $n ) ) ) );
	}
	return '$' . number_format_i18n( round( $n ) );
}

function lavzen_shop_cat_icon( $i ): string {
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
	return $icons[ ( (int) $i ) % count( $icons ) ];
}

function lavzen_shop_hero_stats(): array {
	$stats  = array();
	$counts = wp_count_posts( 'download' );
	$total  = $counts ? (int) $counts->publish : 0;
	if ( $total > 0 ) {
		$stats[] = array( 'b' => number_format_i18n( $total ) . '+', 'span' => __( 'Products', 'lavzentheme' ) );
	}
	if ( function_exists( 'edd_count_total_customers' ) ) {
		$cust = (int) edd_count_total_customers();
		if ( $cust > 0 ) {
			$stats[] = array( 'b' => lavzen_shop_kfmt( $cust ), 'span' => __( 'Customers', 'lavzentheme' ) );
		}
	}
	return $stats;
}

/** Discount/new/hot badge for a download. */
function lavzen_shop_badge( $id ): array {
	$price   = function_exists( 'edd_get_download_price' ) ? (float) edd_get_download_price( $id ) : 0.0;
	$compare = (float) get_post_meta( $id, '_lavtheme_compare_price', true );
	if ( $compare > 0 && $compare > $price ) {
		return array( 'text' => __( 'Sale', 'lavzentheme' ), 'class' => 'sale' );
	}
	if ( ( time() - (int) get_post_time( 'U', true, $id ) ) < 14 * DAY_IN_SECONDS ) {
		return array( 'text' => __( 'New', 'lavzentheme' ), 'class' => 'new' );
	}
	if ( (int) get_post_meta( $id, '_edd_download_sales', true ) >= lavzen_shop_bestseller_threshold() * 4 ) {
		return array( 'text' => __( 'Hot', 'lavzentheme' ), 'class' => 'hot' );
	}
	return array();
}

/** One shop product card (.pcard). */
function lavzen_shop_card_html( $id ): string {
	$id         = absint( $id );
	$permalink  = get_permalink( $id );
	$title      = get_the_title( $id );
	$cats       = get_the_terms( $id, 'download_category' );
	$cat        = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
	$thumb      = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'lavzen-card' ) : '';
	$show_price = '' !== (string) apply_filters( 'lavzen/shop/show_price', '1' );
	$badge      = lavzen_shop_badge( $id );
	$variable   = function_exists( 'edd_has_variable_prices' ) && edd_has_variable_prices( $id );
	$price_num  = function_exists( 'edd_get_download_price' ) ? (float) edd_get_download_price( $id ) : 0.0;
	$compare    = (float) get_post_meta( $id, '_lavtheme_compare_price', true );
	$sales      = function_exists( 'edd_get_download_sales_stats' ) ? (int) edd_get_download_sales_stats( $id ) : 0;
	$rating     = lavzen_shop_rating( $id );
	$excerpt    = wp_trim_words( get_the_excerpt( $id ), 26, '…' );

	$heart = lavzen_get_icon( 'heart' );
	$cart  = lavzen_get_icon( 'cart' );
	$star  = lavzen_get_icon( 'star' );

	ob_start();
	?>
	<article class="pcard glass" data-id="<?php echo esc_attr( $id ); ?>" data-title="<?php echo esc_attr( $title ); ?>" data-cat="<?php echo esc_attr( $cat ); ?>" data-img="<?php echo esc_attr( $thumb ); ?>" data-url="<?php echo esc_url( $permalink ); ?>" data-excerpt="<?php echo esc_attr( $excerpt ); ?>" data-price="<?php echo esc_attr( $show_price && function_exists( 'edd_price' ) ? wp_strip_all_tags( edd_price( $id, false ) ) : '' ); ?>">
		<div class="pthumb">
			<a href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
				<?php if ( $thumb ) : ?><img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy"><?php endif; ?>
			</a>
			<?php if ( ! empty( $badge ) ) : ?><span class="pbadge <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['text'] ); ?></span><?php endif; ?>
			<button type="button" class="pfav" data-fav="<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Add to wishlist', 'lavzentheme' ); ?>" aria-pressed="false"><?php echo $heart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			<button type="button" class="pquick" data-quick="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Quick view', 'lavzentheme' ); ?></button>
		</div>
		<div class="pbody">
			<?php if ( $cat ) : ?><span class="pcat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
			<h3 class="ptitle"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<div class="prate">
				<?php if ( ! empty( $rating ) ) : ?>
					<?php echo $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( number_format_i18n( $rating['avg'], 1 ) ); ?>
					<?php if ( $sales > 0 ) : ?>&nbsp;·&nbsp;<?php echo esc_html( number_format_i18n( $sales ) . ' ' . __( 'sales', 'lavzentheme' ) ); ?><?php endif; ?>
				<?php elseif ( $sales > 0 ) : ?>
					<?php echo esc_html( number_format_i18n( $sales ) . ' ' . __( 'sales', 'lavzentheme' ) ); ?>
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
				<?php if ( $variable || ! function_exists( 'edd_get_purchase_link' ) ) : ?>
					<a class="pbuy" href="<?php echo esc_url( $permalink ); ?>"><?php echo $cart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'View', 'lavzentheme' ); ?></a>
				<?php else : ?>
					<?php
					echo edd_get_purchase_link( array( 'download_id' => $id, 'price' => false, 'text' => __( 'Buy', 'lavzentheme' ), 'class' => 'pbuy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/* ------------------------------ UI builders ------------------------------ */

function lavzen_shop_chip_url( $base, $args ): string {
	$base = remove_query_arg( array( 'pq', 'pcat', 'min', 'max', 'flt', 'rating', 'orderby', 'paged', 'pg', 'view' ), $base );
	return $args ? add_query_arg( $args, $base ) : $base;
}

function lavzen_shop_hero_html(): string {
	$is_tax = is_tax();
	$title  = $is_tax ? single_term_title( '', false ) : __( 'Shop All Products', 'lavzentheme' );
	$stats  = lavzen_shop_hero_stats();
	ob_start();
	?>
	<div class="shop-hero glass">
		<div class="left">
			<span class="kicker"><?php echo esc_html( $is_tax ? __( 'Category', 'lavzentheme' ) : __( 'Digital Marketplace', 'lavzentheme' ) ); ?></span>
			<h1><?php echo esc_html( $title ); ?></h1>
			<?php
			if ( $is_tax ) {
				the_archive_description( '<p>', '</p>' );
			} else {
				echo '<p>' . esc_html__( 'Premium themes, plugins, templates and assets — crafted for creators and businesses.', 'lavzentheme' ) . '</p>';
			}
			?>
		</div>
		<?php if ( ! empty( $stats ) ) : ?>
			<div class="hero-stats">
				<?php foreach ( $stats as $s ) : ?>
					<div class="hstat"><b><?php echo esc_html( $s['b'] ); ?></b><span><?php echo esc_html( $s['span'] ); ?></span></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

function lavzen_shop_sidebar_html(): string {
	$state    = lavzen_shop_filter_state();
	$bounds   = lavzen_shop_price_bounds();
	$action   = lavzen_shop_base_url();
	$selected = $state['pcat'];
	if ( is_tax( 'download_category' ) ) {
		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) && isset( $t->slug ) && ! in_array( $t->slug, $selected, true ) ) {
			$selected[] = $t->slug;
		}
	}
	$terms = array();
	if ( taxonomy_exists( 'download_category' ) ) {
		$top = get_terms( array( 'taxonomy' => 'download_category', 'parent' => 0, 'hide_empty' => false ) );
		if ( ! is_wp_error( $top ) && $top ) {
			$by_slug = array();
			foreach ( $top as $t ) {
				$by_slug[ $t->slug ] = $t;
			}
			$ordered = function_exists( 'lavzen_home_dept_map' ) ? array_keys( lavzen_home_dept_map() ) : array();
			foreach ( $ordered as $slug ) {
				if ( isset( $by_slug[ $slug ] ) ) {
					$terms[] = $by_slug[ $slug ];
					unset( $by_slug[ $slug ] );
				}
			}
			if ( empty( $terms ) ) {
				foreach ( $by_slug as $t ) {
					if ( (int) $t->count > 0 ) {
						$terms[] = $t;
					}
				}
			}
		}
	}
	$pmin   = (int) floor( $bounds['min'] );
	$pmax   = (int) ceil( $bounds['max'] );
	$cmin   = '' !== $state['min'] ? (int) $state['min'] : $pmin;
	$cmax   = '' !== $state['max'] ? (int) $state['max'] : $pmax;
	$counts = wp_count_posts( 'download' );
	$total  = $counts ? (int) $counts->publish : 0;
	$search_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';

	ob_start();
	?>
	<form id="lav-shop-form" class="filters glass" method="get" action="<?php echo esc_url( $action ); ?>" role="search">
		<div class="fhead"><b><?php esc_html_e( 'Filters', 'lavzentheme' ); ?></b><a class="clear" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Clear all', 'lavzentheme' ); ?></a></div>
		<div class="fgroup">
			<span class="flabel"><?php esc_html_e( 'Search', 'lavzentheme' ); ?></span>
			<div class="search-box"><?php echo $search_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><input type="search" name="pq" value="<?php echo esc_attr( $state['pq'] ); ?>" placeholder="<?php esc_attr_e( 'Search products…', 'lavzentheme' ); ?>"></div>
		</div>
		<?php if ( ! empty( $terms ) ) : ?>
			<div class="fgroup">
				<span class="flabel"><?php esc_html_e( 'Categories', 'lavzentheme' ); ?></span>
				<div class="cat-list">
					<a class="cat<?php echo empty( $selected ) ? ' active' : ''; ?>" href="<?php echo esc_url( $action ); ?>"><span class="cic"><?php echo lavzen_shop_cat_icon( 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span class="cname"><?php esc_html_e( 'All Products', 'lavzentheme' ); ?></span><span class="count"><?php echo esc_html( number_format_i18n( $total ) ); ?></span></a>
					<?php $i = 1; foreach ( $terms as $term ) : $on = in_array( $term->slug, $selected, true ); ?>
						<label class="cat<?php echo $on ? ' active' : ''; ?>">
							<input type="checkbox" name="pcat[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( $on ); ?> hidden>
							<span class="cic"><?php echo lavzen_shop_cat_icon( $i ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="cname"><?php echo esc_html( $term->name ); ?></span>
							<span class="count"><?php echo esc_html( number_format_i18n( function_exists( 'lavzen_home_dept_count' ) ? lavzen_home_dept_count( (int) $term->term_id ) : (int) $term->count ) ); ?></span>
						</label>
					<?php $i++; endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="fgroup">
			<span class="flabel"><?php esc_html_e( 'Price range', 'lavzentheme' ); ?></span>
			<div class="range-wrap" data-min="<?php echo esc_attr( $pmin ); ?>" data-max="<?php echo esc_attr( $pmax ); ?>">
				<div class="range-bubbles"><span class="bubble" id="bubMin"><?php echo esc_html( lavzen_shop_money( $cmin ) ); ?></span><span class="bubble" id="bubMax"><?php echo esc_html( lavzen_shop_money( $cmax ) ); ?></span></div>
				<div class="slider"><div class="track"></div><div class="fill" id="fill"></div>
					<input type="range" id="rMin" name="min" min="<?php echo esc_attr( $pmin ); ?>" max="<?php echo esc_attr( $pmax ); ?>" value="<?php echo esc_attr( $cmin ); ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'lavzentheme' ); ?>">
					<input type="range" id="rMax" name="max" min="<?php echo esc_attr( $pmin ); ?>" max="<?php echo esc_attr( $pmax ); ?>" value="<?php echo esc_attr( $cmax ); ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'lavzentheme' ); ?>">
				</div>
			</div>
		</div>
		<div class="fgroup">
			<span class="flabel"><?php esc_html_e( 'Tags', 'lavzentheme' ); ?></span>
			<?php foreach ( array( 'sale' => __( 'On sale', 'lavzentheme' ), 'new' => __( 'New arrivals', 'lavzentheme' ), 'best' => __( 'Best sellers', 'lavzentheme' ) ) as $k => $lbl ) : ?>
				<label class="check"><input type="checkbox" name="flt[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $state['flt'], true ) ); ?>> <?php echo esc_html( $lbl ); ?></label>
			<?php endforeach; ?>
		</div>
		<button type="submit" class="apply-btn"><?php esc_html_e( 'Apply filters', 'lavzentheme' ); ?></button>
	</form>
	<?php
	return (string) ob_get_clean();
}

function lavzen_shop_toolbar_html(): string {
	$state = lavzen_shop_filter_state();
	$total = isset( $GLOBALS['wp_query']->found_posts ) ? (int) $GLOBALS['wp_query']->found_posts : 0;
	$shown = isset( $GLOBALS['wp_query']->post_count ) ? (int) $GLOBALS['wp_query']->post_count : 0;
	$opts  = array( 'relevance' => __( 'Best match', 'lavzentheme' ), 'sales' => __( 'Best sellers', 'lavzentheme' ), 'date' => __( 'Newest', 'lavzentheme' ), 'trending' => __( 'Trending', 'lavzentheme' ), 'price-asc' => __( 'Price: Low to High', 'lavzentheme' ), 'price-desc' => __( 'Price: High to Low', 'lavzentheme' ) );
	$grid_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>';
	$list_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>';
	ob_start();
	?>
	<div class="toolbar glass">
		<span class="count-txt"><?php echo wp_kses_post( sprintf( __( 'Showing %1$s of %2$s products', 'lavzentheme' ), '<b>' . number_format_i18n( $shown ) . '</b>', '<b>' . number_format_i18n( $total ) . '</b>' ) ); ?></span>
		<div class="tbar-right">
			<div class="view-toggle" role="group" aria-label="<?php esc_attr_e( 'View', 'lavzentheme' ); ?>">
				<button type="button" class="<?php echo 'grid' === $state['view'] ? 'active' : ''; ?>" data-view="grid" aria-label="<?php esc_attr_e( 'Grid view', 'lavzentheme' ); ?>"><?php echo $grid_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<button type="button" class="<?php echo 'list' === $state['view'] ? 'active' : ''; ?>" data-view="list" aria-label="<?php esc_attr_e( 'List view', 'lavzentheme' ); ?>"><?php echo $list_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
			<div class="sort">
				<label for="lav-sort"><?php esc_html_e( 'Sort by', 'lavzentheme' ); ?></label>
				<select id="lav-sort" name="orderby" form="lav-shop-form" onchange="if(this.form){this.form.submit();}">
					<?php foreach ( $opts as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $state['orderby'], $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

function lavzen_shop_pagination_html(): string {
	global $wp_query;
	$total = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 0;
	if ( $total < 2 ) {
		return '';
	}
	$links = paginate_links( array( 'type' => 'array', 'mid_size' => 1, 'prev_text' => '‹', 'next_text' => '›', 'total' => $total ) );
	if ( empty( $links ) ) {
		return '';
	}
	return '<nav class="pagination" aria-label="' . esc_attr__( 'Pagination', 'lavzentheme' ) . '">' . implode( '', $links ) . '</nav>';
}
