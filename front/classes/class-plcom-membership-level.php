<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\classes;

use PLCODashboard\classes\PLCO_Abstract_Model;
use PLCOMembership\front\classes\repositories\PLCOM_Recurrences_Repository;

class PLCOM_Membership_Level extends PLCO_Abstract_Model {

	/**
	 * @var string
	 */
	public string $membership_name;

	/**
	 * @var
	 */
	public $role;

	/**
	 * @var
	 */
	public $autorenew;

	/**
	 * @var
	 */
	public $status;

	/**
	 * @var
	 */
	public $recurrences = array();

	/**
	 * @var
	 */
	public $created_at;

	/**
	 * PLCOM_Membership_Level constructor.
	 *
	 * @param $data
	 */
	protected function init( $data ) {

		foreach ( $data as $key => $value ) {
			$exploded = explode( "_", $key );

			foreach ( $exploded as $ex => $item ) {
				$exploded[ $ex ] = ucwords( $item );
			}

			$method = "set" . implode( "", $exploded );

			if ( method_exists( $this, $method ) && ! is_array( $value ) ) {
				$this->$method( $value );
			}
		}

		if ( $data->ID ) {
			$this->setRecurrences( $data->ID );
		}

	}

	/**
	 * @return mixed
	 */
	public function getMembershipName() {
		return $this->membership_name;
	}

	/**
	 * @param mixed $membership_name
	 */
	public function setMembershipName( $membership_name ) {
		$this->membership_name = $membership_name;
	}

	/**
	 * @return mixed
	 */
	public function getRole() {
		return $this->role;
	}

	/**
	 * @param mixed $role
	 */
	public function setRole( $role ) {
		$this->role = $role;
	}

	/**
	 * @return mixed
	 */
	public function getAutorenew() {
		return $this->autorenew;
	}

	/**
	 * @param mixed $autorenew
	 */
	public function setAutorenew( $autorenew ) {
		$this->autorenew = (int) $autorenew;
	}

	/**
	 * @return mixed
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param mixed $status
	 */
	public function setStatus( $status ) {
		$this->status = (int) $status;
	}

	/**
	 * @return PLCOM_Recurrences[]
	 */
	public function getRecurrences() {
		return $this->recurrences;
	}


	/**
	 * @param $membership_id
	 */
	public function setRecurrences( $membership_id ) {
		$this->recurrences = PLCOM_Recurrences_Repository::find_by( array( 'membership_id' => $membership_id ) );
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


}
