<?php
/**
 * Theme Code Studio — section / Global / Schema export (JSON download).
 *
 * Read-only: bundles a front-page section's SAVED editor content into a single
 * versioned JSON file and streams it to the browser as a download. Nothing is
 * ever modified here. Import is handled entirely on the client (parse + validate
 * + preview) and then persisted through the existing per-tab save endpoint, so
 * the PHP-syntax check, the LAVTHEME_ALLOW_PHP_SECTIONS lock, sanitisation and
 * the backup mechanism all still apply — Import never bypasses them.
 *
 * DYNAMIC BY DESIGN: the set of tabs is read at runtime from
 * `lavtheme_cs_fields()` — the very same function the panel UI loops over to
 * build the tab buttons. A tab added there in the future is exported/imported
 * automatically, with no change to this file. (Schema is the one pinned,
 * non-registry panel; its single `json` tab is its definition.)
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Export/import JSON format version. Bump only on a breaking schema change.
 */
if ( ! defined( 'LAVTHEME_CS_EXPORT_FORMAT' ) ) {
	define( 'LAVTHEME_CS_EXPORT_FORMAT', 1 );
}

/**
 * Label + (type => label) field map for an exportable section, read dynamically
 * from the same source the UI builds tabs from.
 *
 * @param string $section Section key (a registry slug, or the special 'schema').
 * @return array|null array( 'label' => string, 'fields' => array ), or null.
 */
function lavtheme_cs_export_fields( $section ) {
	if ( 'schema' === $section ) {
		return array(
			'label'  => __( 'Schema', 'lavtheme' ),
			'fields' => array( 'json' => 'JSON-LD Schema' ),
		);
	}

	$sections = lavtheme_cs_sections();
	if ( ! isset( $sections[ $section ] ) ) {
		return null;
	}

	return array(
		'label'  => $sections[ $section ]['label'],
		'fields' => lavtheme_cs_fields( $section ),
	);
}

/**
 * The saved (effective) value for one exportable field — the same content the
 * editor is seeded with on load (stored override, else the theme-file default).
 *
 * @param string $section Section key.
 * @param string $type    Field type.
 * @return string
 */
function lavtheme_cs_export_value( $section, $type ) {
	if ( 'schema' === $section ) {
		return (string) lavtheme_cs_get_schema();
	}
	return (string) lavtheme_cs_get_value( $section, $type );
}

/**
 * Build the versioned export payload for a section.
 *
 * The `tabs` map is built by iterating the section's real fields, so it always
 * matches exactly what that section has — no hardcoded tab list.
 *
 * @param string $section Section key.
 * @return array|null The payload, or null for an unknown section.
 */
function lavtheme_cs_export_payload( $section ) {
	$info = lavtheme_cs_export_fields( $section );
	if ( null === $info ) {
		return null;
	}

	$tabs = array();
	foreach ( $info['fields'] as $type => $label ) {
		$tabs[ $type ] = lavtheme_cs_export_value( $section, $type );
	}

	return array(
		'lavtheme_export' => true,
		'format_version'  => LAVTHEME_CS_EXPORT_FORMAT,
		'theme'           => 'lavtheme',
		'theme_version'   => defined( 'LAVTHEME_VERSION' ) ? LAVTHEME_VERSION : '',
		'exported_at'     => gmdate( 'Y-m-d\TH:i:s\Z' ),
		'context'         => 'front-page',
		'section'         => array(
			'slug'  => $section,
			'label' => $info['label'],
		),
		'tabs'            => $tabs,
	);
}

/**
 * AJAX (GET): stream a section export as a downloadable JSON file.
 *
 * Guarded by the same capability + nonce as every other studio action. Read-only.
 */
function lavtheme_cs_export_download() {
	if ( ! current_user_can( lavtheme_cs_cap() ) ) {
		wp_die( esc_html__( 'Permission denied.', 'lavtheme' ), '', array( 'response' => 403 ) );
	}

	$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'lavtheme_cs' ) ) {
		wp_die(
			esc_html__( 'This export link expired. Reload the Code Studio page and try again.', 'lavtheme' ),
			'',
			array( 'response' => 400 )
		);
	}

	$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
	$payload = lavtheme_cs_export_payload( $section );
	if ( null === $payload ) {
		wp_die( esc_html__( 'Unknown section.', 'lavtheme' ), '', array( 'response' => 400 ) );
	}

	$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$date = function_exists( 'wp_date' ) ? wp_date( 'Y-m-d' ) : gmdate( 'Y-m-d' );
	$name = 'lavtheme-front-page-' . sanitize_file_name( $section ) . '-' . $date . '.json';

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $name . '"' );
	header( 'X-Content-Type-Options: nosniff' );

	echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON attachment, not HTML.
	exit;
}
add_action( 'wp_ajax_lavtheme_cs_export', 'lavtheme_cs_export_download' );
