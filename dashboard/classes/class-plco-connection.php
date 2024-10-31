<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\classes;


/**
 * Class PLCOM_Connection
 *
 * @package PLCODashboard\classes
 */
class PLCO_Connection extends PLCO_Abstract_Model {

	/**
	 * @var
	 */
	public $connection_type;

	/**
	 * @var
	 */
	public $connection_name;

	/**
	 * @var
	 */
	public $api_key;

	/**
	 * @var
	 */
	public $api_secret;

	/**
	 * @var
	 */
	public $sandbox;

	/**
	 * @var
	 */
	public $extra;

	/**
	 * {no_persist}
	 *
	 * @var
	 */
	public $api_url;

	/**
	 * {no_persist}
	 *
	 * @var
	 */
	public $api_sandbox_url;

	/**
	 * @var
	 */
	public $created_at;

	/**
	 * PLCOM_Payment constructor.
	 *
	 * @param $user_membership
	 */
	public function init($connection) {
		foreach ( get_object_vars( $connection ) as $key => $value ) {
			$exploded = explode( "_", $key );

			foreach ( $exploded as $ex => $item ) {
				$exploded[ $ex ] = ucwords( $item );
			}

			$method = "set" . implode( "", $exploded );

			if (method_exists($this, $method)) {
				$this->$method( $value );
			}
		}

		if(defined('PLCODashboard\admin\classes\PLCO_Const::' . strtoupper($this->getConnectionName()) . "_API_URL")) {
			$this->setApiUrl(constant('PLCODashboard\admin\classes\PLCO_Const::' . strtoupper($this->getConnectionName()) . "_API_URL"));
		}

		if(defined('PLCODashboard\admin\classes\PLCO_Const::' . strtoupper($this->getConnectionName()) . "_API_SANDBOX_URL")) {
			$this->setApiSandboxUrl(constant('PLCODashboard\admin\classes\PLCO_Const::' . strtoupper($this->getConnectionName()) . "_API_SANDBOX_URL"));
		}
	}

	/**
	 * @return mixed
	 */
	public function getConnectionName() {
		return $this->connection_name;
	}

	/**
	 * @param mixed $connection_name
	 */
	public function setConnectionName( $connection_name ) {
		$this->connection_name = $connection_name;
	}
	/**
	 * @return mixed
	 */
	public function getConnectionType() {
		return $this->connection_type;
	}

	/**
	 * @param mixed $connection_type
	 */
	public function setConnectionType( $connection_type ) {
		$this->connection_type = $connection_type;
	}

	/**
	 * @return mixed
	 */
	public function getApiKey() {
		return $this->api_key;
	}

	/**
	 * @param mixed $api_key
	 */
	public function setApiKey( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * @return mixed
	 */
	public function getApiSecret() {
		return $this->api_secret;
	}

	/**
	 * @param mixed $api_secret
	 */
	public function setApiSecret( $api_secret ) {
		$this->api_secret = $api_secret;
	}

	/**
	 * @return mixed
	 */
	public function getSandbox() {
		return $this->sandbox;
	}

	/**
	 * @param mixed $sandbox
	 */
	public function setSandbox( $sandbox ) {
		$this->sandbox = (int) $sandbox;
	}

	/**
	 * @return mixed
	 */
	public function getExtra() {
		return $this->extra;
	}

	/**
	 * @param mixed $extra
	 */
	public function setExtra( $extra ) {
		$this->extra = $extra;
	}

	/**
	 * @return mixed
	 */
	public function getApiUrl() {
		return $this->api_url;
	}

	/**
	 * @param mixed $api_url
	 */
	public function setApiUrl( $api_url ) {
		$this->api_url = $api_url;
	}

	/**
	 * @return mixed
	 */
	public function getApiSandboxUrl() {
		return $this->api_sandbox_url;
	}

	/**
	 * @param mixed $api_sandbox_url
	 */
	public function setApiSandboxUrl( $api_sandbox_url ) {
		$this->api_sandbox_url = $api_sandbox_url;
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
