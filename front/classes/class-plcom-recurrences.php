<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\classes;

use PLCODashboard\classes\PLCO_Abstract_Model;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use PLCOMembership\front\classes\repositories\PLCOM_Payment_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Plan_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Recurrences_Repository;

class PLCOM_Recurrences extends PLCO_Abstract_Model {

	/**
	 * @var
	 */
	public $membership_id;

	/**
	 * @var
	 */
	public $recurrence;

	/**
	 * @var
	 */
	public $recurrence_type;

	/**
	 * @var
	 */
	public $cycles;

	/**
	 * @var
	 */
	public $amount;

	/**
	 * @var
	 */
	public $currency;

	/**
	 * @var PLCOM_Plan[]
	 */
	public array $plans = array();

	/**
	 * {no_persist}
	 * @var
	 */
	public int $members = 0;

	/**
	 * @var
	 */
	public $created_at;


	/**
	 * PLCOM_Recurrences constructor.
	 *
	 * @param $data
	 */
	public function init( $data ) {
		foreach ( get_object_vars( $data ) as $key => $value ) {
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
			$this->setPlans( $data->ID );
			$this->setMembers();
		}
	}

	/**
	 * @return mixed
	 */
	public function getMembershipId() {
		return (int) $this->membership_id;
	}

	/**
	 * @param mixed $membership_id
	 */
	public function setMembershipId( $membership_id ) {
		$this->membership_id = (int) $membership_id;
	}


	/**
	 * @return mixed
	 */
	public function getRecurrence() {
		return $this->recurrence;
	}

	/**
	 * @param mixed $ecurrence
	 */
	public function setRecurrence( $recurrence ) {
		$this->recurrence = (int) $recurrence;
	}

	/**
	 * @return mixed
	 */
	public function getRecurrenceType() {
		return $this->recurrence_type;
	}

	/**
	 * @param mixed $ecurrence_type
	 */
	public function setRecurrenceType( $recurrence_type ) {
		$this->recurrence_type = (int) $recurrence_type;
	}

	/**
	 * @return mixed
	 */
	public function getCycles() {
		return $this->cycles;
	}

	/**
	 * @param mixed $cycles
	 */
	public function setCycles( $cycles ) {
		$this->cycles = (int) $cycles;
	}

	/**
	 * @return double
	 */
	public function getAmount() {
		return $this->amount;
	}

	/**
	 * @param double $amount
	 */
	public function setAmount( $amount ) {
		$this->amount = (double) $amount;
	}

	/**
	 * @return mixed
	 */
	public function getCurrency() {
		return $this->currency;
	}

	/**
	 * @param mixed $currency
	 */
	public function setCurrency( $currency ) {
		$this->currency = (int) $currency;
	}

	/**
	 * @return PLCOM_Plan[]
	 */
	public function getPlans() {
		return $this->plans;
	}

	/**
	 * @param $recurrence_id
	 */
	public function setPlans( $recurrence_id ) {
		$this->plans = PLCOM_Plan_Repository::find_by( array( 'recurrence_id' => (int) $recurrence_id ) );
	}

	/**
	 * @return int
	 */
	public function getMembers() {
		return $this->members;
	}

	/**
	 * @return void
	 */
	public function setMembers() {
		$this->members = PLCOM_Payment_Repository::get_count(
			array( 'recurrence_id' => $this->getID(), 'status' => PLCOM_Const::STATUSES['active'] )
		);
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
