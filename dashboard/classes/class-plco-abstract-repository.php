<?php

/**
 * PluginsCorner - https://pluginscorner.com.com
 *
 * @package plco-dashboard
 */

namespace PLCODashboard\classes;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

abstract class PLCO_Abstract_Repository {

	/**
	 * @var string
	 */
	protected static string $table = '';

	/**
	 * @var string
	 */
	protected static string $model_class = '';

	/**
	 * @param array $criteria
	 *
	 * @return object|array|null
	 */
	public static function find_one_by( array $criteria ) {
		$query = new PLCO_Query( static::$table );
		$query->select();
		foreach ( $criteria as $criterion => $value ) {
			if ( is_array( $value ) ) {
				$query->and_where( $criterion, $value['value'], $value['condition'] );
				continue;
			}
			$query->and_where( $criterion, $value );
		}

		$result = $query->fetch_single();

		if($result) {
			return new static::$model_class( $result );
		}

		return null;

	}

	/**
	 * @param array $criteria
	 * @param int $limit
	 * @param int $offset
	 * @param string $order_by
	 *
	 * @return array|object|null
	 */
	public static function find_by( array $criteria, $limit = null, $offset = null, $order_by = null, $direction = 'ASC' ) {

		$results = array();
		$query = new PLCO_Query( static::$table );
		$query->select();
		foreach ( $criteria as $criterion => $value ) {
			if ( is_array( $value ) && $value['condition'] ) {
				$query->and_where( $criterion, $value['value'], $value['condition'] );
				continue;
			}

			$query->and_where( $criterion, $value );
		}

		if($limit) {
			$query->limit($limit, $offset ? $offset : 0);
		}

		if($order_by) {
			$query->order_by($order_by, $direction);
		}

		$results = $query->fetch_all();

		foreach ( $results as $key => $result ) {
			$results[ $key ] = new static::$model_class( $result );
		}

		return $results;
	}


	/**
	 * @param $order_by
	 * @param $direction
	 *
	 * @return array|object|null
	 */
	public static function get_all($order_by = null, $direction = 'ASC') {
		$query = new PLCO_Query( static::$table );
		$query->select();

		$results = $query->fetch_all();

		if($order_by) {
			$query->order_by($order_by, $direction);
		}

		foreach ( $results as $key => $result ) {
			$results[ $key ] = new static::$model_class( $result );
		}

		return $results;
	}

	/**
	 * @return PLCO_Query
	 */
	public static function query() {
		return new PLCO_Query( static::$table );
	}


	/**
	 * @param PLCO_Abstract_Model $model
	 *
	 * @return false|PLCO_Abstract_Model
	 * @throws ReflectionException
	 */
	public static function persist( PLCO_Abstract_Model $model ) {
		$data = $model->toArray();

		/**
		 * Save sub objects if any are found
		 */
		foreach ( $model as $key => $value ) {
			if ( is_object( $value ) ) {
				if ( strpos( $key, '_id' ) === false ) {
					$data[ $key . '_id' ] = $value->ID;
					unset( $data[ $key ] );
				} else {
					$data[ $key ] = $value->ID;
				}
			}

			$prop = new ReflectionProperty( get_class( $model ), $key );

			/**
			 * Remove data that should not be persisted
			 */
			if ( strpos( $prop->getDocComment(), '{no_persist}' ) !== false ) {
				unset( $data[ $key ] );
			}

			/**
			 * Remove the repository object
			 */
			if ( $key === 'repository' ) {
				unset( $data[ $key ] );
			}

			if ( is_array( $value ) ) {
				unset( $data[ $key ] );
			}
		}

		$query = new PLCO_Query( static::$table );

		$result = $model->getID() ? $query->update( $data ) : $query->insert( $data );

		if ( $result || ( ! $result && ! $query->get_errors() ) ) {

			if ( ! $model->getID() ) {
				$model->setID( $query->get_insert_id() );
			}

			return $model;
		}

		return false;
	}

	/**
	 * @param array $criteria
	 *
	 * @return int
	 */
	public static function get_count( array $criteria ) {
		$query = new PLCO_Query( static::$table );
		$query->select();
		foreach ( $criteria as $criterion => $value ) {
			if ( is_array( $value ) && $value['condition'] ) {
				$query->and_where( $criterion, $value['value'], $value['condition'] );
				continue;
			}

			$query->and_where( $criterion, $value );
		}

		$results = $query->fetch_all();

		return count( $results );
	}

	/**
	 * Destroys Model
	 *
	 * @param PLCO_Abstract_Model $model
	 * @param null $field
	 *
	 * @return bool|int|resource|null
	 */
	public static function destroy( PLCO_Abstract_Model $model, $field = null ) {
		$query  = new PLCO_Query( static::$table );
		$method = $field ? 'get' . ucfirst( $field ) : '';

		return $query->delete( $field ?: 'ID', $field ? $model->$method() : $model->getID() );
	}
}
