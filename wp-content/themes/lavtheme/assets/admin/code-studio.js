/* Theme Code Studio — admin logic: CodeMirror, tabs, AJAX save/restore. */
( function ( $ ) {
	'use strict';

	if ( typeof LavthemeCS === 'undefined' ) {
		return;
	}

	var editors = {};

	function key( section, type ) { return section + '|' + type; }

	function initEditor( ta ) {
		var sec = ta.getAttribute( 'data-section' );
		var type = ta.getAttribute( 'data-type' );
		var mime = ta.getAttribute( 'data-mode' ) || 'text/css';
		var k = key( sec, type );
		if ( editors[ k ] ) {
			return editors[ k ];
		}
		var content = ( LavthemeCS.content[ sec ] && typeof LavthemeCS.content[ sec ][ type ] !== 'undefined' )
			? LavthemeCS.content[ sec ][ type ] : '';
		ta.value = content;

		var settings = ( LavthemeCS.cm && LavthemeCS.cm[ mime ] ) ? LavthemeCS.cm[ mime ] : null;
		var ed = null;
		if ( window.wp && wp.codeEditor && settings ) {
			try {
				ed = wp.codeEditor.initialize( ta, settings );
			} catch ( e ) {
				ed = null;
			}
		}
		editors[ k ] = ed ? { cm: ed.codemirror } : { ta: ta };
		// VS Code-like autocomplete on Ctrl-Space.
		if ( ed && ed.codemirror ) {
			try {
				var ek = ed.codemirror.getOption( 'extraKeys' ) || {};
				ek[ 'Ctrl-Space' ] = 'autocomplete';
				ed.codemirror.setOption( 'extraKeys', ek );
			} catch ( e ) {}
		}
		return editors[ k ];
	}

	function getVal( k ) {
		var e = editors[ k ];
		if ( ! e ) { return ''; }
		return e.cm ? e.cm.getValue() : e.ta.value;
	}

	function setVal( k, v ) {
		var e = editors[ k ];
		if ( ! e ) { return; }
		if ( e.cm ) { e.cm.setValue( v ); } else { e.ta.value = v; }
		if ( LavthemeCS.content ) {
			var p = k.split( '|' );
			if ( ! LavthemeCS.content[ p[ 0 ] ] ) { LavthemeCS.content[ p[ 0 ] ] = {}; }
			LavthemeCS.content[ p[ 0 ] ][ p[ 1 ] ] = v;
		}
	}

	function activeEditorEl( $panel ) {
		return $panel.find( '.lavcs-editorwrap.is-active .lavcs-editor' ).get( 0 );
	}

	function refreshActive( $panel ) {
		var ta = activeEditorEl( $panel );
		if ( ! ta ) { return; }
		var e = initEditor( ta );
		if ( e && e.cm ) {
			setTimeout( function () { e.cm.refresh(); }, 10 );
		}
	}

	function status( $panel, text, ok ) {
		var $s = $panel.find( '.lavcs-status' );
		$s.text( text ).attr( 'data-state', ok ? 'ok' : 'err' );
		if ( ok ) {
			setTimeout( function () { $s.text( '' ).removeAttr( 'data-state' ); }, 2500 );
		}
	}

	$( function () {

		// Left nav → switch section panel.
		$( '.lavcs-navitem' ).on( 'click', function () {
			var sec = $( this ).data( 'section' );
			$( '.lavcs-navitem' ).removeClass( 'is-active' );
			$( this ).addClass( 'is-active' );
			$( '.lavcs-panel' ).removeClass( 'is-active' );
			var $panel = $( '.lavcs-panel[data-section="' + sec + '"]' ).addClass( 'is-active' );
			refreshActive( $panel );
		} );

		// Tabs within a panel (delegated so dynamically built page panels work).
		$( document ).on( 'click', '.lavcs-tab', function () {
			var $panel = $( this ).closest( '.lavcs-panel' );
			var type = $( this ).data( 'type' );
			$panel.find( '.lavcs-tab' ).removeClass( 'is-active' );
			$( this ).addClass( 'is-active' );
			$panel.find( '.lavcs-editorwrap' ).removeClass( 'is-active' );
			$panel.find( '.lavcs-editorwrap[data-type="' + type + '"]' ).addClass( 'is-active' );
			refreshActive( $panel );
		} );

		// Fullscreen toggle (delegated).
		$( document ).on( 'click', '.lavcs-fullscreen', function () {
			var $panel = $( this ).closest( '.lavcs-panel' );
			$panel.toggleClass( 'is-fullscreen' );
			$( 'body' ).toggleClass( 'lavcs-noscroll', $panel.hasClass( 'is-fullscreen' ) );
			refreshActive( $panel );
		} );

		// Save.
		$( '.lavcs-save' ).on( 'click', function () {
			var $panel = $( this ).closest( '.lavcs-panel' );
			var sec = $( this ).data( 'section' );
			var ta = activeEditorEl( $panel );
			if ( ! ta ) { return; }
			var type = ta.getAttribute( 'data-type' );
			var k = key( sec, type );
			initEditor( ta ); // make sure the editor is initialised before reading it.
			if ( editors[ k ] && editors[ k ].cm ) { editors[ k ].cm.save(); }

			// Validate JSON before saving the Schema editor.
			if ( type === 'json' ) {
				var v = $.trim( getVal( k ) );
				if ( v !== '' ) {
					try { JSON.parse( v ); } catch ( err ) {
						status( $panel, LavthemeCS.i18n.badJson + ' ' + err.message, false );
						return;
					}
				}
			}

			status( $panel, LavthemeCS.i18n.saving, true );
			$.post( LavthemeCS.ajaxUrl, {
				action: 'lavtheme_cs_save',
				nonce: LavthemeCS.nonce,
				section: sec,
				type: type,
				content: getVal( k )
			} ).done( function ( res ) {
				if ( res && res.success ) {
					// Sync the saved snapshot so Export's unsaved-change check is accurate.
					if ( ! LavthemeCS.content[ sec ] ) { LavthemeCS.content[ sec ] = {}; }
					LavthemeCS.content[ sec ][ type ] = getVal( k );
					status( $panel, LavthemeCS.i18n.saved, true );
				} else {
					status( $panel, ( res && res.data && res.data.message ) || LavthemeCS.i18n.error, false );
				}
			} ).fail( function () {
				status( $panel, LavthemeCS.i18n.error, false );
			} );
		} );

		// Export → download every tab of this section's SAVED content as one file.
		function triggerDownload( url ) {
			var a = document.createElement( 'a' );
			a.href = url;
			a.download = '';
			a.style.display = 'none';
			document.body.appendChild( a );
			a.click();
			setTimeout( function () { document.body.removeChild( a ); }, 0 );
		}

		$( '.lavcs-export' ).on( 'click', function () {
			var sec = $( this ).data( 'section' );
			var $panel = $( this ).closest( '.lavcs-panel' );

			// Warn if any initialised editor in this section differs from its saved snapshot.
			var dirty = false;
			$panel.find( '.lavcs-editor' ).each( function () {
				var t = this.getAttribute( 'data-type' );
				var k = key( sec, t );
				if ( ! editors[ k ] ) { return; }
				var saved = ( LavthemeCS.content[ sec ] && typeof LavthemeCS.content[ sec ][ t ] !== 'undefined' )
					? LavthemeCS.content[ sec ][ t ] : '';
				if ( getVal( k ) !== saved ) { dirty = true; }
			} );
			if ( dirty && ! window.confirm( LavthemeCS.i18n.unsavedExport ) ) { return; }

			triggerDownload(
				LavthemeCS.ajaxUrl + '?action=lavtheme_cs_export' +
				'&section=' + encodeURIComponent( sec ) +
				'&nonce=' + encodeURIComponent( LavthemeCS.nonce )
			);
		} );

		// ---- Import: load a lavtheme JSON export into the current section ----
		// Dynamic: the destination's real tabs are read from the panel DOM (built
		// from lavtheme_cs_fields), and only matching tabs are filled. Applying
		// reuses the existing per-tab save endpoint, so the PHP syntax check, the
		// PHP-sections lock, sanitisation and the _prev backup all still run.
		var importTarget = null;

		function sectionDestTabs( $panel ) {
			var tabs = [];
			$panel.find( '.lavcs-editor' ).each( function () {
				var t = this.getAttribute( 'data-type' );
				if ( t && tabs.indexOf( t ) === -1 ) { tabs.push( t ); }
			} );
			return tabs;
		}

		function applyImport( sec, $panel, tabs, types ) {
			var pending = types.length;
			var failed = [];
			status( $panel, LavthemeCS.i18n.saving, true );
			types.forEach( function ( type ) {
				$.post( LavthemeCS.ajaxUrl, {
					action: 'lavtheme_cs_save',
					nonce: LavthemeCS.nonce,
					section: sec,
					type: type,
					content: tabs[ type ]
				} ).done( function ( res ) {
					if ( res && res.success ) {
						if ( ! LavthemeCS.content[ sec ] ) { LavthemeCS.content[ sec ] = {}; }
						LavthemeCS.content[ sec ][ type ] = tabs[ type ];
					} else {
						failed.push( type + ': ' + ( ( res && res.data && res.data.message ) || LavthemeCS.i18n.error ) );
					}
				} ).fail( function () {
					failed.push( type );
				} ).always( function () {
					pending--;
					if ( pending === 0 ) {
						if ( failed.length ) {
							status( $panel, LavthemeCS.i18n.error + ' — ' + failed.join( '; ' ), false );
						} else {
							status( $panel, LavthemeCS.i18n.imported, true );
						}
					}
				} );
			} );
		}

		function handleImport( text, target ) {
			var sec = target.sec;
			var $panel = target.$panel;
			var data;
			try { data = JSON.parse( text ); } catch ( e ) {
				window.alert( LavthemeCS.i18n.importBadJson );
				return;
			}
			if ( ! data || data.lavtheme_export !== true || ! data.tabs || typeof data.tabs !== 'object' ) {
				window.alert( LavthemeCS.i18n.importBadFile );
				return;
			}
			if ( parseInt( data.format_version, 10 ) > ( LavthemeCS.exportFormat || 1 ) ) {
				window.alert( LavthemeCS.i18n.importBadVer );
				return;
			}

			var dest = sectionDestTabs( $panel );
			var fileTabs = Object.keys( data.tabs );
			var matched = fileTabs.filter( function ( t ) { return dest.indexOf( t ) !== -1; } );
			var skipped = fileTabs.filter( function ( t ) { return dest.indexOf( t ) === -1; } );
			if ( ! matched.length ) {
				window.alert( LavthemeCS.i18n.importNoMatch );
				return;
			}

			// Snapshot current editor values so the preview can be reverted on cancel.
			var snapshot = {};
			matched.forEach( function ( type ) {
				var ta = $panel.find( '.lavcs-editor[data-type="' + type + '"]' ).get( 0 );
				if ( ta ) { initEditor( ta ); snapshot[ type ] = getVal( key( sec, type ) ); }
			} );

			// Preview: load the imported values into the editors so the user sees them.
			matched.forEach( function ( type ) {
				var ta = $panel.find( '.lavcs-editor[data-type="' + type + '"]' ).get( 0 );
				if ( ta ) { initEditor( ta ); setVal( key( sec, type ), String( data.tabs[ type ] ) ); }
			} );
			$panel.find( '.lavcs-tab[data-type="' + matched[ 0 ] + '"]' ).trigger( 'click' );

			var msg = LavthemeCS.i18n.importConfirm;
			if ( skipped.length ) { msg += '\n\n' + LavthemeCS.i18n.importSkipped + ' ' + skipped.join( ', ' ); }

			if ( ! window.confirm( msg ) ) {
				// Revert the preview to what was there before.
				matched.forEach( function ( type ) { setVal( key( sec, type ), snapshot[ type ] ); } );
				status( $panel, LavthemeCS.i18n.importCancel, true );
				return;
			}
			applyImport( sec, $panel, data.tabs, matched );
		}

		$( '.lavcs-import' ).on( 'click', function () {
			importTarget = { sec: $( this ).data( 'section' ), $panel: $( this ).closest( '.lavcs-panel' ) };
			var fi = $( '.lavcs-import-file' ).get( 0 );
			if ( fi ) { fi.value = ''; fi.click(); }
		} );

		$( '.lavcs-import-file' ).on( 'change', function () {
			var f = this.files && this.files[ 0 ];
			if ( ! f || ! importTarget ) { return; }
			var reader = new FileReader();
			var target = importTarget;
			reader.onload = function ( e ) { handleImport( String( e.target.result ), target ); };
			reader.onerror = function () { window.alert( LavthemeCS.i18n.importBadJson ); };
			reader.readAsText( f );
		} );

		// Reset the ACTIVE tab to its theme-file default (separate from Save).
		$( '.lavcs-reset' ).on( 'click', function () {
			var sec = $( this ).data( 'section' );
			var $panel = $( this ).closest( '.lavcs-panel' );
			var ta = activeEditorEl( $panel );
			if ( ! ta ) { return; }
			var type = ta.getAttribute( 'data-type' );
			if ( ! window.confirm( LavthemeCS.i18n.confirmReset ) ) { return; }
			$.post( LavthemeCS.ajaxUrl, {
				action: 'lavtheme_cs_reset',
				nonce: LavthemeCS.nonce,
				section: sec,
				type: type
			} ).done( function ( res ) {
				if ( res && res.success ) {
					setVal( key( sec, type ), ( res.data && typeof res.data.content !== 'undefined' ) ? res.data.content : '' );
					status( $panel, ( res.data && res.data.message ) || LavthemeCS.i18n.restored, true );
				} else {
					status( $panel, ( res && res.data && res.data.message ) || LavthemeCS.i18n.error, false );
				}
			} ).fail( function () { status( $panel, LavthemeCS.i18n.error, false ); } );
		} );

		// Restore → open modal with backups.
		$( '.lavcs-restore' ).on( 'click', function () {
			var sec = $( this ).data( 'section' );
			var $panel = $( this ).closest( '.lavcs-panel' );
			var ta = activeEditorEl( $panel );
			var type = ta ? ta.getAttribute( 'data-type' ) : 'html';

			$.post( LavthemeCS.ajaxUrl, {
				action: 'lavtheme_cs_backups',
				nonce: LavthemeCS.nonce,
				section: sec
			} ).done( function ( res ) {
				var $list = $( '.lavcs-backups' ).empty();
				$( '.lavcs-modal h2' ).text( 'Backups' );
				if ( ! res || ! res.success || ! res.data.items.length ) {
					$list.append( '<li>' + LavthemeCS.i18n.noBackups + '</li>' );
				} else {
					res.data.items.forEach( function ( it ) {
						$( '<li><button type="button" class="button">' + it.label + '</button></li>' )
							.find( 'button' )
							.on( 'click', function () {
								if ( ! window.confirm( LavthemeCS.i18n.confirmRestore ) ) { return; }
								$.post( LavthemeCS.ajaxUrl, {
									action: 'lavtheme_cs_restore',
									nonce: LavthemeCS.nonce,
									section: sec,
									type: type,
									stamp: it.stamp
								} ).done( function ( r ) {
									if ( r && r.success ) {
										if ( typeof r.data.content !== 'undefined' ) {
											setVal( key( sec, type ), r.data.content );
										}
										status( $panel, LavthemeCS.i18n.restored, true );
										$( '.lavcs-modal' ).attr( 'hidden', true );
									} else {
										status( $panel, ( r && r.data && r.data.message ) || LavthemeCS.i18n.error, false );
									}
								} );
							} )
							.end()
							.appendTo( $list );
					} );
				}
				$( '.lavcs-modal' ).removeAttr( 'hidden' );
			} );
		} );

		$( '.lavcs-modal-close' ).on( 'click', function () {
			$( '.lavcs-modal' ).attr( 'hidden', true );
		} );

		// Mode switch.
		$( '#lavcs-mode' ).on( 'change', function () {
			var mode = $( this ).val();
			var $sel = $( this );
			$.post( LavthemeCS.ajaxUrl, {
				action: 'lavtheme_cs_setmode',
				nonce: LavthemeCS.nonce,
				mode: mode
			} ).done( function ( res ) {
				if ( res && res.success ) {
					LavthemeCS.mode = res.data.mode;
					$( '.lavcs-mode-state' ).attr( 'class', 'lavcs-mode-state ' + res.data.mode ).text( res.data.mode.toUpperCase() );
				} else {
					$sel.val( LavthemeCS.mode );
					window.alert( ( res && res.data && res.data.message ) || LavthemeCS.i18n.error );
				}
			} );
		} );

		// ---- Front-end toggles (minify / header on all pages) ----
		function bindToggle( id, which ) {
			$( '#' + id ).on( 'change', function () {
				var on = $( this ).prop( 'checked' ) ? '1' : '0';
				$.post( LavthemeCS.ajaxUrl, { action: 'lavtheme_cs_toggle', nonce: LavthemeCS.nonce, which: which, on: on } )
					.fail( function () { window.alert( LavthemeCS.i18n.error ); } );
			} );
		}
		bindToggle( 'lavcs-minify', 'minify' );
		bindToggle( 'lavcs-headerglobal', 'header_global' );

		// ---- Context switcher: Front Page vs a Page / Download context ----
		var currentPage = 0;
		var currentCtx = '';

		function escHtml( s ) { return $( '<div>' ).text( s == null ? '' : s ).html(); }
		function escAttr( s ) { return String( s == null ? '' : s ).replace( /"/g, '&quot;' ); }
		function pmode( type ) {
			if ( type === 'css' || type === 'bg' ) { return 'text/css'; }
			if ( type === 'js' ) { return 'text/javascript'; }
			if ( type === 'json' ) { return 'application/json'; }
			return 'application/x-httpd-php';
		}
		// Dispatch page vs download AJAX based on the current context. The shop
		// (download archive) reuses the download (dl) AJAX handlers + context param.
		function ctxIsDl() { return currentCtx.indexOf( 'dl-' ) === 0 || currentCtx === 'shop' || currentCtx === 'blog'; }
		function ctxPost( name, extra ) {
			var d = { action: ( ctxIsDl() ? 'lavtheme_cs_dl_' : 'lavtheme_cs_page_' ) + name, nonce: LavthemeCS.nonce };
			if ( ctxIsDl() ) { d.context = currentCtx; } else { d.page_id = currentPage; }
			return $.post( LavthemeCS.ajaxUrl, $.extend( d, extra || {} ) );
		}

		function buildPageUI( p ) {
			// Drop stale editor instances for this context (DOM is rebuilt).
			Object.keys( editors ).forEach( function ( k ) {
				if ( k.indexOf( currentCtx + '-' ) === 0 ) { delete editors[ k ]; }
			} );

			var $nav = $( '.lavcs-page-nav' ).empty();
			var $main = $( '.lavcs-page-main' ).empty();

			p.sections.forEach( function ( sec, idx ) {
				var sid = currentCtx + '-' + sec.slug;
				LavthemeCS.content[ sid ] = p.data[ sec.slug ] || {};
				var types = Object.keys( sec.fields );

				// Only settings sections (Global / Schema) are fixed; content + custom
				// sections are draggable so they can sit before/after the content.
				var pinned = ( sec.zone === 'settings' );
				var $li = $( '<li class="lavcs-navli' + ( pinned ? ' lavcs-pinned' : '' ) + '" data-slug="' + escAttr( sec.slug ) + '"></li>' );
				$li.append( '<span class="lavcs-drag' + ( pinned ? ' lavcs-drag-off' : '' ) + '" aria-hidden="true">' + ( pinned ? '★' : '⋮⋮' ) + '</span>' );
				$li.append( '<button type="button" class="lavcs-pnavitem' + ( idx === 0 ? ' is-active' : '' ) + '" data-slug="' + escAttr( sec.slug ) + '"><span class="lavcs-navlabel">' + escHtml( sec.label ) + '</span></button>' );
				if ( sec.deletable ) {
					$li.append( '<span class="lavcs-rowtools"><button type="button" class="lavcs-prename" data-slug="' + escAttr( sec.slug ) + '" title="Rename">✎</button><button type="button" class="lavcs-pdel" data-slug="' + escAttr( sec.slug ) + '" title="Delete">✕</button></span>' );
				}
				$nav.append( $li );

				var $panel = $( '<div class="lavcs-panel' + ( idx === 0 ? ' is-active' : '' ) + '" data-section="' + escAttr( sid ) + '" data-slug="' + escAttr( sec.slug ) + '"></div>' );

				// A placeholder section (e.g. the template "Product Content") has no
				// editors — it's just a draggable anchor for ordering.
				if ( ! types.length ) {
					$panel.append( '<p class="description" style="padding:14px;"><strong>' + escHtml( sec.label ) + '</strong> — ' + escHtml( "represents each product's content. Not editable here; drag sections above or below it to place them before / after the content." ) + '</p>' );
					$main.append( $panel );
					return;
				}

				var $tabs = $( '<div class="lavcs-tabs"></div>' );
				types.forEach( function ( t, ti ) {
					$tabs.append( '<button type="button" class="lavcs-tab' + ( ti === 0 ? ' is-active' : '' ) + '" data-type="' + escAttr( t ) + '">' + escHtml( sec.fields[ t ] ) + '</button>' );
				} );
				$tabs.append( '<span class="lavcs-spacer"></span><button type="button" class="button lavcs-fullscreen">⤢</button>' );
				$panel.append( $tabs );

				types.forEach( function ( t, ti ) {
					var $w = $( '<div class="lavcs-editorwrap' + ( ti === 0 ? ' is-active' : '' ) + '" data-type="' + escAttr( t ) + '"></div>' );
					if ( t === 'php' ) {
						var lock = p.phpAllowed ? '' : ' <strong>LOCKED</strong> — add <code>define(\'LAVTHEME_ALLOW_PHP_SECTIONS\', true)</code> to wp-config.php to run it.';
						$w.append( '<p class="lavcs-php-warn">⚠ Custom PHP runs on the server. Only enter trusted code.' + lock + '</p>' );
					}
					if ( t === 'json' && ctxIsDl() ) {
						$w.append( '<p class="description" style="margin:4px 0;">Tokens replaced at render: <code>{{product_name}}</code> <code>{{product_price}}</code> <code>{{product_image}}</code> <code>{{product_url}}</code> <code>{{product_currency}}</code></p>' );
					}
					$w.append( '<textarea class="lavcs-editor" data-section="' + escAttr( sid ) + '" data-type="' + escAttr( t ) + '" data-mode="' + pmode( t ) + '"></textarea>' );
					$panel.append( $w );
				} );

				var $act = $( '<div class="lavcs-actions"></div>' );
				$act.append( '<button type="button" class="button button-primary lavcs-psave" data-slug="' + escAttr( sec.slug ) + '">Save</button>' );
				if ( sec.pagecontent ) {
					$act.append( '<button type="button" class="button lavcs-pcrestore">Restore…</button>' );
					if ( p.shortcode ) { $act.append( '<span class="lavcs-pc-warn">⚠ ' + escHtml( 'contains plugin shortcodes' ) + '</span>' ); }
				}
				if ( sec.placeable && p.placements ) {
					var opts = '';
					Object.keys( p.placements ).forEach( function ( pk ) {
						opts += '<option value="' + pk + '"' + ( pk === sec.placement ? ' selected' : '' ) + '>' + escHtml( p.placements[ pk ] ) + '</option>';
					} );
					$act.append( '<label class="lavcs-placement">Placement: <select class="lavcs-placement-sel" data-slug="' + escAttr( sec.slug ) + '">' + opts + '</select></label>' );
				}
				$act.append( '<span class="lavcs-status" aria-live="polite"></span>' );
				$panel.append( $act );
				$main.append( $panel );
			} );

			if ( $.fn.sortable ) {
				$nav.sortable( {
					items: '> .lavcs-navli:not(.lavcs-pinned)',
					handle: '.lavcs-drag',
					axis: 'y',
					update: function () {
						var order = $nav.find( '> .lavcs-navli' ).map( function () { return $( this ).data( 'slug' ); } ).get();
						ctxPost( 'reorder', { order: order } );
					}
				} );
			}
			refreshActive( $main.find( '.lavcs-panel.is-active' ) );
		}

		function loadCtx() {
			$( '.lavcs-page-loading' ).removeAttr( 'hidden' );
			ctxPost( 'load' )
				.done( function ( res ) {
					$( '.lavcs-page-loading' ).attr( 'hidden', true );
					if ( res && res.success ) { buildPageUI( res.data ); }
					else { $( '.lavcs-page-main' ).html( '<p class="description">' + ( ( res && res.data && res.data.message ) || LavthemeCS.i18n.error ) + '</p>' ); }
				} )
				.fail( function () { $( '.lavcs-page-loading' ).attr( 'hidden', true ); } );
		}

		$( '#lavcs-context' ).on( 'change', function () {
			var v = $( this ).val();
			var $opt = $( this ).find( 'option:selected' );
			if ( v === 'front' ) {
				$( '.lavcs-front-area' ).show();
				$( '.lavcs-page-area' ).attr( 'hidden', true );
				return;
			}
			currentCtx = v;
			currentPage = ( v.indexOf( 'page-' ) === 0 ) ? parseInt( v.substring( 5 ), 10 ) : 0;
			$( '.lavcs-front-area' ).hide();
			$( '.lavcs-page-area' ).removeAttr( 'hidden' );
			$( '.lavcs-page-title' ).text( $.trim( $opt.text() ) );
			var view = $opt.data( 'view' );
			$( '.lavcs-page-view' ).attr( 'href', view || '#' ).toggle( !! view );
			loadCtx();
		} );

		// Page nav switching.
		$( document ).on( 'click', '.lavcs-pnavitem', function () {
			var slug = $( this ).data( 'slug' );
			$( '.lavcs-page-nav .lavcs-pnavitem' ).removeClass( 'is-active' );
			$( this ).addClass( 'is-active' );
			$( '.lavcs-page-main .lavcs-panel' ).removeClass( 'is-active' );
			var $p = $( '.lavcs-page-main .lavcs-panel[data-slug="' + slug + '"]' ).addClass( 'is-active' );
			refreshActive( $p );
		} );

		// Page save.
		$( document ).on( 'click', '.lavcs-psave', function () {
			var $panel = $( this ).closest( '.lavcs-panel' );
			var sid = $panel.data( 'section' );
			var slug = $panel.data( 'slug' );
			var ta = activeEditorEl( $panel );
			if ( ! ta ) { return; }
			var type = ta.getAttribute( 'data-type' );
			var k = key( sid, type );
			initEditor( ta ); // make sure the editor is initialised before reading it.
			if ( editors[ k ] && editors[ k ].cm ) { editors[ k ].cm.save(); }

			if ( type === 'json' ) {
				var v = $.trim( getVal( k ) );
				if ( v !== '' ) { try { JSON.parse( v ); } catch ( err ) { status( $panel, LavthemeCS.i18n.badJson + ' ' + err.message, false ); return; } }
			}
			if ( slug === 'content' && type === 'html' && $panel.find( '.lavcs-pc-warn' ).length ) {
				if ( ! window.confirm( LavthemeCS.i18n.pcEddWarn ) ) { return; }
			}

			status( $panel, LavthemeCS.i18n.saving, true );
			ctxPost( 'save', { slug: slug, type: type, content: getVal( k ) } )
				.done( function ( res ) {
					if ( res && res.success ) { status( $panel, LavthemeCS.i18n.saved, true ); }
					else { status( $panel, ( res && res.data && res.data.message ) || LavthemeCS.i18n.error, false ); }
				} )
				.fail( function () { status( $panel, LavthemeCS.i18n.error, false ); } );
		} );

		// Content restore.
		$( document ).on( 'click', '.lavcs-pcrestore', function () {
			if ( ! window.confirm( LavthemeCS.i18n.pcRestore ) ) { return; }
			var $panel = $( this ).closest( '.lavcs-panel' );
			ctxPost( 'pcrestore' )
				.done( function ( res ) {
					if ( res && res.success ) { setVal( key( currentCtx + '-content', 'html' ), res.data.content ); status( $panel, LavthemeCS.i18n.restored, true ); }
					else { status( $panel, ( res && res.data && res.data.message ) || LavthemeCS.i18n.error, false ); }
				} );
		} );

		// Add / rename / delete section.
		$( document ).on( 'click', '.lavcs-page-add', function () {
			var name = window.prompt( LavthemeCS.i18n.addPrompt, 'New Section' );
			if ( ! name ) { return; }
			ctxPost( 'addsection', { label: name } )
				.done( function ( res ) { if ( res && res.success ) { loadCtx(); } else { window.alert( ( res && res.data && res.data.message ) || LavthemeCS.i18n.error ); } } );
		} );
		$( document ).on( 'click', '.lavcs-prename', function ( e ) {
			e.stopPropagation();
			var slug = $( this ).data( 'slug' );
			var $label = $( '.lavcs-page-nav .lavcs-navli[data-slug="' + slug + '"] .lavcs-navlabel' );
			var name = window.prompt( LavthemeCS.i18n.renamePrompt, $label.text() );
			if ( ! name || name === $label.text() ) { return; }
			ctxPost( 'rename', { slug: slug, label: name } )
				.done( function ( res ) { if ( res && res.success ) { $label.text( name ); } else { window.alert( ( res && res.data && res.data.message ) || LavthemeCS.i18n.error ); } } );
		} );
		$( document ).on( 'click', '.lavcs-pdel', function ( e ) {
			e.stopPropagation();
			var slug = $( this ).data( 'slug' );
			if ( ! window.confirm( LavthemeCS.i18n.delSection ) ) { return; }
			ctxPost( 'delsection', { slug: slug } )
				.done( function ( res ) { if ( res && res.success ) { loadCtx(); } else { window.alert( ( res && res.data && res.data.message ) || LavthemeCS.i18n.error ); } } );
		} );

		// Section placement (front-page, page, or download context).
		$( document ).on( 'change', '.lavcs-placement-sel', function () {
			var slug = $( this ).data( 'slug' );
			var pl = $( this ).val();
			if ( ( pl === 'replace' || pl === 'wrap' ) && ! window.confirm( 'Placement "' + pl + '" changes how the real content is shown. Continue?' ) ) {
				return;
			}
			if ( $( this ).data( 'front' ) ) {
				$.post( LavthemeCS.ajaxUrl, { action: 'lavtheme_cs_setplacement', nonce: LavthemeCS.nonce, slug: slug, placement: pl } );
			} else {
				ctxPost( 'setplacement', { slug: slug, placement: pl } );
			}
		} );

		// ---- Section manager (add / rename / delete / reorder / restore) ----
		function reload() { window.location.reload(); }

		if ( $.fn.sortable ) {
			$( '.lavcs-nav' ).sortable( {
				items: '> .lavcs-navli:not(.lavcs-pinned)',
				handle: '.lavcs-drag',
				axis: 'y',
				update: function () {
					var order = $( '.lavcs-nav > .lavcs-navli:not(.lavcs-pinned)' ).map( function () {
						return $( this ).data( 'section' );
					} ).get();
					$.post( LavthemeCS.ajaxUrl, { action: 'lavtheme_cs_reorder', nonce: LavthemeCS.nonce, order: order } );
				}
			} );
		}

		$( '.lavcs-add' ).on( 'click', function () {
			var name = window.prompt( LavthemeCS.i18n.addPrompt, 'New Section' );
			if ( ! name ) { return; }
			$.post( LavthemeCS.ajaxUrl, { action: 'lavtheme_cs_add', nonce: LavthemeCS.nonce, label: name } )
				.done( function ( res ) {
					if ( res && res.success ) { reload(); }
					else { window.alert( ( res && res.data && res.data.message ) || LavthemeCS.i18n.error ); }
				} );
		} );

		$( document ).on( 'click', '.lavcs-rename', function ( e ) {
			e.stopPropagation();
			var slug = $( this ).data( 'section' );
			var $label = $( '.lavcs-navli[data-section="' + slug + '"] .lavcs-navlabel' );
			var name = window.prompt( LavthemeCS.i18n.renamePrompt, $label.text() );
			if ( ! name || name === $label.text() ) { return; }
			$.post( LavthemeCS.ajaxUrl, { action: 'lavtheme_cs_rename', nonce: LavthemeCS.nonce, slug: slug, label: name } )
				.done( function ( res ) {
					if ( res && res.success ) { $label.text( name ); }
					else { window.alert( ( res && res.data && res.data.message ) || LavthemeCS.i18n.error ); }
				} );
		} );

		$( document ).on( 'click', '.lavcs-del', function ( e ) {
			e.stopPropagation();
			var slug = $( this ).data( 'section' );
			var meta = ( LavthemeCS.sections && LavthemeCS.sections[ slug ] ) || {};
			if ( ! window.confirm( LavthemeCS.i18n.confirmDelete ) ) { return; }
			if ( meta.dynamic && ! window.confirm( LavthemeCS.i18n.confirmDynamic ) ) { return; }
			$.post( LavthemeCS.ajaxUrl, { action: 'lavtheme_cs_delete', nonce: LavthemeCS.nonce, slug: slug } )
				.done( function ( res ) {
					if ( res && res.success ) { reload(); }
					else { window.alert( ( res && res.data && res.data.message ) || LavthemeCS.i18n.error ); }
				} );
		} );

		$( '.lavcs-trash-btn' ).on( 'click', function () {
			$.post( LavthemeCS.ajaxUrl, { action: 'lavtheme_cs_trash', nonce: LavthemeCS.nonce } )
				.done( function ( res ) {
					var $list = $( '.lavcs-backups' ).empty();
					$( '.lavcs-modal h2' ).text( 'Trash' );
					if ( ! res || ! res.success || ! res.data.items.length ) {
						$list.append( '<li>' + LavthemeCS.i18n.noTrash + '</li>' );
					} else {
						res.data.items.forEach( function ( it ) {
							$( '<li><button type="button" class="button">' + it.label + ' (' + it.slug + ') — Restore</button></li>' )
								.find( 'button' ).on( 'click', function () {
									$.post( LavthemeCS.ajaxUrl, { action: 'lavtheme_cs_restore_section', nonce: LavthemeCS.nonce, i: it.i } )
										.done( function ( r ) {
											if ( r && r.success ) { reload(); }
											else { window.alert( ( r && r.data && r.data.message ) || LavthemeCS.i18n.error ); }
										} );
								} ).end().appendTo( $list );
						} );
					}
					$( '.lavcs-modal' ).removeAttr( 'hidden' );
				} );
		} );

		// Initialise the first visible panel's editor.
		refreshActive( $( '.lavcs-panel.is-active' ) );
	} );
} )( jQuery );
