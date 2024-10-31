<?php
/**
 * PluginsCorner - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\admin\classes;


use PLCODashboard\classes\PLCO_Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCOM_Const implements PLCO_Constants {

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
	const API_URL = 'http://local.plco.com/wp-json/';

	/**
	 * Membership Statuses
	 */
	const STATUSES = array(
		"pending" => "PENDING",
		'approval_pending' => "APPROVAL_PENDING",
		'active' => "ACTIVE",
		'suspended' => "SUSPENDED",
		'pending_cancelation' => "PENDING_CANCELATION",
		'canceled' => "CANCELED",
		'refunded' => "REFUNDED",
		'requires_action' => "REQUIRES_ACTION",
		'passed' => "PASSSED",
	);

	const DEFAULT_RESTRICTION_MESSAGE = '<p>This content is restricted to members. To continue log in or purchase a membership.</p> [plcom_login_form]';

	const DEFAULT_EXPIRED_RESTRICTION_MESSAGE = '<p>Your subscription has expired. To continue please renew your membership at your {{account_page}} Page</p> ';

	const SUCCESS_TEMPLATE_SUBJECT = 'Subscription Payment Confirmation';

	const SUCCESS_TEMPLATE_MESSAGE = '<p>Dear {{user_name}}</p><p>This is a payment receipt for your membership:</p><p>{{invoice_data}}</p><p>You may review your payment history at any time by logging in to your account.</p><p>Note: This email will serve as an official receipt for this payment.</p>';

	const CANCEL_TEMPLATE_SUBJECT = 'Subscription Canceled';

	const CANCEL_TEMPLATE_MESSAGE = '<p>Dear {{user_name}}</p><p>We have canceled the following Membership</p><p>Membership name: {{membership_name}}</p>';

	const SUCCESS_CONTENT = '<p>Thank you for your subsciption</p><p>Thank you for joining our website, your payment is being processed and your membership will be activated as soon as the payment is confirmend</p>';

	const CANCEL_CONTENT = '<p>We are sorry to see you go</p><p>Thank you for being a member, your subscription has been canceled and your membership will be terminetate at the end of this billing cycle</p>';

	const REQUIRES_ACTION_TEMPLATE_SUBJECT = '!!Important!! Subscription Payment Requires Your Attention';
	const REQUIRES_ACTION_TEMPLATE_MESSAGE = '<p>Dear {{user_name}}</p><p>The payment for your subscription requires your action. Your bank requires all payments to be validated before being processed. To finalize your payment please log intoyour account ({{account_url}}) and click on the "Confirm Payment" button for your last invoice</p>';

	const USER_CREATED_SUBJECT =  "New User Created";
	const USER_CREATED_MESSAGE = '<p>Dear {{user_name}}</p><p>Here are your login details:</p><p>{{login_details}}</p>';


	/**
	 * Type of recurrences
	 */
	const RECURRENCE_TYPES = array(
		array( 'ID' => 1, 'name' => 'Days' ),
		array( 'ID' => 2, 'name' => 'Months' ),
		array( 'ID' => 3, 'name' => 'Years' ),
	);

	/**
	 * Used Currencies
	 */
	const CURRENCIES = array(
		array( 'ID' => 1, 'handle' => 'usd', 'name' => 'US Dollar', 'sign' => '$' ),
		array( 'ID' => 2, 'handle' => 'eur', 'name' => 'Euro', 'sign' => '€' ),
		array( 'ID' => 3, 'handle' => 'ron', 'name' => 'Romanian Leu', 'sign' => 'RON' ),
	);

	const TABS = array(
		array( 'ID' => "membership_levels", 'name' => "Membership Levels" ),
		array( 'ID' => "member", 'name' => "Member", 'show' => false ),
		array( 'ID' => "active_members", 'name' => "Memberships", "children" => array(
			array( 'ID' => "active_members", 'name' => "Active Memberships" ),
			array( 'ID' => "non_members", 'name' => "Non Memberships" ),
			array( 'ID' => "pending_members", 'name' => "Pending Memberships" ),
			array( 'ID' => "suspended_members", 'name' => "Suspended Memberships" ),
			array( 'ID' => "canceled_members", 'name' => "Canceled Memberships" ),
		) ),
		array( 'ID' => "payments", 'name' => "Payments" ),
		array( 'ID' => "payment_methods", 'name' => "Payment Methods" ),
		array( 'ID' => "email_templates", 'name' => "Email Templates" ),
		array( 'ID' => "roles", 'name' => "Roles" ),
		array( 'ID' => "settings", 'name' => "Settings" ),
		array( 'ID' => "shortcodes", 'name' => "Shortcodes" ),
		array( 'ID' => "paid_invoices", 'name' => "Invoices", "children" => array(
//			array( 'ID' => "unpaid_invoices", 'name' => "Unpaid Invoices" ),
			array( 'ID' => "paid_invoices", 'name' => "Invoices" ),
			array( 'ID' => "invoice_settings", 'name' => "Invoice Generation Settings" ),
			array( 'ID' => "template_settings", 'name' => "Template Settings" ),
		)),
		array( 'ID' => "integrations", 'name' => "Integrations" ),
		array( 'ID' => "statistics", 'name' => "Statistics", "children" => array(
			array( 'ID' => "statistics", 'name' => "Members Statistics" ),
			array( 'ID' => "revenue", 'name' => "Revenue" ),
			array( 'ID' => "revenue_growth", 'name' => "Revenue Growth" ),
		) ),
	);

	/**
	 * @var string
	 */
	const PLUGIN_PREFIX = 'plcom_';

	/**
	 * @var \WP_Query|$wpdb
	 */
	private static $wpdb;

	/**
	 * @var null
	 */
	private static $instance = null;

	/**
	 * PLCOM_Const constructor.
	 */
	private function __construct() {
		global $wpdb;

		self::$wpdb = $wpdb;
	}

	/**
	 * Get the wpdb object (for performance we're singletonig it)
	 *
	 * @return PLCOM_Const|null
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

		return $code === 200 ? $body->success : $body->error;
	}

	/**
	 * Send the Request
	 *
	 * @param string $endpoint
	 * @param array  $body
	 * @param string $method
	 *
	 * @return mixed
	 */
	public static function send_request( $endpoint = '', $body = array(), $method = 'POST' ) {
//		$license = PLCO_License_Manager::get_license();
		$license = array();

		$args = array(
			'sslverify' => false,
			'timeout'   => 120,
			'headers'   => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $license['api_token'],
			),
			'body'      => $body,
		);
		if ( $method == 'POST' || $method == 'PUT' ) {
			$fn = 'wp_remote_post';
		} else {
			$fn = 'wp_remote_get';
		}

		$response = $fn( self::API_URL . $endpoint, $args );

		return $response;

	}

	/**
	 * wrapper over the wp_enqueue_style function
	 * it will add the plugin version to the style link if no version is specified
	 *
	 * @param             $handle
	 * @param string|bool $src
	 * @param array       $deps
	 * @param bool|string $ver
	 * @param string      $media
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
	 * @param bool  $src
	 * @param array $deps
	 * @param bool  $ver
	 * @param bool  $in_footer
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
}
