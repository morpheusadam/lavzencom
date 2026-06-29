<?php
/**
 * Home social-proof band — real store stats where available, plus testimonials.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_stats = lavtheme_home_stats();
?>
<section class="sec sproof" aria-labelledby="sproof-h">
  <div class="wrap">
    <h2 class="sr-only" id="sproof-h"><?php esc_html_e( 'Loved by builders', 'lavtheme' ); ?></h2>
    <ul class="stats">
      <li class="stat"><span class="stat__n"><?php echo esc_html( $lav_stats['listings'] ); ?></span><span class="stat__l"><?php esc_html_e( 'verified listings', 'lavtheme' ); ?></span></li>
      <li class="stat"><span class="stat__n"><?php echo esc_html( $lav_stats['sellers'] ); ?></span><span class="stat__l"><?php esc_html_e( 'independent sellers', 'lavtheme' ); ?></span></li>
      <li class="stat"><span class="stat__n"><?php echo esc_html( $lav_stats['downloads'] ); ?></span><span class="stat__l"><?php esc_html_e( 'downloads', 'lavtheme' ); ?></span></li>
      <li class="stat"><span class="stat__n"><?php echo esc_html( $lav_stats['rating'] ); ?><span class="stat__star" aria-hidden="true">★</span></span><span class="stat__l"><?php esc_html_e( 'avg. rating', 'lavtheme' ); ?></span></li>
    </ul>
    <ul class="quotes">
      <li class="quote"><p><?php echo esc_html__( '“Found a Claude MCP server, bought it, and shipped the same afternoon.”', 'lavtheme' ); ?></p><p class="quote__by"><?php esc_html_e( '— Indie hacker', 'lavtheme' ); ?></p></li>
      <li class="quote"><p><?php echo esc_html__( '“The bundles are the killer feature. One click and my whole agent stack is set up.”', 'lavtheme' ); ?></p><p class="quote__by"><?php esc_html_e( '— ML engineer', 'lavtheme' ); ?></p></li>
      <li class="quote"><p><?php echo esc_html__( '“Selling my prompt packs here finally feels like a real storefront, not a gist.”', 'lavtheme' ); ?></p><p class="quote__by"><?php esc_html_e( '— Prompt author', 'lavtheme' ); ?></p></li>
    </ul>
  </div>
</section>
