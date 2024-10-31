<?php
/**
 * PluginsCorner - https://pluginscorner.com.com
 *
 * @package pluco-membership
 */

namespace PLCODashboard\admin\classes;

use PLCODashboard\classes\PLCO_Constants;
use PLCODashboard\classes\PLCO_License_Manager;
use ReflectionClass;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Const implements PLCO_Constants {

	/**
	 * Plugins Corner Translation Domain
	 */
	const T = 'plco-lang';

	/**
	 * Plugin Version
	 */
	const VERSION = '1';

	/**
	 * Database version
	 */
	const DB_VERSION = '0.1';

	/**
	 * Rest api namespace
	 */
	const REST_NAMESPACE = 'plco/v1';

	/**
	 * PluginsCorner API URL
	 */
	const API_URL = 'https://pluginscorner.com/wp-json/';

	/**
	 * @var string
	 */
	const PLUGIN_PREFIX = 'plco_';

	/**
	 * Available connections
	 */
	const AVAILABLE_CONNECTION = array(
		array( 'ID' => 1, 'handle' => 'paypal', 'name' => 'PayPal', 'type' => 'account' ),
		array( 'ID' => 2, 'handle' => 'stripe', 'name' => 'Stripe', 'type' => 'card' ),
	);

	/**
	 * API Sandbox URL for Paypal
	 */
	const PAYPAL_API_SANDBOX_URL = 'https://api-m.sandbox.paypal.com';

	/**
	 * API URL for Paypal
	 */
	const PAYPAL_API_URL = 'https://api-m.paypal.com';

	/**
	 * Statuses for support
	 */
	const TOPIC_STATUS = [
		1 => 'Open',
		2 => 'Replied',
		0 => 'Closed',
	];


	/**
	 * @var \WP_Query|$wpdb
	 */
	private static $wpdb;


	/**
	 * @var null
	 */
	private static $instance = null;

	/**
	 * PLCO_Const constructor.
	 */
	private function __construct() {
		global $wpdb;

		self::$wpdb = $wpdb;
	}

	/**
	 * Get the wpdb object (for performance we're singletonig it)
	 *
	 * @return PLCO_Const|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the wpdb object (for performance we're singletonig it)
	 *
	 * @return \WP_Query|$wpdb
	 */
	public static function get_wpdb() {
		if ( self::$instance == null ) {
			self::get_instance();
		}

		return self::$wpdb;
	}

	/**
	 * Get the plugin path
	 *
	 * @return string
	 */
	public static function plugin_path() {
		return plugin_dir_path( dirname( dirname( __FILE__ ) ) );
	}

	/**
	 * Get the plugin url
	 *
	 * @return string
	 */
	public static function plugin_uri() {
		return plugin_dir_url( dirname( dirname( __FILE__ ) ) );
	}

	/**
	 * Get the dashboard path
	 *
	 * @return string
	 */
	public static function dashboard_path() {
		return dirname( dirname( __FILE__ ) );
	}

	public static function dashboard_uri() {
		$file_path = __DIR__;

		return str_replace( $_SERVER['DOCUMENT_ROOT'], '', $file_path );
	}

	/**
	 * PluginsCorner components path
	 *
	 * @param string $file
	 * @param string $dir
	 *
	 * @return string
	 */
	public static function path( $file = '', $dir = '' ) {
		$path = self::plugin_path();

		if ( ! empty( $dir ) ) {
			$path .= $dir . DIRECTORY_SEPARATOR;
		}

		return $path . ltrim( $file, '\\/' );
	}

	/**
	 * PluginsCorner components url
	 *
	 * @param string $file
	 * @param string $dir
	 *
	 * @return string
	 */
	public static function url( $file = '', $dir = '' ) {
		return self::plugin_uri() . $dir . '/' . ltrim( $file, '\\/' );
	}

	/**
	 * Return the class name in the proper format
	 *
	 * @param        $file
	 * @param string $extension
	 *
	 * @return mixed|string
	 */
	public static function build_class_name( $file, $extension = '' ) {
		$base       = basename( $file, '.php' );
		$class_name = str_replace( 'class-', '', $base );
		$class_name = str_replace( 'plco-', '', $class_name );
		$class_name = str_replace( '-', '_', $class_name );
		$class_name = implode( '_', array_map( 'ucfirst', explode( '_', $class_name ) ) );
		$class_name = 'PLCO_' . $class_name;

		if ( ! empty( $extension ) ) {
			$class_name .= '_' . $extension;
		}

		return $class_name;
	}

	public static function process_api_response( $response ) {

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body );

		return $body;
	}

	/**
	 * Send the Request
	 *
	 * @param string $endpoint
	 * @param array $body
	 * @param string $method
	 *
	 * @return mixed
	 */
	public static function send_request( $endpoint = '', $body = array(), $method = 'POST' ) {
		$license = PLCO_License_Manager::get_license();

		$args = array(
			'sslverify' => false,
			'timeout'   => 120,
			'headers'   => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $license['jwt_access'],
			),
			'body'      => $body,
		);

		if($method !== "GET") {
			$args['method'] = $method;
		}
		if ( $method == 'POST' || $method == 'PUT' || $method == 'DELETE' ) {
			$fn = 'wp_remote_post';
		} else {
			$fn = 'wp_remote_get';
		}

		$response = $fn( PLCO_Const::get_api_url() . $endpoint, $args );

		return $response;

	}

	/**
	 * wrapper over the wp_enqueue_style function
	 * it will add the plugin version to the style link if no version is specified
	 *
	 * @param             $handle
	 * @param string|bool $src
	 * @param array $deps
	 * @param bool|string $ver
	 * @param string $media
	 */
	public static function enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' ) {
		if ( $ver === false ) {
			$ver = self::VERSION;
		}
		wp_enqueue_style( $handle, $src, $deps, $ver, $media );
	}

	/**
	 * wrapper over the wp_enqueue_script function
	 * it will add the plugin version to the script source if no version is specified
	 *
	 * @param       $handle
	 * @param bool $src
	 * @param array $deps
	 * @param bool $ver
	 * @param bool $in_footer
	 */
	public static function enqueue_script( $handle, $src = false, $deps = array(), $ver = false, $in_footer = false ) {
		if ( $ver === false ) {
			$ver = self::VERSION;
		}

		if ( defined( 'PLCO_DEBUG' ) && PLCO_DEBUG ) {
			$src = preg_replace( '#\.min\.js$#', '.js', $src );
		}

		wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
	}

	/**
	 * Fethces the API URL
	 *
	 * @return string
	 */
	public static function get_api_url() {

		if(file_exists( PLCO_Const::plugin_path() . 'dashboard/admin/classes/class-plco-urls.php' )) {
			if ( defined( 'PLCO_DEBUG' ) && PLCO_DEBUG ) {
				return PLCO_URLS::LOCAL_URL;
			}

			if ( defined( 'PLCO_TEST' ) && PLCO_TEST ) {
				return PLCO_URLS::TEST_URL;
			}

			return PLCO_URLS::STAGE_URL;
		}

		return self::API_URL;
	}
}
