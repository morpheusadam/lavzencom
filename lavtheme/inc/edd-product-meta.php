<?php
/**
 * Custom product meta for the lavtheme single-download design.
 *
 * Adds metaboxes to the EDD download edit screen for every datapoint the
 * product design needs but EDD lacks natively (version, tagline, demo/preview
 * URLs, spec boxes, feature cards, highlights, seller info, and the
 * Tutorials/Q&A/Support tab content). Plus a reused gallery picker.
 *
 * Standard WordPress hooks only (add_meta_box / save_post_download), with a
 * nonce, capability check and per-type sanitisation. Data lives in post meta;
 * the front-end template reads it via the lavtheme_pm_* getters at the bottom.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* ============================ field registries ============================ */

/**
 * Single-line text / url / short-textarea fields (Details & Seller box).
 *
 * @return array
 */
function lavtheme_pm_text_fields() {
	return array(
		'_lav_version'           => array( 'label' => __( 'Version', 'lavtheme' ), 'type' => 'text', 'ph' => 'e.g. 2.4.1' ),
		'_lav_tagline'           => array( 'label' => __( 'Tagline / subtitle', 'lavtheme' ), 'type' => 'textarea', 'ph' => 'Short subtitle (falls back to the excerpt)' ),
		'_lav_demo_url'          => array( 'label' => __( 'Live Demo URL', 'lavtheme' ), 'type' => 'url', 'ph' => 'https://…' ),
		'_lav_preview_url'       => array( 'label' => __( 'Preview URL', 'lavtheme' ), 'type' => 'url', 'ph' => 'https://…' ),
		'_lav_subscription_note' => array( 'label' => __( 'Subscription note', 'lavtheme' ), 'type' => 'text', 'ph' => 'Or subscribe — $100/yr unlimited access' ),
		'_lav_support_label'     => array( 'label' => __( 'Support label', 'lavtheme' ), 'type' => 'text', 'ph' => '24/7' ),
		'_lav_seller_name'       => array( 'label' => __( 'Seller name', 'lavtheme' ), 'type' => 'text', 'ph' => 'defaults to the post author' ),
		'_lav_seller_response'   => array( 'label' => __( 'Avg. response time', 'lavtheme' ), 'type' => 'text', 'ph' => 'under 2 hours' ),
	);
}

/**
 * Checkbox fields.
 *
 * @return array
 */
function lavtheme_pm_check_fields() {
	return array(
		'_lav_show_installment' => __( 'Show “Pay in 4” installment line', 'lavtheme' ),
		'_lav_seller_verified'  => __( 'Verified author badge', 'lavtheme' ),
	);
}

/**
 * Pipe-delimited textarea blocks (one row per line).
 *
 * @return array
 */
function lavtheme_pm_block_fields() {
	return array(
		'product_features'   => array( 'label' => __( 'Key Features', 'lavtheme' ), 'help' => __( 'One per line — shown as the ticked feature list.', 'lavtheme' ) ),
		'_lav_spec_boxes'    => array( 'label' => __( 'Spec Boxes', 'lavtheme' ), 'help' => __( 'One per line: value | label  (max 4). e.g.  CSV | Excel / CSV export', 'lavtheme' ) ),
		'_lav_feature_cards' => array( 'label' => __( 'Feature Cards', 'lavtheme' ), 'help' => __( 'One per line: title | description  (max 6).', 'lavtheme' ) ),
		'_lav_highlights'    => array( 'label' => __( 'Highlights (banner chips)', 'lavtheme' ), 'help' => __( 'One per line: label | url  (max 3, url optional).', 'lavtheme' ) ),
	);
}

/**
 * Rich-text tab fields. Keys are the meta keys; 'editor' is the TinyMCE id
 * (must be lowercase a–z, no underscores).
 *
 * @return array
 */
