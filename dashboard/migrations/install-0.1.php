<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class PLCOM_Install_DB
 */
class PLCO_Install_DB {
	/**
	 * Global wpdb instance
	 * @var $wpdb \wpdb
	 */
	private $wpdb;

	/**
	 * @var string
	 */
	private $table_name;

	/**
	 * @var string
	 */
	private $plugin_prefix = 'plco_';

	/**
	 * @var $prefix string
	 */
	private $prefix;

	/**
	 * CPT_PC_Install_DB constructor.
	 */
	public function __construct() {
		/**
		 * don't do squat if we don't have an upgrade set up
		 */
		if ( ! defined( 'PLCO_DATABASE_UPGRADE' ) ) {
			return;
		}
		/**
		 * set the wpdb within the class
		 */
		global $wpdb;
		/** @var $wpdb \wpdb */
		$this->wpdb   = $wpdb;
		$this->prefix = $this->wpdb->prefix . $this->plugin_prefix;

		$this->create_tables();
	}

	/**
	 * Wrapper for all the tables needed on the install
	 */
	private function create_tables() {
		$this->create_connections_table();
	}

	/**
	 * Create the Connecctions table
	 */
	private function create_connections_table() {

		$this->table_name = $this->prefix . 'connections';


		$sql = "CREATE TABLE  IF NOT EXISTS {$this->table_name} (
			ID INT( 11 ) NOT NULL AUTO_INCREMENT,
			connection_name VARCHAR( 255 ) NOT NULL,
			connection_type VARCHAR( 255 ) NOT NULL,
			api_key VARCHAR( 255 ),
			api_secret VARCHAR( 255 ) ,
			sandbox INT(11) DEFAULT '0',
			extra TEXT DEFAULT '',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";


		$this->wpdb->query( $sql );

		$sql = "INSERT INTO {$this->table_name} (connection_name,connection_type) VALUES ('wordpress','mail')";

		$this->wpdb->query( $sql );
	}
}
