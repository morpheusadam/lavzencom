<?php
/**
 * Home hero — headline, shop-wired search, and the live-department marquee.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_depts = function_exists( 'lavzen_home_departments' ) ? lavzen_home_departments() : array();

$lav_chip_group = static function ( $depts, $decorative, $repeat = 1 ) {
	$tiers = array( 'is--lg', 'is--md', 'is--sm' );
	$html  = '';
	$i     = 0;
	for ( $r = 0; $r < $repeat; $r++ ) {
		foreach ( $depts as $d ) {
			$cls   = $tiers[ $i % 3 ];
			$extra = $decorative ? ' aria-hidden="true" tabindex="-1"' : '';
			$html .= '<a class="hero__chip ' . esc_attr( $cls ) . '" href="' . esc_url( $d['url'] ) . '"' . $extra . '>' . esc_html( $d['name'] ) . '</a>';
			$i++;
		}
	}
	return $html;
};
?>
<section class="hero" aria-label="<?php esc_attr_e( 'Search the LAVZEN marketplace', 'lavzentheme' ); ?>">
  <div class="hero__bg" aria-hidden="true"></div>
  <div class="hero__grain" aria-hidden="true"></div>

  <div class="hero__inner">
    <div class="hero__copy">
      <p class="hero__eyebrow"><?php esc_html_e( 'The marketplace for everything AI', 'lavzentheme' ); ?></p>
      <h1 class="hero__title"><?php esc_html_e( 'Everything AI', 'lavzentheme' ); ?><br><?php esc_html_e( 'One search', 'lavzentheme' ); ?></h1>
      <p class="hero__sub"><?php esc_html_e( 'Models, agents, datasets, hardware — and the', 'lavzentheme' ); ?> <br class="brk-md"><?php esc_html_e( 'talent to ship them.', 'lavzentheme' ); ?></p>
    </div>

    <form class="hero__search" role="search" action="<?php echo esc_url( lavzen_shop_url() ); ?>" method="get">
      <label for="hero-q" class="sr-only"><?php esc_html_e( 'Search the marketplace', 'lavzentheme' ); ?></label>
      <input id="hero-q" name="pq" class="hero__input" type="search" inputmode="search"
             placeholder="<?php esc_attr_e( 'Search anything in AI…', 'lavzentheme' ); ?>" autocomplete="off" enterkeyhint="search" data-typewriter />
      <kbd class="hero__kbd" aria-hidden="true">/</kbd>
      <button class="hero__submit" type="submit" aria-label="<?php esc_attr_e( 'Search', 'lavzentheme' ); ?>">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M5 12h12.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
          <path d="m12.5 6 6 6-6 6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </form>

    <nav class="hero__cats" aria-label="<?php esc_attr_e( 'Browse categories', 'lavzentheme' ); ?>">
      <div class="marquee" data-paused="false">
        <div class="marquee__row"><div class="marquee__track"><div class="marquee__group"><?php echo $lav_chip_group( $lav_depts, false, 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?></div><div class="marquee__group" aria-hidden="true"><?php echo $lav_chip_group( $lav_depts, true, 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div></div>
        <div class="marquee__row marquee__row--alt" aria-hidden="true"><div class="marquee__track"><div class="marquee__group" aria-hidden="true"><?php echo $lav_chip_group( $lav_depts, true, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div class="marquee__group" aria-hidden="true"><?php echo $lav_chip_group( $lav_depts, true, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div></div>
        <div class="marquee__row marquee__row--slow" aria-hidden="true"><div class="marquee__track"><div class="marquee__group" aria-hidden="true"><?php echo $lav_chip_group( array_reverse( $lav_depts ), true, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div class="marquee__group" aria-hidden="true"><?php echo $lav_chip_group( array_reverse( $lav_depts ), true, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div></div>
        <button class="marquee__toggle" type="button" aria-pressed="false" aria-label="<?php esc_attr_e( 'Pause moving categories', 'lavzentheme' ); ?>"><svg class="icon-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="7" y="6" width="3.4" height="12" rx="1.2"/><rect x="13.6" y="6" width="3.4" height="12" rx="1.2"/></svg><svg class="icon-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5z"/></svg><span class="label-pause"><?php esc_html_e( 'Pause', 'lavzentheme' ); ?></span><span class="label-play"><?php esc_html_e( 'Play', 'lavzentheme' ); ?></span></button>
      </div>
    </nav>
  </div>
</section>
