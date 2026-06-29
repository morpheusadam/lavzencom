<?php
/**
 * Custom product meta for the single-download design.
 *
 * Ported from the legacy inc/edd-product-meta.php (renamed lavzen_pm_*; meta keys
 * kept as _lav_* / product_features / _product_gallery_ids for data continuity).
 * Admin metaboxes on the EDD download editor (nonce + cap + per-type sanitise)
 * plus the front-end getters used by template-parts/single-download-body.php.
 * Loaded by the EDD module (outside the autoloaded namespace).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

/* ============================ field registries ============================ */

function lavzen_pm_text_fields(): array {
	return array(
		'_lav_version'           => array( 'label' => __( 'Version', 'lavzentheme' ), 'type' => 'text', 'ph' => 'e.g. 2.4.1' ),
		'_lav_tagline'           => array( 'label' => __( 'Tagline / subtitle', 'lavzentheme' ), 'type' => 'textarea', 'ph' => 'Short subtitle (falls back to the excerpt)' ),
		'_lav_demo_url'          => array( 'label' => __( 'Live Demo URL', 'lavzentheme' ), 'type' => 'url', 'ph' => 'https://…' ),
		'_lav_preview_url'       => array( 'label' => __( 'Preview URL', 'lavzentheme' ), 'type' => 'url', 'ph' => 'https://…' ),
		'_lav_subscription_note' => array( 'label' => __( 'Subscription note', 'lavzentheme' ), 'type' => 'text', 'ph' => 'Or subscribe — $100/yr unlimited access' ),
		'_lav_support_label'     => array( 'label' => __( 'Support label', 'lavzentheme' ), 'type' => 'text', 'ph' => '24/7' ),
		'_lav_seller_name'       => array( 'label' => __( 'Seller name', 'lavzentheme' ), 'type' => 'text', 'ph' => 'defaults to the post author' ),
		'_lav_seller_response'   => array( 'label' => __( 'Avg. response time', 'lavzentheme' ), 'type' => 'text', 'ph' => 'under 2 hours' ),
	);
}

function lavzen_pm_check_fields(): array {
	return array(
		'_lav_show_installment' => __( 'Show “Pay in 4” installment line', 'lavzentheme' ),
		'_lav_seller_verified'  => __( 'Verified author badge', 'lavzentheme' ),
	);
}

function lavzen_pm_block_fields(): array {
	return array(
		'product_features'   => array( 'label' => __( 'Key Features', 'lavzentheme' ), 'help' => __( 'One per line — shown as the ticked feature list.', 'lavzentheme' ) ),
		'_lav_spec_boxes'    => array( 'label' => __( 'Spec Boxes', 'lavzentheme' ), 'help' => __( 'One per line: value | label  (max 4).', 'lavzentheme' ) ),
		'_lav_feature_cards' => array( 'label' => __( 'Feature Cards', 'lavzentheme' ), 'help' => __( 'One per line: title | description  (max 6).', 'lavzentheme' ) ),
		'_lav_highlights'    => array( 'label' => __( 'Highlights (banner chips)', 'lavzentheme' ), 'help' => __( 'One per line: label | url  (max 3, url optional).', 'lavzentheme' ) ),
	);
}

function lavzen_pm_tab_fields(): array {
	return array(
		'_lav_tab_tutorials' => array( 'label' => __( 'Tutorials', 'lavzentheme' ), 'editor' => 'lavtabtutorials' ),
		'_lav_tab_qa'        => array( 'label' => __( 'Q & A', 'lavzentheme' ), 'editor' => 'lavtabqa' ),
		'_lav_tab_support'   => array( 'label' => __( 'Support', 'lavzentheme' ), 'editor' => 'lavtabsupport' ),
	);
}

/* ============================ register metaboxes ========================== */

add_action(
	'add_meta_boxes_download',
	static function () {
		add_meta_box( 'lav_pm_details', __( 'Product Details & Seller', 'lavzentheme' ), 'lavzen_pm_box_details', 'download', 'normal', 'high' );
		add_meta_box( 'lav_pm_blocks', __( 'Content Blocks', 'lavzentheme' ), 'lavzen_pm_box_blocks', 'download', 'normal', 'default' );
		add_meta_box( 'lav_pm_tabs', __( 'Tabs Content', 'lavzentheme' ), 'lavzen_pm_box_tabs', 'download', 'normal', 'default' );
		add_meta_box( 'lav_pm_gallery', __( 'Product Gallery', 'lavzentheme' ), 'lavzen_pm_box_gallery', 'download', 'side', 'default' );
	}
);

function lavzen_pm_admin_css(): string {
	return '<style>.lav-pm .lav-pm-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 18px}.lav-pm .lav-pm-full{grid-column:1/-1}.lav-pm label{display:block;font-weight:600;margin-bottom:4px}.lav-pm input[type=text],.lav-pm input[type=url],.lav-pm textarea{width:100%}.lav-pm .lav-pm-check label{font-weight:400}.lav-pm .description{display:block;color:#646970;margin-top:4px;font-size:12px}.lav-pm-gallery .lav-gallery-preview{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:0 0 10px;padding:0;list-style:none}.lav-pm-gallery .lav-gallery-preview li{position:relative;aspect-ratio:1;border-radius:6px;overflow:hidden;border:1px solid #dcdcde}.lav-pm-gallery .lav-gallery-preview img{width:100%;height:100%;object-fit:cover;display:block}.lav-pm-gallery .lav-gal-rm{position:absolute;top:2px;right:2px;width:20px;height:20px;border:none;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;cursor:pointer;line-height:1;font-size:14px}</style>';
}

