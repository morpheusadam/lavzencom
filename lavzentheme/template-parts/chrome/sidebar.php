<?php
/**
 * Chrome: desktop icon rail / mobile bottom nav.
 *
 * Generic UI icons come from the icon registry (lavzen_icon); the brand-social
 * marks and the hexagon logo stay inline as one-off brand assets.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;
?>
<aside class="sidebar">
	<div class="logo">
		<a class="brand-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — Home">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 2 L20 7 V17 L12 22 L4 17 V7 Z"/><circle cx="12" cy="12" r="3.4"/></svg>
		</a>
	</div>
	<nav class="nav">
		<a class="nav-item nav-desktop" href="https://www.reddit.com" target="_blank" rel="noopener" data-label="Reddit" aria-label="Reddit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="13" r="8"/><circle cx="8.5" cy="13" r="1"/><circle cx="15.5" cy="13" r="1"/><path d="M9 16.5c1.8 1.2 4.2 1.2 6 0"/><path d="M16 6.5 17 3l3 .7"/><circle cx="20" cy="3.7" r="1.1"/></svg></a>
		<a class="nav-item nav-desktop" href="https://www.linkedin.com" target="_blank" rel="noopener" data-label="LinkedIn" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 10v7M7 7v.01M11 17v-4a2 2 0 0 1 4 0v4"/></svg></a>
		<a class="nav-item nav-desktop" href="https://github.com" target="_blank" rel="noopener" data-label="GitHub" aria-label="GitHub"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 19c-4 1.5-4-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.3 4.3 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12 12 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.3 4.3 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/></svg></a>
		<a class="nav-item nav-desktop" href="https://stackoverflow.com" target="_blank" rel="noopener" data-label="Stack Overflow" aria-label="Stack Overflow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 16v4H5v-4"/><path d="M8 16h6M8.5 13l5.8 1.2M9.5 10l5.5 2.2M11 7.2l5 3M13.5 4.5l4 4.2"/></svg></a>
		<a class="nav-item nav-mobile active" href="#home" data-scroll="home" data-label="Home" aria-label="Home"><?php lavzen_icon( 'home' ); ?></a>
		<a class="nav-item nav-mobile" href="#products" data-scroll="products" data-label="Shop" aria-label="Shop"><?php lavzen_icon( 'bag' ); ?></a>
		<button class="nav-item nav-mobile" id="mobileSearchBtn" data-label="Search" aria-label="Search"><?php lavzen_icon( 'search' ); ?></button>
		<a class="nav-item nav-mobile" href="#blog" data-scroll="blog" data-label="Blog" aria-label="Blog"><?php lavzen_icon( 'doc' ); ?></a>
		<button class="nav-item nav-mobile" id="mobileProfileBtn" data-label="Profile" aria-label="Profile"><?php lavzen_icon( 'user' ); ?></button>
	</nav>
</aside>
