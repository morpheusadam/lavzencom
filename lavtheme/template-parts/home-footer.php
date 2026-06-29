<?php
/**
 * Home footer — department links from the live taxonomy, secondary nav columns,
 * newsletter sign-up, and the mobile tab bar.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_depts   = lavtheme_home_departments();
$lav_shop    = lavtheme_shop_url();
$lav_account = function_exists( 'edd_get_option' ) ? (int) edd_get_option( 'purchase_history_page', 0 ) : 0;
$lav_account = $lav_account ? get_permalink( $lav_account ) : home_url( '/' );
$lav_year    = (int) gmdate( 'Y' );
?>
<footer class="footer">
  <div class="wrap footer__grid">
    <div class="footer__brand">
      <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'LAVZEN home', 'lavtheme' ); ?>">LAVZEN</a>
      <p class="footer__tag"><?php esc_html_e( 'Move fast. Stay zen.', 'lavtheme' ); ?></p>
      <form class="news" action="<?php echo esc_url( $lav_shop ); ?>" method="get">
        <label class="sr-only" for="news-email"><?php esc_html_e( 'Email address', 'lavtheme' ); ?></label>
        <input class="news__input" id="news-email" name="email" type="email" inputmode="email" placeholder="<?php esc_attr_e( 'Get new drops weekly', 'lavtheme' ); ?>" autocomplete="email" />
        <button class="news__btn btn-solid" type="submit"><?php esc_html_e( 'Subscribe', 'lavtheme' ); ?></button>
      </form>
    </div>
    <nav class="footer__col" aria-label="<?php esc_attr_e( 'Departments', 'lavtheme' ); ?>">
      <h2 class="footer__h"><?php esc_html_e( 'Departments', 'lavtheme' ); ?></h2>
      <ul>
        <?php foreach ( $lav_depts as $d ) : ?>
          <li><a href="<?php echo esc_url( $d['url'] ); ?>"><?php echo esc_html( $d['name'] ); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <nav class="footer__col" aria-label="<?php esc_attr_e( 'Company', 'lavtheme' ); ?>">
      <h2 class="footer__h"><?php esc_html_e( 'Company', 'lavtheme' ); ?></h2>
      <ul>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'About', 'lavtheme' ); ?></a></li>
        <li><a href="<?php echo esc_url( $lav_shop ); ?>"><?php esc_html_e( 'Browse', 'lavtheme' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Blog', 'lavtheme' ); ?></a></li>
        <li><a href="<?php echo esc_url( $lav_account ); ?>"><?php esc_html_e( 'Account', 'lavtheme' ); ?></a></li>
      </ul>
    </nav>
    <nav class="footer__col" aria-label="<?php esc_attr_e( 'Resources', 'lavtheme' ); ?>">
      <h2 class="footer__h"><?php esc_html_e( 'Resources', 'lavtheme' ); ?></h2>
      <ul>
        <li><a href="<?php echo esc_url( $lav_shop ); ?>"><?php esc_html_e( 'Marketplace', 'lavtheme' ); ?></a></li>
        <li><a href="<?php echo esc_url( $lav_account ); ?>"><?php esc_html_e( 'Downloads', 'lavtheme' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Status', 'lavtheme' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Help', 'lavtheme' ); ?></a></li>
      </ul>
    </nav>
    <nav class="footer__col" aria-label="<?php esc_attr_e( 'Legal', 'lavtheme' ); ?>">
      <h2 class="footer__h"><?php esc_html_e( 'Legal', 'lavtheme' ); ?></h2>
      <ul>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Terms', 'lavtheme' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Privacy', 'lavtheme' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Refunds', 'lavtheme' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Licenses', 'lavtheme' ); ?></a></li>
      </ul>
    </nav>
  </div>
  <div class="wrap footer__bottom">
    <p>© <?php echo esc_html( $lav_year ); ?> LAVZEN</p>
    <p><?php esc_html_e( 'Move fast. Stay zen.', 'lavtheme' ); ?></p>
  </div>
</footer>
<nav class="tabbar" aria-label="<?php esc_attr_e( 'Primary', 'lavtheme' ); ?>">
  <ul class="tabbar__list">
    <li><a class="tab" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-current="page"><span class="tab__i" aria-hidden="true">⌂</span><span class="tab__l"><?php esc_html_e( 'Home', 'lavtheme' ); ?></span></a></li>
    <li><a class="tab" href="<?php echo esc_url( $lav_shop ); ?>"><span class="tab__i" aria-hidden="true">▦</span><span class="tab__l"><?php esc_html_e( 'Browse', 'lavtheme' ); ?></span></a></li>
    <li><a class="tab tab--mid" href="#content"><span class="tab__i" aria-hidden="true">⌕</span><span class="tab__l"><?php esc_html_e( 'Search', 'lavtheme' ); ?></span></a></li>
    <li><a class="tab" href="<?php echo esc_url( $lav_account ); ?>"><span class="tab__i" aria-hidden="true">♡</span><span class="tab__l"><?php esc_html_e( 'Saved', 'lavtheme' ); ?></span></a></li>
    <li><a class="tab" href="<?php echo esc_url( $lav_account ); ?>"><span class="tab__i" aria-hidden="true">◐</span><span class="tab__l"><?php esc_html_e( 'Account', 'lavtheme' ); ?></span></a></li>
  </ul>
</nav>
