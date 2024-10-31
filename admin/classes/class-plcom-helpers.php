<?php
/**
 * PluginsCorner - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\admin\classes;

use Exception;
use PLCODashboard\admin\classes\PLCO_Helpers;
use PLCODashboard\classes\PLCO_Abstract_Helpers;
use PLCODashboard\classes\PLCO_Connection;
use PLCODashboard\classes\PLCO_Abstract_Payment_Method;
use PLCODashboard\classes\PLCO_Mail_Factory;
use PLCODashboard\front\classes\PLCO_Paypal_Payment_Method;
use PLCODashboard\front\classes\PLCO_Wordpress_Mail_Service;
use PLCOMembership\front\classes\PLCOM_Membership_Level;
use PLCOMembership\front\classes\PLCOM_Payment;
use PLCOMembership\front\classes\PLCOM_Plan;
use PLCOMembership\front\classes\PLCOM_Recurrences;
use PLCOMembership\front\classes\PLCOM_User_Membership;
use DateTime;
use PLCODashboard\classes\repositories\PLCO_Connection_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Membership_Level_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Payment_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_User_Membership_Repository;
use ReflectionException;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class PLCOM_Helpers
 *
 * @package PLCOMembership\admin\classes
 */
class PLCOM_Helpers extends PLCO_Abstract_Helpers {

	/**
	 * @var string
	 */
	public static string $user_membership_table;

	/**
	 * @var string
	 */
	public static string $membership_level_table;
	/**
	 * @var string
	 */
	public static string $recurrences_table;

	/**
	 * @var string
	 */
	public static string $payments_table;

	/**
	 * @var string
	 */
	public static string $plans_table;

	/**
	 * @var string
	 */
	public static string $cards_table;
	/**
	 * @var string
	 */
	public static string $post_meta_table;

	/**
	 * PLCOM_Helpers constructor.
	 *
	 * @param        $const
	 * @param string $component_name
	 */
	public function __construct( $const, $component_name = '' ) {
		parent::__construct( $const, $component_name );

		self::$user_membership_table  = self::$wpdb->prefix . $const::PLUGIN_PREFIX . "user_memberships";
		self::$membership_level_table = self::$wpdb->prefix . $const::PLUGIN_PREFIX . "membership_levels";
		self::$recurrences_table      = self::$wpdb->prefix . $const::PLUGIN_PREFIX . "recurrences";
		self::$payments_table         = self::$wpdb->prefix . $const::PLUGIN_PREFIX . "payments";
		self::$plans_table            = self::$wpdb->prefix . $const::PLUGIN_PREFIX . "plans";
		self::$cards_table            = self::$wpdb->prefix . $const::PLUGIN_PREFIX . "cards";
		self::$post_meta_table        = self::$wpdb->prefix . "postmeta";
	}

