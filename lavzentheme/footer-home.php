<?php
/**
 * Front-page footer — marketplace mega-footer (live department links, newsletter)
 * + mobile tab bar. Loaded via get_footer( 'home' ) from front-page.php; closes
 * the same .app shell that header.php opened.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_depts   = function_exists( 'lavzen_home_departments' ) ? lavzen_home_departments() : array();
$lav_shop    = function_exists( 'lavzen_shop_url' ) ? lavzen_shop_url() : home_url( '/' );
$lav_account = function_exists( 'edd_get_option' ) ? (int) edd_get_option( 'purchase_history_page', 0 ) : 0;
$lav_account = $lav_account ? get_permalink( $lav_account ) : home_url( '/' );
$lav_year    = (int) gmdate( 'Y' );
?>
		</main><!-- #content -->
		<footer class="footer">
			<div class="wrap footer__grid">
				<div class="footer__brand">
					<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'LAVZEN home', 'lavzentheme' ); ?>">LAVZEN</a>
					<p class="footer__tag"><?php esc_html_e( 'Move fast. Stay zen.', 'lavzentheme' ); ?></p>
					<form class="news" action="<?php echo esc_url( $lav_shop ); ?>" method="get">
						<label class="sr-only" for="news-email"><?php esc_html_e( 'Email address', 'lavzentheme' ); ?></label>
						<input class="news__input" id="news-email" name="email" type="email" inputmode="email" placeholder="<?php esc_attr_e( 'Get new drops weekly', 'lavzentheme' ); ?>" autocomplete="email" />
						<button class="news__btn btn-solid" type="submit"><?php esc_html_e( 'Subscribe', 'lavzentheme' ); ?></button>
					</form>
				</div>
				<nav class="footer__col" aria-label="<?php esc_attr_e( 'Departments', 'lavzentheme' ); ?>">
					<h2 class="footer__h"><?php esc_html_e( 'Departments', 'lavzentheme' ); ?></h2>
					<ul>
						<?php foreach ( $lav_depts as $d ) : ?>
							<li><a href="<?php echo esc_url( $d['url'] ); ?>"><?php echo esc_html( $d['name'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
				<nav class="footer__col" aria-label="<?php esc_attr_e( 'Company', 'lavzentheme' ); ?>">
					<h2 class="footer__h"><?php esc_html_e( 'Company', 'lavzentheme' ); ?></h2>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'About', 'lavzentheme' ); ?></a></li>
						<li><a href="<?php echo esc_url( $lav_shop ); ?>"><?php esc_html_e( 'Browse', 'lavzentheme' ); ?></a></li>
						<li><a href="<?php echo esc_url( function_exists( 'lavzen_blog_url' ) ? lavzen_blog_url() : home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Blog', 'lavzentheme' ); ?></a></li>
						<li><a href="<?php echo esc_url( $lav_account ); ?>"><?php esc_html_e( 'Account', 'lavzentheme' ); ?></a></li>
					</ul>
				</nav>
				<nav class="footer__col" aria-label="<?php esc_attr_e( 'Resources', 'lavzentheme' ); ?>">
					<h2 class="footer__h"><?php esc_html_e( 'Resources', 'lavzentheme' ); ?></h2>
					<ul>
						<li><a href="<?php echo esc_url( $lav_shop ); ?>"><?php esc_html_e( 'Marketplace', 'lavzentheme' ); ?></a></li>
						<li><a href="<?php echo esc_url( $lav_account ); ?>"><?php esc_html_e( 'Downloads', 'lavzentheme' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Status', 'lavzentheme' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Help', 'lavzentheme' ); ?></a></li>
					</ul>
				</nav>
				<nav class="footer__col" aria-label="<?php esc_attr_e( 'Legal', 'lavzentheme' ); ?>">
					<h2 class="footer__h"><?php esc_html_e( 'Legal', 'lavzentheme' ); ?></h2>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Terms', 'lavzentheme' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Privacy', 'lavzentheme' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Refunds', 'lavzentheme' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Licenses', 'lavzentheme' ); ?></a></li>
					</ul>
				</nav>
			</div>
			<div class="wrap footer__bottom">
				<p>© <?php echo esc_html( $lav_year ); ?> LAVZEN</p>
				<p><?php esc_html_e( 'Move fast. Stay zen.', 'lavzentheme' ); ?></p>
			</div>
		</footer>
		<nav class="tabbar" aria-label="<?php esc_attr_e( 'Primary', 'lavzentheme' ); ?>">
			<ul class="tabbar__list">
				<li><a class="tab" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-current="page"><span class="tab__i" aria-hidden="true">⌂</span><span class="tab__l"><?php esc_html_e( 'Home', 'lavzentheme' ); ?></span></a></li>
				<li><a class="tab" href="<?php echo esc_url( $lav_shop ); ?>"><span class="tab__i" aria-hidden="true">▦</span><span class="tab__l"><?php esc_html_e( 'Browse', 'lavzentheme' ); ?></span></a></li>
				<li><a class="tab tab--mid" href="#content"><span class="tab__i" aria-hidden="true">⌕</span><span class="tab__l"><?php esc_html_e( 'Search', 'lavzentheme' ); ?></span></a></li>
				<li><a class="tab" href="<?php echo esc_url( $lav_account ); ?>"><span class="tab__i" aria-hidden="true">♡</span><span class="tab__l"><?php esc_html_e( 'Saved', 'lavzentheme' ); ?></span></a></li>
				<li><a class="tab" href="<?php echo esc_url( $lav_account ); ?>"><span class="tab__i" aria-hidden="true">◐</span><span class="tab__l"><?php esc_html_e( 'Account', 'lavzentheme' ); ?></span></a></li>
			</ul>
		</nav>
	</div><!-- .main -->
</div><!-- .app -->
<?php wp_footer(); ?>
</body>
</html>
