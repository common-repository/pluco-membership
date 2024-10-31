<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\endpoints;

use PLCODashboard\classes\PLCO_License_Manager;
use PLCODashboard\classes\PLCO_REST_Controller;
use PLCOMembership\admin\classes\PLCOM_Const;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_General_Data_Controller extends PLCO_REST_Controller {

	public $base = 'general_data';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_general_data' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/converge', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'converge' ),
				'permission_callback' => array( $this, 'no_permission_required' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Saves the general data to the DB
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function save_general_data( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();

		foreach ($params as $key => $value) {
			if(is_array($value) && isset($value['ID']) && $value['ID'] === "" && !empty($value['name'])) {
				$content = "";

				if($key === "registration_page") {
					$content = "[plco_registration_form]";
				}

				if($key === "account_page") {
					$content = "[plco_account_form]";
				}

				if($key === "success_page") {
					$content = PLCOM_Const::SUCCESS_CONTENT;;
				}

				if($key === "cancel_page") {
					$content = PLCOM_Const::CANCEL_CONTENT;
				}

				$id = wp_insert_post(array(
					'post_title'    => wp_strip_all_tags( $value['name'] ),
					'post_type'    => 'page',
					'post_status'   => 'publish',
					'post_content'   => $content,
				));

				$params[$key]['ID'] = $id;
				$params[$key]['url'] = get_the_guid( $id);
			}
		}

		update_option('plcom_registration_page', $params['registration_page']);
		update_option('plcom_success_page', $params['success_page']);
		update_option('plcom_account_page', $params['account_page']);
		update_option('plcom_cancel_page', $params['cancel_page']);
		update_option('plcom_multiple_memberships', !!$params['multiple_memberships']);
		update_option('plcom_allow_users_to_admin', $params['allow_users_to_admin']);
		update_option('plcom_hide_admin_bar', $params['hide_admin_bar']);
		update_option('plcom_allow_account_delete', $params['allow_account_delete']);
		update_option('plcom_allow_non_members_registration', $params['allow_non_members_registration']);
		update_option('plcom_content_restriction_message', $params['content_restriction_message']);
		update_option('plcom_expired_restriction_message', $params['expired_restriction_message']);
		update_option( 'plcom_cancel_period', $params['cancel_period'] ?? 30 );
		update_option( 'plcom_grace_period', $params['grace_period'] ?? 15 );

		$params = apply_filters('plcom_after_general_data_save', $params);

		return new WP_REST_Response( $params, 200 );
	}

	/**
	 * @param $request
	 *
	 * @return WP_REST_Response
	 */
	public function converge($request) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();

		$license = PLCO_License_Manager::get_license();

		if($license['control']) {
			update_option( 'plcom_convex_info', 1 );
		} else {
			update_option( 'plcom_convex_info', 0 );
		}

		return new WP_REST_Response( true, 200 );
	}
}
