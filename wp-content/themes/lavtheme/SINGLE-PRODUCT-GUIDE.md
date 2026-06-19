# Single Product Page — Professional Design

A modern, professional single product page template for Easy Digital Downloads (EDD) with glass-morphism aesthetics, responsive design, and comprehensive product showcase features.

## Files Created

### Templates
- **`single-download.php`** — EDD product template with professional layout
- **`assets/css/single-product.css`** — Comprehensive product page styling
- **`assets/js/single-product.js`** — Interactive enhancements

### Modified Files
- **`inc/enqueue.php`** — Added CSS/JS enqueue for product pages

## Features

### Layout & Design
✓ Professional hero section with sticky product image gallery
✓ Responsive two-column layout (desktop) that stacks on mobile
✓ Glass-morphism aesthetic matching theme design tokens
✓ Modern typography using Oswald & Inter fonts
✓ Smooth animations and hover effects

### Product Information
✓ Product title with category badge
✓ Download/sales statistics
✓ Last updated date
✓ Pricing display with gradient styling
✓ Purchase/download button with EDD integration
✓ Product tags with hover effects

### Content Sections
✓ Main product description/content area
✓ Key features list (via custom field: `product_features`)
✓ Related products carousel (same category)
✓ Professional typography with proper hierarchy

### Technical
✓ Responsive design (desktop, tablet, mobile)
✓ Accessibility features (semantic HTML, focus states)
✓ Performance optimized (lazy loading, smooth scrolling)
✓ EDD integration with fallbacks
✓ Custom field support for features

## How to Use

### 1. Basic Product Setup (Automatic)
Once you create an EDD download/product, the single product page will automatically use this template when someone visits the product page.

### 2. Add Product Features (Optional)
To display key features on your product page:

1. Go to EDD Downloads → Edit Your Product
2. Look for the custom field: **`product_features`**
3. Enter features (one per line):
   ```
   Advanced Caching System
   Security Hardening
   Code Studio Integration
   Real-time Analytics
   ```

### 3. Customize via Code Studio (Optional)
If using Theme Code Studio, you can inject custom HTML/CSS per product:
- Product-specific Hero Section
- Custom CSS overrides
- Special promotional sections

## Styling Customization

### Design Tokens (CSS Variables)
The template uses theme CSS variables for consistency. Edit in:
`assets/css/sections/global.root.css`

Key variables:
- `--accent`: Primary color (#7c83ff)
- `--glass-1`, `--glass-2`, `--glass-3`: Glass effect layers
- `--text`, `--text-2`, `--text-3`: Text colors
- `--r-lg`, `--r-md`, `--r-sm`: Border radius tokens

### Custom Colors Example
```css
/* To override product pricing background */
.product-pricing .price-display {
	background: linear-gradient(135deg, #22d3ee, #4a9eff);
}
```

## EDD Integration

The template automatically pulls:
- Product price (via `edd_get_download_price()`)
- Sales count (via `edd_get_download_sales_stats()`)
- Product categories & tags
- Featured image (thumbnail)
- Purchase button/form (via `edd_get_purchase_link()`)

### Requirements
- Easy Digital Downloads plugin must be active
- Product post type: `download`
- Categories taxonomy: `download_category`

## Responsive Breakpoints

| Breakpoint | Changes |
|------------|---------|
| Desktop   | Two-column layout, sticky image |
| 1024px    | Single column layout, flexible grid |
| 640px     | Mobile optimizations, full-width buttons |
| 480px     | Extra padding adjustments, stacked stats |

## Future Enhancements

### Suggested Features
1. **Image Gallery** — Multiple product images with lightbox
2. **Video Integration** — Hero video background
3. **Customer Reviews** — Testimonials section
4. **Licensing Info** — Display product licensing/support
5. **Changelog** — Version history modal
6. **Bundle Pricing** — Multi-product discounts
7. **FAQ Section** — Collapsible Q&A

### Implementation Example (Image Gallery)
```php
// In single-download.php, replace .product-gallery div
$gallery_ids = get_post_meta( $id, 'product_gallery', true );
if ( $gallery_ids ) {
	foreach ( explode( ',', $gallery_ids ) as $img_id ) {
		echo wp_get_attachment_image( $img_id, 'large' );
	}
}
```

## Mobile Optimization

- Touch-friendly button sizes (56px minimum height)
- Readable font sizes (15px minimum)
- Proper spacing and margins
- Optimized images with lazy loading
- Full viewport width on small screens

## Performance Notes

- Single CSS file (single-product.css) only loaded on product pages
- Lazy loading for related product images
- Smooth animations use `var(--ease)` cubic-bezier
- No blocking scripts; JS loads async
- Proper image sizing and aspect ratios

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- CSS Grid & Flexbox
- CSS Custom Properties (variables)
- Backdrop filter (graceful degradation)
- IntersectionObserver for lazy loading

## Troubleshooting

### Product Page Shows Generic Template
- Make sure EDD is active
- Verify post type is `download`
- Check template hierarchy: `single-download.php` → `single.php` → `index.php`

### Styling Not Applied
- Clear browser cache (Ctrl+Shift+Delete)
- Check CSS file is linked in enqueue.php
- Verify `assets/css/single-product.css` exists
- Check console for 404 errors

### EDD Button Not Showing
- EDD plugin must be active
- Product must have pricing configured
- Check `edd_get_purchase_link()` function exists
- Verify `download_id` parameter is correct

### Related Products Not Showing
- Product must have a category assigned
- Category must have related products
- `download_category` taxonomy must exist

## Support & Documentation

- [Easy Digital Downloads Docs](https://easydigitaldownloads.com/)
- [WordPress Template Hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/)
- [CSS Custom Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/--*)

---

**Version**: 1.0.0  
**Last Updated**: 2026-06-19  
**Theme**: lavtheme  
**Dependencies**: Easy Digital Downloads (optional)
