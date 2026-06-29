<?php
/**
 * Home trust strip.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_count = (int) wp_count_posts( 'download' )->publish;
?>
<section class="sec sec--tight trust" aria-label="<?php esc_attr_e( 'Why shop on LAVZEN', 'lavtheme' ); ?>">
  <ul class="wrap trust__row">
    <li class="trust__item"><span aria-hidden="true">✓</span> <?php echo esc_html( sprintf( __( '%s+ verified listings', 'lavtheme' ), number_format_i18n( max( $lav_count, 0 ) ) ) ); ?></li>
    <li class="trust__item"><span aria-hidden="true">⤓</span> <?php esc_html_e( 'Instant delivery', 'lavtheme' ); ?></li>
    <li class="trust__item"><span aria-hidden="true">◇</span> <?php esc_html_e( 'Works with Claude, Cursor & GPT', 'lavtheme' ); ?></li>
    <li class="trust__item"><span aria-hidden="true">⛉</span> <?php esc_html_e( 'Security-scanned', 'lavtheme' ); ?></li>
    <li class="trust__item"><span aria-hidden="true">↩</span> <?php esc_html_e( '14-day refund', 'lavtheme' ); ?></li>
  </ul>
</section>
