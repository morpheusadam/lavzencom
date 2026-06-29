<?php
/**
 * Marketplace home — data + render helpers (EDD-wired).
 *
 * Procedural template helpers ported from the legacy inc/home.php + inc/edd.php
 * (renamed lavzen_*; product/rating/vendor meta keys kept for data continuity).
 * front-page.php and the section template-parts call these by name. Everything is
 * EDD-guarded so it degrades gracefully when EDD is inactive. Loaded by the EDD
 * module (outside the autoloaded namespace, like the other template-tag files).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lavzen_edd_active' ) ) {
	function lavzen_edd_active(): bool {
		return function_exists( 'EDD' ) || class_exists( 'Easy_Digital_Downloads' ) || post_type_exists( 'download' );
	}
}

/** Canonical department map: slug => [ name, glyph ], in display order. */
function lavzen_home_dept_map(): array {
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

/** Count published downloads in a department (incl. descendants). */
function lavzen_home_dept_count( int $term_id ): int {
	$q = new WP_Query(
		array(
			'post_type'      => 'download',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => false,
			'tax_query'      => array(
				array( 'taxonomy' => 'download_category', 'field' => 'term_id', 'terms' => $term_id, 'include_children' => true ),
			),
		)
	);
	return (int) $q->found_posts;
}

/** The ten departments resolved against the live taxonomy (cached). */
function lavzen_home_departments(): array {
	$cache = get_transient( 'lavzen_home_depts' );
	if ( is_array( $cache ) ) {
		return $cache;
	}
	$out = array();
	foreach ( lavzen_home_dept_map() as $slug => $meta ) {
		$term  = get_term_by( 'slug', $slug, 'download_category' );
		$url   = '';
		$count = 0;
		if ( $term && ! is_wp_error( $term ) ) {
			$link  = get_term_link( $term );
			$url   = is_wp_error( $link ) ? '' : $link;
			$count = lavzen_home_dept_count( (int) $term->term_id );
		}
		if ( '' === $url ) {
			$url = lavzen_shop_url();
		}
		$out[] = array( 'slug' => $slug, 'name' => $meta[0], 'glyph' => $meta[1], 'url' => $url, 'count' => $count );
	}
	set_transient( 'lavzen_home_depts', $out, HOUR_IN_SECONDS );
	return $out;
}

/** A category term URL by slug, falling back to the shop URL. */
function lavzen_home_term_url( string $slug ): string {
	$term = get_term_by( 'slug', $slug, 'download_category' );
	if ( $term && ! is_wp_error( $term ) ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	return lavzen_shop_url();
}

/** Walk a download's terms up to the top-level department; return [name,glyph] match. */
function lavzen_home_post_top( int $id ): array {
	$cats = get_the_terms( $id, 'download_category' );
	$map  = lavzen_home_dept_map();
	if ( $cats && ! is_wp_error( $cats ) ) {
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
				return $map[ $top->slug ];
			}
		}
		return array( $cats[0]->name, '◇' );
	}
	return array( '', '◇' );
}

function lavzen_home_post_glyph( int $id ): string {
	$t = lavzen_home_post_top( $id );
	return $t[1];
}

function lavzen_home_post_dept( int $id ): string {
	$t = lavzen_home_post_top( $id );
	return $t[0];
}

/** Formatted price markup: "Free", "$49" or "from $19". */
function lavzen_home_price_html( int $id ): string {
	if ( function_exists( 'edd_is_free_download' ) && edd_is_free_download( $id ) ) {
		return '<span class="card__free">' . esc_html__( 'Free', 'lavzentheme' ) . '</span>';
	}
	$price = function_exists( 'edd_get_download_price' ) ? (float) edd_get_download_price( $id ) : 0.0;
	if ( $price <= 0 ) {
		return '<span class="card__free">' . esc_html__( 'Free', 'lavzentheme' ) . '</span>';
	}
	$show_decimals = ( (float) $price !== (float) (int) $price );
	$amount        = function_exists( 'edd_currency_filter' ) && function_exists( 'edd_format_amount' )
		? wp_strip_all_tags( edd_currency_filter( edd_format_amount( $price, $show_decimals ) ) )
		: '$' . number_format_i18n( $price, $show_decimals ? 2 : 0 );
	$prefix = ( function_exists( 'edd_has_variable_prices' ) && edd_has_variable_prices( $id ) ) ? esc_html__( 'from ', 'lavzentheme' ) : '';
	return '<span class="card__price">' . esc_html( $prefix . $amount ) . '</span>';
}

