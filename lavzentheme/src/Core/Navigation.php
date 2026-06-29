<?php
/**
 * Navigation — topnav, account popover, shop categories, blog URL, seeding.
 *
 * Menus are legitimately theme (presentation) territory. Each renderer uses an
 * assigned WP menu when present, else a live dynamic fallback so the shell is
 * always useful. Ported from the legacy inc/menus.php, re-namespaced; EDD/account
 * helpers are called through function_exists guards so it degrades gracefully
 * until those modules load.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Core;

use Lavzen\Support\Singleton;

defined( 'ABSPATH' ) || exit;

final class Navigation {

	use Singleton;

	protected function init(): void {
		add_action( 'admin_init', array( $this, 'seed_menus' ) );
	}

	/* ----------------------------- Desktop topnav ----------------------------- */

	/**
	 * Render the desktop topnav: assigned 'primary' menu, else the safe fallback.
	 */
	public function topnav(): void {
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'walker'         => new Topnav_Walker(),
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			return;
		}
		echo $this->topnav_fallback(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
	}

	/**
	 * Topnav fallback. On the front page it reproduces the single-page anchor nav;
	 * elsewhere it links to real destinations so inner pages get a usable nav.
	 */
	public function topnav_fallback(): string {
		if ( is_front_page() ) {
			$items = array(
				array( __( 'Home', 'lavzentheme' ), '#home', true ),
				array( __( 'Services', 'lavzentheme' ), '#services', false ),
				array( __( 'Products', 'lavzentheme' ), '#products', false ),
				array( __( 'Blog', 'lavzentheme' ), '#blog', false ),
				array( __( 'Contact', 'lavzentheme' ), '#contact', false ),
			);
		} else {
			$shop    = function_exists( 'lavzen_shop_url' ) ? lavzen_shop_url() : get_post_type_archive_link( 'download' );
			$is_shop = function_exists( 'lavzen_is_shop' ) && lavzen_is_shop();
			$items   = array(
				array( __( 'Home', 'lavzentheme' ), home_url( '/' ), false ),
				array( __( 'Shop', 'lavzentheme' ), $shop ? $shop : home_url( '/' ), $is_shop ),
				array( __( 'Services', 'lavzentheme' ), home_url( '/#services' ), false ),
				array( __( 'Blog', 'lavzentheme' ), $this->blog_url(), is_home() || is_singular( 'post' ) ),
				array( __( 'Contact', 'lavzentheme' ), home_url( '/#contact' ), false ),
			);
		}
		$out = '';
		foreach ( $items as $it ) {
			$out .= '<a href="' . esc_url( $it[1] ) . '"' . ( $it[2] ? ' class="active"' : '' ) . '>' . esc_html( $it[0] ) . '</a>';
		}
		return $out;
	}

	/**
	 * The blog URL — the configured posts/blog page, else home.
	 */
	public function blog_url(): string {
		$pid = function_exists( 'lavzen_blog_page_id' ) ? lavzen_blog_page_id() : (int) get_option( 'page_for_posts' );
		return $pid ? get_permalink( $pid ) : home_url( '/' );
	}

	/* --------------------------- Account popover ------------------------------ */

	/**
	 * A useful EDD/account URL with graceful fallbacks.
	 */
	public function edd_page_url( string $which ): string {
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
	 * The account popover body (avatar + identity + links). Login-aware.
	 */
	public function account_popover(): string {
		$icons = array(
			'orders' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h12M3 12h18M3 17h8"/><circle cx="18" cy="7" r="2"/><circle cx="14" cy="17" r="2"/></svg>',
			'dl'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>',
			'cart'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>',
			'out'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>',
			'in'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/></svg>',
			'user'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
		);

		$history  = $this->edd_page_url( 'history' );
		$checkout = $this->edd_page_url( 'checkout' );

		ob_start();

		if ( is_user_logged_in() ) {
			$u = wp_get_current_user();
			?>
			<div class="acct-head">
				<span class="acct-av"><?php echo get_avatar( $u->ID, 80, '', $u->display_name, array( 'class' => 'acct-avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="acct-id">
					<span class="acct-name"><?php echo esc_html( $u->display_name ); ?></span>
					<span class="acct-mail"><?php echo esc_html( $u->user_email ); ?></span>
					<span class="acct-status"><span class="st-dot"></span><?php esc_html_e( 'Signed in', 'lavzentheme' ); ?></span>
				</span>
			</div>
			<div class="acct-sep"></div>
			<?php if ( $history ) : ?>
				<a class="acct-item" href="<?php echo esc_url( $history ); ?>" role="menuitem"><?php echo $icons['orders']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'My Orders', 'lavzentheme' ); ?></a>
				<a class="acct-item" href="<?php echo esc_url( $history ); ?>" role="menuitem"><?php echo $icons['dl']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'My Downloads', 'lavzentheme' ); ?></a>
			<?php endif; ?>
			<?php if ( $checkout ) : ?>
				<a class="acct-item" href="<?php echo esc_url( $checkout ); ?>" role="menuitem"><?php echo $icons['cart']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Checkout', 'lavzentheme' ); ?></a>
			<?php endif; ?>
			<a class="acct-item" href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" role="menuitem"><?php echo $icons['user']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'My Profile', 'lavzentheme' ); ?></a>
			<?php $this->account_extra_menu(); ?>
			<div class="acct-sep"></div>
			<a class="acct-item acct-logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" role="menuitem"><?php echo $icons['out']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Log out', 'lavzentheme' ); ?></a>
			<?php
		} else {
			$login = $this->edd_page_url( 'login' );
			$login = $login ? $login : wp_login_url();
			?>
			<div class="acct-head">
				<span class="acct-av"><?php echo $icons['user']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="acct-id">
					<span class="acct-name"><?php esc_html_e( 'Welcome', 'lavzentheme' ); ?></span>
					<span class="acct-mail"><?php esc_html_e( 'Sign in to your account', 'lavzentheme' ); ?></span>
				</span>
			</div>
			<div class="acct-sep"></div>
			<a class="acct-item" href="<?php echo esc_url( $login ); ?>" role="menuitem"><?php echo $icons['in']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Log in', 'lavzentheme' ); ?></a>
			<?php if ( get_option( 'users_can_register' ) ) : ?>
				<a class="acct-item" href="<?php echo esc_url( wp_registration_url() ); ?>" role="menuitem"><?php echo $icons['user']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Create account', 'lavzentheme' ); ?></a>
			<?php endif; ?>
			<?php if ( $history ) : ?>
				<a class="acct-item" href="<?php echo esc_url( $history ); ?>" role="menuitem"><?php echo $icons['orders']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Order history', 'lavzentheme' ); ?></a>
			<?php endif; ?>
			<?php
		}

		return (string) ob_get_clean();
	}

	/**
	 * Append the assigned 'account' menu (if any) as extra account items.
	 */
	public function account_extra_menu(): void {
		if ( ! has_nav_menu( 'account' ) ) {
			return;
		}
		$locations = get_nav_menu_locations();
		$items     = empty( $locations['account'] ) ? array() : wp_get_nav_menu_items( $locations['account'] );
		if ( empty( $items ) ) {
			return;
		}
		foreach ( $items as $it ) {
			echo '<a class="acct-item" href="' . esc_url( $it->url ) . '" role="menuitem">' . esc_html( $it->title ) . '</a>';
		}
	}

	/* ------------------------- Shop categories nav ---------------------------- */

	public function shop_categories_nav( int $limit = 0 ): string {
		if ( has_nav_menu( 'shop_categories' ) ) {
			return (string) wp_nav_menu(
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

	/* ----------------------------- Menu seeding ------------------------------- */

	/**
	 * Run-once: seed starter menus so Appearance → Menus is ready to edit.
	 */
	public function seed_menus(): void {
		if ( get_option( 'lavzen_menus_seeded' ) ) {
			return;
		}
		if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		update_option( 'lavzen_menus_seeded', 1 );

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

		$shop = function_exists( 'lavzen_shop_url' ) ? lavzen_shop_url() : get_post_type_archive_link( 'download' );
		$shop = $shop ? $shop : home_url( '/' );

		$ensure(
			__( 'Main Navigation', 'lavzentheme' ),
			array(
				array( 'title' => __( 'Home', 'lavzentheme' ), 'url' => home_url( '/' ) ),
				array( 'title' => __( 'Shop', 'lavzentheme' ), 'url' => $shop ),
				array( 'title' => __( 'Blog', 'lavzentheme' ), 'url' => $this->blog_url() ),
			),
			''
		);

		set_theme_mod( 'nav_menu_locations', $locations );
	}
}
