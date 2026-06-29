<?php
/**
 * Footer: footer content + closing of the page shell.
 *
 * Mirrors header.php: the front page closes its standalone marketplace shell
 * (<main id="content">, marketplace footer + mobile tab bar); every other
 * template closes the original .main / .app shell.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( is_front_page() ) {
	echo '</main><!-- #content -->';
	get_template_part( 'template-parts/home-footer' );
	echo '</div><!-- .main --></div><!-- .app -->';
	wp_footer();
	echo '</body></html>';
	return;
}

echo '</main><!-- #main -->';

lavtheme_render_section( 'footer' );
?>
</div><!-- .main -->
</div><!-- .app -->
<?php wp_footer(); ?>
</body>
</html>
