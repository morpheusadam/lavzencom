<?php
/**
 * Products section — EDD downloads (category bubbles + product grid),
 * with a faithful static fallback when EDD is unavailable.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lavtheme_bubbles = function_exists( 'lavtheme_category_bubbles_html' ) ? lavtheme_category_bubbles_html() : '';
$lavtheme_grid    = function_exists( 'lavtheme_products_grid_html' ) ? lavtheme_products_grid_html() : '';
?>
<section class="block" id="products"><div class="block-head"><div><div class="kicker">Transactional engine</div><h2 class="block-title">Browse Digital Downloads</h2></div></div>
<?php
if ( '' !== $lavtheme_bubbles ) {
	echo $lavtheme_bubbles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* / wp_kses internally.
} else {
	// Original static fallback (EDD inactive or no categories).
	?>
	<nav class="iconnav" aria-label="Product categories"><div class="iconnav-row"><a class="ibubble" href="#products"><span class="bub" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2.6l2.7 5.6 6.1.8-4.5 4.3 1.1 6.1L12 16.7 6.5 19.4l1.1-6.1L3.1 9l6.1-.8z" fill="currentColor"/><path d="M12 2.6l2.7 5.6 6.1.8-4.5 4.3 1.1 6.1L12 16.7z" fill="#fff" opacity=".3"/></svg></span><span class="ilabel">Themes</span> </a><a class="ibubble" href="#products"><span class="bub" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="3.5" width="7.5" height="7.5" rx="2" fill="currentColor"/><rect x="13" y="3.5" width="7.5" height="7.5" rx="2" fill="currentColor" opacity=".6"/><rect x="3.5" y="13" width="7.5" height="7.5" rx="2" fill="currentColor" opacity=".6"/><rect x="13" y="13" width="7.5" height="7.5" rx="2" fill="currentColor"/></svg></span><span class="ilabel">Browser Extensions</span> </a><a class="ibubble" href="#products"><span class="bub" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9.2" fill="currentColor"/><path d="M3 12h18" stroke="#fff" stroke-width="1.4" opacity=".55"/><ellipse cx="12" cy="12" rx="4" ry="9.2" stroke="#fff" stroke-width="1.4" fill="none" opacity=".55"/></svg></span><span class="ilabel">WP Plugins</span> </a><a class="ibubble" href="#products"><span class="bub" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M6 6 12 18M18 6 12 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".55"/><circle cx="6" cy="6" r="3.2" fill="currentColor"/><circle cx="18" cy="6" r="3.2" fill="currentColor"/><circle cx="12" cy="18" r="3.2" fill="currentColor"/></svg></span><span class="ilabel">n8n Templates</span> </a><a class="ibubble" href="#products"><span class="bub" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M21.5 3.2 2.6 11c-.9.4-.8 1.7.1 2l4.9 1.5 1.7 5.3c.3.8 1.3.9 1.8.2l2.3-3.1 4.6 3.4c.6.5 1.5.1 1.7-.6L23 4.6c.2-1-.7-1.8-1.5-1.4z" fill="currentColor"/><path d="m8 14.5 9-7.5-6 8.2z" fill="#fff" opacity=".4"/></svg></span><span class="ilabel">Telegram Bots</span> </a><a class="ibubble" href="#products"><span class="bub" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="4.4" fill="currentColor"/><path d="M12 2.5v3.2M12 18.3v3.2M2.5 12h3.2M18.3 12h3.2M5.5 5.5l2.2 2.2M16.3 16.3l2.2 2.2M18.5 5.5l-2.2 2.2M7.7 16.3l-2.2 2.2" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/></svg></span><span class="ilabel">AI Scripts</span> </a><a class="ibubble" href="#products"><span class="bub" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M8.5 7 3.5 12l5 5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M15.5 7l5 5-5 5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" opacity=".7"/><rect x="11" y="4.5" width="2.2" height="15" rx="1.1" fill="currentColor" transform="rotate(12 12 12)" opacity=".55"/></svg></span><span class="ilabel">Scripts</span> </a><a class="ibubble is-more" href="#contact" data-scroll="contact"><span class="bub" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="5" cy="12" r="2.2" fill="currentColor"/><circle cx="12" cy="12" r="2.2" fill="currentColor"/><circle cx="19" cy="12" r="2.2" fill="currentColor"/></svg></span><span class="ilabel">More</span></a></div></nav>
	<?php
}

if ( '' !== $lavtheme_grid ) {
	echo $lavtheme_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* / wp_kses internally.
}
?>
</section>
