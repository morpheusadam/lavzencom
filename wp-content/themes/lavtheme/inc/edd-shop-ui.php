<?php
/**
 * Shop archive — UI builders (hero, filter sidebar, toolbar, active chips,
 * pagination, quick-view modal). Split from inc/edd-shop.php (which holds the
 * query/data layer) only to keep each file focused. All output is escaped here.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/** Format a price using the EDD currency formatter (whole units). */
function lavtheme_shop_money( $n ) {
	$n = (float) $n;
	if ( function_exists( 'edd_currency_filter' ) && function_exists( 'edd_format_amount' ) ) {
		return wp_strip_all_tags( edd_currency_filter( edd_format_amount( round( $n ) ) ) );
	}
	return '$' . number_format_i18n( round( $n ) );
}

/** Build a filter URL from an args map (handles pcat[]/flt[] arrays). */
function lavtheme_shop_chip_url( $base, $args ) {
	$base = remove_query_arg( array( 'pq', 'pcat', 'min', 'max', 'flt', 'rating', 'orderby', 'paged', 'view' ), $base );
	return $args ? add_query_arg( $args, $base ) : $base;
}

/** Hero strip with real stats. */
function lavtheme_shop_hero_html() {
	$is_tax = is_tax();
	$title  = $is_tax ? single_term_title( '', false ) : __( 'Shop All Products', 'lavtheme' );
	$stats  = lavtheme_shop_hero_stats();
	ob_start();
	?>
	<div class="shop-hero glass">
		<div class="left">
			<span class="kicker"><?php echo esc_html( $is_tax ? __( 'Category', 'lavtheme' ) : __( 'Digital Marketplace', 'lavtheme' ) ); ?></span>
			<h1><?php echo esc_html( $title ); ?></h1>
			<?php
			if ( $is_tax ) {
				the_archive_description( '<p>', '</p>' );
			} else {
				echo '<p>' . esc_html__( 'Premium themes, plugins, templates and assets — crafted for creators and businesses.', 'lavtheme' ) . '</p>';
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
	return ob_get_clean();
}

/** Filter sidebar (the GET form the sort select references). */
function lavtheme_shop_sidebar_html() {
	$state    = lavtheme_shop_filter_state();
	$bounds   = lavtheme_shop_price_bounds();
	$action   = lavtheme_shop_base_url();
	$selected = $state['pcat'];
	if ( is_tax( 'download_category' ) ) {
		$t = get_queried_object();
		if ( $t && ! is_wp_error( $t ) && isset( $t->slug ) && ! in_array( $t->slug, $selected, true ) ) {
			$selected[] = $t->slug;
		}
	}
	$terms = taxonomy_exists( 'download_category' ) ? get_terms( array( 'taxonomy' => 'download_category', 'hide_empty' => true ) ) : array();
	$pmin  = (int) floor( $bounds['min'] );
	$pmax  = (int) ceil( $bounds['max'] );
	$cmin  = '' !== $state['min'] ? (int) $state['min'] : $pmin;
	$cmax  = '' !== $state['max'] ? (int) $state['max'] : $pmax;

	$counts = wp_count_posts( 'download' );
	$total  = $counts ? (int) $counts->publish : 0;

	$search_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';

	ob_start();
	?>
	<form id="lav-shop-form" class="filters glass" method="get" action="<?php echo esc_url( $action ); ?>" role="search">
		<div class="fhead"><b><?php esc_html_e( 'Filters', 'lavtheme' ); ?></b><a class="clear" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Clear all', 'lavtheme' ); ?></a></div>

		<div class="fgroup">
			<span class="flabel"><?php esc_html_e( 'Search', 'lavtheme' ); ?></span>
			<div class="search-box">
				<?php echo $search_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="search" name="pq" value="<?php echo esc_attr( $state['pq'] ); ?>" placeholder="<?php esc_attr_e( 'Search products…', 'lavtheme' ); ?>">
			</div>
		</div>

		<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
			<div class="fgroup">
				<span class="flabel"><?php esc_html_e( 'Categories', 'lavtheme' ); ?></span>
				<div class="cat-list">
					<a class="cat<?php echo empty( $selected ) ? ' active' : ''; ?>" href="<?php echo esc_url( $action ); ?>">
						<span class="cic"><?php echo lavtheme_shop_cat_icon( 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="cname"><?php esc_html_e( 'All Products', 'lavtheme' ); ?></span>
						<span class="count"><?php echo esc_html( number_format_i18n( $total ) ); ?></span>
					</a>
					<?php
					$i = 1;
					foreach ( $terms as $term ) :
						$on = in_array( $term->slug, $selected, true );
						?>
						<label class="cat<?php echo $on ? ' active' : ''; ?>">
							<input type="checkbox" name="pcat[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( $on ); ?> hidden>
							<span class="cic"><?php echo lavtheme_shop_cat_icon( $i ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="cname"><?php echo esc_html( $term->name ); ?></span>
							<span class="count"><?php echo esc_html( number_format_i18n( (int) $term->count ) ); ?></span>
						</label>
						<?php
						$i++;
					endforeach;
					?>
				</div>
			</div>
		<?php endif; ?>

		<div class="fgroup">
			<span class="flabel"><?php esc_html_e( 'Price range', 'lavtheme' ); ?></span>
			<div class="range-wrap" data-min="<?php echo esc_attr( $pmin ); ?>" data-max="<?php echo esc_attr( $pmax ); ?>">
				<div class="range-bubbles"><span class="bubble" id="bubMin"><?php echo esc_html( lavtheme_shop_money( $cmin ) ); ?></span><span class="bubble" id="bubMax"><?php echo esc_html( lavtheme_shop_money( $cmax ) ); ?></span></div>
				<div class="slider">
					<div class="track"></div>
					<div class="fill" id="fill"></div>
					<input type="range" id="rMin" name="min" min="<?php echo esc_attr( $pmin ); ?>" max="<?php echo esc_attr( $pmax ); ?>" value="<?php echo esc_attr( $cmin ); ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'lavtheme' ); ?>">
					<input type="range" id="rMax" name="max" min="<?php echo esc_attr( $pmin ); ?>" max="<?php echo esc_attr( $pmax ); ?>" value="<?php echo esc_attr( $cmax ); ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'lavtheme' ); ?>">
				</div>
			</div>
		</div>

		<?php if ( lavtheme_shop_has_reviews() ) : ?>
			<div class="fgroup">
				<span class="flabel"><?php esc_html_e( 'Rating', 'lavtheme' ); ?></span>
				<?php foreach ( array( 45 => '4.5', 40 => '4.0', 30 => '3.0' ) as $rv => $lbl ) : ?>
					<label class="check"><input type="radio" name="rating" value="<?php echo esc_attr( $rv ); ?>" <?php checked( $state['rating'], $rv ); ?>> <?php echo esc_html( $lbl . ' ' . __( '& up', 'lavtheme' ) ); ?></label>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="fgroup">
			<span class="flabel"><?php esc_html_e( 'Tags', 'lavtheme' ); ?></span>
			<?php foreach ( array( 'sale' => __( 'On sale', 'lavtheme' ), 'new' => __( 'New arrivals', 'lavtheme' ), 'best' => __( 'Best sellers', 'lavtheme' ) ) as $k => $lbl ) : ?>
				<label class="check"><input type="checkbox" name="flt[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $state['flt'], true ) ); ?>> <?php echo esc_html( $lbl ); ?></label>
			<?php endforeach; ?>
		</div>

		<button type="submit" class="apply-btn"><?php esc_html_e( 'Apply filters', 'lavtheme' ); ?></button>
	</form>
	<?php
	return ob_get_clean();
}

/** Toolbar: result count + grid/list toggle + sort. */
function lavtheme_shop_toolbar_html() {
	$state = lavtheme_shop_filter_state();
	$total = isset( $GLOBALS['wp_query']->found_posts ) ? (int) $GLOBALS['wp_query']->found_posts : 0;
	$shown = isset( $GLOBALS['wp_query']->post_count ) ? (int) $GLOBALS['wp_query']->post_count : 0;

	$opts = array( 'relevance' => __( 'Best match', 'lavtheme' ), 'sales' => __( 'Best sellers', 'lavtheme' ), 'date' => __( 'Newest', 'lavtheme' ) );
	if ( lavtheme_shop_has_reviews() ) {
		$opts['rating'] = __( 'Best rated', 'lavtheme' );
	}
	$opts['trending']   = __( 'Trending', 'lavtheme' );
	$opts['price-asc']  = __( 'Price: Low to High', 'lavtheme' );
	$opts['price-desc'] = __( 'Price: High to Low', 'lavtheme' );

	$grid_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>';
	$list_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>';

	ob_start();
	?>
	<div class="toolbar glass">
		<span class="count-txt"><?php echo wp_kses_post( sprintf( __( 'Showing %1$s of %2$s products', 'lavtheme' ), '<b>' . number_format_i18n( $shown ) . '</b>', '<b>' . number_format_i18n( $total ) . '</b>' ) ); ?></span>
		<div class="tbar-right">
			<div class="view-toggle" role="group" aria-label="<?php esc_attr_e( 'View', 'lavtheme' ); ?>">
				<button type="button" class="<?php echo 'grid' === $state['view'] ? 'active' : ''; ?>" data-view="grid" aria-label="<?php esc_attr_e( 'Grid view', 'lavtheme' ); ?>"><?php echo $grid_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<button type="button" class="<?php echo 'list' === $state['view'] ? 'active' : ''; ?>" data-view="list" aria-label="<?php esc_attr_e( 'List view', 'lavtheme' ); ?>"><?php echo $list_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
			<div class="sort">
				<label for="lav-sort"><?php esc_html_e( 'Sort by', 'lavtheme' ); ?></label>
				<select id="lav-sort" name="orderby" form="lav-shop-form" onchange="if(this.form){this.form.submit();}">
					<?php foreach ( $opts as $k => $lbl ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $state['orderby'], $k ); ?>><?php echo esc_html( $lbl ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/** Active-filter chips (each removable via a link). */
function lavtheme_shop_chips_html() {
	$state = lavtheme_shop_filter_state();
	$base  = lavtheme_shop_base_url();

	$args = array();
	if ( '' !== $state['pq'] ) {
		$args['pq'] = $state['pq'];
	}
	if ( $state['pcat'] ) {
		$args['pcat'] = $state['pcat'];
	}
	if ( '' !== $state['min'] ) {
		$args['min'] = $state['min'];
	}
	if ( '' !== $state['max'] ) {
		$args['max'] = $state['max'];
	}
	if ( $state['flt'] ) {
		$args['flt'] = $state['flt'];
	}
	if ( $state['rating'] ) {
		$args['rating'] = $state['rating'];
	}
	if ( 'relevance' !== $state['orderby'] ) {
		$args['orderby'] = $state['orderby'];
	}

	$x_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>';
	$chips = array();

	foreach ( $state['pcat'] as $slug ) {
		$term = get_term_by( 'slug', $slug, 'download_category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$rest         = $args;
		$rest['pcat'] = array_values( array_diff( $state['pcat'], array( $slug ) ) );
		if ( ! $rest['pcat'] ) {
			unset( $rest['pcat'] );
		}
		$chips[] = array( 'label' => $term->name, 'url' => lavtheme_shop_chip_url( $base, $rest ) );
	}
	if ( '' !== $state['min'] || '' !== $state['max'] ) {
		$lbl  = lavtheme_shop_money( '' !== $state['min'] ? $state['min'] : 0 ) . ' – ' . lavtheme_shop_money( '' !== $state['max'] ? $state['max'] : 0 );
		$rest = $args;
		unset( $rest['min'], $rest['max'] );
		$chips[] = array( 'label' => $lbl, 'url' => lavtheme_shop_chip_url( $base, $rest ) );
	}
	if ( '' !== $state['pq'] ) {
		$rest = $args;
		unset( $rest['pq'] );
		$chips[] = array( 'label' => '“' . $state['pq'] . '”', 'url' => lavtheme_shop_chip_url( $base, $rest ) );
	}
	$tagnames = array( 'sale' => __( 'On sale', 'lavtheme' ), 'new' => __( 'New', 'lavtheme' ), 'best' => __( 'Best sellers', 'lavtheme' ) );
	foreach ( $state['flt'] as $f ) {
		if ( ! isset( $tagnames[ $f ] ) ) {
			continue;
		}
		$rest        = $args;
		$rest['flt'] = array_values( array_diff( $state['flt'], array( $f ) ) );
		if ( ! $rest['flt'] ) {
			unset( $rest['flt'] );
		}
		$chips[] = array( 'label' => $tagnames[ $f ], 'url' => lavtheme_shop_chip_url( $base, $rest ) );
	}
	if ( $state['rating'] ) {
		$rest = $args;
		unset( $rest['rating'] );
		$chips[] = array( 'label' => ( $state['rating'] / 10 ) . '+ ★', 'url' => lavtheme_shop_chip_url( $base, $rest ) );
	}

	if ( ! $chips ) {
		return '';
	}
	ob_start();
	?>
	<div class="active-chips">
		<span class="ac-label"><?php esc_html_e( 'Active:', 'lavtheme' ); ?></span>
		<?php foreach ( $chips as $c ) : ?>
			<span class="chip"><?php echo esc_html( $c['label'] ); ?> <a href="<?php echo esc_url( $c['url'] ); ?>" aria-label="<?php esc_attr_e( 'Remove filter', 'lavtheme' ); ?>"><?php echo $x_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></span>
		<?php endforeach; ?>
		<a class="chip chip-clear" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Clear all', 'lavtheme' ); ?></a>
	</div>
	<?php
	return ob_get_clean();
}

/** Pagination matching the design (styled paginate_links). */
function lavtheme_shop_pagination_html() {
	global $wp_query;
	$total = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 0;
	if ( $total < 2 ) {
		return '';
	}
	$links = paginate_links(
		array(
			'type'      => 'array',
			'mid_size'  => 1,
			'prev_text' => '‹',
			'next_text' => '›',
			'current'   => max( 1, (int) get_query_var( 'paged' ) ),
			'total'     => $total,
		)
	);
	if ( empty( $links ) ) {
		return '';
	}
	return '<nav class="pagination" aria-label="' . esc_attr__( 'Pagination', 'lavtheme' ) . '">' . implode( '', $links ) . '</nav>';
}

/** Quick-view modal shell (populated by shop.js from card data attributes). */
function lavtheme_shop_quickview_html() {
	ob_start();
	?>
	<div class="lav-qv" id="lavQv" hidden>
		<div class="lav-qv__overlay" data-qv-close></div>
		<div class="lav-qv__panel glass" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Quick view', 'lavtheme' ); ?>">
			<button type="button" class="lav-qv__close" data-qv-close aria-label="<?php esc_attr_e( 'Close', 'lavtheme' ); ?>">&times;</button>
			<div class="lav-qv__media"><img src="" alt="" data-qv-img></div>
			<div class="lav-qv__body">
				<span class="pcat" data-qv-cat></span>
				<h3 data-qv-title></h3>
				<div class="lav-qv__price" data-qv-price></div>
				<p data-qv-excerpt></p>
				<a class="pbuy" data-qv-link href="#"><?php esc_html_e( 'View details', 'lavtheme' ); ?></a>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
