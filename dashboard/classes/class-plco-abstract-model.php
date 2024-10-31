<?php

/**
 * PluginsCorner - https://pluginscorner.com.com
 *
 * @package plco-dashboard
 */

namespace PLCODashboard\classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

abstract class PLCO_Abstract_Model {
	/**
	 * @var int
	 */
	public int $ID = 0;

	/**
	 * @var PLCO_Abstract_Repository
	 */
	protected PLCO_Abstract_Repository $repository;

	public function __construct( $data ) {
		$name_arr         = explode( '\\', get_called_class() );
		$result           = implode( '\\', array_merge( array_slice( $name_arr, 0, count( $name_arr ) - 1 ), array( 'repositories' ), array_slice( $name_arr, count( $name_arr ) - 1 ) ) ) . '_Repository';
		$this->repository = new $result;
		$this->init( $data );
	}

	/**
	 * Initialize the object
	 * @return void
	 */
	protected function init( $data ) {

	}

	/**
	 * @return int
	 */
	public function getID() {
		return $this->ID;
	}

	/**
	 * @param int $ID
	 */
	public function setID( $ID ) {
		$this->ID = (int) $ID;
	}

	/**
	 * @return PLCO_Abstract_Repository
	 */
	public function getRepository() {
		return $this->repository;
	}

	/**
	 * Transform object to array
	 *
	 * @return array
	 */
	public function toArray() {
		return json_decode( json_encode( $this ), true );
	}

	/**
	 * @return bool|int|\mysqli_result|resource|null
	 */
	public function persist() {
		return $this->repository::persist( $this );
	}
}