	/**
	 * Creates a user with the default non member membership
	 *
	 * @param $data
	 *
	 * @return array|bool|mixed|object|string|void
	 * @throws ReflectionException
	 */
	public static function create_user_with_memberhip( $data ) {

		if ( is_user_logged_in() ) {
			$user_membership = null;
			if ( current_user_can( 'administrator' ) ) {
				return __( "You're an administrator! Administrators do not need memberships for access", PLCOM_Const::T );
			}
			$wp_user = wp_get_current_user();
			$user_id = get_current_user_id();

			$data['email']      = $wp_user->data->user_email;
			$data['first_name'] = $wp_user->data->first_name;
			$data['last_name']  = $wp_user->data->last_name;

			$user_memberships = PLCOM_User_Membership_Repository::find_by( [ 'user_id' => $user_id ] );

			if ( ! empty( $user_memberships ) ) {

				/** @var PLCOM_User_Membership $membership */
				foreach ( $user_memberships as $membership ) {
					if ( (int) $membership->getMembership()->getID() === 1 ) {
						$user_membership = $membership;
					}
				}
			}

			if ( ! $user_membership ) {

				/** @var PLCOM_Membership_Level $non_member */
				$non_member = PLCOM_Membership_Level_Repository::find_one_by( array( 'ID' => 1 ) );

				$model           = new PLCOM_User_Membership( (object) array(
					'user_id'       => $user_id,
					'membership_id' => $non_member->getID(),
					'state'         => "ACTIVE",
				) );
				$user_membership = $model->getRepository()->persist( $model );

				foreach ( $wp_user->roles as $role ) {
					$wp_user->remove_role( $role );
				}
				$wp_user->add_role( $non_member->getRole() );
			}

		} else {
			if ( ! $data['email'] ) {
				return new WP_Error(401, __( "Email is missing", PLCOM_Const::T ));
			}

			if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
				return new WP_Error(401, __( "Email is invalid", PLCOM_Const::T ));
			}

			if ( ! $data['first_name'] ) {
				return new WP_Error(401, __( "Name is missing", PLCOM_Const::T ));
			}
			$user_name = strstr( sanitize_email( $data['email'] ), '@', true );

			$user_id = username_exists( $user_name );

			$user_by_email = get_user_by('email', $data['email']);

			if($user_by_email) {
				return new WP_Error(401, __( "Email already registered", PLCOM_Const::T ));
			}

			if ( $user_id ) {
				$user_name = $user_name . mt_rand( 1, 100 );
			}


			/** @var PLCOM_Membership_Level $non_member */
			$non_member = PLCOM_Membership_Level_Repository::find_one_by( array( 'ID' => 1 ) );
			$admin_bar  = get_option( 'plcom_hide_admin_bar' );


			$random_password = wp_generate_password( 15, false );

			$user_data = array(
				"user_login"           => $user_name,
				"user_pass"            => $random_password,
				"user_email"           => sanitize_email( $data['email'] ),
				"first_name"           => sanitize_text_field( $data['first_name'] ),
				"last_name"            => sanitize_text_field( $data['last_name'] ),
				"show_admin_bar_front" => (int) $admin_bar ? 'false' : 'true',
				"role"                 => $non_member->getRole(),
			);

			$user_id = wp_insert_user( $user_data );

			do_action('plcom_after_user_insert', $data, $user_id);

			if ( $user_id ) {
				$model           = new PLCOM_User_Membership( (object) array(
					'user_id'       => $user_id,
					'membership_id' => $non_member->getID(),
					'state'         => 'ACTIVE',
				) );
				$user_membership = $model->getRepository()->persist( $model );
			}

			$success_subject = PLCOM_Const::USER_CREATED_SUBJECT;
			$success_message = PLCOM_Const::USER_CREATED_MESSAGE;

			$success_message = str_replace( "{{user_name}}", $data['first_name'] . " " . $data['last_name'], $success_message );
			$success_message = str_replace( "{{login_details}}", "Username: " . $user_name . "<br/>Password:" . $random_password, $success_message );

			$factory = new PLCO_Mail_Factory();
			/** @var PLCO_Wordpress_Mail_Service $service */
			$service = $factory->get_mail_service();
			$service->send_mail('', sanitize_email( $data['email'] ), $success_subject, $success_message, array( "content-type" => "text/html" ));

		}

		if ( $user_id ) {

			$connection = PLCO_Connection_Repository::find_one_by( array( 'connection_name' => sanitize_text_field( $data['payment_method'] ) ) );
			$method     = "PLCODashboard\\front\\classes\\PLCO_" . ucfirst( $connection->getConnectionName() ) . "_Payment_Method";
			/** @var PLCO_Paypal_Payment_Method $payment_method */
			$payment_method = new $method( $connection );
			/** @var PLCOM_Membership_Level $membeship_level */
			$membeship_level     = PLCOM_Membership_Level_Repository::find_one_by( array( 'ID' => sanitize_text_field( $data["level"] ) ) );

			if($membeship_level->getStatus() === 0) {
				throw new Exception(__('Membership does not exist', PLCOM_Const::T));
			}

			$selected_recurrence = "";

			foreach ( $membeship_level->getRecurrences() as $recurrence ) {
				if ( $recurrence->getID() === (int) sanitize_text_field( $data["recurrence"] ) ) {
					$selected_recurrence = $recurrence;
					break;
				}
			}

			if ( isset( $recurrence ) && (int) ceil( $recurrence->getAmount() ) === 0 ) {
				$success = get_option( 'plcom_success_page', array(
					'ID'   => '',
					'name' => '',
					'url'  => ''
				) );

				return $success['url'];
			}

			$result = $payment_method->make_payment( $user_membership, $selected_recurrence, $membeship_level, $data );

			if(isset($result->error)) {
				return new WP_Error(401, $result->message) ;
			}

			if ( ! is_user_logged_in() ) {
				wp_clear_auth_cookie();
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id );
			}

			$payment_data = array(
				"user_id"            => $user_membership->getUser()->data->ID,
				"user_membership_id" => $user_membership->getID(),
				"membership_id"      => $membeship_level->getID(),
				"recurrence_id"      => $selected_recurrence->getID(),
				"subscription_id"    => $result->id,
				"transaction_id"     => isset( $result->transaction_id ) ? $result->transaction_id : "",
				"payment_amount"     => $selected_recurrence->getAmount(),
				"status"             => strtoupper( $result->status ),
				"payment_type_id"    => $connection->getID(),
				"original_response"  => '',
			);

