<?php
/**
 * Home "Browse by department" bento — built from the live download_category
 * top-level terms (links + counts are real).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_depts = lavtheme_home_departments();
?>
<section class="sec" aria-labelledby="bento-h">
  <div class="wrap">
    <div class="head head--center">
      <h2 class="head__title" id="bento-h"><?php esc_html_e( 'Browse by department', 'lavtheme' ); ?></h2>
      <p class="head__sub"><?php esc_html_e( 'Ten departments, from open-weight models to the talent to ship them', 'lavtheme' ); ?></p>
    </div>
    <nav aria-label="<?php esc_attr_e( 'Departments', 'lavtheme' ); ?>">
      <ul class="bento">
        <?php
        foreach ( $lav_depts as $i => $d ) :
            $size = 0 === $i ? ' bento__tile--xl' : ( ( 1 === $i || 2 === $i ) ? ' bento__tile--lg' : '' );
            $label = 0 === $d['count']
                ? __( 'Explore', 'lavtheme' )
                : sprintf( _n( '%s listing', '%s listings', $d['count'], 'lavtheme' ), number_format_i18n( $d['count'] ) );
            ?>
            <li class="bento__tile<?php echo esc_attr( $size ); ?>">
              <a class="bento__link" href="<?php echo esc_url( $d['url'] ); ?>">
                <span class="bento__glyph" aria-hidden="true"><?php echo esc_html( $d['glyph'] ); ?></span>
                <span class="bento__name"><?php echo esc_html( $d['name'] ); ?></span>
                <span class="bento__count"><?php echo esc_html( $label ); ?></span>
                <span class="bento__go" aria-hidden="true">→</span>
              </a>
            </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</section>
