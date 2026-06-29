<?php
/**
 * Home trust strip.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lav_count = (int) wp_count_posts( 'download' )->publish;
?>
<section class="sec sec--tight trust" aria-label="<?php esc_attr_e( 'Why shop on LAVZEN', 'lavzentheme' ); ?>">
  <ul class="wrap trust__row">
    <li class="trust__item"><span aria-hidden="true">✓</span> <?php echo esc_html( sprintf( __( '%s+ verified listings', 'lavzentheme' ), number_format_i18n( max( $lav_count, 0 ) ) ) ); ?></li>
    <li class="trust__item"><span aria-hidden="true">⤓</span> <?php esc_html_e( 'Instant delivery', 'lavzentheme' ); ?></li>
    <li class="trust__item"><span aria-hidden="true">◇</span> <?php esc_html_e( 'Works with Claude, Cursor & GPT', 'lavzentheme' ); ?></li>
    <li class="trust__item"><span aria-hidden="true">⛉</span> <?php esc_html_e( 'Security-scanned', 'lavzentheme' ); ?></li>
    <li class="trust__item"><span aria-hidden="true">↩</span> <?php esc_html_e( '14-day refund', 'lavzentheme' ); ?></li>
  </ul>
</section>
