<?php
/**
 * Module registry.
 *
 * Returns the ordered list of feature-module classes the Module_Manager should
 * instantiate and boot. Order is boot order. Add a feature = add its class here
 * (and create it under src/Modules/). Filterable via the `lavzen/modules` hook.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

return array(
	// Populated per build phase (Phase 4). Examples once built:
	// \Lavzen\Modules\Seo\Seo_Module::class,
	// \Lavzen\Modules\Performance\Performance_Module::class,
	// \Lavzen\Modules\Security\Security_Module::class,
	// \Lavzen\Modules\Code_Studio\Code_Studio_Module::class,
	// \Lavzen\Modules\Edd\Edd_Module::class,
	// \Lavzen\Modules\Blog\Blog_Module::class,
);
