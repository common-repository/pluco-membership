<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\classes;

use PLCOMembership\admin\classes\PLCOM_Admin_Init;
use PLCOMembership\admin\classes\PLCOM_DB_Manager;
use PLCOMembership\front\classes\PLCOM_Front_Init;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}


/**
 * Initialize the plugin
 *
 * Class PLCOM_Initialize
 *
 * @package PLCO_Membership\classes
 */
class PLCOM_Initialize {

	/**
	 * The plugin's path
	 *
	 * @var string
	 */
	private $plugin_path = null;

	public function __construct() {
		$this->plugin_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->load_dashboard();
	}



	protected function load_dashboard() {
		add_action( 'plugins_loaded', array( $this, 'add_dashboard_to_globals' ) );
		add_action( 'after_setup_theme', array( $this, 'initialize' ), 99 );
		add_action( 'plco_dashboard_loaded', array($this, 'db_check'), 10 );
	}

	public function initialize() {
		$this->init();
		$this->admin_init();
	}

	/**
	 * Initialize the front end
	 */
	private function init() {
		new PLCOM_Front_Init("membership", false );
	}

	/**
	 * Initialize the backend
	 */
	private function admin_init() {
		if ( is_admin() ) {
			new PLCOM_Admin_Init("membership", true );
		}
	}

	/**
	 * Check if we have the latest db tables if not we should update them
	 */
	public function db_check() {
		new PLCOM_DB_Manager();
	}


	/**
	 * Include the dashborad files to be shared between the products
	 */
	public function add_dashboard_to_globals() {
		$dashboard_path      = $this->plugin_path . 'dashboard';
		$version_path = $dashboard_path . '/version.php';

		if ( is_file( $version_path ) ) {
			$version                                  = require_once( $version_path );
			$GLOBALS['plco_versions'][ $version ] = array(
				'path'   => $dashboard_path . '/dashboard.php',
			);
		}
	}
}
