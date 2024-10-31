<?php
/**
 * PluginsCorner - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\admin\classes;

use PLCODashboard\classes\PLCO_Abstract_Helpers;
use PLCODashboard\classes\PLCO_Connection;
use PLCOMembership\admin\classes\PLCOM_Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Helpers extends PLCO_Abstract_Helpers {

	public static $connections_table;

	public function __construct( $const = '', $component_name = '' ) {
		if ( ! $const ) {
			$const = PLCO_Const::get_instance();
		}
		parent::__construct( $const, $component_name );

		self::$connections_table = self::$wpdb->prefix . $const::PLUGIN_PREFIX . "connections";
	}

	/**
	 * Eqneuque Dashboard css and scripts / this should be available everywhere that's why it's not in the dashboard
	 * component
	 */
	public static function enqueue_dashboard() {

		self::$const::enqueue_script( 'plco-materialize', PLCO_Const::plugin_uri() . 'admin/js/dist/materialize.min.js', array(
			'jquery',
			'backbone',
		), false, true );

		self::$const::enqueue_style( 'plco-materialize', PLCO_Const::plugin_uri() . 'admin/css/materialize.css' );
		self::$const::enqueue_style( 'plco-styles', PLCO_Const::plugin_uri() . 'admin/css/styles.css' );
	}

	/**
	 * Fetches a connection by it's handle
	 *
	 * @param $connection_name
	 *
	 * @return PLCO_Connection
	 */
	public static function get_connection_by_name( $connection_name ) {
		$query = 'SELECT * FROM ' . self::$connections_table . ' WHERE connection_name = ' . "'" . $connection_name . "'";

		$result = self::$wpdb->get_row( $query );

		if ( $result ) {
			return new PLCO_Connection( self::$wpdb->get_row( $query ) );
		} else {
			return false;
		}


	}

	/**
	 * Fetches a connection by it's ID
	 *
	 * @param $connection_name
	 *
	 * @return PLCO_Connection
	 */
	public static function get_connection_by_id( $ID ) {
		$query = 'SELECT * FROM ' . self::$connections_table . ' WHERE ID = ' . $ID;

		return new PLCO_Connection( self::$wpdb->get_row( $query ) );
	}

	/**
	 * Fetch all connections
	 *
	 * @return array|object|null
	 */
	public static function get_connections() {
		$query   = 'SELECT * FROM ' . self::$connections_table;
		$results = self::$wpdb->get_results( $query );

		foreach ( $results as $key => $result ) {
			$results[ $key ] = new PLCO_Connection( $result );
		}

		return $results;
	}

}
