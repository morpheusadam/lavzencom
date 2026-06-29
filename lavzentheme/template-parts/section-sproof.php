<?php
/**
 * Home social-proof band — real store stats + testimonials.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_stats = function_exists( 'lavzen_home_stats' ) ? lavzen_home_stats() : array( 'listings' => '', 'sellers' => '', 'downloads' => '', 'rating' => '' );
?>
<section class="sec sproof" aria-labelledby="sproof-h">
  <div class="wrap">
    <h2 class="sr-only" id="sproof-h"><?php esc_html_e( 'Loved by builders', 'lavzentheme' ); ?></h2>
    <ul class="stats">
      <li class="stat"><span class="stat__n"><?php echo esc_html( $lav_stats['listings'] ); ?></span><span class="stat__l"><?php esc_html_e( 'verified listings', 'lavzentheme' ); ?></span></li>
      <li class="stat"><span class="stat__n"><?php echo esc_html( $lav_stats['sellers'] ); ?></span><span class="stat__l"><?php esc_html_e( 'independent sellers', 'lavzentheme' ); ?></span></li>
      <li class="stat"><span class="stat__n"><?php echo esc_html( $lav_stats['downloads'] ); ?></span><span class="stat__l"><?php esc_html_e( 'downloads', 'lavzentheme' ); ?></span></li>
      <li class="stat"><span class="stat__n"><?php echo esc_html( $lav_stats['rating'] ); ?><span class="stat__star" aria-hidden="true">★</span></span><span class="stat__l"><?php esc_html_e( 'avg. rating', 'lavzentheme' ); ?></span></li>
    </ul>
    <ul class="quotes">
      <li class="quote"><p><?php echo esc_html__( '“Found a Claude MCP server, bought it, and shipped the same afternoon.”', 'lavzentheme' ); ?></p><p class="quote__by"><?php esc_html_e( '— Indie hacker', 'lavzentheme' ); ?></p></li>
      <li class="quote"><p><?php echo esc_html__( '“The bundles are the killer feature. One click and my whole agent stack is set up.”', 'lavzentheme' ); ?></p><p class="quote__by"><?php esc_html_e( '— ML engineer', 'lavzentheme' ); ?></p></li>
      <li class="quote"><p><?php echo esc_html__( '“Selling my prompt packs here finally feels like a real storefront, not a gist.”', 'lavzentheme' ); ?></p><p class="quote__by"><?php esc_html_e( '— Prompt author', 'lavzentheme' ); ?></p></li>
    </ul>
  </div>
</section>
