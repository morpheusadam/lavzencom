/* Theme Code Studio — "Pro" enhancement layer.
 *
 * A self-contained module that upgrades the studio into a VS Code-like editor
 * WITHOUT touching the core logic in code-studio.js. It reads the live
 * CodeMirror instances straight from the DOM (cm.getTextArea() gives the source
 * <textarea> back, so we can read its data-section / data-type) and drives the
 * existing Save buttons, so it stays correct for the Front Page sections AND the
 * AJAX-built page/context panels alike.
 *
 * Adds: command palette (Ctrl+K), save shortcut (Ctrl/Cmd+S), unsaved-change
 * tracking + leave guard, a status bar, find & replace (Ctrl+F / Ctrl+H), and an
 * editor toolbar (copy, word-wrap, font-size, shortcut help).
 */
( function ( $ ) {
	'use strict';
	if ( typeof LavthemeCS === 'undefined' ) {
		return;
	}

	var PREFS_KEY = 'lavcsPro';
	var prefs = { wrap: false, fontSize: 13 };
	try {
		var saved = JSON.parse( window.localStorage.getItem( PREFS_KEY ) || '{}' );
		prefs = $.extend( prefs, saved );
	} catch ( e ) {}
	function savePrefs() {
		try { window.localStorage.setItem( PREFS_KEY, JSON.stringify( prefs ) ); } catch ( e ) {}
	}

	var dirty = {}; // key "section|type" -> true
	var proReady = false; // becomes true shortly after load so init transients never count as edits

	/* ---------- locating the active editor ---------- */
	function visibleArea() {
		var page = document.querySelector( '.lavcs-page-area' );
		if ( page && ! page.hasAttribute( 'hidden' ) ) { return page; }
		return document.querySelector( '.lavcs-front-area' );
	}
	function activeTextarea() {
		var area = visibleArea();
		if ( ! area ) { return null; }
		var wrap = area.querySelector( '.lavcs-panel.is-active .lavcs-editorwrap.is-active' );
		return wrap ? wrap.querySelector( '.lavcs-editor' ) : null;
	}
	function cmOf( ta ) {
		if ( ! ta ) { return null; }
		var el = ta.nextSibling;
		if ( el && el.CodeMirror ) { return el.CodeMirror; }
		var found = ta.parentNode ? ta.parentNode.querySelector( '.CodeMirror' ) : null;
		return found && found.CodeMirror ? found.CodeMirror : null;
	}
	function activeCM() { return cmOf( activeTextarea() ); }
	function activeSaveBtn() {
		var area = visibleArea();
		if ( ! area ) { return null; }
		var panel = area.querySelector( '.lavcs-panel.is-active' );
		return panel ? panel.querySelector( '.lavcs-save, .lavcs-psave' ) : null;
	}
	function metaOf( cm ) {
		var ta = cm && cm.getTextArea ? cm.getTextArea() : null;
		if ( ! ta ) { return null; }
		return { sec: ta.getAttribute( 'data-section' ), type: ta.getAttribute( 'data-type' ), mode: ta.getAttribute( 'data-mode' ) || '' };
	}
	function baselineFor( m ) {
		if ( ! m ) { return ''; }
		var c = LavthemeCS.content || {};
		return ( c[ m.sec ] && typeof c[ m.sec ][ m.type ] !== 'undefined' ) ? String( c[ m.sec ][ m.type ] ) : '';
	}

	/* ---------- build the chrome (status bar, palette, find, help) ---------- */
	var $bar, $palette, $find, $help, $pill, $preview, $history;

	function buildChrome() {
		var $wrap = $( '.lavcs-wrap' );
		if ( ! $wrap.length ) { return; }

		// Unsaved pill in the topbar.
		$pill = $( '<span class="lavcs-pro-pill" hidden>● ' + 'Unsaved changes' + '</span>' );
		$( '.lavcs-topbar' ).append( $pill );

		// Status bar (sticky to bottom of the studio).
		$bar = $(
			'<div class="lavcs-pro-bar">' +
				'<span class="lavcs-pro-seg lavcs-pro-pos">Ln 1, Col 1</span>' +
				'<span class="lavcs-pro-seg lavcs-pro-sel"></span>' +
				'<span class="lavcs-pro-seg lavcs-pro-len"></span>' +
				'<span class="lavcs-pro-grow"></span>' +
				'<span class="lavcs-pro-seg lavcs-pro-state" data-state="saved">Saved</span>' +
				'<span class="lavcs-pro-seg lavcs-pro-mode">—</span>' +
				'<button type="button" class="lavcs-pro-btn" data-act="find" title="Find / Replace (Ctrl+F)">⌕ Find</button>' +
				'<button type="button" class="lavcs-pro-btn" data-act="wrap" title="Toggle word wrap">⤶ Wrap</button>' +
				'<button type="button" class="lavcs-pro-btn" data-act="fontdown" title="Smaller font">A−</button>' +
				'<button type="button" class="lavcs-pro-btn" data-act="fontup" title="Larger font">A+</button>' +
				'<button type="button" class="lavcs-pro-btn" data-act="copy" title="Copy editor contents">⧉ Copy</button>' +
				'<button type="button" class="lavcs-pro-btn" data-act="format" title="Format / beautify (CSS &amp; JSON)">{ } Format</button>' +
				'<button type="button" class="lavcs-pro-btn" data-act="preview" title="Live preview of the target page">▣ Preview</button>' +
				'<button type="button" class="lavcs-pro-btn" data-act="history" title="Revision history">↺ History</button>' +
				'<button type="button" class="lavcs-pro-btn" data-act="palette" title="Command palette (Ctrl+K)">⌘ Go</button>' +
				'<button type="button" class="lavcs-pro-btn" data-act="help" title="Keyboard shortcuts (?)">⌨</button>' +
			'</div>'
		);
		$wrap.append( $bar );

		// Command palette.
		$palette = $(
			'<div class="lavcs-pro-modal lavcs-pro-palette" hidden>' +
				'<div class="lavcs-pro-modal-box">' +
					'<input type="text" class="lavcs-pro-pinput" placeholder="Jump to a section or context…" autocomplete="off">' +
					'<ul class="lavcs-pro-plist"></ul>' +
				'</div>' +
			'</div>'
		);
		$wrap.append( $palette );

		// Find & replace bar.
		$find = $(
			'<div class="lavcs-pro-find" hidden>' +
				'<input type="text" class="lavcs-pro-find-q" placeholder="Find" autocomplete="off">' +
				'<input type="text" class="lavcs-pro-find-r" placeholder="Replace" autocomplete="off">' +
				'<label class="lavcs-pro-find-cs"><input type="checkbox"> Aa</label>' +
				'<span class="lavcs-pro-find-count">0/0</span>' +
				'<button type="button" class="lavcs-pro-btn" data-fact="prev" title="Previous (Shift+Enter)">↑</button>' +
				'<button type="button" class="lavcs-pro-btn" data-fact="next" title="Next (Enter)">↓</button>' +
				'<button type="button" class="lavcs-pro-btn" data-fact="rep" title="Replace">Replace</button>' +
				'<button type="button" class="lavcs-pro-btn" data-fact="repall" title="Replace all">All</button>' +
				'<button type="button" class="lavcs-pro-btn" data-fact="close" title="Close (Esc)">✕</button>' +
			'</div>'
		);
		$wrap.append( $find );

		// Shortcut help overlay.
		$help = $(
			'<div class="lavcs-pro-modal lavcs-pro-help" hidden>' +
				'<div class="lavcs-pro-modal-box">' +
					'<h2>Keyboard shortcuts</h2>' +
					'<table class="lavcs-pro-keys">' +
						row( 'Ctrl / ⌘ + S', 'Save the active editor' ) +
						row( 'Ctrl / ⌘ + K', 'Command palette — jump anywhere' ) +
						row( 'Ctrl / ⌘ + F', 'Find in the active editor' ) +
						row( 'Ctrl / ⌘ + H', 'Find & replace' ) +
						row( 'Enter / Shift+Enter', 'Next / previous match (in Find)' ) +
						row( 'Esc', 'Close palette / find / fullscreen' ) +
						row( '?', 'This help' ) +
					'</table>' +
					'<button type="button" class="button lavcs-pro-help-close">Close</button>' +
				'</div>' +
			'</div>'
		);
		$wrap.append( $help );

		// Live preview (iframe of the target page; CSS/JS edits injected live).
		$preview = $(
			'<div class="lavcs-pro-modal lavcs-pro-preview" hidden>' +
				'<div class="lavcs-pro-pv-box">' +
					'<div class="lavcs-pro-pv-head">' +
						'<strong class="lavcs-pro-pv-title">Live preview</strong>' +
						'<span class="lavcs-pro-pv-url"></span>' +
						'<span class="lavcs-pro-grow"></span>' +
						'<button type="button" class="lavcs-pro-btn lavcs-pro-pv-w is-on" data-w="desktop">🖥 Desktop</button>' +
						'<button type="button" class="lavcs-pro-btn lavcs-pro-pv-w" data-w="mobile">📱 Mobile</button>' +
						'<button type="button" class="lavcs-pro-btn" data-pv="apply" title="Re-inject the current (unsaved) edits">↻ Apply edits</button>' +
						'<button type="button" class="lavcs-pro-btn" data-pv="open" title="Open in a new tab">↗</button>' +
						'<button type="button" class="lavcs-pro-btn" data-pv="close">✕</button>' +
					'</div>' +
					'<div class="lavcs-pro-pv-stage"><iframe class="lavcs-pro-pv-frame" referrerpolicy="no-referrer"></iframe></div>' +
				'</div>' +
			'</div>'
		);
		$wrap.append( $preview );

		// Revision history.
		$history = $(
			'<div class="lavcs-pro-modal lavcs-pro-history" hidden>' +
				'<div class="lavcs-pro-modal-box">' +
					'<h2>Revision history <span class="lavcs-pro-hist-sub">— click a version to load it, then Save to apply</span></h2>' +
					'<ul class="lavcs-pro-hist-list"></ul>' +
					'<button type="button" class="button lavcs-pro-hist-close">Close</button>' +
				'</div>' +
			'</div>'
		);
		$wrap.append( $history );
	}
	function row( k, d ) { return '<tr><th><kbd>' + k + '</kbd></th><td>' + d + '</td></tr>'; }

	/* ---------- status bar updates + dirty tracking ---------- */
	function modeLabel( m ) {
		if ( ! m ) { return '—'; }
		switch ( m.mode ) {
			case 'text/css': return ( m.type === 'mcss' ) ? 'Mobile CSS' : 'CSS';
			case 'text/javascript': return 'JavaScript';
			case 'application/json': return 'JSON';
			case 'application/x-httpd-php': return ( m.type === 'php' ) ? 'PHP' : 'HTML / PHP';
		}
		return m.type ? m.type.toUpperCase() : '—';
	}
	function refreshBar() {
		if ( ! $bar ) { return; }
		var cm = activeCM();
		if ( ! cm ) {
			$bar.find( '.lavcs-pro-pos' ).text( '—' );
			$bar.find( '.lavcs-pro-sel, .lavcs-pro-len' ).text( '' );
			$bar.find( '.lavcs-pro-mode' ).text( '—' );
			return;
		}
		var c = cm.getCursor();
		var doc = cm.getDoc();
		var sel = cm.getSelection();
		var m = metaOf( cm );
		$bar.find( '.lavcs-pro-pos' ).text( 'Ln ' + ( c.line + 1 ) + ', Col ' + ( c.ch + 1 ) );
		$bar.find( '.lavcs-pro-sel' ).text( sel ? ( sel.length + ' selected' ) : '' );
		$bar.find( '.lavcs-pro-len' ).text( doc.lineCount() + ' lines · ' + cm.getValue().length + ' chars' );
		$bar.find( '.lavcs-pro-mode' ).text( modeLabel( m ) );
		var k = m ? ( m.sec + '|' + m.type ) : '';
		var isDirty = !! dirty[ k ];
		$bar.find( '.lavcs-pro-state' ).attr( 'data-state', isDirty ? 'unsaved' : 'saved' ).text( isDirty ? '● Unsaved' : 'Saved' );
	}
	function recomputeDirty( cm ) {
		var m = metaOf( cm );
		if ( ! m || ! m.sec ) { return; }
		var k = m.sec + '|' + m.type;
		if ( proReady && cm.getValue() !== baselineFor( m ) ) { dirty[ k ] = true; } else { delete dirty[ k ]; }
		var any = Object.keys( dirty ).length > 0;
		if ( $pill ) { $pill.prop( 'hidden', ! any ); }
		markNavDirty();
	}
	function markNavDirty() {
		// Best-effort: flag the currently-visible nav items whose section is dirty.
		$( '.lavcs-navitem, .lavcs-pnavitem' ).each( function () {
			var sec = this.getAttribute( 'data-section' );
			var slug = this.getAttribute( 'data-slug' );
			var hit = false;
			for ( var k in dirty ) {
				var s = k.split( '|' )[ 0 ];
				if ( ( sec && s === sec ) || ( slug && s.indexOf( '-' + slug ) === s.length - slug.length - 1 ) ) { hit = true; break; }
			}
			$( this ).toggleClass( 'lavcs-pro-dirty', hit );
		} );
	}

	var hookedFlag = '_lavcsProHooked';
	function ensureHooked( cm ) {
		if ( ! cm || cm[ hookedFlag ] ) { return; }
		cm[ hookedFlag ] = true;
		cm.on( 'cursorActivity', refreshBar );
		cm.on( 'change', function () { recomputeDirty( cm ); refreshBar(); } );
		cm.on( 'focus', function () { applyPrefs( cm ); refreshBar(); } );
		applyPrefs( cm );
	}
	function syncActive() {
		var cm = activeCM();
		if ( cm ) { ensureHooked( cm ); }
		refreshBar();
	}

	/* ---------- editor prefs (wrap / font) ---------- */
	function applyPrefs( cm ) {
		if ( ! cm ) { return; }
		try {
			cm.setOption( 'lineWrapping', !! prefs.wrap );
			var w = cm.getWrapperElement();
			if ( w ) { w.style.fontSize = prefs.fontSize + 'px'; }
			cm.refresh();
		} catch ( e ) {}
	}
	function applyPrefsAll() {
		$( '.CodeMirror' ).each( function () { if ( this.CodeMirror ) { applyPrefs( this.CodeMirror ); } } );
	}

	/* ---------- command palette ---------- */
	var paletteItems = [];
	function buildPaletteItems() {
		var items = [];
		// Front-page sections.
		$( '.lavcs-front-area .lavcs-nav .lavcs-navitem' ).each( function () {
			items.push( { label: $( this ).text().trim(), kind: 'Section', act: 'section', sec: this.getAttribute( 'data-section' ) } );
		} );
		// Editing contexts (the big dropdown).
		$( '#lavcs-context option' ).each( function () {
			var v = this.value, t = $( this ).text().replace( /\s+/g, ' ' ).trim();
			if ( v ) { items.push( { label: t, kind: 'Context', act: 'context', val: v } ); }
		} );
		paletteItems = items;
	}
	function renderPalette( q ) {
		var ql = q.toLowerCase();
		var $list = $palette.find( '.lavcs-pro-plist' ).empty();
		var matches = paletteItems.filter( function ( it ) {
			return ! ql || it.label.toLowerCase().indexOf( ql ) !== -1 || it.kind.toLowerCase().indexOf( ql ) !== -1;
		} ).slice( 0, 60 );
		matches.forEach( function ( it, i ) {
			var $li = $( '<li class="lavcs-pro-pitem' + ( i === 0 ? ' is-active' : '' ) + '"></li>' );
			$li.append( '<span class="lavcs-pro-pkind">' + it.kind + '</span>' );
			$li.append( '<span class="lavcs-pro-plabel"></span>' );
			$li.find( '.lavcs-pro-plabel' ).text( it.label );
			$li.data( 'it', it );
			$list.append( $li );
		} );
		if ( ! matches.length ) { $list.append( '<li class="lavcs-pro-pempty">No matches</li>' ); }
	}
	function gotoItem( it ) {
		if ( ! it ) { return; }
		if ( it.act === 'context' ) {
			var $sel = $( '#lavcs-context' );
			if ( $sel.val() !== it.val ) { $sel.val( it.val ).trigger( 'change' ); }
		} else if ( it.act === 'section' ) {
			var $ctx = $( '#lavcs-context' );
			if ( $ctx.length && $ctx.val() !== 'front' ) { $ctx.val( 'front' ).trigger( 'change' ); }
			$( '.lavcs-front-area .lavcs-navitem[data-section="' + it.sec + '"]' ).trigger( 'click' );
		}
		closePalette();
		setTimeout( syncActive, 200 );
	}
	function openPalette() {
		buildPaletteItems();
		renderPalette( '' );
		$palette.prop( 'hidden', false );
		var $in = $palette.find( '.lavcs-pro-pinput' ).val( '' );
		setTimeout( function () { $in.trigger( 'focus' ); }, 0 );
	}
	function closePalette() { if ( $palette ) { $palette.prop( 'hidden', true ); } }

	/* ---------- find & replace ---------- */
	var findState = { marks: [], hits: [], idx: -1, cm: null };
	function clearFindMarks() {
		findState.marks.forEach( function ( mk ) { try { mk.clear(); } catch ( e ) {} } );
		findState.marks = [];
	}
	function runFind( jumpTo ) {
		var cm = findState.cm = activeCM();
		clearFindMarks();
		findState.hits = []; findState.idx = -1;
		if ( ! cm ) { updateFindCount(); return; }
		var q = $find.find( '.lavcs-pro-find-q' ).val();
		if ( ! q ) { updateFindCount(); return; }
		var cs = $find.find( '.lavcs-pro-find-cs input' ).prop( 'checked' );
		var text = cm.getValue();
		var hay = cs ? text : text.toLowerCase();
		var needle = cs ? q : q.toLowerCase();
		var from = 0, i;
		while ( ( i = hay.indexOf( needle, from ) ) !== -1 ) {
			findState.hits.push( i );
			from = i + needle.length;
			if ( findState.hits.length > 5000 ) { break; }
		}
		findState.hits.forEach( function ( pos ) {
			var s = cm.posFromIndex( pos ), e = cm.posFromIndex( pos + needle.length );
			findState.marks.push( cm.markText( s, e, { className: 'lavcs-pro-hit' } ) );
		} );
		if ( findState.hits.length && jumpTo !== false ) { gotoHit( 0 ); }
		updateFindCount();
	}
	function gotoHit( n ) {
		var cm = findState.cm; if ( ! cm || ! findState.hits.length ) { return; }
		var len = findState.hits.length;
		findState.idx = ( ( n % len ) + len ) % len;
		var q = $find.find( '.lavcs-pro-find-q' ).val();
		var pos = findState.hits[ findState.idx ];
		var s = cm.posFromIndex( pos ), e = cm.posFromIndex( pos + q.length );
		cm.setSelection( s, e );
		cm.scrollIntoView( { from: s, to: e }, 80 );
		updateFindCount();
	}
	function updateFindCount() {
		$find.find( '.lavcs-pro-find-count' ).text( ( findState.hits.length ? ( findState.idx + 1 ) : 0 ) + '/' + findState.hits.length );
	}
	function replaceOne() {
		var cm = findState.cm; if ( ! cm || findState.idx < 0 || ! findState.hits.length ) { return; }
		var q = $find.find( '.lavcs-pro-find-q' ).val();
		var r = $find.find( '.lavcs-pro-find-r' ).val();
		var pos = findState.hits[ findState.idx ];
		cm.replaceRange( r, cm.posFromIndex( pos ), cm.posFromIndex( pos + q.length ) );
		runFind( false );
		if ( findState.hits.length ) { gotoHit( findState.idx ); }
	}
	function replaceAll() {
		var cm = findState.cm = activeCM(); if ( ! cm ) { return; }
		var q = $find.find( '.lavcs-pro-find-q' ).val(); if ( ! q ) { return; }
		var r = $find.find( '.lavcs-pro-find-r' ).val();
		var cs = $find.find( '.lavcs-pro-find-cs input' ).prop( 'checked' );
		var text = cm.getValue();
		var out, n = 0;
		if ( cs ) {
			out = text.split( q ).join( r ); n = text.split( q ).length - 1;
		} else {
			var lower = text.toLowerCase(), needle = q.toLowerCase(), res = '', from = 0, i;
			while ( ( i = lower.indexOf( needle, from ) ) !== -1 ) { res += text.slice( from, i ) + r; from = i + needle.length; n++; }
			res += text.slice( from ); out = res;
		}
		if ( n ) { cm.setValue( out ); }
		runFind();
	}
	function openFind( withReplace ) {
		var cm = activeCM(); if ( ! cm ) { return; }
		$find.prop( 'hidden', false );
		$find.toggleClass( 'has-replace', !! withReplace );
		var sel = cm.getSelection();
		var $q = $find.find( '.lavcs-pro-find-q' );
		if ( sel && sel.indexOf( '\n' ) === -1 ) { $q.val( sel ); }
		setTimeout( function () { $q.trigger( 'focus' ).select(); runFind(); }, 0 );
	}
	function closeFind() { if ( $find ) { $find.prop( 'hidden', true ); clearFindMarks(); } }

	/* ---------- format / beautify (dependency-free) ---------- */
	// String/comment-aware CSS tidier: one declaration per line, braces indented,
	// blank line between top-level rules. Never reorders or rewrites values, so it
	// can't change meaning — only whitespace/layout.
	function beautifyCSS( src ) {
		var i = 0, n = src.length, out = [], indent = 0, line = '';
		function ind() { return new Array( indent + 1 ).join( '  ' ); }
		function push( s ) { if ( s.replace( /\s+/g, '' ) !== '' ) { out.push( ind() + s.trim() ); } }
		while ( i < n ) {
			var c = src[ i ];
			if ( c === '/' && src[ i + 1 ] === '*' ) { // comment — kept verbatim
				var e = src.indexOf( '*/', i + 2 ); if ( e < 0 ) { e = n - 2; }
				var cm = src.slice( i, e + 2 );
				if ( line.trim() === '' ) { out.push( ind() + cm ); } else { line += cm; }
				i = e + 2; continue;
			}
			if ( c === '"' || c === "'" ) { // string — kept verbatim
				var q = c, j = i + 1;
				while ( j < n ) { if ( src[ j ] === '\\' ) { j += 2; continue; } if ( src[ j ] === q ) { break; } j++; }
				line += src.slice( i, j + 1 ); i = j + 1; continue;
			}
			if ( c === '{' ) { out.push( ind() + line.trim() + ' {' ); line = ''; indent++; i++; continue; }
			if ( c === '}' ) {
				if ( line.trim() !== '' ) { push( line ); }
				line = ''; indent = Math.max( 0, indent - 1 ); out.push( ind() + '}' );
				if ( indent === 0 ) { out.push( '' ); }
				i++; continue;
			}
			if ( c === ';' ) { line += ';'; push( line ); line = ''; i++; continue; }
			if ( c === '\n' || c === '\r' ) { i++; continue; }
			line += c; i++;
		}
		if ( line.trim() !== '' ) { push( line ); }
		return out.join( '\n' ).replace( /\n{3,}/g, '\n\n' ).replace( /[ \t]+\n/g, '\n' ).trim() + '\n';
	}
	function formatActive() {
		var cm = activeCM(); if ( ! cm ) { return; }
		var m = metaOf( cm ); var v = cm.getValue(); var out = null;
		try {
			if ( m && ( m.mode === 'application/json' || m.type === 'json' ) ) { out = JSON.stringify( JSON.parse( v ), null, 2 ); }
			else if ( m && m.mode === 'text/css' ) { out = beautifyCSS( v ); }
			else { flashBtn( '.lavcs-pro-btn[data-act="format"]', 'CSS / JSON only' ); return; }
		} catch ( err ) { flashBtn( '.lavcs-pro-btn[data-act="format"]', 'Invalid: ' + String( err.message ).slice( 0, 26 ) ); return; }
		if ( out != null && out !== v ) { cm.setValue( out ); recomputeDirty( cm ); refreshBar(); flashBtn( '.lavcs-pro-btn[data-act="format"]', 'Formatted ✓' ); }
		else { flashBtn( '.lavcs-pro-btn[data-act="format"]', 'Already tidy' ); }
	}

	/* ---------- live preview ---------- */
	function previewURL() {
		var $sel = $( '#lavcs-context' );
		if ( $sel.length && $sel.val() !== 'front' ) {
			var v = $sel.find( 'option:selected' ).data( 'view' );
			if ( v ) { return v; }
		}
		return window.location.origin + '/';
	}
	function injectPreview() {
		var cm = activeCM(); if ( ! cm ) { return; }
		var m = metaOf( cm ); if ( ! m ) { return; }
		var fr = $preview.find( '.lavcs-pro-pv-frame' ).get( 0 );
		var doc; try { doc = fr.contentDocument; } catch ( e ) { return; }
		if ( ! doc || ! doc.head ) { return; }
		var ps = doc.getElementById( 'lavcs-pro-pv-style' ); if ( ps ) { ps.remove(); }
		var pj = doc.getElementById( 'lavcs-pro-pv-js' ); if ( pj ) { pj.remove(); }
		var v = cm.getValue();
		if ( m.mode === 'text/css' ) {
			var s = doc.createElement( 'style' ); s.id = 'lavcs-pro-pv-style'; s.textContent = v; doc.head.appendChild( s );
		} else if ( m.mode === 'text/javascript' ) {
			var sc = doc.createElement( 'script' ); sc.id = 'lavcs-pro-pv-js'; sc.textContent = 'try{' + v + '}catch(e){console.warn(e);}'; doc.body.appendChild( sc );
		}
	}
	function openPreview() {
		var url = previewURL();
		$preview.removeAttr( 'hidden' );
		$preview.find( '.lavcs-pro-pv-url' ).text( url );
		var $f = $preview.find( '.lavcs-pro-pv-frame' );
		$f.off( 'load.pv' ).on( 'load.pv', injectPreview );
		if ( $f.attr( 'src' ) !== url ) { $f.attr( 'src', url ); } else { injectPreview(); }
	}
	function setPreviewWidth( w ) {
		$preview.find( '.lavcs-pro-pv-frame' ).css( 'max-width', w === 'mobile' ? '390px' : '100%' );
		$preview.find( '.lavcs-pro-pv-w' ).removeClass( 'is-on' );
		$preview.find( '.lavcs-pro-pv-w[data-w="' + w + '"]' ).addClass( 'is-on' );
	}

	/* ---------- revision history ---------- */
	function historyParams() {
		var ta = activeTextarea(); if ( ! ta ) { return null; }
		var type = ta.getAttribute( 'data-type' );
		var area = visibleArea();
		if ( area && area.classList.contains( 'lavcs-front-area' ) ) {
			return { scope: 'front', section: ta.getAttribute( 'data-section' ), type: type };
		}
		var ctx = $( '#lavcs-context' ).val();
		var panel = area ? area.querySelector( '.lavcs-panel.is-active' ) : null;
		var slug = panel ? panel.getAttribute( 'data-slug' ) : '';
		if ( ctx && ctx.indexOf( 'page-' ) === 0 ) {
			return { scope: 'page', page_id: parseInt( ctx.substring( 5 ), 10 ), slug: slug, type: type };
		}
		return { scope: 'dl', context: ctx, slug: slug, type: type };
	}
	function loadRevision( content ) {
		var cm = activeCM(); if ( ! cm ) { return; }
		cm.setValue( content ); recomputeDirty( cm ); refreshBar();
		$history.attr( 'hidden', 'hidden' );
		flashBtn( '.lavcs-pro-btn[data-act="history"]', 'Loaded — review & Save' );
	}
	function openHistory() {
		var p = historyParams(); if ( ! p ) { return; }
		$history.removeAttr( 'hidden' );
		var $list = $history.find( '.lavcs-pro-hist-list' ).empty().append( '<li class="lavcs-pro-pempty">Loading…</li>' );
		$.post( LavthemeCS.ajaxUrl, $.extend( { action: 'lavtheme_cs_history', nonce: LavthemeCS.nonce }, p ) )
			.done( function ( res ) {
				$list.empty();
				if ( ! res || ! res.success || ! res.data.items.length ) {
					$list.append( '<li class="lavcs-pro-pempty">No saved revisions yet for this tab. They build up each time you Save.</li>' );
					return;
				}
				res.data.items.forEach( function ( it ) {
					var $li = $( '<li class="lavcs-pro-hist-item"></li>' );
					$( '<span class="lavcs-pro-hist-meta"></span>' ).text( it.ago + ' · ' + it.lines + ' lines · ' + it.chars + ' chars' ).appendTo( $li );
					$( '<pre class="lavcs-pro-hist-prev"></pre>' ).text( String( it.content || '' ).slice( 0, 500 ) ).appendTo( $li );
					$( '<button type="button" class="button lavcs-pro-hist-load">Load into editor</button>' )
						.on( 'click', function () { loadRevision( it.content ); } ).appendTo( $li );
					$list.append( $li );
				} );
			} )
			.fail( function () { $list.html( '<li class="lavcs-pro-pempty">Could not load history.</li>' ); } );
	}

	/* ---------- actions / events ---------- */
	function doSave() {
		var btn = activeSaveBtn();
		if ( btn ) { btn.click(); }
	}
	function copyActive() {
		var cm = activeCM(); if ( ! cm ) { return; }
		var txt = cm.getValue();
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( txt );
		} else {
			var ta = document.createElement( 'textarea' ); ta.value = txt; document.body.appendChild( ta ); ta.select();
			try { document.execCommand( 'copy' ); } catch ( e ) {} document.body.removeChild( ta );
		}
		flashBtn( '.lavcs-pro-btn[data-act="copy"]', 'Copied ✓' );
	}
	function flashBtn( sel, txt ) {
		var $b = $bar.find( sel ); var old = $b.text();
		$b.text( txt ); setTimeout( function () { $b.text( old ); }, 1200 );
	}

	function isTyping( e ) {
		var t = e.target;
		return t && ( t.tagName === 'INPUT' || t.tagName === 'SELECT' );
	}

	$( function () {
		buildChrome();
		setTimeout( syncActive, 300 );
		// Tracking starts after the editors finish initialising, so first-paint
		// transients can never be mistaken for unsaved edits.
		setTimeout( function () { proReady = true; }, 1600 );

		// Re-sync the active editor + prefs whenever the user navigates the studio.
		$( document ).on( 'click', '.lavcs-navitem, .lavcs-pnavitem, .lavcs-tab', function () {
			setTimeout( syncActive, 60 );
		} );
		$( '#lavcs-context' ).on( 'change', function () { setTimeout( syncActive, 700 ); } );

		// Status-bar buttons.
		$bar.on( 'click', '.lavcs-pro-btn', function () {
			var act = this.getAttribute( 'data-act' );
			if ( act === 'find' ) { openFind( false ); }
			else if ( act === 'wrap' ) { prefs.wrap = ! prefs.wrap; savePrefs(); applyPrefsAll(); $( this ).toggleClass( 'is-on', prefs.wrap ); }
			else if ( act === 'fontup' ) { prefs.fontSize = Math.min( 22, prefs.fontSize + 1 ); savePrefs(); applyPrefsAll(); }
			else if ( act === 'fontdown' ) { prefs.fontSize = Math.max( 9, prefs.fontSize - 1 ); savePrefs(); applyPrefsAll(); }
			else if ( act === 'copy' ) { copyActive(); }
			else if ( act === 'format' ) { formatActive(); }
			else if ( act === 'preview' ) { openPreview(); }
			else if ( act === 'history' ) { openHistory(); }
			else if ( act === 'palette' ) { openPalette(); }
			else if ( act === 'help' ) { $help.prop( 'hidden', false ); }
		} );

		// Preview modal controls.
		$preview.on( 'click', '.lavcs-pro-btn', function () {
			var pv = this.getAttribute( 'data-pv' ), w = this.getAttribute( 'data-w' );
			if ( w ) { setPreviewWidth( w ); }
			else if ( pv === 'apply' ) { injectPreview(); }
			else if ( pv === 'open' ) { window.open( previewURL(), '_blank', 'noopener' ); }
			else if ( pv === 'close' ) { $preview.attr( 'hidden', 'hidden' ); }
		} );
		$preview.on( 'click', function ( e ) { if ( e.target === $preview[ 0 ] ) { $preview.attr( 'hidden', 'hidden' ); } } );

		// History modal close.
		$history.on( 'click', '.lavcs-pro-hist-close', function () { $history.attr( 'hidden', 'hidden' ); } );
		$history.on( 'click', function ( e ) { if ( e.target === $history[ 0 ] ) { $history.attr( 'hidden', 'hidden' ); } } );
		$bar.find( '.lavcs-pro-btn[data-act="wrap"]' ).toggleClass( 'is-on', prefs.wrap );

		// Palette interactions.
		$palette.on( 'input', '.lavcs-pro-pinput', function () { renderPalette( this.value ); } );
		$palette.on( 'click', '.lavcs-pro-pitem', function () { gotoItem( $( this ).data( 'it' ) ); } );
		$palette.on( 'click', function ( e ) { if ( e.target === $palette[ 0 ] ) { closePalette(); } } );
		$palette.on( 'keydown', '.lavcs-pro-pinput', function ( e ) {
			var $items = $palette.find( '.lavcs-pro-pitem' );
			var $cur = $items.filter( '.is-active' );
			if ( e.key === 'ArrowDown' ) { e.preventDefault(); var $n = $cur.next( '.lavcs-pro-pitem' ); if ( ! $n.length ) { $n = $items.first(); } $items.removeClass( 'is-active' ); $n.addClass( 'is-active' ); scrollIntoList( $n ); }
			else if ( e.key === 'ArrowUp' ) { e.preventDefault(); var $p = $cur.prev( '.lavcs-pro-pitem' ); if ( ! $p.length ) { $p = $items.last(); } $items.removeClass( 'is-active' ); $p.addClass( 'is-active' ); scrollIntoList( $p ); }
			else if ( e.key === 'Enter' ) { e.preventDefault(); gotoItem( $cur.data( 'it' ) ); }
			else if ( e.key === 'Escape' ) { closePalette(); }
		} );
		function scrollIntoList( $el ) { if ( $el && $el.length && $el[ 0 ].scrollIntoView ) { $el[ 0 ].scrollIntoView( { block: 'nearest' } ); } }

		// Find interactions.
		$find.on( 'input', '.lavcs-pro-find-q', function () { runFind(); } );
		$find.on( 'change', '.lavcs-pro-find-cs input', function () { runFind(); } );
		$find.on( 'keydown', '.lavcs-pro-find-q', function ( e ) {
			if ( e.key === 'Enter' ) { e.preventDefault(); if ( e.shiftKey ) { gotoHit( findState.idx - 1 ); } else { gotoHit( findState.idx + 1 ); } }
			else if ( e.key === 'Escape' ) { closeFind(); }
		} );
		$find.on( 'click', '.lavcs-pro-btn', function () {
			var a = this.getAttribute( 'data-fact' );
			if ( a === 'next' ) { gotoHit( findState.idx + 1 ); }
			else if ( a === 'prev' ) { gotoHit( findState.idx - 1 ); }
			else if ( a === 'rep' ) { replaceOne(); }
			else if ( a === 'repall' ) { replaceAll(); }
			else if ( a === 'close' ) { closeFind(); }
		} );

		// Help close.
		$help.on( 'click', '.lavcs-pro-help-close', function () { $help.prop( 'hidden', true ); } );
		$help.on( 'click', function ( e ) { if ( e.target === $help[ 0 ] ) { $help.prop( 'hidden', true ); } } );

		// Global keyboard shortcuts.
		$( document ).on( 'keydown', function ( e ) {
			var mod = e.ctrlKey || e.metaKey;
			if ( mod && ( e.key === 's' || e.key === 'S' ) ) { e.preventDefault(); doSave(); return; }
			if ( mod && ( e.key === 'k' || e.key === 'K' ) ) { e.preventDefault(); openPalette(); return; }
			if ( mod && ( e.key === 'f' || e.key === 'F' ) ) { var cm = activeCM(); if ( cm ) { e.preventDefault(); openFind( false ); } return; }
			if ( mod && ( e.key === 'h' || e.key === 'H' ) ) { var cmh = activeCM(); if ( cmh ) { e.preventDefault(); openFind( true ); } return; }
			if ( e.key === 'Escape' ) {
				if ( $palette && ! $palette.prop( 'hidden' ) ) { closePalette(); }
				else if ( $find && ! $find.prop( 'hidden' ) ) { closeFind(); }
				else if ( $preview && ! $preview.prop( 'hidden' ) ) { $preview.attr( 'hidden', 'hidden' ); }
				else if ( $history && ! $history.prop( 'hidden' ) ) { $history.attr( 'hidden', 'hidden' ); }
				else if ( $help && ! $help.prop( 'hidden' ) ) { $help.prop( 'hidden', true ); }
				else { $( '.lavcs-panel.is-fullscreen' ).removeClass( 'is-fullscreen' ); $( 'body' ).removeClass( 'lavcs-noscroll' ); }
				return;
			}
			if ( e.key === '?' && ! isTyping( e ) ) { e.preventDefault(); $help.prop( 'hidden', false ); }
		} );

		// Warn before leaving with unsaved edits.
		window.addEventListener( 'beforeunload', function ( e ) {
			if ( Object.keys( dirty ).length ) { e.preventDefault(); e.returnValue = ''; return ''; }
		} );
	} );
} )( jQuery );
