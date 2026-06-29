<?php
/**
 * Home "Build a stack" collections rail — curated bundles linking into filtered
 * shop searches.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_shop = lavzen_shop_url();
$lav_cols = array(
	array( 'glyph' => '◈', 'title' => __( 'Ship an AI SaaS this weekend', 'lavzentheme' ), 'desc' => __( 'Agent + prompts + template + dataset', 'lavzentheme' ), 'meta' => __( '4 items · save 20%', 'lavzentheme' ), 'q' => 'saas' ),
	array( 'glyph' => '⬡', 'title' => __( 'The Claude power-user starter', 'lavzentheme' ), 'desc' => __( 'MCP servers, skills & prompt packs', 'lavzentheme' ), 'meta' => __( '6 items · save 15%', 'lavzentheme' ), 'q' => 'claude' ),
	array( 'glyph' => '✦', 'title' => __( 'Automate your sales pipeline', 'lavzentheme' ), 'desc' => __( 'n8n flows, agents & integrations', 'lavzentheme' ), 'meta' => __( '5 items · save 18%', 'lavzentheme' ), 'q' => 'n8n' ),
	array( 'glyph' => '▦', 'title' => __( 'Computer-vision quickstart', 'lavzentheme' ), 'desc' => __( 'Model + dataset + edge dev kit', 'lavzentheme' ), 'meta' => __( '3 items · save 12%', 'lavzentheme' ), 'q' => 'vision' ),
);
?>
<section class="sec rail" data-rail aria-labelledby="coll-h">
  <div class="wrap rail__top">
    <div class="head">
      <h2 class="head__title" id="coll-h"><?php esc_html_e( 'Build a stack, not a search', 'lavzentheme' ); ?></h2>
      <p class="head__sub"><?php esc_html_e( 'Curated kits that get you shipping faster', 'lavzentheme' ); ?></p>
    </div>
    <div class="rail__nav" data-nav>
      <a class="head__link" href="<?php echo esc_url( $lav_shop ); ?>"><?php esc_html_e( 'All collections', 'lavzentheme' ); ?></a>
      <button class="rail__btn" type="button" aria-label="<?php esc_attr_e( 'Scroll collections left', 'lavzentheme' ); ?>" data-prev><?php echo lavzen_home_arrow( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
      <button class="rail__btn" type="button" aria-label="<?php esc_attr_e( 'Scroll collections right', 'lavzentheme' ); ?>" data-next><?php echo lavzen_home_arrow( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
    </div>
  </div>
  <div class="rail__viewport" data-viewport tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Collections — scrollable', 'lavzentheme' ); ?>">
    <ul class="rail__track rail__track--coll">
      <?php foreach ( $lav_cols as $i => $c ) : ?>
      <li class="ccard c<?php echo (int) $i; ?>">
        <div class="ccard__glyph" aria-hidden="true"><?php echo esc_html( $c['glyph'] ); ?></div>
        <div class="ccard__body">
          <p class="ccard__kicker"><?php esc_html_e( 'Collection', 'lavzentheme' ); ?></p>
          <h3 class="ccard__title"><a href="<?php echo esc_url( add_query_arg( 'pq', $c['q'], $lav_shop ) ); ?>"><?php echo esc_html( $c['title'] ); ?></a></h3>
          <p class="ccard__desc"><?php echo esc_html( $c['desc'] ); ?></p>
          <p class="ccard__meta"><?php echo esc_html( $c['meta'] ); ?></p>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
