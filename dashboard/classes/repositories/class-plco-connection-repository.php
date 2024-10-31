<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\classes\repositories;

use PLCODashboard\classes\PLCO_Abstract_Repository;
use PLCODashboard\classes\PLCO_Connection;

class PLCO_Connection_Repository extends PLCO_Abstract_Repository {

	/**
	 * @var string
	 */
	protected static string $table = 'connections';

	/**
	 * @var string
	 */
	protected static string $model_class = PLCO_Connection::class;

}
