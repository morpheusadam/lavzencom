<?php
/**
 * Theme plugins autoloader.
 *
 * Includes every module entry file at plugins/<slug>/<slug>.php so a new feature
 * is activated just by dropping its folder here — no functions.php edit needed.
 * Also provides a shared admin "placeholder" renderer the stub modules use until
 * their real logic is built.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Discover and load every theme-plugin module.
 */
function lavtheme_load_theme_plugins() {
	$base = get_theme_file_path( 'plugins' );
	$dirs = glob( $base . '/*', GLOB_ONLYDIR );
	if ( ! $dirs ) {
		return;
	}
	foreach ( $dirs as $dir ) {
		$slug  = basename( $dir );
		$entry = $dir . '/' . $slug . '.php';
		if ( is_readable( $entry ) ) {
			require_once $entry;
		}
	}
}
lavtheme_load_theme_plugins();

/**
 * The Code Studio top-level menu slug a module should attach under.
 *
 * @return string
 */
function lavtheme_plugins_parent_slug() {
	return defined( 'LAVTHEME_CS_SLUG' ) ? LAVTHEME_CS_SLUG : 'lavtheme-code-studio';
}

/**
 * The capability gating theme-plugin admin screens.
 *
 * @return string
 */
function lavtheme_plugins_cap() {
	return function_exists( 'lavtheme_cs_cap' ) ? lavtheme_cs_cap() : 'manage_options';
}

/**
 * Register a module as a submenu under the Code Studio main menu.
 *
 * @param array $args slug, title, (optional) menu_title, callback, position.
 */
function lavtheme_plugins_register_menu( $args ) {
	$defaults = array(
		'slug'       => '',
		'title'      => '',
		'menu_title' => '',
		'callback'   => '__return_null',
		'position'   => null,
	);
	$a = wp_parse_args( $args, $defaults );
	if ( '' === $a['slug'] || '' === $a['title'] ) {
		return;
	}
	add_action(
		'admin_menu',
		function () use ( $a ) {
			add_submenu_page(
				lavtheme_plugins_parent_slug(),
				$a['title'],
				'' !== $a['menu_title'] ? $a['menu_title'] : $a['title'],
				lavtheme_plugins_cap(),
				$a['slug'],
				$a['callback'],
				$a['position']
			);
		},
		20
	);
}

/**
 * Render a simple "Hello World" placeholder admin screen for a stub module.
 *
 * @param string $title Feature title.
 * @param string $desc  Short description of what it will do.
 */
function lavtheme_plugins_placeholder( $title, $desc = '' ) {
	if ( ! current_user_can( lavtheme_plugins_cap() ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( $title ); ?></h1>
		<div style="margin-top:20px;max-width:680px;padding:32px;border:1px solid #e2e4e7;border-radius:12px;background:#fff;">
			<p style="font-size:22px;font-weight:600;margin:0 0 8px;">👋 <?php esc_html_e( 'Hello World', 'lavtheme' ); ?></p>
			<p style="margin:0;color:#646970;font-size:14px;line-height:1.6;">
				<?php
				if ( '' !== $desc ) {
					echo esc_html( $desc );
				} else {
					/* translators: %s: feature name. */
					printf( esc_html__( 'The “%s” module is registered. Its functionality will be implemented next.', 'lavtheme' ), esc_html( $title ) );
				}
				?>
			</p>
			<p style="margin:16px 0 0;">
				<span style="display:inline-block;padding:4px 12px;border-radius:999px;background:#f0f0f1;color:#646970;font-size:12px;font-weight:600;"><?php esc_html_e( 'Coming soon', 'lavtheme' ); ?></span>
			</p>
		</div>
	</div>
	<?php
}
