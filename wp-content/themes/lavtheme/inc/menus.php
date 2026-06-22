<?php
/**
 * Navigation menus — real, manageable WordPress menus for the theme shell.
 *
 * Locations are registered in inc/setup.php (primary, mobile, social_sidebar,
 * account, shop_categories). Each renderer uses wp_nav_menu() when a menu is
 * assigned and otherwise a LIVE dynamic fallback, so the shell always shows
 * useful content (and the front-page single-page nav is never broken) while
 * staying fully editable in Appearance → Menus.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* ----------------------------------------------------------------------- *
 * Desktop topnav (.topnav) — bare <a> items to match the design.
 * ----------------------------------------------------------------------- */

/**
 * Walker that emits bare <a> items (no <ul>/<li>) for the flex .topnav.
 */
class Lavtheme_Topnav_Walker extends Walker_Nav_Menu {
	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$active  = in_array( 'current-menu-item', $classes, true ) || in_array( 'current_page_item', $classes, true );
		$url     = ! empty( $item->url ) ? $item->url : '#';
		$output .= '<a href="' . esc_url( $url ) . '"' . ( $active ? ' class="active"' : '' ) . '>' . esc_html( $item->title ) . '</a>';
	}
}

/** Desktop topnav fallback. On the front page it reproduces the original
 *  single-page anchor nav exactly (pixel-safe); elsewhere it links to real
 *  destinations (Home / Shop / Blog) so inner pages get a usable nav. */
function lavtheme_topnav_fallback() {
	if ( is_front_page() ) {
		$items = array(
			array( 'Home', '#home', true ),
			array( 'Services', '#services', false ),
			array( 'Products', '#products', false ),
			array( 'Blog', '#blog', false ),
			array( 'Contact', '#contact', false ),
		);
	} else {
		$shop  = function_exists( 'lavtheme_shop_url' ) ? lavtheme_shop_url() : get_post_type_archive_link( 'download' );
		$items = array(
			array( 'Home', home_url( '/' ), false ),
			array( 'Shop', $shop ? $shop : home_url( '/' ), lavtheme_is_shop() ),
			array( 'Services', home_url( '/#services' ), false ),
			array( 'Blog', lavtheme_blog_url(), is_home() || is_singular( 'post' ) ),
			array( 'Contact', home_url( '/#contact' ), false ),
		);
	}
	$out = '';
	foreach ( $items as $it ) {
		$out .= '<a href="' . esc_url( $it[1] ) . '"' . ( $it[2] ? ' class="active"' : '' ) . '>' . esc_html( $it[0] ) . '</a>';
	}
	return $out;
}

/** The blog URL — the dynamic blog page (posts page or theme Blog page), else home. */
function lavtheme_blog_url() {
	$pid = function_exists( 'lavtheme_blog_page_id' ) ? lavtheme_blog_page_id() : (int) get_option( 'page_for_posts' );
	if ( $pid ) {
		return get_permalink( $pid );
	}
	return home_url( '/' );
}