/** Seller/vendor name (meta, else author display name). */
function lavzen_home_vendor( int $id ): string {
	$vendor = (string) get_post_meta( $id, '_lavzen_vendor', true );
	if ( '' !== $vendor ) {
		return $vendor;
	}
	$author = (int) get_post_field( 'post_author', $id );
	$name   = $author ? get_the_author_meta( 'display_name', $author ) : '';
	return $name ? $name : 'LAVZEN';
}

/** Rating tuple [avg,count] or empty. */
function lavzen_home_rating( int $id ): array {
	$avg = (float) get_post_meta( $id, '_lavzen_rating', true );
	if ( $avg <= 0 ) {
		return array();
	}
	return array( round( $avg, 1 ), (int) get_post_meta( $id, '_lavzen_rating_count', true ) );
}

/** Compatibility chips (comma-separated meta, max 2). */
function lavzen_home_chips( int $id ): array {
	$raw = (string) get_post_meta( $id, '_lavzen_chips', true );
	if ( '' === $raw ) {
		return array();
	}
	return array_slice( array_filter( array_map( 'trim', explode( ',', $raw ) ) ), 0, 2 );
}

/** Compact count (1.2k). */
function lavzen_home_kfmt( $n ): string {
	$n = (int) $n;
	if ( $n >= 1000 ) {
		return rtrim( rtrim( number_format( $n / 1000, 1 ), '0' ), '.' ) . 'k';
	}
	return number_format_i18n( $n );
}

/** Store-wide stats for the social-proof band (cached, with fallbacks). */
function lavzen_home_stats(): array {
	$cache = get_transient( 'lavzen_home_stats' );
	if ( is_array( $cache ) ) {
		return $cache;
	}
	global $wpdb;
	$listings  = (int) wp_count_posts( 'download' )->publish;
	$sellers   = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_lavzen_vendor' AND meta_value <> ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$downloads = (int) $wpdb->get_var( "SELECT SUM(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key = '_edd_download_sales'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$rating    = (float) $wpdb->get_var( "SELECT AVG(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key = '_lavzen_rating' AND meta_value+0 > 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$stats     = array(
		'listings'  => $listings > 0 ? number_format_i18n( $listings ) : '1,240',
		'sellers'   => $sellers > 0 ? number_format_i18n( $sellers ) : '380',
		'downloads' => $downloads > 0 ? lavzen_home_kfmt( $downloads ) : '92k',
		'rating'    => $rating > 0 ? number_format_i18n( round( $rating, 1 ), 1 ) : '4.8',
	);
	set_transient( 'lavzen_home_stats', $stats, 6 * HOUR_IN_SECONDS );
	return $stats;
}

/** The shared "save" (wishlist) heart button markup. */
function lavzen_home_save_btn( string $title ): string {
	return '<button class="card__save" type="button" aria-pressed="false" aria-label="'
		. esc_attr( sprintf( __( 'Save %s', 'lavzentheme' ), $title ) ) . '" data-save>'
		. '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.6-9.2-9C1.3 7.7 3 5 6 5c1.9 0 3.2 1.1 4 2.3C10.8 6.1 12.1 5 14 5c3 0 4.7 2.7 3.2 6C19 15.4 12 20 12 20z"/></svg>'
		. '</button>';
}

