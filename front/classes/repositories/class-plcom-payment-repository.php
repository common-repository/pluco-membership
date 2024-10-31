<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\classes\repositories;

use PLCODashboard\classes\PLCO_Abstract_Repository;
use PLCOMembership\front\classes\PLCOM_Payment;

class PLCOM_Payment_Repository extends PLCO_Abstract_Repository {

    /**
     * @var string
     */
    protected static string $table = 'payments';

    /**
     * @var string
     */
    protected static string $model_class = PLCOM_Payment::class;

}