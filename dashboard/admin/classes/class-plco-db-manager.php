<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\admin\classes;
use DirectoryIterator;
use ReflectionClass;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class which manages the database creation and management
 *
 * Class PLCO_DB_Manager
 */
class PLCO_DB_Manager {

	/**
	 * database version
	 *
	 * @var
	 */
	private $db_version;

	/**
	 * plugin version
	 *
	 * @var
	 */
	private $plugin_version;

	/**
	 * PLCOM_DB_Manager constructor.
	 */
	public function __construct() {
		$this->check_db();
		$this->check_plugin();
	}

	/**
	 * We should have the format of the file as: {name}-{v}.{v}.php
	 */
	private function check_db() {
		if ( is_admin() && ! empty( $_REQUEST['PLCO_db_reset'] ) ) {
			delete_option( 'PLCO_plugin_version' );
			delete_option( 'PLCO_db_version' );
		}

		$this->db_version = get_option( 'PLCO_db_version', 0.0 );



		if ( version_compare( $this->db_version, PLCO_Const::DB_VERSION, '<' ) ) {
			$migrations = PLCO_Const::plugin_path() . 'migrations';
			$dir        = new DirectoryIterator( $migrations );
			$scripts    = array();


			foreach ( $dir as $fileinfo ) {
				if ( ! $fileinfo->isDot() ) {
					$file         = $fileinfo->getFilename();
					$base         = basename( $file, '.php' );
					$file_version = trim( explode( '-', $base )[1] );

					if ( version_compare( $file_version, $this->db_version, '>' ) ) {
						$scripts[ $base ] = $fileinfo->getPathname();
					}
				}
			}


			if ( ! empty( $scripts ) ) {
				define( 'PLCO_DATABASE_UPGRADE', true );

				foreach ( $scripts as $key => $script ) {
					/**
					 * Create the classname from the filename minus the version
					 */
					$class_name = substr( $key, 0, strpos( $key, '-' ) );
					$class_name = implode( '_', array_map( 'ucfirst', explode( '_', $class_name ) ) );
					$class_name = 'PLCODashboard\migrations\PLCO_' . $class_name . '_DB';

					include_once( $script );

					/**
					 * instantiate the class and let it do it's magic
					 */
					new $class_name();

				}
			}

			/**
			 * update the db version which we installed
			 */
			update_option( 'PLCO_db_version', PLCO_Const::DB_VERSION );
		}
	}

	/**
	 * Check the plugin version, update it and do whatever needs to be done for the new version
	 */
	private function check_plugin() {

		$this->plugin_version = get_option( 'PLCOM_plugin_version', 0.1 );

		if ( version_compare( $this->plugin_version, PLCO_Const::VERSION, '<' ) ) {
			/**
			 * Versioning stuff goes here
			 */
			update_option( 'PLCO_plugin_version', PLCO_Const::VERSION );
		}
	}
}
