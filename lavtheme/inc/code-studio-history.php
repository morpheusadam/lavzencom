<?php
/**
 * Theme Code Studio — multi-revision history.
 *
 * Non-invasive: instead of touching every save handler, this hooks the generic
 * `updated_option` action and, whenever one of Code Studio's CONTENT options
 * changes, pushes the previous value onto a capped `{option}_revs` list. The
 * studio already keeps a single `_prev` for one-step Restore; this adds a deeper,
 * timestamped history alongside it without changing any existing behaviour.
 *
 * A read-only AJAX endpoint returns the revisions for the active editor; the Pro
 * JS loads a chosen revision INTO the editor (the user then Saves to apply), so
 * no extra write path is introduced.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LAVTHEME_CS_REV_MAX' ) ) {
	define( 'LAVTHEME_CS_REV_MAX', 15 );
}

/**
 * Is an option one of Code Studio's editable CONTENT fields (not a marker,
 * registry, backup or setting)?
 *
 * @param string $key Option name.
 * @return bool
 */
function lavtheme_cs_is_content_option( $key ) {
	if ( 0 !== strpos( $key, 'lavtheme_cs_' ) ) {
		return false;
	}
	foreach ( array( '_prev', '_empty', '_bak', '_revs' ) as $suffix ) {
		if ( substr( $key, -strlen( $suffix ) ) === $suffix ) {
			return false;
		}
	}
	if ( 0 === strpos( $key, 'lavtheme_cs_registry_' ) || 0 === strpos( $key, 'lavtheme_cs_pcbak_' ) ) {
		return false;
	}
	$settings = array( 'lavtheme_cs_mode', 'lavtheme_cs_minify', 'lavtheme_cs_header_global', 'lavtheme_cs_trash', 'lavtheme_cs_export_format' );
	if ( in_array( $key, $settings, true ) ) {
		return false;
	}
	return true;
}

/**
 * Capture the previous value of a content option into its revisions list.
 *
 * @param string $option Option name.
 * @param mixed  $old    Old value.
 * @param mixed  $value  New value.
 */
function lavtheme_cs_capture_revision( $option, $old, $value ) {
	if ( ! lavtheme_cs_is_content_option( $option ) ) {
		return;
	}
	if ( ! is_string( $old ) || '' === trim( $old ) ) {
		return; // nothing meaningful to preserve.
	}
	if ( is_string( $value ) && trim( $value ) === trim( $old ) ) {
		return; // unchanged.
	}
	$revs = get_option( $option . '_revs', array() );
	if ( ! is_array( $revs ) ) {
		$revs = array();
	}
	if ( ! empty( $revs ) && isset( $revs[0]['v'] ) && $revs[0]['v'] === $old ) {
		return; // already the most recent revision.
	}
	array_unshift( $revs, array( 't' => time(), 'v' => (string) $old ) );
	if ( count( $revs ) > LAVTHEME_CS_REV_MAX ) {
		$revs = array_slice( $revs, 0, LAVTHEME_CS_REV_MAX );
	}
	update_option( $option . '_revs', $revs, false );
}
add_action( 'updated_option', 'lavtheme_cs_capture_revision', 10, 3 );

/**
 * Resolve the option key for the active editor from the AJAX request scope.
 *
 * @return string Option name, or '' when the scope is invalid.
 */
function lavtheme_cs_history_key() {
	$scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( 'front' === $scope ) {
		$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'schema' === $section ) {
			return 'lavtheme_cs_schema';
		}
		return function_exists( 'lavtheme_cs_key' ) ? lavtheme_cs_key( $section, $type ) : '';
	}
	if ( 'page' === $scope ) {
		$id   = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return ( $id && function_exists( 'lavtheme_cs_page_key' ) ) ? lavtheme_cs_page_key( $id, $slug, $type ) : '';
	}
	if ( 'dl' === $scope ) {
		$ctx  = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return function_exists( 'lavtheme_cs_dl_key' ) ? lavtheme_cs_dl_key( $ctx, $slug, $type ) : '';
	}
	return '';
}

/**
 * AJAX: list the revision history for the active editor.
 */
function lavtheme_cs_ajax_history() {
	lavtheme_cs_guard();
	$key = lavtheme_cs_history_key();
	if ( '' === $key ) {
		wp_send_json_error( array( 'message' => __( 'Unknown editor.', 'lavtheme' ) ), 400 );
	}
	$revs = get_option( $key . '_revs', array() );
	if ( ! is_array( $revs ) ) {
		$revs = array();
	}
	$items = array();
	$now   = time();
	foreach ( $revs as $i => $r ) {
		$t = isset( $r['t'] ) ? (int) $r['t'] : 0;
		$v = isset( $r['v'] ) ? (string) $r['v'] : '';
		$items[] = array(
			'i'       => $i,
			'ago'     => $t ? sprintf( /* translators: %s: human time difference. */ __( '%s ago', 'lavtheme' ), human_time_diff( $t, $now ) ) : '',
			'when'    => $t ? gmdate( 'Y-m-d H:i', $t ) : '',
			'chars'   => strlen( $v ),
			'lines'   => substr_count( $v, "\n" ) + 1,
			'content' => $v,
		);
	}
	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_lavtheme_cs_history', 'lavtheme_cs_ajax_history' );
