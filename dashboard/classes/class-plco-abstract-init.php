<?php
/**
 * PluginsCorner - https://pluginscorner.com.com
 *
 * @package pluco-membership
 */

namespace PLCODashboard\classes;

use PLCOMembership\admin\classes\PLCOM_Const;
use ReflectionClass;
use DirectoryIterator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

abstract class PLCO_Abstract_Init {
	/**
	 * @var string
	 */
	protected $component_name;

	/**
	 * @var string
	 */
	protected $include_dir;

	/**
	 * @var PLCO_Actions_Loader
	 */
	protected $loader;

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * @var array
	 */
	protected $endpoints;

	/**
	 * @var \WP_Query|$wpdb
	 */
	protected $wpdb;

	/**
	 * Default Constants File Name
	 *
	 * @var string
	 */
	protected $constants_filename = "class-plco-const.php";

	/**
	 * Default Helper File Name
	 *
	 * @var string
	 */
	protected $helper_filename = "class-plco-helpers.php";

	/**
	 * Constants
	 *
	 * @var \PLCODashboard\admin\classes\PLCO_Const|PLCOM_Const
	 */
	protected $const;

	/**
	 * Helper
	 *
	 * @var \PLCODashboard\dashboard\classes\PLCO_Abstract_Helpers
	 */
	protected $helper;

	/**
	 * @var bool
	 */
	protected $is_pro = false;

	/**
	 * @var array
	 */
	protected $license = array();


	/**
	 * PLCO_Abstract_Init constructor.
	 *
	 * @param string $component_name
	 * @param bool   $is_admin_page
	 */
	public function __construct( $component_name = '', $is_admin_page = false ) {

		$this->license = PLCO_License_Manager::get_license();

		if ( file_exists( PLCOM_Const::plugin_path() . 'pro/admin/classes/class-plcom-pro-admin-init.php' ) ) {
			$this->is_pro = true;
		}

		$this->load_constants();

		$this->wpdb = $this->const::get_wpdb();

		$this->component_name = $component_name;
		$this->set_version( '1.0.0' );

		$this->include_dir = $is_admin_page ? 'admin' : 'front';
		$this->load_dependencies();
		$this->add_actions();
		$this->loader->run();

		$this->init();

	}

	/**
	 * Loads constants
	 *
	 * @return void
	 */
	protected function load_constants() {
		$reflector = new ReflectionClass( static::class );
		$dirname   = pathinfo( $reflector->getFileName(), PATHINFO_DIRNAME );
		$namespace = $reflector->getNamespaceName();

		/**
		 * Try and determine if the constants class is added in the front or in the admin
		 */
		if ( ! file_exists( $dirname . DIRECTORY_SEPARATOR . $this->constants_filename ) ) {
			if ( strpos( $dirname, 'front' ) !== false ) {
				$dirname = str_replace( "front", "admin", $dirname );
				include_once( $dirname . DIRECTORY_SEPARATOR . $this->constants_filename );
				$namespace = str_replace( "front", "admin", $namespace );
			} elseif ( strpos( $dirname, 'front' ) !== false ) {
				$dirname = str_replace( "admin", "front", $dirname );
				include_once( $dirname . DIRECTORY_SEPARATOR . $this->constants_filename );
				$namespace = str_replace( "admin", "front", $namespace );
			}
		} else {
			include_once( $dirname . DIRECTORY_SEPARATOR . $this->constants_filename );
		}


		$base       = basename( $this->constants_filename, '.php' );
		$class_name = str_replace( 'class-', '', $base );
		$class_name = str_replace( '-', '_', $class_name );
		$parts      = explode( '_', $class_name );
		$parts[0]   = strtoupper( $parts[0] );
		$class_name = implode( '_', array_map( 'ucfirst', $parts ) );

		$this->const = call_user_func( array(
			$namespace . "\\" . $class_name,
			'get_instance'
		) );
	}

