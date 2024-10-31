<?php
/**
 * PluginsCorner - https://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\classes;


interface PLCO_Constants {

	/**
	 * Get the instance
	 */
	public static function get_instance();

	/**
	 * Get the wpdb object (for performance we're singletonig it)
	 */
	public static function get_wpdb();

	/**
	 * Get the plugin path
	 */
	public static function plugin_path();

	/**
	 * Get the plugin url
	 */
	public static function plugin_uri();

	/**
	 * PluginsCorner components path
	 *
	 * @param string $file
	 * @param string $dir
	 */
	public static function path( $file = '', $dir = '' );

	/**
	 * PluginsCorner components url
	 *
	 * @param string $file
	 * @param string $dir
	 */
	public static function url( $file = '', $dir = '' );

}