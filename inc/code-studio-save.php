<?php
/**
 * Theme Code Studio — save / restore handlers (AJAX).
 *
 * Mode "db"  : content stored in wp_options and injected live (never fatals).
 * Mode "file": HTML/PHP is written to the theme file on disk, guarded by the
 *              LAVTHEME_ALLOW_FILE_WRITE constant, with an automatic backup and
 *              a PHP syntax check before writing.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shared guard for every Code Studio AJAX call.
 */
function lavtheme_cs_guard() {
	if ( ! current_user_can( lavtheme_cs_cap() ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lavtheme' ) ), 403 );
	}
	if ( ! check_ajax_referer( 'lavtheme_cs', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'lavtheme' ) ), 400 );
	}
}

/**
 * Validate a section key and return its definition, or send an error.
 *
 * @param string $section Section key.
 * @return array
 */
function lavtheme_cs_require_section( $section ) {
	$sections = lavtheme_cs_sections();
	if ( ! isset( $sections[ $section ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown section.', 'lavtheme' ) ), 400 );
	}
	return $sections[ $section ];
}

/**
 * AJAX: switch save mode.
 */
function lavtheme_cs_ajax_setmode() {
	lavtheme_cs_guard();
	$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'db';
	if ( 'file' === $mode && ! lavtheme_cs_file_allowed() ) {
		wp_send_json_error( array( 'message' => __( 'File mode is locked by wp-config.', 'lavtheme' ) ), 403 );
	}
	update_option( 'lavtheme_cs_mode', 'file' === $mode ? 'file' : 'db' );
	wp_send_json_success( array( 'mode' => lavtheme_cs_mode() ) );
}
add_action( 'wp_ajax_lavtheme_cs_setmode', 'lavtheme_cs_ajax_setmode' );

/**
 * AJAX: flip a boolean toggle (minify / header_global).
 */
function lavtheme_cs_ajax_toggle() {
	lavtheme_cs_guard();
	$which = isset( $_POST['which'] ) ? sanitize_key( wp_unslash( $_POST['which'] ) ) : '';
	$on    = ( isset( $_POST['on'] ) && '1' === (string) wp_unslash( $_POST['on'] ) ) ? '1' : '0';

	$map = array(
		'minify'        => 'lavtheme_cs_minify',
		'header_global' => 'lavtheme_cs_header_global',
	);
	if ( ! isset( $map[ $which ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown toggle.', 'lavtheme' ) ), 400 );
	}
	update_option( $map[ $which ], $on );
	wp_send_json_success( array( 'which' => $which, 'on' => $on ) );
}
add_action( 'wp_ajax_lavtheme_cs_toggle', 'lavtheme_cs_ajax_toggle' );

/**
 * AJAX: save one editor's content.
 */
function lavtheme_cs_ajax_save() {
	lavtheme_cs_guard();

	$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
	$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	// Raw content: do NOT sanitize_text_field (it would destroy code). Unslash only.
	$content = isset( $_POST['content'] ) ? (string) wp_unslash( $_POST['content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	// Schema is a special (non-registry) editor: validate JSON, store, done.
	if ( 'schema' === $section ) {
		$trimmed = trim( $content );
		if ( '' !== $trimmed ) {
			json_decode( $trimmed );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				wp_send_json_error( array( 'message' => __( 'Invalid JSON: ', 'lavtheme' ) . json_last_error_msg() ) );
			}
		}
		update_option( 'lavtheme_cs_schema', $trimmed );
		wp_send_json_success( array( 'message' => __( 'Schema saved.', 'lavtheme' ) ) );
	}

	// Per-context (EDD / Woo page) custom layer: css / js / html_before / html_after.
	if ( 0 === strpos( $section, 'ctx-' ) ) {
		$ckey     = substr( $section, 4 );
		$contexts = function_exists( 'lavtheme_cs_contexts' ) ? lavtheme_cs_contexts() : array();
		if ( ! isset( $contexts[ $ckey ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown context.', 'lavtheme' ) ), 400 );
		}
		if ( ! in_array( $type, array( 'css', 'js', 'html_before', 'html_after' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown field.', 'lavtheme' ) ), 400 );
		}
		if ( 'css' === $type ) {
			$clean = lavtheme_sanitize_css( $content );
		} elseif ( 'js' === $type ) {
			$clean = str_ireplace( '</script', '<\/script', $content );
		} else {
			// HTML before/after — stored raw, rendered through wp_kses at output.
			$clean = (string) $content;
		}
		lavtheme_cs_ctx_set( $ckey, $type, $clean );
		wp_send_json_success( array( 'message' => __( 'Saved.', 'lavtheme' ), 'context' => $ckey ) );
	}

	$def    = lavtheme_cs_require_section( $section );
	$fields = lavtheme_cs_fields( $section );
	if ( ! isset( $fields[ $type ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown editor type.', 'lavtheme' ) ), 400 );
	}

	// PHP tab → syntax-check + backup; stored even when locked, run only if unlocked.
	if ( 'php' === $type ) {
		$err = '';
		if ( '' !== trim( $content ) && ! lavtheme_cs_check_php( $content, $err ) ) {
			wp_send_json_error( array( 'message' => __( 'PHP syntax error — not saved: ', 'lavtheme' ) . $err ) );
		}
		$key = lavtheme_cs_key( $section, 'php' );
		update_option( $key . '_bak', get_option( $key, '' ) );
		update_option( $key, (string) $content );
		$msg = lavtheme_cs_php_allowed()
			? __( 'PHP saved and active.', 'lavtheme' )
			: __( 'PHP saved, but NOT running — add define(\'LAVTHEME_ALLOW_PHP_SECTIONS\', true) to wp-config.php to enable it.', 'lavtheme' );
		wp_send_json_success( array( 'message' => $msg ) );
	}

	// HTML/PHP in File mode → write the file on disk.
	if ( 'html' === $type && 'file' === lavtheme_cs_mode() ) {
		if ( ! lavtheme_cs_file_allowed() ) {
			wp_send_json_error( array( 'message' => __( 'File mode is locked.', 'lavtheme' ) ), 403 );
		}
		$err  = '';
		$ok   = lavtheme_cs_write_file( $def['file'], $content, $err );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => $err ) );
		}
		// Drop any DB override so the file is the single source of truth.
		delete_option( lavtheme_cs_key( $section, 'html' ) );
		wp_send_json_success( array( 'message' => __( 'File written.', 'lavtheme' ), 'mode' => 'file' ) );
	}

	// Otherwise store in the database (sanitised by type).
	$key     = lavtheme_cs_key( $section, $type );
	$clean   = lavtheme_cs_sanitize( $content, $type );
	$default = lavtheme_cs_default_value( $section, $type );

	// Empty content needs care — distinguish two intents:
	//  - HTML/markup: an empty save falls back to the file (never blank a section;
	//    also keeps dynamic sections like Products/Blog rendering their PHP — Bug 3).
	//  - CSS/JS/Mobile: honour an EXPLICIT clear. Store an "_empty" marker so render
	//    injects nothing and does NOT fall back to the file default — otherwise an
	//    intentionally-cleared style/script silently reappears from the file.
	if ( '' === trim( $clean ) ) {
		if ( 'html' === $type ) {
			delete_option( $key );
			delete_option( $key . '_prev' );
			delete_option( $key . '_empty' );
			wp_send_json_success( array( 'message' => __( 'Matches default — using the theme file.', 'lavtheme' ), 'mode' => 'db' ) );
		}
		$prev = get_option( $key, '' );
		update_option( $key . '_prev', $prev );
		update_option( $key, '' );
		update_option( $key . '_empty', '1' );
		wp_send_json_success( array( 'message' => __( 'Cleared — this tab now outputs nothing. Use “Reset to default” to bring the file default back.', 'lavtheme' ), 'mode' => 'db', 'emptied' => true ) );
	}

	// Identical to the real file default → drop the override so the file is the
	// single source of truth (and clear any previous explicit-empty marker).
	if ( trim( $clean ) === trim( $default ) ) {
		delete_option( $key );
		delete_option( $key . '_prev' );
		delete_option( $key . '_empty' );
		wp_send_json_success( array( 'message' => __( 'Matches default — using the theme file.', 'lavtheme' ), 'mode' => 'db' ) );
	}

	$prev = get_option( $key, '' );
	update_option( $key . '_prev', $prev );
	update_option( $key, $clean );
	delete_option( $key . '_empty' );

	wp_send_json_success( array( 'message' => __( 'Saved.', 'lavtheme' ), 'mode' => 'db' ) );
}
add_action( 'wp_ajax_lavtheme_cs_save', 'lavtheme_cs_ajax_save' );

/**
 * Sanitise editor content for database storage by type.
 *
 * @param string $content Raw content.
 * @param string $type    Editor type.
 * @return string
 */
function lavtheme_cs_sanitize( $content, $type ) {
	switch ( $type ) {
		case 'css':
		case 'mcss':
		case 'root':
		case 'bg':
			return lavtheme_sanitize_css( $content );
		case 'js':
			// Trusted (manage_options); neutralise a premature closing tag.
			return str_ireplace( '</script', '<\/script', (string) $content );
		case 'html':
		default:
			// Stored raw; rendered through lavtheme_kses_extended() at output time.
			return (string) $content;
	}
}

/**
 * Validate PHP syntax without executing.
 *
 * @param string $code  Code (may be mixed HTML/PHP).
 * @param string $error Filled with the parse error message on failure.
 * @return bool
 */
function lavtheme_cs_php_syntax_ok( $code, &$error ) {
	$error = '';

	// Prefer php -l when shell access is available.
	if ( function_exists( 'shell_exec' ) ) {
		$disabled = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );
		if ( ! in_array( 'shell_exec', $disabled, true ) ) {
			$tmp = wp_tempnam( 'lavcs' );
			if ( $tmp ) {
				file_put_contents( $tmp, $code ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents
				$out = shell_exec( 'php -l ' . escapeshellarg( $tmp ) . ' 2>&1' );
				wp_delete_file( $tmp );
				if ( null !== $out && '' !== trim( (string) $out ) ) {
					if ( false !== stripos( $out, 'No syntax errors' ) ) {
						return true;
					}
					if ( false !== stripos( $out, 'error' ) ) {
						$error = trim( (string) $out );
						return false;
					}
				}
			}
		}
	}

	// Fallback: token_get_all with TOKEN_PARSE throws ParseError on bad syntax.
	if ( function_exists( 'token_get_all' ) && defined( 'TOKEN_PARSE' ) ) {
		try {
			token_get_all( $code, TOKEN_PARSE );
			return true;
		} catch ( \ParseError $e ) {
			$error = $e->getMessage();
			return false;
		} catch ( \Throwable $e ) {
			$error = $e->getMessage();
			return false;
		}
	}

	return true;
}

/**
 * Is this relative path one we are allowed to write?
 *
 * @param string $relpath Theme-relative path.
 * @return bool
 */
function lavtheme_cs_is_writable_target( $relpath ) {
	$allowed = array();
	foreach ( lavtheme_cs_sections() as $section ) {
		if ( ! empty( $section['file'] ) ) {
			$allowed[] = $section['file'];
		}
	}
	// Normalise and block traversal.
	if ( false !== strpos( $relpath, '..' ) ) {
		return false;
	}
	return in_array( $relpath, $allowed, true );
}

/**
 * Copy a theme file into a timestamped backup folder.
 *
 * @param string $relpath Theme-relative path.
 * @return string|false Timestamp on success.
 */
function lavtheme_cs_backup_file( $relpath ) {
	$src = get_theme_file_path( $relpath );
	if ( ! is_readable( $src ) ) {
		return false;
	}
	$stamp = gmdate( 'Ymd-His' );
	$dir   = LAVTHEME_DIR . '.backups/' . $stamp . '/' . dirname( $relpath );
	wp_mkdir_p( $dir );
	$dest = LAVTHEME_DIR . '.backups/' . $stamp . '/' . $relpath;
	return copy( $src, $dest ) ? $stamp : false;
}

/**
 * Write a theme file with a backup + syntax check.
 *
 * @param string $relpath Theme-relative path.
 * @param string $content New content.
 * @param string $error   Filled with an error message on failure.
 * @return bool
 */
function lavtheme_cs_write_file( $relpath, $content, &$error ) {
	$error = '';

	if ( ! lavtheme_cs_is_writable_target( $relpath ) ) {
		$error = __( 'This file is not writable through the studio.', 'lavtheme' );
		return false;
	}

	if ( ! lavtheme_cs_php_syntax_ok( $content, $error ) ) {
		$error = __( 'PHP syntax error — not saved: ', 'lavtheme' ) . $error;
		return false;
	}

	$path = get_theme_file_path( $relpath );
	if ( ! is_writable( $path ) && file_exists( $path ) ) {
		$error = __( 'File is not writable (check permissions).', 'lavtheme' );
		return false;
	}

	lavtheme_cs_backup_file( $relpath );

	if ( false === file_put_contents( $path, $content ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents
		$error = __( 'Write failed.', 'lavtheme' );
		return false;
	}

	return true;
}

/**
 * AJAX: list available backups (file mode) or the previous DB value (db mode).
 */
function lavtheme_cs_ajax_backups() {
	lavtheme_cs_guard();
	$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
	$def     = lavtheme_cs_require_section( $section );
	$mode    = lavtheme_cs_mode();
	$items   = array();

	if ( 'file' === $mode && ! empty( $def['file'] ) ) {
		$base = LAVTHEME_DIR . '.backups';
		if ( is_dir( $base ) ) {
			foreach ( array_diff( (array) scandir( $base ), array( '.', '..' ) ) as $stamp ) {
				if ( is_readable( $base . '/' . $stamp . '/' . $def['file'] ) ) {
					$items[] = array(
						'stamp' => $stamp,
						'label' => $stamp,
					);
				}
			}
			rsort( $items );
		}
	} else {
		// DB mode: a single previous value if present.
		if ( '' !== get_option( lavtheme_cs_key( $section, 'html' ) . '_prev', '' )
			|| '' !== get_option( lavtheme_cs_key( $section, 'css' ) . '_prev', '' ) ) {
			$items[] = array(
				'stamp' => 'prev',
				'label' => __( 'Previous saved value', 'lavtheme' ),
			);
		}
	}

	wp_send_json_success( array( 'mode' => $mode, 'items' => $items ) );
}
add_action( 'wp_ajax_lavtheme_cs_backups', 'lavtheme_cs_ajax_backups' );

/**
 * AJAX: restore a backup (file mode) or the previous DB value.
 */
function lavtheme_cs_ajax_restore() {
	lavtheme_cs_guard();
	$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
	$stamp   = isset( $_POST['stamp'] ) ? sanitize_file_name( wp_unslash( $_POST['stamp'] ) ) : '';
	$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'html';
	$def     = lavtheme_cs_require_section( $section );

	if ( 'file' === lavtheme_cs_mode() && ! empty( $def['file'] ) && 'prev' !== $stamp ) {
		$backup = LAVTHEME_DIR . '.backups/' . $stamp . '/' . $def['file'];
		if ( ! is_readable( $backup ) ) {
			wp_send_json_error( array( 'message' => __( 'Backup not found.', 'lavtheme' ) ) );
		}
		$content = (string) file_get_contents( $backup ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents
		$err     = '';
		if ( ! lavtheme_cs_write_file( $def['file'], $content, $err ) ) {
			wp_send_json_error( array( 'message' => $err ) );
		}
		wp_send_json_success( array( 'content' => $content, 'message' => __( 'Restored.', 'lavtheme' ) ) );
	}

	// DB mode: swap back the previous value.
	$key  = lavtheme_cs_key( $section, $type );
	$prev = get_option( $key . '_prev', null );
	if ( null === $prev ) {
		wp_send_json_error( array( 'message' => __( 'Nothing to restore.', 'lavtheme' ) ) );
	}
	$cur = get_option( $key, '' );
	update_option( $key, $prev );
	update_option( $key . '_prev', $cur );
	wp_send_json_success( array( 'content' => $prev, 'message' => __( 'Restored.', 'lavtheme' ) ) );
}
add_action( 'wp_ajax_lavtheme_cs_restore', 'lavtheme_cs_ajax_restore' );

/**
 * AJAX: reset the active tab to the theme file default.
 *
 * Drops the DB override AND the explicit-empty marker so render falls back to the
 * real file default again. The current value is snapshotted to `_prev` first, so
 * the change is still undoable via Restore. Read of the default uses the same
 * source as the editors (file default / schema default).
 */
function lavtheme_cs_ajax_reset() {
	lavtheme_cs_guard();
	$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
	$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';

	// Schema is a special (non-registry) editor.
	if ( 'schema' === $section ) {
		$cur = get_option( 'lavtheme_cs_schema', null );
		if ( null !== $cur ) {
			update_option( 'lavtheme_cs_schema_prev', $cur );
		}
		delete_option( 'lavtheme_cs_schema' );
		wp_send_json_success(
			array(
				'content' => lavtheme_cs_schema_default(),
				'message' => __( 'Reset to default.', 'lavtheme' ),
			)
		);
	}

	lavtheme_cs_require_section( $section );
	$fields = lavtheme_cs_fields( $section );
	if ( ! isset( $fields[ $type ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown editor type.', 'lavtheme' ) ), 400 );
	}

	$key = lavtheme_cs_key( $section, $type );
	$cur = get_option( $key, null );
	if ( null !== $cur ) {
		update_option( $key . '_prev', $cur ); // keep a one-step undo (Restore).
	}
	delete_option( $key );
	delete_option( $key . '_empty' );

	wp_send_json_success(
		array(
			'content' => lavtheme_cs_default_value( $section, $type ),
			'message' => __( 'Reset to the theme file default.', 'lavtheme' ),
		)
	);
}
add_action( 'wp_ajax_lavtheme_cs_reset', 'lavtheme_cs_ajax_reset' );
