# Single EDD Product Page — Professional Implementation

A professional, advanced single product page template for Easy Digital Downloads with modern WordPress patterns, security best practices, and premium features.

## 🎯 Overview

This implementation uses **WordPress hooks & filters** for maximum extensibility and follows **WordPress Coding Standards (WPCS)**. All components are fully customizable via child themes or filters.

## 📁 Files

### Templates
- **`single-download.php`** — Main product template using WordPress action hooks
- Modular design with `do_action()` throughout

### Includes (Functions)
- **`inc/edd-single-product-hooks.php`** — Hook implementations with defaults
  - `lavtheme_product_gallery` — Gallery display
  - `lavtheme_product_meta` — Title, category, stats
  - `lavtheme_product_price` — Pricing & purchase button
  - `lavtheme_product_tags` — Product tags
  - `lavtheme_product_content` — Main description
  - `lavtheme_product_features` — Features list
  - `lavtheme_product_changelog` — Version history
  - `lavtheme_product_related` — Related products

### Styling
- **`assets/css/single-product.css`** — Professional glass-morphism styling
- **`assets/js/single-product.js`** — Advanced interactions & analytics

### Updates
- **`functions.php`** — Requires new hooks file
- **`inc/enqueue.php`** — Loads CSS/JS for product pages

## 🔌 Hook Architecture

### Action Hooks (Customization Points)

```php
// Product gallery
do_action( 'lavtheme_product_gallery', $id );

// Product metadata (category, title, stats)
do_action( 'lavtheme_product_meta', $product_data );

// Pricing section
do_action( 'lavtheme_product_price', $product_data );

// Product tags
do_action( 'lavtheme_product_tags', $tags );

// Main content
do_action( 'lavtheme_product_content', $id );

// Features list
do_action( 'lavtheme_product_features', $id, $features );

// Changelog/version history
do_action( 'lavtheme_product_changelog', $id, $changelog );

// Related products
do_action( 'lavtheme_product_related', $product_data );
```

### Filter Hooks (Data Manipulation)

```php
// Filter product data before render
$product_data = apply_filters( 'lavtheme_product_data', $data, $id );
```

## 🎨 Features

### Core Features
✅ Professional glass-morphism design  
✅ Responsive layout (desktop, tablet, mobile)  
✅ Sticky product image (desktop)  
✅ Product statistics (downloads, updated date)  
✅ EDD price integration  
✅ Purchase/download button  
✅ Product categories & tags  
✅ Key features list  
✅ Related products carousel  
✅ Version history/changelog  
✅ System requirements display  

### Advanced Features
✅ WordPress action hooks throughout  
✅ Custom filters for data manipulation  
✅ Proper sanitization & escaping  
✅ Security headers (Cache-Control, X-Content-Type-Options)  
✅ Analytics tracking (GA4 ready)  
✅ Lazy loading images  
✅ Smooth scroll navigation  
✅ Keyboard navigation  
✅ Scroll depth tracking  
✅ IntersectionObserver for performance  
✅ Image prefetching on hover  
✅ Page visibility API support  

## 🔐 Security Implementation

### Nonce & Capability Checks
- All form submissions use WordPress nonces
- Proper capability checks for privileged operations
- Verified on server-side

### Sanitization & Escaping
```php
// Sanitized input
$title   = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
$content = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) );
$url     = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );

// Escaped output
echo esc_html( $title );
echo wp_kses_post( $content );
echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Link', 'lavtheme' ) . '</a>';
```

### Headers
```php
if ( ! headers_sent() ) {
	header( 'Cache-Control: public, max-age=3600' );
	header( 'X-Content-Type-Options: nosniff' );
}
```

## 💾 Custom Fields

Add these custom fields in EDD Download settings:

| Field | Purpose | Format |
|-------|---------|--------|
| `product_features` | Key features list | One per line |
| `product_requirements` | System requirements | HTML/text |
| `product_changelog` | Version history | HTML/text |
| `_product_gallery_ids` | Additional gallery images | Comma-separated IDs |

## 📦 Customization via Hooks

### Example: Customize Features Display

**In child theme's `functions.php`:**

```php
// Remove default features hook
remove_action( 'lavtheme_product_features', 'lavtheme_product_features_default', 10 );

// Add custom features display
add_action( 'lavtheme_product_features', 'my_custom_features', 10, 2 );
function my_custom_features( $id, $features ) {
	if ( ! $features ) return;
	
	$features_array = array_filter( array_map( 'trim', explode( "\n", $features ) ) );
	?>
	<div class="my-custom-features">
		<?php foreach ( $features_array as $feature ) : ?>
			<div class="my-feature">✨ <?php echo esc_html( $feature ); ?></div>
		<?php endforeach; ?>
	</div>
	<?php
}
```

### Example: Add Custom Section

```php
// Add after related products
add_action( 'lavtheme_product_related', 'my_custom_cta_section', 20 );
function my_custom_cta_section( $data ) {
	?>
	<div class="custom-cta">
		<h2>Premium Support Available</h2>
		<p>Get dedicated support for this product</p>
	</div>
	<?php
}
```

### Example: Filter Product Data

```php
add_filter( 'lavtheme_product_data', 'my_product_data_filter', 10, 2 );
function my_product_data_filter( $data, $id ) {
	// Modify price display
	$data['price_label'] = 'Special Offer: ';
	
	return $data;
}
```

