<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\classes;

use PLCODashboard\classes\PLCO_Abstract_Model;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use PLCOMembership\front\classes\repositories\PLCOM_Membership_Level_Repository;
use WP_User;

/**
 * Class PLCOM_User_Membership
 *
 * @package PLCOMembership\front\classes
 */
class PLCOM_User_Membership extends PLCO_Abstract_Model {

	/**
	 * @var
	 */
	public $user;
	/**
	 * @var
	 */
	public $membership;

	/**
	 * @var
	 */
	public $state;
	/**
	 * @var
	 */
	public $complementary;
	/**
	 * @var
	 */
	public $created_at;

	/**
	 * @var
	 */
	public $expires_at;

	/**
	 * @var
	 */
	public $next_billing_date;

	/**
	 * PLCOM_User_Membership Constructor
	 *
	 * @param $user_membership
	 */
	public function init( $data ) {
		foreach ( get_object_vars( $data ) as $key => $value ) {
			$exploded = explode( "_", $key );

			foreach ( $exploded as $ex => $item ) {
				$exploded[ $ex ] = ucwords( $item );
			}

			$method = "set" . implode( "", $exploded );

			if ( method_exists( $this, $method ) ) {
				$this->$method( $value );
			}
		}

		$this->setUser( $data->user_id );
		if ( $data->membership_id ) {
			$this->setMembership( $data->membership_id );
		}
	}

	/**
	 * @return mixed
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @param mixed $user
	 */
	public function setUser( $user ) {
		$user_obj = new WP_User( $user );
		/**
		 * Remove Sensitive data from the user object
		 */
		unset($user_obj->data->user_pass);
		unset($user_obj->data->user_activation_key);
		unset($user_obj->data->user_status);
		unset($user_obj->caps);
		unset($user_obj->cap_key);
		unset($user_obj->allcaps);
		$meta = get_user_meta($user_obj->ID);
		if($meta) {
			foreach ($meta as $key => $item) {
				if(strpos($key, 'plcom_extra_') !== false) {
					$meta[$key] = maybe_unserialize(is_array($item) ? $item[0] : $item);
				} else {
					unset($meta[$key]);
				}
			}

			$user_obj->meta = $meta;
		}

		$this->user = $user_obj;
	}

	/**
	 * @return PLCOM_Membership_Level
	 */
	public function getMembership() {
		return $this->membership;
	}

	/**
	 * @param int $membership
	 */
	public function setMembership( $membership ) {
		$this->membership = PLCOM_Membership_Level_Repository::find_one_by( array( 'ID' => $membership ) );
	}


	/**
	 * @return mixed
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @param mixed $state
	 */
	public function setState( $state ) {
		$this->state = $state;
	}

	/**
	 * @return mixed
	 */
	public function getComplementary() {
		return $this->complementary;
	}

	/**
	 * @param mixed $complementary
	 */
	public function setComplementary( $complementary ) {
		$this->complementary = $complementary;
	}

	/**
	 * @return mixed
	 */
	public function getCreatedAt() {
		return $this->created_at;
	}

	/**
	 * @param mixed $created_at
	 */
	public function setCreatedAt( $created_at ) {
		$this->created_at = $created_at;
	}

	/**
	 * @return mixed
	 */
	public function getExpiresAt() {
		return $this->expires_at;
	}

	/**
	 * @param mixed $expires_at
	 */
	public function setExpiresAt( $expires_at ) {
		$this->expires_at = $expires_at;
	}

	/**
	 * @return mixed
	 */
	public function getNextBillingDate() {
		return $this->next_billing_date;
	}

	/**
	 * @param mixed $next_billing_date
	 */
	public function setNextBillingDate( $next_billing_date ) {
		$this->next_billing_date = $next_billing_date;
	}


}
