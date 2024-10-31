<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\endpoints;

use PLCODashboard\classes\PLCO_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Email_Templates_Controller extends PLCO_REST_Controller {

	public $base = 'email_templates';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_email_templates' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
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
	public function save_email_templates( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();

		update_option('plcom_success_template_subject', $params['success_template_subject']);
		update_option('plcom_success_template_message', $params['success_template_message']);
		update_option('plcom_cancel_template_subject', $params['cancel_template_subject']);
		update_option('plcom_cancel_template_message', $params['cancel_template_message']);
		update_option('plcom_requires_action_template_subject', $params['requires_action_template_subject']);
		update_option('plcom_requires_action_template_message', $params['requires_action_template_message']);

		return new WP_REST_Response( $params, 200 );
	}
}
