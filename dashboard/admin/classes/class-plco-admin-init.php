<?php

namespace PLCODashboard\admin\classes;

use PLCODashboard\classes\PLCO_Abstract_Init;
use PLCODashboard\classes\PLCO_Abstract_Helpers;
use PLCODashboard\classes\PLCO_License_Manager;


class PLCO_Admin_Init extends PLCO_Abstract_Init {

	public function init() {

	}


	public function add_actions() {
		$this->loader->add_action( 'admin_menu', $this, 'admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_utils', 5);
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_scripts', 20 );
	}

	/**
	 * Creates the admin menu
	 */
	public function admin_menu() {
		$capability = apply_filters( 'PLCO_access_capability', 'manage_options' );

		add_menu_page(
			'Plugins Corner',
			'Plugins Corner',
			$capability,
			'plco_dashboard',
			array( $this, 'dash_page' ),
			$this->const::plugin_uri() . '/images/icon.jpg',
			25
		);

		$items = array();
		$items = apply_filters( 'PLCO_dashboard_items', $items );

		foreach ( $items as $item ) {

			$url = $item['ID'];
			/**
			 * With no callback function most likely we have a inner
			 * page redirect so we should change the submenu url in
			 * order to reflect this and work properly
			 */
			if ( ! isset( $item['function'] ) ) {
				if ( post_type_exists( $item['ID'] ) ) {
					$url = 'edit.php?post_type=' . $item['ID'];
				} else {
					$url = 'admin.php?page=' . $item['ID'];
				}
			}

			/**
			 * $item['function'] should be an associative array with the fields as follows:
			 * array('class_name', 'method_name')
			 * in order for the add_submenu_page function to create the correct callback
			 *
			 * if the callback is empty check the above conditional statement
			 */
			$callback = isset( $item['function'] ) ? $item['function'] : '';

			if ( current_user_can( $item['capability'] ) && $item['installed'] && ( ! isset( $item['no_settings'] ) || $item['no_settings'] === false ) ) {
				add_submenu_page( 'plco_dashboard', $item['name'], $item['name'], $item['capability'], $url, $callback );
			}
		}

		/**
		 * No Settings as of now
		 */
		add_submenu_page( 'plco_dashboard', 'Support', 'Support', $capability, 'plco_support', array( $this, 'support_page' ) );
	}

	/**
	 * Render the Dashboard Page
	 */
	public function dash_page() {
		$this->template( 'dashboard.php' );
	}

	/**
	 * Render the Dashboard Page
	 */
	public function support_page() {
		$this->template( 'support.php' );
	}

	/**
	 * Enqueue the admin scrips
	 * @param $handle
	 */
	public function enqueue_admin_scripts($handle) {

		if($handle === "toplevel_page_plco_dashboard") {
			$this->enqueue_script( 'plco-dash', PLCO_Const::url() . 'admin/js/dist/dashboard.min.js', array(
				'jquery',
				'backbone',
			), false, true );
		}

		if($handle === 'plugins-corner_page_plco_support') {
			$this->enqueue_script( 'plco-support', PLCO_Const::url() . 'admin/js/support/dist/support.min.js', array(
				'jquery',
				'backbone',
			), false, true );
		}

	}

	/**
	 * Enqueue all utilities
	 */
	public function enqueue_utils() {
		/**
		 * Make sure we have jquery enqueued everywhere
		 */
		wp_enqueue_script( 'jquery' );

		/**
		 * For now backbone, color picker and sortable should only be used on the backend
		 */
		if ( is_admin() ) {
			wp_enqueue_script( 'backbone' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'jquery-ui-sortable', false, array( 'jquery' ) );

			$this->enqueue_script( 'plco-utils', PLCO_Const::url() . 'admin/js/dist/_util.min.js', array(
				'jquery',
				'backbone',
			), false, true );

			$this->enqueue_style('material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons');

			$this->helper::enqueue_dashboard();

			wp_localize_script( 'plco-utils', 'PLCOUtils', $this->get_localization() );

			$this->load_backbone_templates();
		}
	}

	/**
	 * Get data for ThriveUtils
	 * @return array
	 */
	public function get_localization() {

		$list = apply_filters( 'PLCO_dashboard_items', array() );

		$data = array(
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'is_pro'  => 0,
			't'      => require $this->const::plugin_path() . '/i18n.php',
			'products' => $list,
			'routes' => array(
				'license' =>  $this->get_route_url( 'license' ),
				'login' =>  $this->get_route_url( 'login' ),
				'topics' => $this->get_route_url( 'topics' ),
				'replies' => $this->get_route_url( 'replies' ),
			),
			'license'  => PLCO_License_Manager::get_license(),
		);

		$data = apply_filters('plco_dashboard_localization', $data);

		return $data;
	}

}
