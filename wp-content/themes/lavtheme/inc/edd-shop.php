<?php
/**
 * Easy Digital Downloads — Shop archive (the download post-type archive).
 *
 * Provides the filtered main query (via pre_get_posts), the filter sidebar,
 * the sort control, product cards and badges that power archive-download.php
 * and taxonomy-download_*.php.
 *
 * IMPORTANT: these are THEME templates (standard WordPress hierarchy) and a
 * pre_get_posts filter on the real EDD query — no EDD internal template
 * (/edd/...) is ever overridden, so nothing breaks on an EDD update. The grid
 * stays wired to the real `download` post type, taxonomies and price meta.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Products per shop page.
 *
 * @return int
 */
function lavtheme_shop_per_page() {
	$n = absint( lavtheme_option( 'shop_per_page', 12 ) );
	return $n > 0 ? $n : 12;
}

/**
 * Is the given (or current) query the shop — the download archive or a
 * download taxonomy term archive?
 *
 * @param WP_Query|null $query Optional query to test; defaults to the main query.
 * @return bool
 */
function lavtheme_is_shop( $query = null ) {
	if ( $query instanceof WP_Query ) {
		return $query->is_post_type_archive( 'download' ) || $query->is_tax( array( 'download_category', 'download_tag' ) );
	}
	return is_post_type_archive( 'download' ) || is_tax( array( 'download_category', 'download_tag' ) );
}

/**
 * Apply the shop filters/sort to the real EDD main query.
 *
 * Filters arrive as read-only GET params (like a search), so no nonce is
 * required. A custom `pq` param is used for keyword search instead of `s` so
 * WordPress does not switch the template to search.php.
 *
 * @param WP_Query $query The query.
 */