function lavtheme_pm_tab_fields() {
	return array(
		'_lav_tab_tutorials' => array( 'label' => __( 'Tutorials', 'lavtheme' ), 'editor' => 'lavtabtutorials' ),
		'_lav_tab_qa'        => array( 'label' => __( 'Q & A', 'lavtheme' ), 'editor' => 'lavtabqa' ),
		'_lav_tab_support'   => array( 'label' => __( 'Support', 'lavtheme' ), 'editor' => 'lavtabsupport' ),
	);
}

/* ============================ register metaboxes ========================== */

function lavtheme_pm_add_metaboxes() {
	add_meta_box( 'lav_pm_details', __( 'Product Details & Seller', 'lavtheme' ), 'lavtheme_pm_box_details', 'download', 'normal', 'high' );
	add_meta_box( 'lav_pm_blocks', __( 'Content Blocks', 'lavtheme' ), 'lavtheme_pm_box_blocks', 'download', 'normal', 'default' );
	add_meta_box( 'lav_pm_tabs', __( 'Tabs Content', 'lavtheme' ), 'lavtheme_pm_box_tabs', 'download', 'normal', 'default' );
	add_meta_box( 'lav_pm_gallery', __( 'Product Gallery', 'lavtheme' ), 'lavtheme_pm_box_gallery', 'download', 'side', 'default' );
}
add_action( 'add_meta_boxes_download', 'lavtheme_pm_add_metaboxes' );

/**
 * Minimal admin styling for the boxes (printed once with the first box).
 */
function lavtheme_pm_admin_css() {
	return '<style>
	.lav-pm .lav-pm-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 18px}
	.lav-pm .lav-pm-row{margin:0}
	.lav-pm .lav-pm-full{grid-column:1/-1}
	.lav-pm label{display:block;font-weight:600;margin-bottom:4px}
	.lav-pm input[type=text],.lav-pm input[type=url],.lav-pm textarea{width:100%}
	.lav-pm .lav-pm-check label{font-weight:400}
	.lav-pm .description{display:block;color:#646970;margin-top:4px;font-size:12px}
	.lav-pm-gallery .lav-gallery-preview{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:0 0 10px;padding:0;list-style:none}
	.lav-pm-gallery .lav-gallery-preview li{position:relative;aspect-ratio:1;border-radius:6px;overflow:hidden;border:1px solid #dcdcde}
	.lav-pm-gallery .lav-gallery-preview img{width:100%;height:100%;object-fit:cover;display:block}
	.lav-pm-gallery .lav-gal-rm{position:absolute;top:2px;right:2px;width:20px;height:20px;border:none;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;cursor:pointer;line-height:1;font-size:14px}
	</style>';
}

/* ============================ render callbacks =========================== */

function lavtheme_pm_box_details( $post ) {
	wp_nonce_field( 'lavtheme_pm_save', 'lavtheme_pm_nonce' );
	echo lavtheme_pm_admin_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static style.
	echo '<div class="lav-pm"><div class="lav-pm-grid">';

	foreach ( lavtheme_pm_text_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, $key, true );
		$cls = 'textarea' === $f['type'] ? 'lav-pm-row lav-pm-full' : 'lav-pm-row';
		echo '<p class="' . esc_attr( $cls ) . '">';
		echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $f['label'] ) . '</label>';
		if ( 'textarea' === $f['type'] ) {
			echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="2" placeholder="' . esc_attr( $f['ph'] ) . '">' . esc_textarea( (string) $val ) . '</textarea>';
		} else {
			$itype = 'url' === $f['type'] ? 'url' : 'text';
			echo '<input type="' . esc_attr( $itype ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $val ) . '" placeholder="' . esc_attr( $f['ph'] ) . '">';
		}
		echo '</p>';
	}

	foreach ( lavtheme_pm_check_fields() as $key => $label ) {
		$val = get_post_meta( $post->ID, $key, true );
		echo '<p class="lav-pm-row lav-pm-check"><label><input type="checkbox" name="' . esc_attr( $key ) . '" value="1" ' . checked( $val, '1', false ) . '> ' . esc_html( $label ) . '</label></p>';
	}

	echo '</div></div>';
}

