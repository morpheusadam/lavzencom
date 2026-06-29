<?php
/**
 * Section_Store — the single data layer for all Code Studio overrides.
 *
 * Replaces the THREE parallel CRUD families of the legacy theme
 * (lavtheme_cs_* / lavtheme_cs_dl_* / lavtheme_cs_page_*) with one store keyed by
 * a (scope, section, type) triple:
 *
 *   scope   : 'global' | 'ctx:{context}' (single|404|shop|blog|account|auth|dl|wp-dash) | 'page:{id}'
 *   section : a section slug within the scope ('global', 'hero', 'header', 'design', …)
 *   type    : the editor field ('html'|'css'|'js'|'mcss'|'php'|'root'|'bg'|'schema')
 *
 * Architectural fix (from the audit): overrides are stored with autoload=FALSE.
 * They are only needed when rendering their specific scope, never on every
 * request — so they no longer bloat the autoloaded options (the legacy store put
 * ~192 KB on every page load). A one-step previous value supports Restore, and an
 * explicit-empty marker distinguishes "intentionally cleared" from "no override".
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Code_Studio;

defined( 'ABSPATH' ) || exit;

final class Section_Store {

	private const PREFIX = 'lavzen_cs_';

	/** Editor field types this store accepts. */
	public const TYPES = array( 'html', 'css', 'js', 'mcss', 'php', 'root', 'bg', 'schema' );

	/**
	 * Build the option name for a (scope, section, type) triple.
	 *
	 * @param string $scope   e.g. 'global', 'ctx:404', 'page:42'.
	 * @param string $section Section slug.
	 * @param string $type    Field type.
	 * @param string $suffix  Optional sub-key ('prev' | 'empty').
	 */
	private function key( string $scope, string $section, string $type, string $suffix = '' ): string {
		$scope   = str_replace( ':', '_', sanitize_key( $scope ) );
		$section = sanitize_key( $section );
		$type    = sanitize_key( $type );
		$base    = self::PREFIX . $scope . '__' . $section . '__' . $type;
		return '' === $suffix ? $base : $base . '_' . $suffix;
	}

	/**
	 * Whether an override exists for this triple (an intentional clear counts).
	 */
	public function has( string $scope, string $section, string $type ): bool {
		return null !== get_option( $this->key( $scope, $section, $type ), null )
			|| $this->is_empty( $scope, $section, $type );
	}

	/**
	 * Get the stored override, or null when there is none.
	 *
	 * Returns '' (empty string) when the field was intentionally cleared — the
	 * caller should then inject nothing rather than fall back to the file default.
	 */
	public function get( string $scope, string $section, string $type ): ?string {
		if ( $this->is_empty( $scope, $section, $type ) ) {
			return '';
		}
		$value = get_option( $this->key( $scope, $section, $type ), null );
		return null === $value ? null : (string) $value;
	}

	/**
	 * Store an override (non-autoloaded). Snapshots the previous value for Restore
	 * and clears any explicit-empty marker.
	 */
	public function set( string $scope, string $section, string $type, string $value ): void {
		$this->snapshot( $scope, $section, $type );
		update_option( $this->key( $scope, $section, $type ), $value, false );
		delete_option( $this->key( $scope, $section, $type, 'empty' ) );
	}

	/**
	 * Mark a field as intentionally cleared (inject nothing; do not fall back to
	 * the file default). Snapshots the previous value first.
	 */
	public function mark_empty( string $scope, string $section, string $type ): void {
		$this->snapshot( $scope, $section, $type );
		delete_option( $this->key( $scope, $section, $type ) );
		update_option( $this->key( $scope, $section, $type, 'empty' ), '1', false );
	}

	/**
	 * Is this field an intentional clear?
	 */
	public function is_empty( string $scope, string $section, string $type ): bool {
		return '1' === (string) get_option( $this->key( $scope, $section, $type, 'empty' ), '' );
	}

	/**
	 * Drop the override entirely (Reset to file default). Keeps a one-step undo.
	 */
	public function reset( string $scope, string $section, string $type ): void {
		$this->snapshot( $scope, $section, $type );
		delete_option( $this->key( $scope, $section, $type ) );
		delete_option( $this->key( $scope, $section, $type, 'empty' ) );
	}

	/**
	 * Snapshot the current value into the one-step "prev" slot (non-autoloaded).
	 */
	public function snapshot( string $scope, string $section, string $type ): void {
		$current = get_option( $this->key( $scope, $section, $type ), null );
		if ( null !== $current ) {
			update_option( $this->key( $scope, $section, $type, 'prev' ), $current, false );
		}
	}

	/**
	 * Restore the previous value (one-step undo). Returns the restored value, or
	 * null when there is nothing to restore.
	 */
	public function restore_prev( string $scope, string $section, string $type ): ?string {
		$prev = get_option( $this->key( $scope, $section, $type, 'prev' ), null );
		if ( null === $prev ) {
			return null;
		}
		$current = get_option( $this->key( $scope, $section, $type ), '' );
		update_option( $this->key( $scope, $section, $type ), $prev, false );
		update_option( $this->key( $scope, $section, $type, 'prev' ), $current, false );
		delete_option( $this->key( $scope, $section, $type, 'empty' ) );
		return (string) $prev;
	}
}
