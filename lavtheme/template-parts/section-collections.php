<?php
/**
 * Home "Build a stack" collections rail. Curated bundles, each linking into a
 * filtered shop search so the cards resolve to real listings.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_shop = lavtheme_shop_url();
$lav_cols = array(
	array( 'glyph' => '◈', 'title' => __( 'Ship an AI SaaS this weekend', 'lavtheme' ), 'desc' => __( 'Agent + prompts + template + dataset', 'lavtheme' ), 'meta' => __( '4 items · save 20%', 'lavtheme' ), 'q' => 'saas' ),
	array( 'glyph' => '⬡', 'title' => __( 'The Claude power-user starter', 'lavtheme' ), 'desc' => __( 'MCP servers, skills & prompt packs', 'lavtheme' ), 'meta' => __( '6 items · save 15%', 'lavtheme' ), 'q' => 'claude' ),
	array( 'glyph' => '✦', 'title' => __( 'Automate your sales pipeline', 'lavtheme' ), 'desc' => __( 'n8n flows, agents & integrations', 'lavtheme' ), 'meta' => __( '5 items · save 18%', 'lavtheme' ), 'q' => 'n8n' ),
	array( 'glyph' => '▦', 'title' => __( 'Computer-vision quickstart', 'lavtheme' ), 'desc' => __( 'Model + dataset + edge dev kit', 'lavtheme' ), 'meta' => __( '3 items · save 12%', 'lavtheme' ), 'q' => 'vision' ),
);
?>
<section class="sec rail" data-rail aria-labelledby="coll-h">
  <div class="wrap rail__top">
    <div class="head">
      <h2 class="head__title" id="coll-h"><?php esc_html_e( 'Build a stack, not a search', 'lavtheme' ); ?></h2>
      <p class="head__sub"><?php esc_html_e( 'Curated kits that get you shipping faster', 'lavtheme' ); ?></p>
    </div>
    <div class="rail__nav" data-nav>
      <a class="head__link" href="<?php echo esc_url( $lav_shop ); ?>"><?php esc_html_e( 'All collections', 'lavtheme' ); ?></a>
      <button class="rail__btn" type="button" aria-label="<?php esc_attr_e( 'Scroll collections left', 'lavtheme' ); ?>" data-prev><?php echo lavtheme_home_arrow( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
      <button class="rail__btn" type="button" aria-label="<?php esc_attr_e( 'Scroll collections right', 'lavtheme' ); ?>" data-next><?php echo lavtheme_home_arrow( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
    </div>
  </div>
  <div class="rail__viewport" data-viewport tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Collections — scrollable', 'lavtheme' ); ?>">
    <ul class="rail__track rail__track--coll">
      <?php foreach ( $lav_cols as $i => $c ) : ?>
      <li class="ccard c<?php echo (int) $i; ?>">
        <div class="ccard__glyph" aria-hidden="true"><?php echo esc_html( $c['glyph'] ); ?></div>
        <div class="ccard__body">
          <p class="ccard__kicker"><?php esc_html_e( 'Collection', 'lavtheme' ); ?></p>
          <h3 class="ccard__title"><a href="<?php echo esc_url( add_query_arg( 'pq', $c['q'], $lav_shop ) ); ?>"><?php echo esc_html( $c['title'] ); ?></a></h3>
          <p class="ccard__desc"><?php echo esc_html( $c['desc'] ); ?></p>
          <p class="ccard__meta"><?php echo esc_html( $c['meta'] ); ?></p>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
