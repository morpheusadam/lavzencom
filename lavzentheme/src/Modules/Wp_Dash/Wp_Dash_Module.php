<?php
/**
 * WP Dash module — a glassmorphism skin + welcome hero for the WordPress admin
 * dashboard. Ported from plugins/wp-dash (the skin is now a static asset, not a
 * Code Studio context). Admin-only; scoped to the Dashboard, skips the block editor.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Wp_Dash;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Wp_Dash_Module extends Abstract_Module {

	public function id(): string {
		return 'wp_dash';
	}

	public function boot(): void {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_notices', array( $this, 'hero' ), 5 );
	}

	/** True on the native Dashboard home screen. */
	private function is_dashboard(): bool {
		if ( function_exists( 'get_current_screen' ) ) {
			$s = get_current_screen();
			if ( $s && isset( $s->base ) ) {
				return 'dashboard' === $s->base;
			}
		}
		return isset( $GLOBALS['pagenow'] ) && 'index.php' === $GLOBALS['pagenow'];
	}

	/** Enqueue the dashboard skin (skips the block editor). */
	public function assets(): void {
		if ( function_exists( 'get_current_screen' ) ) {
			$s = get_current_screen();
			if ( $s && method_exists( $s, 'is_block_editor' ) && $s->is_block_editor() ) {
				return;
			}
		}
		$css = 'assets/dist/admin/wp-dash.css';
		$js  = 'assets/dist/admin/wp-dash.js';
		wp_enqueue_style( 'lavzen-wpdash', LAVZEN_URI . $css, array(), (string) @filemtime( LAVZEN_DIR . $css ) );
		wp_enqueue_script( 'lavzen-wpdash', LAVZEN_URI . $js, array(), (string) @filemtime( LAVZEN_DIR . $js ), true );
	}

	/** Render the glass welcome hero at the top of the Dashboard. */
	public function hero(): void {
		if ( ! $this->is_dashboard() ) {
			return;
		}
		$user  = wp_get_current_user();
		$name  = $user && $user->display_name ? $user->display_name : __( 'there', 'lavzentheme' );
		$stats = array(
			array( __( 'Posts', 'lavzentheme' ), (int) wp_count_posts( 'post' )->publish ),
			array( __( 'Comments', 'lavzentheme' ), (int) wp_count_comments()->approved ),
			array( __( 'Users', 'lavzentheme' ), (int) count_users()['total_users'] ),
			array( __( 'Products', 'lavzentheme' ), post_type_exists( 'download' ) ? (int) wp_count_posts( 'download' )->publish : 0 ),
		);
		?>
		<div class="lavwp-hero">
			<div class="lavwp-orb a" aria-hidden="true"></div>
			<div class="lavwp-orb b" aria-hidden="true"></div>
			<div class="lavwp-hero-in">
				<span class="lavwp-eyebrow"><?php esc_html_e( 'Lavzen · Control center', 'lavzentheme' ); ?></span>
				<h2 class="lavwp-title">
					<?php
					/* translators: %s: user display name. */
					printf( esc_html__( 'Welcome back, %s', 'lavzentheme' ), '<b>' . esc_html( $name ) . '</b>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- name escaped.
					?>
					<span class="lavwp-wave" aria-hidden="true">👋</span>
				</h2>
				<p class="lavwp-sub"><?php esc_html_e( 'Your site at a glance — content, engagement and store, wrapped in glass.', 'lavzentheme' ); ?></p>
				<div class="lavwp-quick">
					<?php foreach ( $stats as $s ) : ?>
						<div class="lavwp-stat">
							<span class="lavwp-stat-n" data-count="<?php echo esc_attr( $s[1] ); ?>"><?php echo esc_html( number_format_i18n( $s[1] ) ); ?></span>
							<span class="lavwp-stat-l"><?php echo esc_html( $s[0] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="lavwp-actions">
					<a class="lavwp-btn primary" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"><?php esc_html_e( 'Write a post', 'lavzentheme' ); ?></a>
					<a class="lavwp-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=lavzen-code-studio' ) ); ?>"><?php esc_html_e( 'Code Studio', 'lavzentheme' ); ?></a>
					<a class="lavwp-btn ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site', 'lavzentheme' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}
}