function lavtheme_pm_box_blocks( $post ) {
	echo '<div class="lav-pm">';
	foreach ( lavtheme_pm_block_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, $key, true );
		echo '<p class="lav-pm-row lav-pm-full">';
		echo '<label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $f['label'] ) . '</strong></label>';
		echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="4" class="widefat">' . esc_textarea( (string) $val ) . '</textarea>';
		echo '<span class="description">' . esc_html( $f['help'] ) . '</span>';
		echo '</p>';
	}
	echo '</div>';
}

function lavtheme_pm_box_tabs( $post ) {
	foreach ( lavtheme_pm_tab_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, $key, true );
		echo '<p style="margin:14px 0 6px"><strong>' . esc_html( $f['label'] ) . '</strong></p>';
		wp_editor(
			(string) $val,
			$f['editor'],
			array(
				'textarea_name' => $key,
				'textarea_rows' => 6,
				'media_buttons' => false,
				'teeny'         => true,
				'tinymce'       => array( 'toolbar1' => 'bold,italic,bullist,numlist,link,unlink,undo,redo' ),
				'quicktags'     => true,
			)
		);
	}
}

function lavtheme_pm_box_gallery( $post ) {
	$ids = get_post_meta( $post->ID, '_product_gallery_ids', true );
	$ids = $ids ? array_filter( array_map( 'absint', explode( ',', (string) $ids ) ) ) : array();
	echo '<div class="lav-pm lav-pm-gallery">';
	echo '<input type="hidden" name="_product_gallery_ids" id="lav_gallery_ids" value="' . esc_attr( implode( ',', $ids ) ) . '">';
	echo '<ul id="lav_gallery_preview" class="lav-gallery-preview">';
	foreach ( $ids as $gid ) {
		$src = wp_get_attachment_image_url( $gid, 'thumbnail' );
		if ( ! $src ) {
			continue;
		}
		echo '<li data-id="' . esc_attr( $gid ) . '"><img src="' . esc_url( $src ) . '" alt=""><button type="button" class="lav-gal-rm" aria-label="Remove">&times;</button></li>';
	}
	echo '</ul>';
	echo '<button type="button" class="button" id="lav_gallery_add">' . esc_html__( 'Select images', 'lavtheme' ) . '</button>';
	echo '<p class="description">' . esc_html__( 'Images shown in the product gallery section (drag order = insert order).', 'lavtheme' ) . '</p>';
	echo '</div>';
	?>
	<script>
	( function( $ ) {
		if ( typeof wp === 'undefined' || ! wp.media ) { return; }
		var frame, $ids = $( '#lav_gallery_ids' ), $prev = $( '#lav_gallery_preview' );
		function sync() {
			var a = [];
			$prev.children( 'li' ).each( function() { a.push( $( this ).data( 'id' ) ); } );
			$ids.val( a.join( ',' ) );
		}
		$( '#lav_gallery_add' ).on( 'click', function( e ) {
			e.preventDefault();
			if ( frame ) { frame.open(); return; }
			frame = wp.media( { title: 'Select gallery images', button: { text: 'Use images' }, multiple: true, library: { type: 'image' } } );
			frame.on( 'select', function() {
				frame.state().get( 'selection' ).each( function( att ) {
					var id = att.id;
					if ( $prev.find( 'li[data-id="' + id + '"]' ).length ) { return; }
					var s = att.attributes.sizes, url = ( s && s.thumbnail ) ? s.thumbnail.url : att.attributes.url;
					$prev.append( '<li data-id="' + id + '"><img src="' + url + '" alt=""><button type="button" class="lav-gal-rm" aria-label="Remove">&times;</button></li>' );
				} );
				sync();
			} );
			frame.open();
		} );
		$prev.on( 'click', '.lav-gal-rm', function( e ) { e.preventDefault(); $( this ).closest( 'li' ).remove(); sync(); } );
	} )( jQuery );
	</script>
	<?php
}