/** One product card (.card <li>) wired to real EDD data. */
function lavzen_home_product_card( int $id, string $rank = '' ): string {
	$id        = absint( $id );
	$permalink = get_permalink( $id );
	$title     = get_the_title( $id );
	$dept      = lavzen_home_post_dept( $id );
	$glyph     = lavzen_home_post_glyph( $id );
	$vendor    = lavzen_home_vendor( $id );
	$rating    = lavzen_home_rating( $id );
	$chips     = lavzen_home_chips( $id );
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
		<?php echo lavzen_home_save_btn( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaping. ?>
		<div class="card__body">
			<p class="card__meta">
				<?php if ( '' !== $dept ) : ?><span class="card__dept"><?php echo esc_html( $dept ); ?></span><?php endif; ?>
				<?php if ( $verified ) : ?><span class="card__verif" title="<?php esc_attr_e( 'Verified', 'lavzentheme' ); ?>">✓ <?php esc_html_e( 'Verified', 'lavzentheme' ); ?></span><?php endif; ?>
			</p>
			<h3 class="card__title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<p class="card__by">
				<?php echo esc_html( sprintf( __( 'by %s', 'lavzentheme' ), $vendor ) ); ?>
				<?php if ( $rating ) : ?>
					· <span class="card__rate" aria-hidden="true">★ <?php echo esc_html( number_format_i18n( $rating[0], 1 ) ); ?></span>
					<?php if ( $rating[1] > 0 ) : ?><span class="card__count">(<?php echo esc_html( lavzen_home_kfmt( $rating[1] ) ); ?>)</span><?php endif; ?>
				<?php endif; ?>
			</p>
			<?php if ( $chips ) : ?>
				<p class="card__chips"><?php foreach ( $chips as $chip ) : ?><span class="cchip"><?php echo esc_html( $chip ); ?></span><?php endforeach; ?></p>
			<?php endif; ?>
			<div class="card__foot">
				<?php echo lavzen_home_price_html( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
				<a class="card__get" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Get %s', 'lavzentheme' ), $title ) ); ?>"><?php esc_html_e( 'Get', 'lavzentheme' ); ?></a>
			</div>
		</div>
	</li>
	<?php
	return (string) ob_get_clean();
}

/** Rail navigation arrow SVG. */
function lavzen_home_arrow( string $dir ): string {
	$path = 'prev' === $dir ? 'M15 5l-7 7 7 7' : 'M9 5l7 7-7 7';
	return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . $path . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/** Query downloads for a rail. */
function lavzen_home_query( array $args ): WP_Query {
	$defaults = array(
		'post_type'           => 'download',
		'post_status'         => 'publish',
		'posts_per_page'      => 10,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	return new WP_Query( array_merge( $defaults, $args ) );
}

/** Render a full product rail section. */
function lavzen_home_rail( array $cfg ): void {
	$query = isset( $cfg['query'] ) ? $cfg['query'] : null;
	if ( ! $query instanceof WP_Query || ! $query->have_posts() ) {
		wp_reset_postdata();
		return;
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
					<a class="head__link" href="<?php echo esc_url( $cfg['view_url'] ); ?>"><?php echo esc_html( ! empty( $cfg['view_all'] ) ? $cfg['view_all'] : __( 'View all', 'lavzentheme' ) ); ?></a>
				<?php endif; ?>
				<button class="rail__btn" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Scroll %s left', 'lavzentheme' ), $title ) ); ?>" data-prev><?php echo lavzen_home_arrow( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<button class="rail__btn" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Scroll %s right', 'lavzentheme' ), $title ) ); ?>" data-next><?php echo lavzen_home_arrow( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
		</div>
		<div class="rail__viewport" data-viewport tabindex="0" role="group" aria-label="<?php echo esc_attr( sprintf( __( '%s — scrollable', 'lavzentheme' ), $title ) ); ?>">
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
					echo lavzen_home_product_card( get_the_ID(), $badge ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
				}
				wp_reset_postdata();
				?>
			</ul>
		</div>
	</section>
	<?php
}