/** Render the desktop topnav: assigned 'primary' menu, else the safe fallback. */
function lavtheme_topnav() {
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'items_wrap'     => '%3$s',
				'walker'         => new Lavtheme_Topnav_Walker(),
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	echo lavtheme_topnav_fallback(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
}

/* ----------------------------------------------------------------------- *
 * Account popover — login-aware EDD account links.
 * ----------------------------------------------------------------------- */

/** A useful EDD/account URL with graceful fallbacks. */
function lavtheme_edd_page_url( $which ) {
	$map = array(
		'history'  => 'purchase_history_page',
		'checkout' => 'purchase_page',
		'login'    => 'login_page',
	);
	if ( isset( $map[ $which ] ) && function_exists( 'edd_get_option' ) ) {
		$pid = (int) edd_get_option( $map[ $which ], 0 );
		if ( $pid && get_post( $pid ) ) {
			return get_permalink( $pid );
		}
	}
	if ( 'checkout' === $which && function_exists( 'edd_get_checkout_uri' ) ) {
		return edd_get_checkout_uri();
	}
	return '';
}

/**
 * The account popover body (avatar + identity + links). Login-aware and wired
 * to real EDD pages; an assigned 'account' menu is appended when present.
 *
 * @return string
 */
function lavtheme_account_popover() {
	$icons = array(
		'dash'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M20 20H3"/></svg>',
		'orders' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h12M3 12h18M3 17h8"/><circle cx="18" cy="7" r="2"/><circle cx="14" cy="17" r="2"/></svg>',
		'dl'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>',
		'cart'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>',
		'out'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>',
		'in'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/></svg>',
		'user'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
	);

	$history  = lavtheme_edd_page_url( 'history' );
	$checkout = lavtheme_edd_page_url( 'checkout' );

	// Designed My Account dashboard (seeded page); fall back to EDD pages if absent.
	$acct_id     = function_exists( 'lavtheme_account_page_id' ) ? lavtheme_account_page_id() : 0;
	$acct_orders = $acct_id ? lavtheme_account_url( 'orders' ) : $history;
	$acct_dl     = $acct_id ? lavtheme_account_url( 'downloads' ) : $history;
	$acct_prof   = $acct_id ? lavtheme_account_url( 'profile' ) : admin_url( 'profile.php' );

	ob_start();

	if ( is_user_logged_in() ) {
		$u = wp_get_current_user();
		?>
		<div class="acct-head">
			<span class="acct-av"><?php echo get_avatar( $u->ID, 80, '', $u->display_name, array( 'class' => 'acct-avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="acct-id">
				<span class="acct-name"><?php echo esc_html( $u->display_name ); ?></span>
				<span class="acct-mail"><?php echo esc_html( $u->user_email ); ?></span>
				<span class="acct-status"><span class="st-dot"></span><?php esc_html_e( 'Signed in', 'lavtheme' ); ?></span>
			</span>
		</div>
		<div class="acct-sep"></div>
		<?php if ( $history ) : ?>
			<a class="acct-item" href="<?php echo esc_url( $acct_orders ); ?>" role="menuitem"><?php echo $icons['orders']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'My Orders', 'lavtheme' ); ?></a>
			<a class="acct-item" href="<?php echo esc_url( $acct_dl ); ?>" role="menuitem"><?php echo $icons['dl']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'My Downloads', 'lavtheme' ); ?></a>
		<?php endif; ?>
		<?php if ( $checkout ) : ?>
			<a class="acct-item" href="<?php echo esc_url( $checkout ); ?>" role="menuitem"><?php echo $icons['cart']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Checkout', 'lavtheme' ); ?></a>
		<?php endif; ?>
		<a class="acct-item" href="<?php echo esc_url( $acct_prof ); ?>" role="menuitem"><?php echo $icons['user']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'My Profile', 'lavtheme' ); ?></a>
		<?php lavtheme_account_extra_menu(); ?>
		<div class="acct-sep"></div>
		<a class="acct-item acct-logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" role="menuitem"><?php echo $icons['out']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Log out', 'lavtheme' ); ?></a>
		<?php
	} else {
		$login = lavtheme_edd_page_url( 'login' );
		$login = $login ? $login : wp_login_url();
		?>
		<div class="acct-head">
			<span class="acct-av"><?php echo $icons['user']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="acct-id">
				<span class="acct-name"><?php esc_html_e( 'Welcome', 'lavtheme' ); ?></span>
				<span class="acct-mail"><?php esc_html_e( 'Sign in to your account', 'lavtheme' ); ?></span>
			</span>
		</div>
		<div class="acct-sep"></div>
		<a class="acct-item" href="<?php echo esc_url( $login ); ?>" role="menuitem"><?php echo $icons['in']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Log in', 'lavtheme' ); ?></a>
		<?php if ( get_option( 'users_can_register' ) ) : ?>
			<a class="acct-item" href="<?php echo esc_url( wp_registration_url() ); ?>" role="menuitem"><?php echo $icons['user']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Create account', 'lavtheme' ); ?></a>
		<?php endif; ?>
		<?php if ( $history ) : ?>
			<a class="acct-item" href="<?php echo esc_url( $history ); ?>" role="menuitem"><?php echo $icons['orders']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Order history', 'lavtheme' ); ?></a>
		<?php endif; ?>
		<?php
	}

	return ob_get_clean();
}

