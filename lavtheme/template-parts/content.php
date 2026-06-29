<?php
/**
 * Generic post card — reuses the front-page `.post` styling.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$cat   = get_the_category();
$pill  = ! empty( $cat ) ? $cat[0]->name : get_post_type();
$thumb = has_post_thumbnail()
	? get_the_post_thumbnail_url( get_the_ID(), 'lavtheme-card' )
	: 'https://placehold.co/640x400/0b1120/7c83ff?text=' . rawurlencode( get_the_title() );
$initial = strtoupper( substr( get_the_author(), 0, 1 ) );
?>
<a class="post" href="<?php the_permalink(); ?>">
	<div class="post-thumb">
		<span class="post-pill"><?php echo esc_html( $pill ); ?></span>
		<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
	</div>
	<div class="post-body">
		<h3 class="post-title"><?php the_title(); ?></h3>
		<p class="post-sub"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
		<div class="post-foot">
			<span class="post-avatar"><?php echo esc_html( $initial ); ?></span>
			<div>
				<div class="post-who"><?php the_author(); ?></div>
				<div class="post-sub2"><?php echo esc_html( get_the_date() ); ?></div>
			</div>
		</div>
	</div>
</a>
