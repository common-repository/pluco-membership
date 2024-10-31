<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\endpoints;

use DateTime;
use PLCODashboard\classes\PLCO_Connection;
use PLCODashboard\classes\PLCO_REST_Controller;
use PLCODashboard\front\classes\PLCO_Paypal_Payment_Method;
use PLCODashboard\front\classes\PLCO_Stripe_Payment_Method;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use PLCOMembership\front\classes\PLCOM_Membership_Level;
use PLCOMembership\front\classes\PLCOM_User_Membership;
use PLCOMembership\front\classes\repositories\PLCOM_Membership_Level_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Payment_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_User_Membership_Repository;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Members_Controller extends PLCO_REST_Controller {

	public $base = 'members';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_members' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_member' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/add_complementary', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_complementary' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'suspend_member' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Gets the members from the DB
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_members( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$result = PLCOM_Helpers::get_members( $params );
		unset( $result["_"] );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Gets specific member from the DB
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_member( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$result = PLCOM_User_Membership_Repository::find_one_by( array( 'ID' => (int) $params['ID'] ) );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Adds a complementary membership
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 * @throws \ReflectionException
	 */
	public function add_complementary( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();

		if ( ! $params['membership_id'] ) {
			return new \WP_Error( 'code', __( 'Please Select a membership level.', PLCOM_Const::T ) );
		}

		/** @var PLCOM_Membership_Level $membership */
		$membership = PLCOM_Membership_Level_Repository::find_one_by( array( "ID" => $params['membership_id'] ) );



		if ( $params['current_membership_id'] === 1 ) {
			/** @var PLCOM_User_Membership $user_membership */
			$user_membership = PLCOM_User_Membership_Repository::find_one_by( array( 'ID' => (int) $params['user_membership_id'] ) );
			$user_membership->setComplementary( 1 );
			$user_membership->setMembership( $params['membership_id'] );
		} else {
			$multiple_memberships = get_option( 'plcom_multiple_memberships', 0 );

			if ( ! $multiple_memberships ) {
				/** @var PLCOM_User_Membership[] $old_memberships */
				$old_memberships = PLCOM_User_Membership_Repository::find_by(
					array(
						'user_id' => (int) $params['user_id'],
						'state'   => 'ACTIVE'
					)
				);

				foreach ( $old_memberships as $old_membership ) {
					if ( (int) $old_membership->getComplementary() === 1 ) {
						$old_membership->setState( PLCOM_Const::STATUSES['canceled'] );
						$old_membership->getRepository()->persist( $old_membership );
					} else {
						$result = PLCOM_Helpers::cancel_membership( $old_membership->getID() );

						if ( ! $result ) {
							return new WP_Error( 'code', __( 'Cannot cancel existing memberships, please try again.', PLCOM_Const::T ) );
						}
					}
				}
			}

			$user_membership = new PLCOM_User_Membership( (object) array(
				'membership_id' => $params['membership_id'],
				'state'         => 'ACTIVE',
				'user_id'       => $params['user_id'],
				'complementary' => 1
			) );
		}



		if ( $params['recurrence'] > 0 ) {
			foreach ( PLCOM_Const::RECURRENCE_TYPES as $recurrence_type ) {
				if ( (int) $recurrence_type["ID"] === (int) $params['recurrence_type'] ) {
					$recurrence_type_name = $recurrence_type["name"];
				}
			}
			$date = new DateTime();
			$date->modify( "+" . $params['recurrence'] . " " . $recurrence_type_name );
			$user_membership->setExpiresAt( $date->format( 'Y-m-d H:i:s' ) );
		}

		$user_membership = $user_membership->getRepository()->persist( $user_membership );


		$user            = new \WP_User($user_membership->getUser()->ID);
		$is_admin        = false;


		foreach ( $user->roles as $role ) {
			/**
			 * DO NOT REMOVE ADMINS
			 */
			if ( $role === "administrator" ) {
				$is_admin = true;
				continue;
			}

			$user->remove_role( $role );
		}


		if ( ! $is_admin ) {
			$user->add_role( $membership->getRole() );
		}


		$data = array(
			'user_membership_id' => $user_membership->getID(),
		);
		do_action( 'plcom_initial_membership_created', $data );

		return new WP_REST_Response( $user_membership, 200 );
	}

	/**
	 * Gets specific member from the DB
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 * @throws \ReflectionException
	 */
	public function suspend_member( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();

		/** @var PLCOM_User_Membership $membership */
		$membership = PLCOM_User_Membership_Repository::find_one_by( array( 'ID' => $params['ID'] ) );

		if ( (int) $membership->getComplementary() === 1 ) {
			$membership->setComplementary( 0 );
			$membership->setMembership( 1 );
			$membership->setExpiresAt( null );
			$membership->setState( 'ACTIVE' );

			$result = $membership->getRepository()->persist($membership);
		} else {
			$result = PLCOM_Helpers::cancel_membership( $params['ID'] );
		}

		if ( $result ) {
			return new WP_REST_Response( $params, 200 );
		}

		return new WP_Error( 'code', __( 'Failed to cancel membership. Try again. If the error persists please contact our technical team.', PLCOM_Const::T ) );
	}
}
