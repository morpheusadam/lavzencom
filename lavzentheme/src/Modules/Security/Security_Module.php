<?php
/**
 * Security module — technology-fingerprint hardening (behavior preserved).
 *
 * Relocated from the legacy plugins/security/security.php into the module system,
 * behavior unchanged (per the refactor brief — deeper hardening is handled later
 * by the owner). Feature 1: hide the WordPress/version tells (generator meta,
 * RSD/WLW/REST/oEmbed/emoji discovery, the ?ver= query, X-Pingback / X-Powered-By
 * / X-Redirect-By headers, jQuery Migrate, wp-embed, feed link advertisements).
 *
 * Settings live in the `lavzen_security` option (array of on/off flags); the
 * fingerprint feature defaults ON, matching the legacy default. A settings screen
 * is registered as a submenu under Code Studio.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Security;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Security_Module extends Abstract_Module {

	private const OPTION = 'lavzen_security';

	public function id(): string {
		return 'security';
	}

	public function boot(): void {
		if ( $this->enabled( 'fingerprint' ) ) {
			$this->fingerprint();
		}
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'menu' ), 21 );
			add_action( 'admin_init', array( $this, 'save_settings' ) );
		}
	}

	/* ------------------------------ settings ------------------------------ */

	private function defaults(): array {
		return array( 'fingerprint' => 1 );
	}

	private function opts(): array {
		$o = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $o ) ? $o : array(), $this->defaults() );
	}

	private function enabled( string $key ): bool {
		$o = $this->opts();
		return ! empty( $o[ $key ] );
	}

	/* ------------------------------ admin screen ------------------------------ */

	public function menu(): void {
		$parent = 'lavzen-code-studio';
		add_submenu_page(
			$parent,
			__( 'Security', 'lavzentheme' ),
			__( 'Security', 'lavzentheme' ),
			'manage_options',
			'lavzen-security',
			array( $this, 'render' )
		);
	}

	public function save_settings(): void {
		if ( empty( $_POST['lavzen_security_save'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'lavzen_security' );
		$in  = isset( $_POST['sec'] ) && is_array( $_POST['sec'] ) ? wp_unslash( $_POST['sec'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$out = array( 'fingerprint' => empty( $in['fingerprint'] ) ? 0 : 1 );
		update_option( self::OPTION, $out );
		add_settings_error( 'lavzen_security', 'saved', __( 'Security settings saved.', 'lavzentheme' ), 'updated' );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o = $this->opts();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Security', 'lavzentheme' ); ?></h1>
			<?php settings_errors( 'lavzen_security' ); ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'lavzen_security' ); ?>
				<input type="hidden" name="lavzen_security_save" value="1">
				<p>
					<label>
						<input type="checkbox" name="sec[fingerprint]" value="1" <?php checked( ! empty( $o['fingerprint'] ) ); ?>>
						<strong><?php esc_html_e( 'Hide technology fingerprint', 'lavzentheme' ); ?></strong>
					</label>
				</p>
				<p class="description"><?php esc_html_e( 'Strips the WordPress/version signals (generator meta + version, REST/RSD/WLW/oEmbed/shortlink discovery, emoji detection, ?ver= query, X-Pingback / X-Powered-By / X-Redirect-By headers).', 'lavzentheme' ); ?></p>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'lavzentheme' ); ?></button></p>
			</form>
		</div>
		<?php
	}

	/* -------------------- Feature 1: fingerprint hardening -------------------- */

	public function fingerprint(): void {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
		remove_action( 'wp_head', 'edd_version_in_header' );

		// Catch-all: strip any <meta name="generator"> added via closures.
		add_action( 'wp_head', static function () { ob_start(); }, 0 );
		add_action(
			'wp_head',
			static function () {
				$head = (string) ob_get_clean();
				echo preg_replace( '/[ \t]*<meta[^>]+name=(["\'])generator\1[^>]*>\s*/i', '', $head ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			},
			PHP_INT_MAX
		);

		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		add_filter( 'wp_headers', static function ( $headers ) {
			unset( $headers['X-Pingback'] );
			return $headers;
		} );
		add_filter( 'xmlrpc_methods', static function ( $methods ) {
			unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
			return $methods;
		} );
		add_action( 'send_headers', static function () {
			if ( ! headers_sent() ) {
				header_remove( 'X-Powered-By' );
			}
		}, 0 );
		add_filter( 'x_redirect_by', '__return_false' );

		// Mask ?ver= so it doesn't leak component versions (stable per-version hash).
		$mask_ver = static function ( $src ) {
			if ( ! $src || false === strpos( $src, 'ver=' ) ) {
				return $src;
			}
			return preg_replace_callback(
				'/([?&]ver=)([^&#]+)/',
				static function ( $m ) {
					$salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'lavzen';
					return $m[1] . substr( md5( $m[2] . $salt ), 0, 8 );
				},
				$src
			);
		};
		add_filter( 'style_loader_src', $mask_ver, 9999 );
		add_filter( 'script_loader_src', $mask_ver, 9999 );

		add_action( 'wp_default_scripts', static function ( $scripts ) {
			if ( is_admin() ) {
				return;
			}
			if ( isset( $scripts->registered['jquery'] ) && ! empty( $scripts->registered['jquery']->deps ) ) {
				$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
			}
		} );

		add_action( 'wp_enqueue_scripts', static function () { wp_dequeue_script( 'wp-embed' ); }, 100 );
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}
}