/* ============================ save ======================================= */

function lavtheme_pm_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['lavtheme_pm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lavtheme_pm_nonce'] ) ), 'lavtheme_pm_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'download' !== get_post_type( $post_id ) ) {
		return;
	}

	$set = function ( $key, $clean ) use ( $post_id ) {
		if ( '' === trim( (string) $clean ) ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $clean );
		}
	};

	// Text / url / short textarea.
	foreach ( lavtheme_pm_text_fields() as $key => $f ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'url' === $f['type'] ) {
			$clean = esc_url_raw( $raw );
		} elseif ( 'textarea' === $f['type'] ) {
			$clean = sanitize_textarea_field( $raw );
		} else {
			$clean = sanitize_text_field( $raw );
		}
		$set( $key, $clean );
	}

	// Checkboxes.
	foreach ( lavtheme_pm_check_fields() as $key => $label ) {
		if ( ! empty( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, '1' );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}

	// Pipe-delimited blocks.
	foreach ( lavtheme_pm_block_fields() as $key => $f ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$set( $key, sanitize_textarea_field( $raw ) );
	}

	// Rich-text tabs.
	foreach ( lavtheme_pm_tab_fields() as $key => $f ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$set( $key, wp_kses_post( $raw ) );
	}

	// Gallery ids.
	$g   = isset( $_POST['_product_gallery_ids'] ) ? wp_unslash( $_POST['_product_gallery_ids'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) $g ) ) ) );
	if ( $ids ) {
		update_post_meta( $post_id, '_product_gallery_ids', implode( ',', $ids ) );
	} else {
		delete_post_meta( $post_id, '_product_gallery_ids' );
	}
}
add_action( 'save_post_download', 'lavtheme_pm_save' );

/* ============================ admin assets =============================== */

function lavtheme_pm_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && 'download' === $screen->post_type ) {
		wp_enqueue_media();
	}
}
add_action( 'admin_enqueue_scripts', 'lavtheme_pm_admin_assets' );

/* ===================== front-end getters (used by template) ============== */

/**
 * A meta value with an empty-string fallback.
 *
 * @param int    $id      Download ID.
 * @param string $key     Meta key.
 * @param string $default Fallback.
 * @return string
 */
function lavtheme_pm_get( $id, $key, $default = '' ) {
	$v = get_post_meta( $id, $key, true );
	return ( '' === $v || null === $v ) ? $default : (string) $v;
}

/** True when a checkbox meta is on. */
function lavtheme_pm_is_on( $id, $key ) {
	return '1' === (string) get_post_meta( $id, $key, true );
}

/**
 * Parse a pipe-delimited textarea into rows of $cols trimmed columns.
 *
 * @param int    $id   Download ID.
 * @param string $key  Meta key.
 * @param int    $cols Columns per row.
 * @param int    $max  Max rows (0 = all).
 * @return array<int,array<int,string>>
 */
function lavtheme_pm_rows( $id, $key, $cols = 2, $max = 0 ) {
	$raw = (string) get_post_meta( $id, $key, true );
	if ( '' === trim( $raw ) ) {
		return array();
	}
	$rows = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, $cols ) );
		while ( count( $parts ) < $cols ) {
			$parts[] = '';
		}
		$rows[] = $parts;
		if ( $max && count( $rows ) >= $max ) {
			break;
		}
	}
	return $rows;
}

/**
 * Parse a textarea into a flat list (one trimmed item per line).
 *
 * @param int    $id  Download ID.
 * @param string $key Meta key.
 * @param int    $max Max items (0 = all).
 * @return string[]
 */
function lavtheme_pm_list( $id, $key, $max = 0 ) {
	$raw = (string) get_post_meta( $id, $key, true );
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$out[] = $line;
		if ( $max && count( $out ) >= $max ) {
			break;
		}
	}
	return $out;
}
