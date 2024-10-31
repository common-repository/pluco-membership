<?php
/**
 * PluginsCorner - https://pluginscorner.com.com
 *
 * @package pluco-membership
 */

namespace PLCODashboard\classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}


/**
 * Class PLCO_Actions_Loader
 */
class PLCO_Actions_Loader {

	/**
	 * @var array
	 */
	protected $actions;

	/**
	 * @var array
	 */
	protected $filters;

	/**
	 * PLCO_Actions_Loader constructor.
	 */
	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Add action Method
	 *
	 * @param $hook
	 * @param $component
	 * @param $callback
	 * @param int $priority
	 * @param int $accepted_args
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add filter method
	 *
	 * @param $hook
	 * @param $component
	 * @param $callback
	 * @param int $priority
	 * @param int $accepted_args
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add to the array of filters or actions
	 *
	 * @param $hooks
	 * @param $hook
	 * @param $component
	 * @param $callback
	 * @param $priority
	 * @param $accepted_args
	 *
	 * @return array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Run the actions and filters
	 */
	public function run( $item = '' ) {
		foreach ( $this->filters as $hook ) {
			if ( empty( $item ) || $hook['hook'] === $item ) {
				add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
			}
		}
		foreach ( $this->actions as $hook ) {
			if ( empty( $item ) || $hook['hook'] === $item ) {
				add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
			}
		}
	}
}
