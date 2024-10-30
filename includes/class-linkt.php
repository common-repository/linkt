<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Linkt {

	/**
	 * The single instance of Linkt.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = LINKT_PLUGIN_VERSION ) {
		$this->_version = $version;
		$this->_token = 'linkt';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Dequeue Google Site Kits scripts only on Linkt admin page
		if (is_admin()) add_action('admin_enqueue_scripts', array( $this, 'admin_dequeue_gsk' ), 99);

		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new Linkt_Admin_API();
		}
		
		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $slug_url    Slug_URL of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $slug_url = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new Linkt_Post_Type( $post_type, $plural, $single, $slug_url, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new Linkt_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), LINKT_PLUGIN_VERSION );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Remove Google Site Kit JS on Linkt admin page
	 */
	public function admin_dequeue_gsk() {
		$screen = get_current_screen();
		if ( $this->linkt_is_plugin_active('google-site-kit.php') && get_option('wpt_linkt_setting_enable_charts') && $screen->base == 'post' && $screen->post_type == 'linkt' ) {
			wp_deregister_script('googlesitekit-google-charts');
			wp_deregister_script('googlesitekit-base-data-js-extra');
			wp_deregister_script('googlesitekit-base');
		}
	}

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin.js', array( 'jquery' ), LINKT_PLUGIN_VERSION );
		wp_enqueue_script( $this->_token . '-admin' );
		
		// Include Page Observer Javascript only on Linkt - Add New screen
		$screen = get_current_screen();
		if ( $screen->action == 'add' && $screen->post_type == 'linkt' ) {
			wp_register_script( $this->_token . '-page-observer', esc_url( $this->assets_url ) . 'js/post-observer.js', array( 'jquery' ), LINKT_PLUGIN_VERSION );
			wp_enqueue_script( $this->_token . '-page-observer' );
		}
		
		$graphtexts = array(
            'date' => __( 'Date', 'linkt' ),
            'clicks' => __( 'Clicks', 'linkt' ),
			'setting_currentmonth' => __( 'Current Month', 'linkt' ),
			'setting_2month' => __( 'Last Month + Current Month', 'linkt' ),
			'setting_7days' => __( 'Last 7 Days', 'linkt' ),
        );

		if (get_option( 'wpt_linkt_setting_enable_charts' )) {
			if ( $screen->base == 'post' && $screen->post_type == 'linkt' ) {
				wp_register_script( $this->_token . '-google-charts', 'https://www.gstatic.com/charts/loader.js', array(), LINKT_PLUGIN_VERSION, true );
				wp_enqueue_script( $this->_token . '-google-charts' );
				
				wp_register_script( $this->_token . '-gcharts-custom', esc_url( $this->assets_url ) . 'js/gcharts-custom.js', array( $this->_token . '-google-charts', 'jquery' ), LINKT_PLUGIN_VERSION );
				wp_localize_script( $this->_token . '-gcharts-custom', 'ajax_object', array( 
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'graphtext' => $graphtexts
				) );
				wp_enqueue_script( $this->_token . '-gcharts-custom' );
			}
		}
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'linkt', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'linkt';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Function to determine whether a plugin is active.
	 *
	 * @param string $plugin_name plugin name, as the plugin-filename.php
	 * @return boolean true if the named plugin is installed and active
	 * @since 1.0.0
	 */
	public static function linkt_is_plugin_active( $plugin_name ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$plugin_filenames = array();

		foreach ( $active_plugins as $plugin ) {

			if ( false !== strpos( $plugin, '/' ) ) {

				// normal plugin name (plugin-dir/plugin-filename.php)
				list( , $filename ) = explode( '/', $plugin );

			} else {

				// no directory, just plugin file
				$filename = $plugin;
			}

			$plugin_filenames[] = $filename;
		}

		return in_array( $plugin_name, $plugin_filenames );
	}

	/**
	 * Main Linkt Instance
	 *
	 * Ensures only one instance of Linkt is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Linkt()
	 * @return Main Linkt instance
	 */
	public static function instance ( $file = '', $version = LINKT_PLUGIN_VERSION ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'linkt' ), LINKT_PLUGIN_VERSION );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'linkt' ), LINKT_PLUGIN_VERSION );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', LINKT_PLUGIN_VERSION );
	} // End _log_version_number ()

}