function lavzen_pm_box_details( $post ): void {
	wp_nonce_field( 'lavzen_pm_save', 'lavzen_pm_nonce' );
	echo lavzen_pm_admin_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static style.
	echo '<div class="lav-pm"><div class="lav-pm-grid">';
	foreach ( lavzen_pm_text_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, $key, true );
		$cls = 'textarea' === $f['type'] ? 'lav-pm-row lav-pm-full' : 'lav-pm-row';
		echo '<p class="' . esc_attr( $cls ) . '"><label for="' . esc_attr( $key ) . '">' . esc_html( $f['label'] ) . '</label>';
		if ( 'textarea' === $f['type'] ) {
			echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="2" placeholder="' . esc_attr( $f['ph'] ) . '">' . esc_textarea( (string) $val ) . '</textarea>';
		} else {
			$itype = 'url' === $f['type'] ? 'url' : 'text';
			echo '<input type="' . esc_attr( $itype ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $val ) . '" placeholder="' . esc_attr( $f['ph'] ) . '">';
		}
		echo '</p>';
	}
	foreach ( lavzen_pm_check_fields() as $key => $label ) {
		$val = get_post_meta( $post->ID, $key, true );
		echo '<p class="lav-pm-row lav-pm-check"><label><input type="checkbox" name="' . esc_attr( $key ) . '" value="1" ' . checked( $val, '1', false ) . '> ' . esc_html( $label ) . '</label></p>';
	}
	echo '</div></div>';
}

function lavzen_pm_box_blocks( $post ): void {
	echo '<div class="lav-pm">';
	foreach ( lavzen_pm_block_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, $key, true );
		echo '<p class="lav-pm-row lav-pm-full"><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $f['label'] ) . '</strong></label>';
		echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="4" class="widefat">' . esc_textarea( (string) $val ) . '</textarea>';
		echo '<span class="description">' . esc_html( $f['help'] ) . '</span></p>';
	}
	echo '</div>';
}

function lavzen_pm_box_tabs( $post ): void {
	foreach ( lavzen_pm_tab_fields() as $key => $f ) {
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

function lavzen_pm_box_gallery( $post ): void {
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
	echo '<button type="button" class="button" id="lav_gallery_add">' . esc_html__( 'Select images', 'lavzentheme' ) . '</button>';
	echo '<p class="description">' . esc_html__( 'Images shown in the product gallery section.', 'lavzentheme' ) . '</p></div>';
	?>
	<script>
	( function( $ ) {
		if ( typeof wp === 'undefined' || ! wp.media ) { return; }
		var frame, $ids = $( '#lav_gallery_ids' ), $prev = $( '#lav_gallery_preview' );
		function sync() { var a = []; $prev.children( 'li' ).each( function() { a.push( $( this ).data( 'id' ) ); } ); $ids.val( a.join( ',' ) ); }
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

add_action(
	'save_post_download',
	static function ( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['lavzen_pm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lavzen_pm_nonce'] ) ), 'lavzen_pm_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || 'download' !== get_post_type( $post_id ) ) {
			return;
		}
		$set = static function ( $key, $clean ) use ( $post_id ) {
			if ( '' === trim( (string) $clean ) ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $clean );
			}
		};
		foreach ( lavzen_pm_text_fields() as $key => $f ) {
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
		foreach ( lavzen_pm_check_fields() as $key => $label ) {
			if ( ! empty( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, '1' );
			} else {
				delete_post_meta( $post_id, $key );
			}
		}
		foreach ( lavzen_pm_block_fields() as $key => $f ) {
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$set( $key, sanitize_textarea_field( $raw ) );
		}
		foreach ( lavzen_pm_tab_fields() as $key => $f ) {
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$set( $key, wp_kses_post( $raw ) );
		}
		$g   = isset( $_POST['_product_gallery_ids'] ) ? wp_unslash( $_POST['_product_gallery_ids'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) $g ) ) ) );
		if ( $ids ) {
			update_post_meta( $post_id, '_product_gallery_ids', implode( ',', $ids ) );
		} else {
			delete_post_meta( $post_id, '_product_gallery_ids' );
		}
	}
);

add_action(
	'admin_enqueue_scripts',
	static function ( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'download' === $screen->post_type ) {
			wp_enqueue_media();
		}
	}
);

/* ===================== front-end getters (used by the template) ============ */

function lavzen_pm_get( $id, $key, $default = '' ): string {
	$v = get_post_meta( $id, $key, true );
	return ( '' === $v || null === $v ) ? $default : (string) $v;
}

function lavzen_pm_is_on( $id, $key ): bool {
	return '1' === (string) get_post_meta( $id, $key, true );
}

function lavzen_pm_rows( $id, $key, $cols = 2, $max = 0 ): array {
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

function lavzen_pm_list( $id, $key, $max = 0 ): array {
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
