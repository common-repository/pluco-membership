<?php

spl_autoload_register(function ($class_name) {

	$namespaces = array(
		'PLCODashboard',
	);

	$namespace_exploded = explode('\\', $class_name);

	if(count($namespace_exploded) > 0) {
		$namespace = $namespace_exploded[0];
	} else {
		return;
	}

	if (!in_array($namespace, $namespaces)) {
		return;
	}

	$exploded = explode('\\', str_replace($namespace .'\\', '', str_replace('_', '-', $class_name)));
	$exploded[array_key_last($exploded)] = 'class-' . $exploded[array_key_last($exploded)] . '.php';
	$path = strtolower(implode('/', $exploded));

	include $path;
});

/**
 * Returns the dashboard version and loads the correct one
 */
if ( ! function_exists( 'plco_load_the_dashboard' ) ) {

	add_action( 'after_setup_theme', 'plco_load_the_dashboard', 9 );

	function plco_compare_versions( $v1, $v2 ) {
		return version_compare( $v1, $v2 );
	}

	/**
	 * Load test
	 */
	function plco_load_the_dashboard() {
		uksort( $GLOBALS['plco_versions'], 'plco_compare_versions' );

		$last_dash = $GLOBALS['plco_versions'] = end( $GLOBALS['plco_versions'] );

		require_once $last_dash['path'];
	}
}


return "1.0.1";
