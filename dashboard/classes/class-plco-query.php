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

class PLCO_Query {

	/**
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * @var string
	 */
	private string $prefix = 'plcom_';

	/**
	 *
	 * @var array|string[]
	 */
	private array $plco_prefix = [
		'connections'
	];

	/**
	 *
	 * @var array|string[]
	 */
	private array $plcos_prefix = [
		'replies',
		'topics'
	];

	/**
	 * @var string
	 */
	private string $table;

	/**
	 * @var string
	 */
	private string $select = '';

	/**
	 * @var string
	 */
	private string $where = '';

	/**
	 * @var string
	 */
	private string $order_by = '';

	/**
	 * @var string
	 */
	private string $limit = '';

	/**
	 * @param $table
	 */
	public function __construct( $table ) {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $table;

		if ( in_array( $this->table, $this->plco_prefix ) ) {
			$this->prefix = 'plco_';
		}

		if ( in_array( $this->table, $this->plcos_prefix ) ) {
			$this->prefix = 'plcos_';
		}
	}

	/**
	 * @param array $fields
	 *
	 * @return void
	 */
	public function select( array $fields = [] ) {
		if ( empty( $fields ) ) {
			$this->select = 'SELECT * FROM ' . $this->wpdb->prefix . $this->prefix . $this->table;
		} else {

			$this->select = 'SELECT ';

			foreach ( $fields as $key => $field ) {
				if ( count( $fields ) === ( $key + 1 ) ) {
					$this->select .= $field;
					continue;
				}

				$this->select .= $field . ',';
			}

			$this->select .= ' FROM ' . $this->wpdb->prefix . $this->prefix . $this->table;
		}
	}

	/**
	 * @param string $field
	 * @param int|string $value
	 * @param string $condition
	 *
	 * @return void
	 */
	public function where( string $field, $value, string $condition = '=' ) {
		if ( empty( $this->where ) ) {
			$this->where = ' WHERE ' . $field . ' ' . $condition . ' ' . '"' . $value . '"';

			return;
		}

		$this->and_where( $field, $value, $condition );
	}

	/**
	 * @param string $field
	 * @param string|int $value
	 * @param string $condition
	 *
	 * @return void
	 */
	public function and_where( string $field, $value, string $condition = '=' ) {
		if ( empty( $this->where ) ) {
			$this->where( $field, $value, $condition );

			return;
		}
		$this->where .= ' AND ' . $field . ' ' . $condition . ' ' . '"' . $value . '"';
	}

	/**
	 * @param string $field
	 * @param string|int $value
	 * @param string $condition
	 *
	 * @return void
	 */
	public function or_where( string $field, $value, string $condition = '=' ) {
		if ( empty( $this->where ) ) {
			$this->where( $field, $value, $condition );

			return;
		}
		$this->where .= ' OR ' . $field . ' ' . $condition . ' ' . '"' . $value . '"';
	}

	/**
	 * @param string $column
	 * @param string $direction
	 *
	 * @return void
	 */
	public function order_by( string $column, string $direction = "ASC" ) {
		$this->order_by = ' ORDER BY ' . $column . ' ' . $direction;
	}

	/**
	 * @param int $row_count
	 * @param int $offset
	 *
	 * @return void
	 */
	public function limit( int $row_count, int $offset = 0 ) {
		$this->limit = ' LIMIT ' . $offset . ' , ' . $row_count;
	}

	/**
	 * Insert elements
	 *
	 * @param $elements
	 *
	 * @return bool|int|\mysqli_result|resource|null
	 */
	public function insert( $elements ) {
		unset( $elements['created_at'] );

		return $this->wpdb->insert(
			$this->wpdb->prefix . $this->prefix . $this->table,
			$elements,
			$this->get_types( $elements )
		);
	}

	/**
	 * Update in the table
	 *
	 * @param $elements
	 *
	 * @return bool|int|\mysqli_result|resource|null
	 */
	public function update( $elements ) {
		$id = $elements['ID'];
		unset( $elements['ID'] );

		return $this->wpdb->update(
			$this->wpdb->prefix . $this->prefix . $this->table,
			$elements,
			array( 'ID' => $id ),
			$this->get_types( $elements )
		);
	}

	/**
	 * Deletes Item from the db
	 *
	 * @param $field
	 * @param $data
	 *
	 * @return bool|int|\mysqli_result|resource|null
	 */
	public function delete( $field, $data ) {
		return $this->wpdb->delete(
			$this->wpdb->prefix . $this->prefix . $this->table,
			array(
				$field => $data
			),
			$this->get_types( array( $data ) )
		);
	}

	/**
	 * @return array|object|null
	 */
	public function fetch_all() {
		return $this->wpdb->get_results( $this->build_query() );
	}

	/**
	 * @return array|object|\stdClass|void|null
	 */
	public function fetch_single() {
		return $this->wpdb->get_row( $this->build_query() );
	}

	/**
	 * Builds the query
	 *
	 * @return string
	 */
	private function build_query() {
		if ( empty( $this->select ) ) {
			$this->select();
		}

		return $this->select . $this->where . $this->order_by . $this->limit;
	}

	/**
	 * Defines the data types
	 *
	 * @param $data
	 *
	 * @return array
	 */
	private function get_types( $data ) {
		$types = array();
		foreach ( $data as $item ) {
			switch ( gettype( $item ) ) {
				case 'string':
					$types[] = '%s';
					break;
				case 'integer':
				case 'NULL':
					$types[] = '%d';
					break;
				case 'double':
					$types[] = '%f';
					break;
			}
		}

		return $types;
	}

	/**
	 * Returns errors if any
	 *
	 * @return string
	 */
	public function get_insert_id() {
		return $this->wpdb->insert_id;
	}

	/**
	 * Returns errors if any
	 *
	 * @return string
	 */
	public function get_errors() {
		return $this->wpdb->last_error;
	}

	/**
	 * Returns errors if any
	 *
	 * @return string
	 */
	public function last_query() {
		return $this->wpdb->last_query;
	}

}
