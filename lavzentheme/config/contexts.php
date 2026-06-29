<?php
/**
 * Context registry.
 *
 * Each row defines ONE front-end context. Adding a context = adding a row here
 * (no new PHP file) — this is what replaces the legacy per-context clone files.
 *
 *   when       : conditional tag / callable that returns true on that context.
 *   css / js   : asset handles → assets/dist/{css,js}/<handle>.{css,js}
 *   body_class : class added to <body>.
 *   deps       : class/function names that must exist for the context to apply.
 *
 * Custom conditionals (lavzen_is_*) are provided by their feature modules; until
 * a module loads, an unknown conditional simply never matches (no fatal).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

return array(
	'single'  => array(
		'when'       => 'is_single',
		'css'        => array( 'single' ),
		'js'         => array( 'single' ),
		'body_class' => 'ctx-single',
	),
	'blog'    => array(
		'when'       => 'is_home',
		'css'        => array( 'blog' ),
		'js'         => array(),
		'body_class' => 'ctx-blog',
	),
	'404'     => array(
		'when'       => 'is_404',
		'css'        => array( '404' ),
		'js'         => array(),
		'body_class' => 'ctx-404',
	),
	'download' => array(
		'when'       => 'lavzen_is_download',
		'css'        => array( 'download' ),
		'js'         => array( 'download' ),
		'body_class' => 'ctx-download',
		'deps'       => array( 'Easy_Digital_Downloads' ),
	),
	'shop'    => array(
		'when'       => 'lavzen_is_shop',
		'css'        => array( 'shop' ),
		'js'         => array( 'shop' ),
		'body_class' => 'ctx-shop',
		'deps'       => array( 'Easy_Digital_Downloads' ),
	),
	'account' => array(
		'when'       => 'lavzen_is_account',
		'css'        => array( 'account' ),
		'js'         => array( 'account' ),
		'body_class' => 'ctx-account',
	),
	'auth'    => array(
		'when'       => 'lavzen_is_auth',
		'css'        => array( 'auth' ),
		'js'         => array( 'auth' ),
		'body_class' => 'ctx-auth',
	),
);
