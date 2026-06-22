<?php
/**
 * Footer: footer content + closing of .main / .app shell.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

echo '</main><!-- #main -->';

lavtheme_render_section( 'footer' );
?>
</div><!-- .main -->
</div><!-- .app -->
<?php wp_footer(); ?>
</body>
</html>