	/**
	 * Load Dependecies
	 */
	protected function load_dependencies() {

		/**
		 * Load component helper file if exists and instantiate it's class
		 */
		$this->load_component_helper();


		/**
		 * Initialize the actions loader
		 */
		$this->loader = new PLCO_Actions_Loader();

		/**
		 * Load the default files for admin EX: include (CPT_PC_Const::components_path($this->include_dir . '/xx.php', $this->component_name))
		 */
		$endpoints_dir = $this->const::plugin_path() . $this->include_dir . '/endpoints';

		/**
		 * Autoload the endpoint classes
		 */
		if ( file_exists( $endpoints_dir ) ) {

			$dir = new DirectoryIterator( $endpoints_dir );

			foreach ( $dir as $file_info ) {
				if ( $file_info->isDot() ) {
					continue;
				}

				/**
				 * Create the classname from the filename
				 * The files should be in the following format:  class-cpt-pc-{class-name}.php
				 */
				$file       = $this->const::build_class_name( $file_info->getFilename() );
				$reflector  = new ReflectionClass( static::class );
				$namespaced = str_replace( 'classes', 'endpoints', $reflector->getNamespaceName() ) . "\\" . $file;


				$this->endpoints[] = $namespaced;

				require_once( $file_info->getPathname() );

			}

			$this->loader->add_action( 'rest_api_init', $this, 'create_initial_rest_routes' );
			$this->loader->run( 'rest_api_init' );

		}

	}

	/**
	 * Load component helper
	 */
	private function load_component_helper() {
//		$this->load( 'helpers/class-plco-helpers.php' );
//		$helpers_class_name = $this->const::build_class_name( $this->component_name, 'Helpers' );

		$reflector = new ReflectionClass( static::class );
		$dirname   = pathinfo( $reflector->getFileName(), PATHINFO_DIRNAME );
		$namespace = $reflector->getNamespaceName();

		/**
		 * Try and determine if the constants class is added in the front or in the admin
		 */
		if ( ! file_exists( $dirname . DIRECTORY_SEPARATOR . $this->helper_filename ) ) {
			if ( strpos( $dirname, 'front' ) !== false ) {
				$dirname = str_replace( "front", "admin", $dirname );
				if ( file_exists( $dirname . DIRECTORY_SEPARATOR . $this->helper_filename ) ) {
					include_once( $dirname . DIRECTORY_SEPARATOR . $this->helper_filename );
					$namespace = str_replace( "front", "admin", $namespace );
				}
			} elseif ( strpos( $dirname, 'front' ) !== false ) {
				$dirname = str_replace( "admin", "front", $dirname );
				if ( file_exists( $dirname . DIRECTORY_SEPARATOR . $this->helper_filename ) ) {
					include_once( $dirname . DIRECTORY_SEPARATOR . $this->helper_filename );
					$namespace = str_replace( "admin", "front", $namespace );
				}
			}
		} else {
			include_once( $dirname . DIRECTORY_SEPARATOR . $this->helper_filename );
		}


		$base       = basename( $this->helper_filename, '.php' );
		$class_name = str_replace( 'class-', '', $base );
		$class_name = str_replace( '-', '_', $class_name );
		$parts      = explode( '_', $class_name );
		$parts[0]   = strtoupper( $parts[0] );
		$class_name = $namespace . "\\" . implode( '_', array_map( 'ucfirst', $parts ) );


		if ( class_exists( $class_name ) ) {

			$this->helper = new $class_name( $this->const, $this->component_name );
		}

	}

	/**
	 * Abstract actions and filters to be extended
	 */
	public function add_actions() {

	}

	/**
	 * Abstract init to be extended
	 */
	public function init() {

	}

	/**
	 * Add the action for backbone templates
	 */
	public function load_backbone_templates() {
		/**
		 * include backbone script templates at the bottom of the page
		 */
		$this->loader->add_action( 'admin_print_footer_scripts', $this, 'backbone_templates' );

		$this->loader->run( 'admin_print_footer_scripts' );
	}

	/**
	 * Return the template path
	 *
	 * @return string
	 */
	public function bb_template_path() {
		return $this->const::path( '/templates/views', $this->include_dir );
	}

	/**
	 * Include the backbone templates as scripts
	 */
	public function backbone_templates() {

		$template_path = $this->bb_template_path();
		$templates     = $this->get_backbone_templates( $template_path, 'views' );

		foreach ( $templates as $tpl_id => $path ) {

			echo '<script type="text/template" id="' . $tpl_id . '">';
			include $path;
			echo '</script>';
		}
	}

