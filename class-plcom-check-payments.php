<?php

namespace PLCOMembership;

use DateTime;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\front\classes\PLCOM_User_Membership;
use PLCOMembership\front\classes\repositories\PLCOM_User_Membership_Repository;

class PLCOM_Check_Payments {

	/** @var PLCOM_Const|null */
	private $const;

	/** @var PLCOM_Helpers */
	private $helper;

	/**
	 * PLCOM_Check_Payments constructor.
	 *
	 * @throws \Exception
	 */
	public function __construct() {
		$this->const  = PLCOM_Const::get_instance();
		$this->helper = new PLCOM_Helpers( $this->const, '' );
		$this->get_payments();
	}

	/**
	 * Fetches all payments
	 *
	 * @throws \Exception
	 */
	private function get_payments() {
		$unpaid_memberships = $this->helper::get_unpaid_memberships();
		/** @var PLCOM_User_Membership $unpaid_membership */
		foreach ( $unpaid_memberships as $unpaid_membership ) {
			$grace_period = get_option( 'plcom_grace_period', 15 );
			$expiry_date  = new DateTime( $unpaid_membership->getNextBillingDate() );
			$expiry_date  = $expiry_date->modify( '+' . $grace_period . ' days' );
			$now          = new DateTime();
			/**
			 * Suspend Membership if grace period has passed
			 */
			if ( $expiry_date < $now ) {
				$unpaid_membership->setState( $this->const::STATUSES['suspended'] );
				$unpaid_membership->getRepository()->persist( $unpaid_membership );
			}
		}

		$suspended_memberships = PLCOM_User_Membership_Repository::find_by(array('state' => PLCOM_Const::STATUSES['suspended'], 'membership_id' => array('condition' => '>', 'value' => 1)));

		foreach ($suspended_memberships as $suspended_membership) {
			$cancel_period = get_option( 'plcom_cancel_period', 30 );
			$expiry_date  = new DateTime( $suspended_membership->getNextBillingDate() ?? 'NOW' );
			$expiry_date  = $expiry_date->modify( '+' . $cancel_period . ' days' );
			$now          = new DateTime();

			if ( $expiry_date < $now ) {
				$this->helper->cancel_membership($suspended_membership->getID());
			}
		}

		/** @var PLCOM_User_Membership[] $complementary_memberships */
		$complementary_memberships = PLCOM_User_Membership_Repository::find_by(array('state' => PLCOM_Const::STATUSES['active'], 'complementary' => 1, 'membership_id' => array('condition' => '>', 'value' => 1), 'expires_at' => array('condition' => '<>', 'value' => '')));

		foreach ($complementary_memberships as $complementary_membership) {
			$expiry_date  = new DateTime( $complementary_membership->getExpiresAt() );
			$now          = new DateTime();

			if ( $expiry_date < $now ) {
				$complementary_membership->setState( PLCOM_Const::STATUSES['canceled'] );
				$complementary_membership->getRepository()->persist( $complementary_membership );
			}
		}
	}
}
