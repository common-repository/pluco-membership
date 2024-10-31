<?php
/**
 * PluginsCorner - https://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\classes;

use PLCODashboard\admin\classes\PLCO_Const;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

abstract class PLCO_Abstract_Helpers {

	/**
	 * @var \WP_Query|\wpdb
	 */
	protected static $wpdb;

	/**
	 * @var
	 */
	protected static $component_name;

	/**
	 * @var PLCO_Actions_Loader
	 */
	protected $loader;

	/**
	 * @var PLCO_Const
	 */
	protected static $const;

	/**
	 * PLCO_Abstract_Helpers constructor.
	 *
	 * @param string $component_name
	 */
	public function __construct( $const, $component_name = '' ) {
		self::$const = $const;
		self::$wpdb  = self::$const::get_wpdb();

		$this->set_component( $component_name );

		/**
		 * Initialize the actions loader
		 */
		$this->loader = new PLCO_Actions_Loader();
		$this->add_actions();
		$this->loader->run();
	}


	/**
	 * Abstract actions and filters to be extended
	 */
	public function add_actions() {

	}

	/**
	 * @param $component_name
	 */
	protected function set_component( $component_name ) {
		self::$component_name = $component_name;
	}

	/**
	 * @param $path
	 */
	protected function load_helper( $path ) {
		$file = self::$const::path( $path, self::$component_name );

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Checks if we have a pro version of the plugin
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return file_exists( PLCOM_Const::plugin_path() . 'pro/admin/classes/class-plcom-pro-admin-init.php' );
	}
}