	/**
	 * Gets the backbone templates so we can use them in views
	 *
	 * @param null   $dir
	 * @param string $root
	 *
	 * @return array
	 */
	public function get_backbone_templates( $dir = null, $root = 'template' ) {
		if ( null === $dir ) {
			return array();
		}

		$folders   = scandir( $dir );
		$templates = array();
		foreach ( $folders as $item ) {
			if ( in_array( $item, array( '.', '..' ) ) ) {
				continue;
			}
			if ( is_dir( $dir . '/' . $item ) ) {
				$templates = array_merge( $templates, $this->get_backbone_templates( $dir . '/' . $item, $root ) );
			}
			if ( is_file( $dir . '/' . $item ) ) {
				$_parts               = explode( $root, $dir );
				$_truncated           = end( $_parts );
				$tpl_id               = ( ! empty( $_truncated ) ? trim( $_truncated, '/\\' ) . '/' : '' ) . str_replace( array(
						'.php',
						'.phtml',
					), '', $item );
				$tpl_id               = str_replace( array( '/', '\\' ), '-', $tpl_id );
				$templates[ $tpl_id ] = $dir . '/' . $item;
			}
		}

		return $templates;
	}


	/**
	 * @return string
	 */
	public function get_component_name() {
		return $this->component_name;
	}

	/**
	 * @return PLCO_Actions_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * @param PLCO_Actions_Loader $loader
	 */
	public function set_loader( $loader ) {
		$this->loader = $loader;
	}

	/**
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * @param string $version
	 */
	public function set_version( $version ) {
		$this->version = $version;
	}

	/**
	 * Get the route URL
	 *
	 * @param       $endpoint
	 * @param int   $id
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_route_url( $endpoint, $id = 0, $args = array() ) {

		$url = get_rest_url() . $this->const::REST_NAMESPACE . '/' . $endpoint;

		if ( ! empty( $id ) && is_numeric( $id ) ) {
			$url .= '/' . $id;
		}

		if ( ! empty( $args ) ) {
			add_query_arg( $args, $url );
		}

		return $url;
	}

	/**
	 * Instantiate the rest routes for ajax calls
	 *
	 * @throws \ReflectionException
	 */
	public function create_initial_rest_routes() {
		foreach ( $this->endpoints as $e ) {
			/** @var PLCO_REST_Controller $controller */
			$controller = new $e();
			$controller->register_routes();
		}
	}

	/**
	 * Loads a speciofic file within the component dir
	 *
	 * @param $path
	 */
	protected function load( $path ) {
		$file = $this->const::path( $path, $this->component_name );

		if ( file_exists( $file ) ) {
			require( $file );
		}
	}

	/**
	 * Load a specific template file
	 *
	 * @param string $file
	 * @param array  $data
	 */
	protected function template( $file = '', $data = array() ) {
		require( $this->const::path( '/templates/' . $file, $this->include_dir ) );
	}

	/**
	 * wrapper over the wp_enqueue_script function
	 * it will add the plugin version to the script source if no version is specified
	 *
	 * @param             $handle
	 * @param string|bool $src
	 * @param array       $deps
	 * @param bool        $ver
	 * @param bool        $in_footer
	 */
	protected function enqueue_script( $handle, $src = false, $deps = array(), $ver = false, $in_footer = false ) {
		if ( $ver === false ) {
			$ver = $this->version;
		}

		if ( defined( 'PLCO_DEBUG' ) && PLCO_DEBUG ) {
			$src = preg_replace( '#\.min\.js$#', '.js', $src );
		}

		wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
	}

	/**
	 * wrapper over the wp_enqueue_style function
	 * it will add the plugin version to the style link if no version is specified
	 *
	 * @param             $handle
	 * @param string|bool $src
	 * @param array       $deps
	 * @param bool|string $ver
	 * @param string      $media
	 */
	protected function enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' ) {
		if ( $ver === false ) {
			$ver = $this->version;
		}
		wp_enqueue_style( $handle, $src, $deps, $ver, $media );
	}
}