			$payment_data = apply_filters('plcom_after_payment_data', $payment_data);

			$model   = new PLCOM_Payment( (object) $payment_data );
			$payment = $model->getRepository()->persist( $model );

			do_action( 'plcom_after_check_payment' );

			if ( isset( $result->next_action ) ) {

				if ( $result->next_action === 'validate' ) {
					return array(
						'confirmation_required' => false,
					);
				}

				return array(
					'confirmation_required' => true,
					'sdk'                   => $result->next_action === "use_stripe_sdk",
					'client_secret'         => $result->client_secret,
					'payment_id'            => $payment->getID()
				);
			} elseif ( $result->links ) {
				$url = "";
				foreach ( $result->links as $link ) {
					if ( $link->rel === "approve" ) {
						$url = $link->href;
					}
				}

				return $url;
			} else {
				return $result;
			}


		} else {
			return __( "User could not be added please contact us via email", PLCOM_Const::T );
		}

	}

	/**
	 * @param $ID
	 *
	 * @return bool
	 * @throws ReflectionException
	 */
	public static function cancel_membership( $ID ) {
		/** @var PLCOM_User_Membership $user_membership */
		$user_membership = PLCOM_User_Membership_Repository::find_one_by( array( 'ID' => $ID ) );
		$payments        = PLCOM_Payment_Repository::find_by( array( 'user_membership_id' => $ID ) );


		/** @var PLCO_Connection $connection */
		$connection = $payments[0]->getPaymentType();
		$method     = "PLCODashboard\\front\\classes\\PLCO_" . ucfirst( $connection->getConnectionName() ) . "_Payment_Method";
		/** @var PLCO_Paypal_Payment_Method $payment_method */
		$payment_method = new $method( $connection );
		$result         = $payment_method->cancel_subscription( $payments[0]->getSubscriptionId() );

		if ( $result ) {
			$user_membership->setState( PLCOM_Const::STATUSES['pending_cancelation'] );
			$user_membership->getRepository()->persist( $user_membership );

			/**
			 * Add Non Member Membership
			 */
			$model           = new PLCOM_User_Membership( (object) array(
				'user_id'       => $user_membership->getUser()->ID,
				'membership_id' => 1,
				'state'         => 'ACTIVE',
			) );

			$model->getRepository()->persist( $model );
		}

		return $result;


	}

	/**
	 * Fetches the payments
	 *
	 * @param $params
	 *
	 * @return mixed
	 */
	public static function get_members( $params ) {

		if ( ! $params["state"] ) {
			$params["state"] = "ACTIVE";
		}

		$table = self::$user_membership_table;

		$query = "SELECT {$table}.ID, {$table}.user_id, {$table}.membership_id, {$table}.state, {$table}.complementary, {$table}.created_at, {$table}.expires_at, {$table}.next_billing_date FROM " . self::$user_membership_table;

		$query .= ' INNER JOIN ' .  self::$wpdb->prefix . 'users' .  ' ON '. self::$user_membership_table .'.user_id = '.  self::$wpdb->prefix . 'users' . '.ID';

		$query .= " WHERE state = '";

		if ( $params["state"] !== "PENDING" && $params["state"] !== "NON" ) {
			$query .= $params["state"] . "'";
		} else {
			$query .= "ACTIVE' AND next_billing_date IS NULL";

			if ( $params["state"] === "PENDING" ) {
				$query .= " AND membership_id > 1";
			} else {
				$query .= " AND membership_id = 1";
			}
		}

		if ( $params["state"] === "ACTIVE" ) {
			if($params["membership_level"] && $params["membership_level"] !== 'all') {
				$query .= " AND membership_id = " . (int) $params["membership_level"];
			} else {
				$query .= " AND membership_id <> 1";
			}
		}

		if($params['complementary'] && $params['complementary'] !== 'all') {
			$query .= " AND complementary = " . (int) $params['complementary'];
		}

		if($params['search']) {
			$query .= " AND (" . self::$wpdb->prefix . 'users' . ".display_name LIKE '%" . $params['search'] . "%'";
			$query .= " OR " . self::$wpdb->prefix . 'users' . ".user_email LIKE '%" . $params['search'] . "%')";
		}


		$query .= " LIMIT " . ( ( (int) $params["current_page"] - 1 ) * $params["items_per_page"] ) . ", " . $params["items_per_page"];

		$results = self::$wpdb->get_results( $query );

		$count = "SELECT COUNT(*) as total_items FROM " . self::$user_membership_table . " WHERE state = '";


		if ( $params["state"] !== "PENDING" && $params["state"] !== "NON" ) {
			$count .= $params["state"] . "'";
		} else {
			$count .= "ACTIVE' AND next_billing_date IS NULL";
		}

		if ( $params["state"] === "ACTIVE" ) {
			if($params["membership_level"] && $params["membership_level"] !== 'all') {
				$count .= " AND membership_id = " . (int) $params["membership_level"];
			} else {
				$count .= " AND membership_id <> 1";
			}
		}

		$params["total_items"] = self::$wpdb->get_row( $count )->total_items;
		$params["members"]     = array();

		foreach ( $results as $key => $result ) {
			$membership                 = (array) new PLCOM_User_Membership( $result );
			$membership["user"]         = (array) $membership["user"];
			$membership["user"]["data"] = (array) $membership["user"]["data"];
			unset( $membership["user"]["data"]["user_pass"] );
			unset( $membership["user"]["data"]["user_activation_key"] );
			unset( $membership["user"]["data"]["user_status"] );
			unset( $membership["user"]["caps"] );
			unset( $membership["user"]["cap_key"] );
			unset( $membership["user"]["allcaps"] );
			$params["members"][ $key ] = $membership;
		}

		return $params;
	}

	/**
	 * @param $params
	 *
	 * @return mixed
	 */
	public static function get_payments( $params ) {

		$query = 'SELECT * FROM ' . self::$payments_table . " WHERE status = 'ACTIVE'";

		$extra_query = '';
		if(!empty($params['extra'])) {
			foreach ($params['extra'] as $extra) {
				if(isset($extra['user_membership_id'])) {
					$extra_query = ' AND user_membership_id =' . $extra['user_membership_id'];
				}
			}
		}

		$query .= $extra_query;

		$query .= " ORDER BY created_at DESC";
		$query .= " LIMIT " . ( ( (int) $params["current_page"] - 1 ) * $params["items_per_page"] ) . ", " . $params["items_per_page"];

		$results = self::$wpdb->get_results( $query );
		$extra_count = '';
		if(!empty($params['extra'])) {
			foreach ($params['extra'] as $extra) {
				if(isset($extra['user_membership_id'])) {
					$extra_count = ' AND user_membership_id =' . $extra['user_membership_id'];
				}
			}
		}

		$count = "SELECT COUNT(*) as total_items FROM " . self::$payments_table . " WHERE status = 'ACTIVE'" . $extra_count;

		$params["total_items"] = self::$wpdb->get_row( $count )->total_items;
		$params["payments"]    = array();

		foreach ( $results as $key => $result ) {
			$payment                 = (array) new PLCOM_Payment( $result );
			$payment["user"]         = (array) $payment["user"];
			$payment["user"]["data"] = (array) $payment["user"]["data"];
			unset( $payment["user"]["data"]["user_pass"] );
			unset( $payment["user"]["data"]["user_activation_key"] );
			unset( $payment["user"]["data"]["user_status"] );
			unset( $payment["user"]["caps"] );
			unset( $payment["user"]["cap_key"] );
			unset( $payment["user"]["allcaps"] );
			$params["payments"][ $key ] = $payment;
		}

		return $params;
	}

	/**
	 * @param $user_id
	 *
	 * @return mixed
	 */
	public static function get_user_requires_action_payments( $user_id ) {
		$query = 'SELECT * FROM ' . self::$payments_table . ' WHERE user_id = ' . $user_id . " AND status = 'REQUIRES_ACTION'";

		$results = self::$wpdb->get_results( $query );

		foreach ( $results as $key => $result ) {
			$results[ $key ] = new PLCOM_Payment( $result );
		}

		return $results;
	}

	/**
	 * Get a list of all protected pages and posts
	 *
	 * @return array|object|null
	 */
	public static function get_all_protections() {
		$query   = "SELECT * FROM " . self::$post_meta_table . " WHERE meta_key = 'plco_protected'";
		$results = self::$wpdb->get_results( $query, ARRAY_A );
		foreach ( $results as $key => $result ) {
			unset( $results[ $key ]["meta_id"] );
			unset( $results[ $key ]["meta_key"] );
			$results[ $key ]["post_id"]           = (int) $result["post_id"];
			$results[ $key ]["membership_levels"] = maybe_unserialize( unserialize( $result["meta_value"] ) );
			unset( $results[ $key ]["meta_value"] );
		}

		return $results;
	}


	/**
	 * Check if the user has the correct access to the post
	 *
	 * @param $page_protection
	 *
	 * @return bool
	 */
	public static function has_access( $page_protection ) {
		$user = wp_get_current_user();

		/**
		 * Do not allow not logged in users
		 */
		if ( ! $user->ID ) {
			return false;
		}
		/**
		 * Allow administrators
		 */
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		/** @var PLCOM_User_Membership $user_membership */
		$user_memberships = PLCOM_User_Membership_Repository::find_by(
			array(
				'ID'    => $user->ID,
				'state' => 'ACTIVE',
			) );

		$has_access = false;
		foreach ( $page_protection as $protection ) {
			foreach ( $user_memberships as $user_membership ) {
				if ( (int) $protection["ID"] === (int) $user_membership->getMembership()->getID() && $user_membership->getState() === "ACTIVE" ) {
					$has_access = true;
				}
			}

		}

		return $has_access;
	}

	/**
	 * Checks the plans
	 */
	public static function check_plans( $level_id = null ) {
		$connections = PLCO_Helpers::get_connections();

		/** @var $connection PLCO_Connection */
		foreach ( $connections as $connection ) {

			$method = "PLCODashboard\\front\\classes\\PLCO_" . ucfirst( $connection->getConnectionName() ) . "_Payment_Method";
			/** @var PLCO_Paypal_Payment_Method $payment_method */
			$payment_method = new $method( $connection );

			if ( ! $level_id ) {
				$membership_levels = PLCOM_Membership_Level_Repository::get_all();
				/** @var $level PLCOM_Membership_Level */
				foreach ( $membership_levels as $level ) {
					self::check_plan( $level, $connection, $payment_method );
				}

			} else {
				/** @var PLCOM_Membership_Level $level */
				$level = PLCOM_Membership_Level_Repository::find_one_by( array( 'ID' => $level_id ) );
				self::check_plan( $level, $connection, $payment_method );
			}
		}
	}

	/**
	 * Checks if the plan exists
	 *
	 * @param PLCOM_Membership_Level $level
	 * @param PLCO_Connection $connection
	 * @param PLCO_Abstract_Payment_Method $payment_method
	 */
	public static function check_plan( PLCOM_Membership_Level $level, PLCO_Connection $connection, PLCO_Abstract_Payment_Method $payment_method ) {
		if ( ! empty( $level->getRecurrences() ) ) {
			/** @var $recurrence PLCOM_Recurrences */
			foreach ( $level->getRecurrences() as $recurrence ) {
				if ( (int) ceil( $recurrence->getAmount() ) === 0 ) {
					continue;
				}
				if ( empty( $recurrence->getPlans() ) ) {
					$plan = new PLCOM_Plan((object) array());
					$plan->setRecurrenceId( $recurrence->getID() );
					$plan->setConnectionId( $connection->getID() );
					$plan = $payment_method->add_plan( $plan, $recurrence, $level );

					$payment_method->activate_plan( $plan );
				} else {
					$plan_found = false;
					/** @var PLCOM_Plan $plan */
					foreach ( $recurrence->getPlans() as $plan ) {
						if ( (int) $plan->getConnectionId() === (int) $connection->getID() ) {
							$plan_found = true;
							if ( ! $plan->getPlanCreated() ) {
								$payment_method->add_plan( $plan, $recurrence, $level );
							}
							if ( ! $plan->getPlanActivated() ) {
								$payment_method->activate_plan( $plan );
							}
						}
					}

					if ( ! $plan_found ) {
						$plan = new PLCOM_Plan((object) array());
						$plan->setRecurrenceId( $recurrence->getID() );
						$plan->setConnectionId( $connection->getID() );
						$plan = $payment_method->add_plan( $plan, $recurrence, $level );
						$payment_method->activate_plan( $plan );
					}
				}
			}
		}
	}

	/**
	 * Fetches unpaid memberships
	 *
	 * @return array|object|null
	 * @throws Exception
	 */
	public static function get_unpaid_memberships() {
		$now   = new DateTime();
		$query = 'SELECT * FROM ' . self::$user_membership_table . ' WHERE membership_id > 1 AND state = "ACTIVE" AND complementary = 0 AND (next_billing_date <= ' . "'" . $now->format( "Y-m-d H:i:s" ) . "' OR next_billing_date is NULL)";

		$results = self::$wpdb->get_results( $query );

		foreach ( $results as $key => $result ) {
			$results[ $key ] = new PLCOM_User_Membership( $result );
		}

		return $results;
	}

	/**
	 * Checks if we have a pro version of the plugin
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return file_exists( PLCOM_Const::plugin_path() . 'pro/admin/classes/class-plcom-pro-admin-init.php' );
	}
}