function lavtheme_shop_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! lavtheme_is_shop( $query ) ) {
		return;
	}

	$query->set( 'posts_per_page', lavtheme_shop_per_page() );

	// Keyword search (custom param; see note above).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$pq = isset( $_GET['pq'] ) ? sanitize_text_field( wp_unslash( $_GET['pq'] ) ) : '';
	if ( '' !== $pq ) {
		$query->set( 's', $pq );
	}

	// Category filter (multi). Skipped on a category term archive (already filtered).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$raw_cat = isset( $_GET['pcat'] ) ? wp_unslash( $_GET['pcat'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$slugs   = is_array( $raw_cat ) ? $raw_cat : explode( ',', (string) $raw_cat );
	$slugs   = array_values( array_filter( array_map( 'sanitize_title', $slugs ) ) );
	if ( $slugs && ! $query->is_tax( 'download_category' ) && taxonomy_exists( 'download_category' ) ) {
		$tax   = (array) $query->get( 'tax_query' );
		$tax[] = array(
			'taxonomy' => 'download_category',
			'field'    => 'slug',
			'terms'    => $slugs,
		);
		$query->set( 'tax_query', $tax );
	}

	// Price range (numeric `edd_price` meta — simple products).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$min = isset( $_GET['min'] ) && '' !== $_GET['min'] ? (float) $_GET['min'] : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max = isset( $_GET['max'] ) && '' !== $_GET['max'] ? (float) $_GET['max'] : 0;
	if ( $min > 0 || $max > 0 ) {
		$meta = (array) $query->get( 'meta_query' );
		if ( $min > 0 && $max > 0 ) {
			$meta[] = array( 'key' => 'edd_price', 'value' => array( min( $min, $max ), max( $min, $max ) ), 'type' => 'NUMERIC', 'compare' => 'BETWEEN' );
		} elseif ( $min > 0 ) {
			$meta[] = array( 'key' => 'edd_price', 'value' => $min, 'type' => 'NUMERIC', 'compare' => '>=' );
		} else {
			$meta[] = array( 'key' => 'edd_price', 'value' => $max, 'type' => 'NUMERIC', 'compare' => '<=' );
		}
		$query->set( 'meta_query', $meta );
	}

	// Sort.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
	switch ( $orderby ) {
		case 'price_low':
			$query->set( 'meta_key', 'edd_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'ASC' );
			break;
		case 'price_high':
			$query->set( 'meta_key', 'edd_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
		case 'popular':
			$query->set( 'meta_key', '_edd_download_sales' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
		case 'title':
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
			break;
		case 'date':
		default:
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'DESC' );
			break;
	}
}
add_action( 'pre_get_posts', 'lavtheme_shop_pre_get_posts' );

/**
 * Min/max `edd_price` across the catalogue (cached) — used as slider hints.
 *
 * @return array{min:float,max:float}
 */
function lavtheme_shop_price_bounds() {
	$cached = get_transient( 'lavtheme_shop_price_bounds' );
	if ( is_array( $cached ) && isset( $cached['min'], $cached['max'] ) ) {
		return $cached;
	}
	global $wpdb;
	// No user input; reading numeric price meta only.
	$row    = $wpdb->get_row( "SELECT MIN(meta_value+0) AS min_v, MAX(meta_value+0) AS max_v FROM {$wpdb->postmeta} WHERE meta_key = 'edd_price'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$bounds = array(
		'min' => $row ? (float) $row->min_v : 0,
		'max' => $row ? (float) $row->max_v : 0,
	);
	set_transient( 'lavtheme_shop_price_bounds', $bounds, 12 * HOUR_IN_SECONDS );
	return $bounds;
}

/**
 * Clear the cached price bounds when a download price changes.
 */
function lavtheme_shop_flush_bounds() {
	delete_transient( 'lavtheme_shop_price_bounds' );
}
add_action( 'save_post_download', 'lavtheme_shop_flush_bounds' );
add_action( 'deleted_post', 'lavtheme_shop_flush_bounds' );

/**
 * Current filter state read from the request.
 *
 * @return array
 */
function lavtheme_shop_filter_state() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$raw_cat = isset( $_GET['pcat'] ) ? wp_unslash( $_GET['pcat'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$slugs   = is_array( $raw_cat ) ? $raw_cat : explode( ',', (string) $raw_cat );
	return array(
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'pq'      => isset( $_GET['pq'] ) ? sanitize_text_field( wp_unslash( $_GET['pq'] ) ) : '',
		'pcat'    => array_values( array_filter( array_map( 'sanitize_title', $slugs ) ) ),
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'min'     => isset( $_GET['min'] ) && '' !== $_GET['min'] ? (float) $_GET['min'] : '',
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'max'     => isset( $_GET['max'] ) && '' !== $_GET['max'] ? (float) $_GET['max'] : '',
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'orderby' => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'date',
	);
}

/**
 * Build the filter sidebar (search + categories + price). Output is fully
 * escaped here; callers can echo it directly.
 *
 * @return string
 */
function lavtheme_shop_sidebar_html() {
	$state  = lavtheme_shop_filter_state();
	$bounds = lavtheme_shop_price_bounds();
	$action = get_post_type_archive_link( 'download' );
	$action = $action ? $action : home_url( '/' );

	// Pre-select the current term when viewing a category term archive.
	$selected = $state['pcat'];
	if ( is_tax( 'download_category' ) ) {
		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) && isset( $t->slug ) && ! in_array( $t->slug, $selected, true ) ) {
			$selected[] = $t->slug;
		}
	}

	$terms = taxonomy_exists( 'download_category' )
		? get_terms( array( 'taxonomy' => 'download_category', 'hide_empty' => true ) )
		: array();

	ob_start();
	?>
	<form id="lav-shop-form" class="lav-filters" method="get" action="<?php echo esc_url( $action ); ?>" role="search">
		<div class="lav-filters__head">
			<h2 class="lav-filters__title"><?php esc_html_e( 'Filters', 'lavtheme' ); ?></h2>
			<a class="lav-filters__clear" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Clear all', 'lavtheme' ); ?></a>
		</div>

		<div class="lav-fgroup">
			<label class="lav-search">
				<svg class="lav-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
				<input type="search" name="pq" value="<?php echo esc_attr( $state['pq'] ); ?>" placeholder="<?php esc_attr_e( 'Search products…', 'lavtheme' ); ?>" aria-label="<?php esc_attr_e( 'Search products', 'lavtheme' ); ?>">
			</label>
		</div>

		<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
			<div class="lav-fgroup">
				<h3 class="lav-fgroup__title"><?php esc_html_e( 'Categories', 'lavtheme' ); ?></h3>
				<ul class="lav-checks">
					<?php foreach ( $terms as $term ) : ?>
						<li>
							<label class="lav-check">
								<input type="checkbox" name="pcat[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $selected, true ) ); ?>>
								<span class="lav-check__box" aria-hidden="true"></span>
								<span class="lav-check__label"><?php echo esc_html( $term->name ); ?></span>
								<span class="lav-check__count"><?php echo esc_html( number_format_i18n( (int) $term->count ) ); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="lav-fgroup">
			<h3 class="lav-fgroup__title"><?php esc_html_e( 'Price', 'lavtheme' ); ?></h3>
			<div class="lav-price">
				<label class="lav-price__field">
					<span><?php esc_html_e( 'Min', 'lavtheme' ); ?></span>
					<input type="number" name="min" inputmode="numeric" min="0" step="1" value="<?php echo esc_attr( '' !== $state['min'] ? $state['min'] : '' ); ?>" placeholder="<?php echo esc_attr( (string) (int) floor( $bounds['min'] ) ); ?>">
				</label>
				<span class="lav-price__sep" aria-hidden="true">–</span>
				<label class="lav-price__field">
					<span><?php esc_html_e( 'Max', 'lavtheme' ); ?></span>
					<input type="number" name="max" inputmode="numeric" min="0" step="1" value="<?php echo esc_attr( '' !== $state['max'] ? $state['max'] : '' ); ?>" placeholder="<?php echo esc_attr( (string) (int) ceil( $bounds['max'] ) ); ?>">
				</label>
			</div>
		</div>

		<div class="lav-filters__actions">
			<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Apply', 'lavtheme' ); ?></button>
			<a class="btn btn-ghost" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Reset', 'lavtheme' ); ?></a>
		</div>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * The sort dropdown for the top bar. It references the sidebar form via the
 * HTML5 `form` attribute so a single GET request carries every filter + sort.
 *
 * @param array $state Filter state (orderby).
 * @return string
 */
function lavtheme_shop_sort_html( $state ) {
	$current = isset( $state['orderby'] ) ? $state['orderby'] : 'date';
	$opts    = array(
		'date'       => __( 'Newest', 'lavtheme' ),
		'price_low'  => __( 'Price: Low to High', 'lavtheme' ),
		'price_high' => __( 'Price: High to Low', 'lavtheme' ),
		'popular'    => __( 'Best Selling', 'lavtheme' ),
		'title'      => __( 'Name (A–Z)', 'lavtheme' ),
	);
	ob_start();
	?>
	<label class="lav-sort">
		<span class="lav-sort__label"><?php esc_html_e( 'Sort by', 'lavtheme' ); ?></span>
		<select name="orderby" form="lav-shop-form" class="lav-select" onchange="if(this.form){this.form.submit();}">
			<?php foreach ( $opts as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<?php
	return ob_get_clean();
}

/**
 * Compute a product badge: discount (when a compare price meta is set), free,
 * or new. Honest and data-driven — no fake sales.
 *
 * @param int $id Download ID.
 * @return array{text:string,class:string}|array
 */
function lavtheme_shop_badge( $id ) {
	$price   = function_exists( 'edd_get_download_price' ) ? (float) edd_get_download_price( $id ) : 0.0;
	$compare = (float) get_post_meta( $id, '_lavtheme_compare_price', true );

	if ( $compare > 0 && $compare > $price ) {
		$pct = (int) round( ( ( $compare - $price ) / $compare ) * 100 );
		if ( $pct > 0 ) {
			return array( 'text' => '-' . $pct . '%', 'class' => 'is-sale' );
		}
	}
	if ( 0.0 === $price ) {
		return array( 'text' => __( 'Free', 'lavtheme' ), 'class' => 'is-free' );
	}
	if ( ( time() - (int) get_post_time( 'U', true, $id ) ) < 14 * DAY_IN_SECONDS ) {
		return array( 'text' => __( 'New', 'lavtheme' ), 'class' => 'is-new' );
	}
	return array();
}

/**
 * Render one product card. Mirrors the front-page `.lavp-card` look but uses an
 * <article> (not a wrapping <a>) so the real EDD purchase button can live in it.
 *
 * @param int $id Download ID.
 * @return string
 */
function lavtheme_shop_card_html( $id ) {
	$id         = absint( $id );
	$permalink  = get_permalink( $id );
	$title      = get_the_title( $id );
	$cats       = get_the_terms( $id, 'download_category' );
	$cat        = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
	$thumb      = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'lavtheme-card' ) : '';
	$show_price = '' !== (string) lavtheme_option( 'show_price', '1' );
	$badge      = lavtheme_shop_badge( $id );
	$variable   = function_exists( 'edd_has_variable_prices' ) && edd_has_variable_prices( $id );

	ob_start();
	?>
	<article class="lavp-card glass">
		<?php if ( ! empty( $badge ) ) : ?>
			<span class="lav-card__badge <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['text'] ); ?></span>
		<?php endif; ?>
		<a class="lavp-thumb" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( $thumb ) : ?>
				<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
			<?php endif; ?>
		</a>
		<div class="lavp-body">
			<?php if ( $cat ) : ?><div class="lavp-cat"><?php echo esc_html( $cat ); ?></div><?php endif; ?>
			<h3 class="lavp-title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<div class="lavp-foot">
				<span class="lavp-price">
					<?php
					if ( $show_price && function_exists( 'edd_price' ) ) {
						echo wp_kses_post( edd_price( $id, false ) );
					}
					?>
				</span>
				<?php
				if ( $variable || ! function_exists( 'edd_get_purchase_link' ) ) :
					?>
					<a class="btn btn-ghost lavp-buy" href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'View', 'lavtheme' ); ?></a>
					<?php
				else :
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- EDD builds escaped markup.
					echo edd_get_purchase_link(
						array(
							'download_id' => $id,
							'price'       => false,
							'text'        => __( 'Add to Cart', 'lavtheme' ),
							'class'       => 'btn btn-primary lavp-buy',
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
