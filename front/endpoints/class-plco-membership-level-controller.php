<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\endpoints;

use PLCODashboard\classes\PLCO_REST_Controller;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use PLCOMembership\front\classes\PLCOM_Membership_Level;
use PLCOMembership\pro\front\classes\PLCOM_Pro_Membership_Level;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Membership_Level_Controller extends PLCO_REST_Controller {

	public $base = 'membership_level';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'edit_membership_level' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_membership_level' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'edit_membership_level' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Saves the membership to the DB
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function edit_membership_level( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$is_pro = filter_var( $request->get_header( 'X-Is-Pro' ), FILTER_VALIDATE_BOOLEAN );
		$model  = $is_pro ?
			new PLCOM_Pro_Membership_Level( (object) $params ) :
			new PLCOM_Membership_Level( (object) $params );

		$result = $model->getRepository()->persist( $model );

		if ( $result ) {
			return new WP_REST_Response( $result, 200 );
		}

		return new \WP_Error( 'code', __( 'Failed to edit membership. Try again. If the error persists please contact our technical team.', PLCOM_Const::T ) );
	}

	/**
	 * Deletes a membership level
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_membership_level( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$model  = new PLCOM_Membership_Level( (object) $params );
		foreach ( $model->getRecurrences() as $recurrence ) {
			foreach ( $recurrence->getPlans() as $plan ) {
				$plan->getRepository()->destroy( $plan );
			}

			$recurrence->getRepository()->destroy( $recurrence );
		}
		$result = $model->getRepository()->destroy( $model );

		if ( $result ) {
			return new WP_REST_Response( $result, 200 );
		}

		return new \WP_Error( 'code', __( 'Failed to delete membership. Try again. If the error persists please contact our technical team.', PLCOM_Const::T ) );
	}
}