/** Append the assigned 'account' menu (if any) as extra account items. */
function lavtheme_account_extra_menu() {
	if ( ! has_nav_menu( 'account' ) ) {
		return;
	}
	$items = wp_get_nav_menu_items( get_nav_menu_locations()['account'] );
	if ( empty( $items ) ) {
		return;
	}
	foreach ( $items as $it ) {
		echo '<a class="acct-item" href="' . esc_url( $it->url ) . '" role="menuitem">' . esc_html( $it->title ) . '</a>';
	}
}

/* ----------------------------------------------------------------------- *
 * Shop categories nav (live EDD download_category) for the shop_categories
 * location, with a fallback that lists the real terms.
 * ----------------------------------------------------------------------- */

function lavtheme_shop_categories_nav( $limit = 0 ) {
	if ( has_nav_menu( 'shop_categories' ) ) {
		return wp_nav_menu(
			array(
				'theme_location' => 'shop_categories',
				'container'      => false,
				'menu_class'     => 'lav-cats-menu',
				'echo'           => false,
				'depth'          => 2,
				'fallback_cb'    => false,
			)
		);
	}
	if ( ! taxonomy_exists( 'download_category' ) ) {
		return '';
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'download_category',
			'hide_empty' => true,
			'number'     => $limit > 0 ? $limit : 0,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}
	$out = '<ul class="lav-cats-menu">';
	foreach ( $terms as $t ) {
		$out .= '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . ' <span>' . esc_html( number_format_i18n( (int) $t->count ) ) . '</span></a></li>';
	}
	$out .= '</ul>';
	return $out;
}

/* ----------------------------------------------------------------------- *
 * Run-once: seed starter menus so Appearance → Menus is ready to edit. Purely
 * additive (creates nav-menu terms + items); never touches EDD/orders/content.
 * ----------------------------------------------------------------------- */

function lavtheme_seed_menus() {
	if ( get_option( 'lavtheme_menus_seeded' ) ) {
		return;
	}
	if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return; // only seed from an admin context.
	}
	update_option( 'lavtheme_menus_seeded', 1 );

	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );

	$ensure = function ( $name, $items, $location ) use ( &$locations ) {
		$existing = wp_get_nav_menu_object( $name );
		$menu_id  = $existing ? (int) $existing->term_id : (int) wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) || ! $menu_id ) {
			return;
		}
		if ( ! $existing ) {
			foreach ( $items as $it ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'  => $it['title'],
						'menu-item-url'    => $it['url'],
						'menu-item-status' => 'publish',
					)
				);
			}
		}
		if ( $location && empty( $locations[ $location ] ) ) {
			$locations[ $location ] = $menu_id;
		}
	};

	$shop = function_exists( 'lavtheme_shop_url' ) ? lavtheme_shop_url() : get_post_type_archive_link( 'download' );
	$shop = $shop ? $shop : home_url( '/' );

	$ensure(
		__( 'Main Navigation', 'lavtheme' ),
		array(
			array( 'title' => __( 'Home', 'lavtheme' ), 'url' => home_url( '/' ) ),
			array( 'title' => __( 'Shop', 'lavtheme' ), 'url' => $shop ),
			array( 'title' => __( 'Blog', 'lavtheme' ), 'url' => lavtheme_blog_url() ),
		),
		'' // not auto-assigned to primary (keeps the front-page anchor nav).
	);

	$history  = lavtheme_edd_page_url( 'history' );
	$ensure(
		__( 'Account', 'lavtheme' ),
		array_values(
			array_filter(
				array(
					$history ? array( 'title' => __( 'My Orders', 'lavtheme' ), 'url' => $history ) : null,
					array( 'title' => __( 'My Profile', 'lavtheme' ), 'url' => admin_url( 'profile.php' ) ),
				)
			)
		),
		'' // account popover already renders dynamic links; leave unassigned.
	);

	set_theme_mod( 'nav_menu_locations', $locations );
}
add_action( 'admin_init', 'lavtheme_seed_menus' );
