/**
 * Code Studio editor — vanilla JS driving the unified AJAX layer.
 *
 * Populates the surface/field selectors from LavzenCS.surfaces, loads the
 * current value (override or file default) on change, and saves/resets/restores
 * via admin-ajax. No build step, no dependencies.
 *
 * @package Lavzen
 */
( function () {
	'use strict';

	var CS = window.LavzenCS || {};
	var $ = function ( id ) { return document.getElementById( id ); };

	var scopeSel = $( 'lavcs-scope' );
	var typeSel  = $( 'lavcs-type' );
	var editor   = $( 'lavcs-editor' );
	var statusEl = $( 'lavcs-status' );
	var flagEl   = $( 'lavcs-flag' );

	if ( ! scopeSel || ! editor || ! CS.surfaces ) {
		return;
	}

	function section() {
		var s = CS.surfaces[ scopeSel.value ];
		return s ? s.section : 'design';
	}

	function setStatus( msg, isError ) {
		statusEl.textContent = msg || '';
		statusEl.className = 'lavcs-status' + ( isError ? ' is-error' : '' );
	}

	function post( action, extra ) {
		var body = new URLSearchParams();
		body.set( 'action', action );
		body.set( 'nonce', CS.nonce );
		body.set( 'scope', scopeSel.value );
		body.set( 'section', section() );
		body.set( 'type', typeSel.value );
		Object.keys( extra || {} ).forEach( function ( k ) { body.set( k, extra[ k ] ); } );
		return fetch( CS.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( function ( r ) { return r.json(); } );
	}

	function fillScopes() {
		Object.keys( CS.surfaces ).forEach( function ( key ) {
			var o = document.createElement( 'option' );
			o.value = key;
			o.textContent = CS.surfaces[ key ].label;
			scopeSel.appendChild( o );
		} );
	}

	function fillTypes() {
		var types = ( CS.surfaces[ scopeSel.value ] || {} ).types || [];
		typeSel.innerHTML = '';
		types.forEach( function ( t ) {
			var o = document.createElement( 'option' );
			o.value = t;
			o.textContent = t.toUpperCase();
			typeSel.appendChild( o );
		} );
	}

	function load() {
		editor.value = '';
		setStatus( CS.i18n.loading );
		flagEl.textContent = '';
		post( 'lavzen_cs_load' ).then( function ( res ) {
			if ( res && res.success ) {
				editor.value = res.data.value || '';
				flagEl.textContent = res.data.is_override ? '● override' : 'default';
				flagEl.className = 'lavcs-flag' + ( res.data.is_override ? ' is-override' : '' );
				setStatus( '' );
			} else {
				setStatus( ( res && res.data && res.data.message ) || CS.i18n.error, true );
			}
		} ).catch( function () { setStatus( CS.i18n.error, true ); } );
	}

	function save() {
		setStatus( '…' );
		post( 'lavzen_cs_save', { content: editor.value } ).then( function ( res ) {
			if ( res && res.success ) {
				setStatus( res.data.message || CS.i18n.saved );
				load();
			} else {
				setStatus( ( res && res.data && res.data.message ) || CS.i18n.error, true );
			}
		} ).catch( function () { setStatus( CS.i18n.error, true ); } );
	}

	function reset() {
		if ( ! window.confirm( CS.i18n.confirm ) ) { return; }
		post( 'lavzen_cs_reset' ).then( function ( res ) {
			if ( res && res.success ) {
				editor.value = res.data.value || '';
				setStatus( res.data.message || '' );
				load();
			} else {
				setStatus( CS.i18n.error, true );
			}
		} ).catch( function () { setStatus( CS.i18n.error, true ); } );
	}

	function restore() {
		post( 'lavzen_cs_restore' ).then( function ( res ) {
			if ( res && res.success ) {
				editor.value = res.data.value || '';
				setStatus( res.data.message || '' );
				load();
			} else {
				setStatus( ( res && res.data && res.data.message ) || CS.i18n.error, true );
			}
		} ).catch( function () { setStatus( CS.i18n.error, true ); } );
	}

	scopeSel.addEventListener( 'change', function () { fillTypes(); load(); } );
	typeSel.addEventListener( 'change', load );
	$( 'lavcs-save' ).addEventListener( 'click', save );
	$( 'lavcs-reset' ).addEventListener( 'click', reset );
	$( 'lavcs-restore' ).addEventListener( 'click', restore );

	// Ctrl/Cmd+S saves.
	editor.addEventListener( 'keydown', function ( e ) {
		if ( ( e.ctrlKey || e.metaKey ) && 's' === e.key.toLowerCase() ) {
			e.preventDefault();
			save();
		}
	} );

	fillScopes();
	fillTypes();
	load();
}() );
