<?php
/**
 * Home "Turn your AI work into income" seller band.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_sell = function_exists( 'edd_get_option' ) ? (int) edd_get_option( 'purchase_history_page', 0 ) : 0;
$lav_sell = $lav_sell ? get_permalink( $lav_sell ) : home_url( '/' );
?>
<section class="sec" aria-labelledby="sell-h">
  <div class="wrap">
    <div class="sell">
      <div class="sell__copy">
        <p class="sell__kicker"><?php esc_html_e( 'For creators', 'lavzentheme' ); ?></p>
        <h2 class="sell__title" id="sell-h"><?php esc_html_e( 'Turn your AI work into income', 'lavzentheme' ); ?></h2>
        <p class="sell__sub"><?php esc_html_e( 'List a model, agent, prompt pack or dataset. We handle delivery, licensing and payouts — you keep building.', 'lavzentheme' ); ?></p>
        <ol class="sell__steps">
          <li><span aria-hidden="true">1</span> <?php esc_html_e( 'Upload & price your work', 'lavzentheme' ); ?></li>
          <li><span aria-hidden="true">2</span> <?php esc_html_e( 'Get verified', 'lavzentheme' ); ?></li>
          <li><span aria-hidden="true">3</span> <?php esc_html_e( 'Earn on every sale', 'lavzentheme' ); ?></li>
        </ol>
      </div>
      <a class="btn-solid sell__cta" href="<?php echo esc_url( $lav_sell ); ?>"><?php esc_html_e( 'Start selling', 'lavzentheme' ); ?></a>
    </div>
  </div>
</section>
