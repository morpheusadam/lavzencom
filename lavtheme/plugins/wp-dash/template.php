<?php
/**
 * WP Dash — editable HTML template for the native WordPress Dashboard hero.
 *
 * This is the default "HTML / PHP" body of the Code Studio "WP Dash" context.
 * It renders a glassmorphism welcome panel at the top of the Dashboard. Edit it
 * (and the CSS/JS) in Code Studio → WP Dash. assets/dash-skin.css + dash-skin.js
 * are the context's CSS/JS editors.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lavwp_user  = wp_get_current_user();
$lavwp_name  = $lavwp_user && $lavwp_user->display_name ? $lavwp_user->display_name : __( 'there', 'lavtheme' );
$lavwp_posts = (int) wp_count_posts( 'post' )->publish;
$lavwp_users = (int) count_users()['total_users'];
$lavwp_prods = post_type_exists( 'download' ) ? (int) wp_count_posts( 'download' )->publish : 0;
$lavwp_comm  = (int) wp_count_comments()->approved;

$lavwp_stats = array(
	array( __( 'Posts', 'lavtheme' ), $lavwp_posts ),
	array( __( 'Comments', 'lavtheme' ), $lavwp_comm ),
	array( __( 'Users', 'lavtheme' ), $lavwp_users ),
	array( __( 'Products', 'lavtheme' ), $lavwp_prods ),
);
?>
<div class="lavwp-hero">
	<div class="lavwp-orb a" aria-hidden="true"></div>
	<div class="lavwp-orb b" aria-hidden="true"></div>
	<div class="lavwp-hero-in">
		<span class="lavwp-eyebrow"><?php esc_html_e( 'Lavzen · Control center', 'lavtheme' ); ?></span>
		<h2 class="lavwp-title">
			<?php
			/* translators: %s: user display name. */
			printf( esc_html__( 'Welcome back, %s', 'lavtheme' ), '<b>' . esc_html( $lavwp_name ) . '</b>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- name escaped.
			?>
			<span class="lavwp-wave" aria-hidden="true">👋</span>
		</h2>
		<p class="lavwp-sub"><?php esc_html_e( 'Your site at a glance — content, engagement and store, wrapped in glass.', 'lavtheme' ); ?></p>

		<div class="lavwp-quick">
			<?php foreach ( $lavwp_stats as $s ) : ?>
				<div class="lavwp-stat">
					<span class="lavwp-stat-n" data-count="<?php echo esc_attr( $s[1] ); ?>"><?php echo esc_html( number_format_i18n( $s[1] ) ); ?></span>
					<span class="lavwp-stat-l"><?php echo esc_html( $s[0] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="lavwp-actions">
			<a class="lavwp-btn primary" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"><?php esc_html_e( 'Write a post', 'lavtheme' ); ?></a>
			<a class="lavwp-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=lavtheme-wp-dash' ) ); ?>"><?php esc_html_e( 'Open WP Dash', 'lavtheme' ); ?></a>
			<a class="lavwp-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=lavtheme-code-studio' ) ); ?>"><?php esc_html_e( 'Code Studio', 'lavtheme' ); ?></a>
			<a class="lavwp-btn ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site', 'lavtheme' ); ?></a>
		</div>
	</div>
</div>
