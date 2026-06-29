<?php
/**
 * Chrome: site footer.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

$lavzen_year = (int) gmdate( 'Y' );
?>
<footer class="glass footer">
	<div class="f-grid">
		<div class="f-brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand-link" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — Home" style="display:inline-flex;align-items:center;color:inherit;text-decoration:none">
				<svg aria-hidden="true" focusable="false" class="logo-type" fill="none" viewbox="0 0 320 52" xmlns="http://www.w3.org/2000/svg"><defs><lineargradient id="lgf" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#F0843A"></stop><stop offset="1" stop-color="#E06A1F"></stop></lineargradient></defs><path d="M26 4 L46 15 V37 L26 48 L6 37 V15 Z" fill="url(#lgf)"></path><circle cx="26" cy="26" fill="none" r="7.5" stroke="#fff" stroke-width="3"></circle><text font-family="Oswald, sans-serif" font-size="30" font-weight="700" letter-spacing="0.5" x="62" y="35"><tspan fill="#ffffff">LAVZEN</tspan><tspan fill="#F4914B">WEB</tspan></text></svg>
			</a>
			<p class="foot-tag"><?php esc_html_e( 'Build, automate & rank — the tools and templates teams use to ship faster and grow organic traffic.', 'lavzentheme' ); ?></p>
			<div class="brand-meta"><a href="mailto:hello@lavzen.com">hello@lavzen.com</a> <span><?php esc_html_e( 'Remote-first · Worldwide', 'lavzentheme' ); ?></span></div>
		</div>
		<nav class="f-col" aria-label="<?php esc_attr_e( 'Product', 'lavzentheme' ); ?>">
			<h3><?php esc_html_e( 'Product', 'lavzentheme' ); ?> <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></h3>
			<ul><li><a href="#products" data-scroll="products"><?php esc_html_e( 'Scripts', 'lavzentheme' ); ?></a></li><li><a href="#products" data-scroll="products"><?php esc_html_e( 'AI Scripts', 'lavzentheme' ); ?></a></li><li><a href="#products" data-scroll="products"><?php esc_html_e( 'Telegram Bots', 'lavzentheme' ); ?></a></li><li><a href="#products" data-scroll="products"><?php esc_html_e( 'n8n Templates', 'lavzentheme' ); ?></a></li><li><a href="#products" data-scroll="products"><?php esc_html_e( 'WP Plugins', 'lavzentheme' ); ?></a></li></ul>
		</nav>
		<nav class="f-col" aria-label="<?php esc_attr_e( 'Company', 'lavzentheme' ); ?>">
			<h3><?php esc_html_e( 'Company', 'lavzentheme' ); ?> <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></h3>
			<ul><li><a href="#work" data-scroll="work"><?php esc_html_e( 'Case Studies', 'lavzentheme' ); ?></a></li><li><a href="#blog" data-scroll="blog"><?php esc_html_e( 'Blog', 'lavzentheme' ); ?></a></li><li><a href="#services" data-scroll="services"><?php esc_html_e( 'Services', 'lavzentheme' ); ?></a></li><li><a href="#contact" data-scroll="contact"><?php esc_html_e( 'Contact', 'lavzentheme' ); ?></a></li></ul>
		</nav>
		<nav class="f-col" aria-label="<?php esc_attr_e( 'Support', 'lavzentheme' ); ?>">
			<h3><?php esc_html_e( 'Support', 'lavzentheme' ); ?> <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></h3>
			<ul><li><a href="#"><?php esc_html_e( 'Help Center', 'lavzentheme' ); ?></a></li><li><a href="#"><?php esc_html_e( 'Documentation', 'lavzentheme' ); ?></a></li><li><a href="#"><?php esc_html_e( 'System Status', 'lavzentheme' ); ?></a></li><li><a href="#"><?php esc_html_e( 'Refund Policy', 'lavzentheme' ); ?></a></li></ul>
		</nav>
	</div>
	<div class="foot-bottom">
		<div class="muted">
			<?php
			/* translators: %d: current year. */
			printf( esc_html__( '© %d Lavzen Web. All rights reserved.', 'lavzentheme' ), $lavzen_year );
			?>
			· <a href="#"><?php esc_html_e( 'Privacy', 'lavzentheme' ); ?></a> · <a href="#"><?php esc_html_e( 'Terms', 'lavzentheme' ); ?></a> · <a href="#"><?php esc_html_e( 'Cookies', 'lavzentheme' ); ?></a>
		</div>
		<div class="foot-social" aria-label="<?php esc_attr_e( 'Social media', 'lavzentheme' ); ?>">
			<a class="soc" href="#" aria-label="X"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-7.5 8.6L23 22h-6.9l-5.4-7-6.2 7H1.4l8-9.2L1 2h7l4.9 6.5zM16.7 20h1.7L7.4 4H5.6z"/></svg></a>
			<a class="soc" href="#" aria-label="GitHub"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-3.2 19.5c.5.1.7-.2.7-.5v-1.7c-2.8.6-3.4-1.4-3.4-1.4-.5-1.2-1.1-1.5-1.1-1.5-.9-.6.1-.6.1-.6 1 .1 1.5 1 1.5 1 .9 1.5 2.3 1.1 2.9.8.1-.7.4-1.1.6-1.3-2.2-.3-4.6-1.1-4.6-4.9 0-1.1.4-2 1-2.7-.1-.3-.4-1.3.1-2.7 0 0 .8-.3 2.7 1a9.4 9.4 0 0 1 5 0c1.9-1.3 2.7-1 2.7-1 .5 1.4.2 2.4.1 2.7.6.7 1 1.6 1 2.7 0 3.8-2.4 4.6-4.6 4.9.4.3.7.9.7 1.9v2.8c0 .3.2.6.7.5A10 10 0 0 0 12 2z"/></svg></a>
			<a class="soc" href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5A2.5 2.5 0 1 0 5 8.5 2.5 2.5 0 0 0 4.98 3.5zM3 9h4v12H3zM10 9h3.8v1.7h.1c.5-1 1.8-2 3.7-2 4 0 4.7 2.6 4.7 6V21h-4v-5.3c0-1.3 0-2.9-1.8-2.9s-2 1.4-2 2.8V21h-4z"/></svg></a>
			<a class="soc" href="#" aria-label="YouTube"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 12s0-3.2-.4-4.7c-.2-.9-.9-1.5-1.7-1.7C19.4 5.2 12 5.2 12 5.2s-7.4 0-8.9.4c-.8.2-1.5.8-1.7 1.7C1 8.8 1 12 1 12s0 3.2.4 4.7c.2.9.9 1.5 1.7 1.7 1.5.4 8.9.4 8.9.4s7.4 0 8.9-.4c.8-.2 1.5-.8 1.7-1.7.4-1.5.4-4.7.4-4.7zM9.8 15.3V8.7l6.2 3.3z"/></svg></a>
		</div>
		<div class="pay-badges" aria-label="<?php esc_attr_e( 'Accepted payments', 'lavzentheme' ); ?>"><span class="pay">VISA</span><span class="pay">MC</span><span class="pay">PayPal</span><span class="pay">Stripe</span></div>
	</div>
</footer>