## 📊 Analytics Integration

The template includes **Google Analytics 4** ready tracking:

```javascript
// Page view
gtag( 'event', 'view_item', { items: [ { item_name: document.title } ] } );

// Purchase click
gtag( 'event', 'add_to_cart', { value: price, currency: 'USD' } );

// Related product click
gtag( 'event', 'view_item', { items: [ { item_name: title } ] } );

// Tag click
gtag( 'event', 'search', { search_term: tagName } );

// Scroll depth
gtag( 'event', 'scroll', { value: scrollPercentage } );
```

**No GA4 tracking code?** The template gracefully degrades — no errors, just skips analytics.

## 🎯 Extended Features (Optional)

### Implement Image Gallery
```php
// In child theme single-download.php or via filter
$gallery_ids = get_post_meta( $id, '_product_gallery_ids', true );
if ( $gallery_ids ) {
	foreach ( explode( ',', $gallery_ids ) as $img_id ) {
		echo wp_get_attachment_image( absint( $img_id ), 'large' );
	}
}
```

### Add Pricing Tiers
Extend the `lavtheme_product_price` hook:
```php
remove_action( 'lavtheme_product_price', 'lavtheme_product_price_display', 10 );
add_action( 'lavtheme_product_price', 'my_tiered_pricing', 10 );
function my_tiered_pricing( $data ) {
	// Display multiple pricing tiers
}
```

### Add Reviews/Testimonials
Add via `lavtheme_product_content` hook or custom field.

### Add Video Demo
```php
// Custom field: product_video_url
$video = get_post_meta( $id, 'product_video_url', true );
if ( $video ) {
	echo wp_oembed_get( esc_url( $video ) );
}
```

## 📱 Responsive Breakpoints

| Breakpoint | Layout Changes |
|------------|-----------------|
| **Desktop** (>1024px) | 2-column, sticky image |
| **Tablet** (641-1024px) | Single column, flexible grid |
| **Mobile** (<640px) | Full-width, touch optimized |

## ♿ Accessibility

✅ Semantic HTML5  
✅ ARIA labels where needed  
✅ Keyboard navigation (Tab, Arrow keys, Escape)  
✅ Focus indicators  
✅ Color contrast (WCAG AA)  
✅ Image alt text  
✅ Form labels & descriptions  

## 🚀 Performance Optimizations

✅ Lazy loading for images  
✅ Image preloading on hover  
✅ Debounced scroll events  
✅ IntersectionObserver for animations  
✅ CSS Grid for efficient layouts  
✅ Optimized animations (GPU-accelerated)  
✅ Proper image sizing  
✅ Cache headers set  

## 📋 WordPress Standards Compliance

✅ **WPCS** (WordPress Coding Standards)  
✅ **Nonce verification** on all forms  
✅ **Sanitization** on all inputs  
✅ **Escaping** on all outputs  
✅ **Prepared statements** for DB queries  
✅ **Capability checks** for privileged ops  
✅ **Internationalization** (i18n) ready  
✅ **Action/Filter hooks** throughout  

## 🧪 Testing Checklist

- [ ] Product displays correctly on desktop/tablet/mobile
- [ ] Featured image loads properly
- [ ] Price displays correctly
- [ ] Purchase button works
- [ ] Tags are clickable
- [ ] Related products show
- [ ] Features list displays
- [ ] Changelog renders
- [ ] Smooth scroll works
- [ ] Lazy loading works
- [ ] Analytics tracking works
- [ ] Keyboard navigation works
- [ ] No console errors
- [ ] Mobile touch events work
- [ ] Accessibility check passes

## 📚 EDD Integration

### Hooks Used
```php
edd_get_download_price( $id )           // Get product price
edd_get_download_sales_stats( $id )     // Get download count
edd_get_purchase_link( $args )          // Get purchase button
```

### Requirements
- Easy Digital Downloads plugin must be active
- Product must be published
- Download must have pricing configured

### Custom Hooks
```php
// Filter product data
apply_filters( 'lavtheme_product_data', $data, $id );
```

## 🐛 Troubleshooting

### Product Page Blank
1. Check EDD is active: `function_exists( 'edd_get_download_price' )`
2. Verify post type is `download`
3. Check PHP error logs

### Hooks Not Firing
1. Verify hook name matches exactly
2. Check `inc/edd-single-product-hooks.php` is loaded
3. Verify priority (default is 10)

### Styling Issues
1. Clear browser cache
2. Verify `single-product.css` loads on single download pages
3. Check for conflicting CSS
4. Inspect element in DevTools

### Analytics Not Tracking
1. Verify Google Analytics code installed
2. Check `gtag` function exists: `typeof window.gtag === 'function'`
3. Check browser console for errors
4. Verify GA4 property ID is correct

## 📖 Resources

- [Easy Digital Downloads Docs](https://easydigitaldownloads.com/)
- [WordPress Theme Development](https://developer.wordpress.org/themes/)
- [WordPress Hooks Reference](https://developer.wordpress.org/plugins/hooks/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)

---

**Version**: 2.0.0 (Advanced WordPress Hooks Edition)  
**Last Updated**: 2026-06-19  
**Theme**: lavtheme  
**Dependencies**: Easy Digital Downloads (optional)
