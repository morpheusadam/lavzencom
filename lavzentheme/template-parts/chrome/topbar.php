<?php
/**
 * Chrome: top bar (brand, primary nav, notifications, account popover).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;
?>
<svg id="lzx-glass-svg" aria-hidden="true" width="0" height="0" style="position:absolute;width:0;height:0;overflow:hidden;pointer-events:none"><defs><filter id="lzx-glass-distort" x="-25%" y="-25%" width="150%" height="150%" color-interpolation-filters="sRGB"><feTurbulence type="fractalNoise" baseFrequency="0.006 0.008" numOctaves="2" seed="7" result="n"/><feGaussianBlur in="n" stdDeviation="1.2" result="b"/><feDisplacementMap in="SourceGraphic" in2="b" scale="55" xChannelSelector="R" yChannelSelector="G"/></filter></defs></svg>
<header class="topbar">
	<span class="lzx-rfr"></span><span class="lzx-tnt"></span><span class="lzx-shn"></span>
	<div class="brand">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand-link" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> &mdash; Home" style="display:inline-flex;align-items:center;color:inherit;text-decoration:none">
			<svg aria-hidden="true" focusable="false" class="logo-type" fill="none" viewbox="0 0 320 52" xmlns="http://www.w3.org/2000/svg"><defs><lineargradient id="lg" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#F5F5F5"></stop><stop offset="1" stop-color="#9A9A9A"></stop></lineargradient></defs><path d="M26 4 L46 15 V37 L26 48 L6 37 V15 Z" fill="url(#lg)"></path><circle cx="26" cy="26" fill="none" r="7.5" stroke="#fff" stroke-width="3"></circle><text font-family="Oswald, sans-serif" font-size="30" font-weight="700" letter-spacing="0.5" x="62" y="35"><tspan fill="#ffffff">LAVZEN</tspan><tspan fill="#ABABAB">WEB</tspan></text></svg>
		</a>
	</div>
	<nav class="topnav" id="topnav"><?php lavzen_topnav(); ?></nav>
	<div class="top-actions">
		<div class="ta-wrap">
			<button class="icon-btn has-dot" id="notifBtn" aria-label="<?php esc_attr_e( 'Notifications', 'lavzentheme' ); ?>" aria-haspopup="true" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg></button>
		</div>
		<div class="ta-wrap">
			<button class="avatar is-online" id="avatarBtn" aria-label="<?php esc_attr_e( 'Account menu', 'lavzentheme' ); ?>" aria-haspopup="true" aria-expanded="false">
				<img loading="lazy" decoding="async" src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%2096%2096%22%3E%3Cdefs%3E%3ClinearGradient%20id%3D%22av%22%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%221%22%20y2%3D%221%22%3E%3Cstop%20offset%3D%220%22%20stop-color%3D%22%234A4A4A%22/%3E%3Cstop%20offset%3D%221%22%20stop-color%3D%22%23292929%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect%20width%3D%2296%22%20height%3D%2296%22%20rx%3D%2224%22%20fill%3D%22url%28%23av%29%22/%3E%3Ctext%20x%3D%2248%22%20y%3D%2262%22%20font-family%3D%22Oswald,sans-serif%22%20font-size%3D%2244%22%20font-weight%3D%22700%22%20fill%3D%22%23F5F5F5%22%20text-anchor%3D%22middle%22%3ED%3C/text%3E%3C/svg%3E" alt="<?php esc_attr_e( 'Profile', 'lavzentheme' ); ?>">
			</button>
			<div class="popover acct-pop" id="acctPop" role="menu" aria-label="<?php esc_attr_e( 'Account', 'lavzentheme' ); ?>"><?php echo lavzen_account_popover(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the builder. ?></div>
		</div>
		<button class="menu-toggle" id="menuToggle" aria-label="<?php esc_attr_e( 'Toggle menu', 'lavzentheme' ); ?>" aria-expanded="false"><svg class="ic-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg><svg class="ic-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
	</div>
</header>
