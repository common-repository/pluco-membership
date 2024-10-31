<?php


namespace PLCODashboard;

use PLCODashboard\admin\classes\PLCO_Admin_Init;
use PLCODashboard\admin\classes\PLCO_DB_Manager;
use PLCODashboard\front\classes\PLCO_Front_Init;




class PLCO_Dashboard {

	public function __construct() {
		$this->db_check();
		$this->load_dependencies();
		$this->initialize();
	}

	private function load_dependencies() {
		/**
		 * Triggered to let all the plugins know that the necessary data is loaded so they can also load
		 */
		do_action("plco_dashboard_loaded");

	}

	private function initialize() {
		$this->init();
		$this->admin_init();
		/**
		 * The hook should be triggered when the dashboard has everything ready on the backend side
		 */
		do_action("plco_dashboard_fully_loaded");
	}

	/**
	 * Initialize the front end
	 */
	private function init() {
		new PLCO_Front_Init();
	}

	/**
	 * Initialize the backend
	 */
	private function admin_init() {
		if ( is_admin() ) {
			new PLCO_Admin_Init('', true );
		}
	}

	/**
	 * Check if we have the latest db tables if not we should update them
	 */
	private function db_check() {
		new PLCO_DB_Manager();
	}
}


new PLCO_Dashboard();
