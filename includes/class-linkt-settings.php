<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Linkt_Settings {

	/**
	 * The single instance of Linkt_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		$this->parent = $parent;

		$this->base = 'wpt_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings & license page
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=linkt', __( 'Linkt Settings', 'linkt' ), __( 'Settings', 'linkt' ), 'manage_options', $this->parent->_token . '_settings', array( $this, 'settings_page' ) );
		// add_action( 'admin_print_styles-linkt', array( $this, 'settings_assets' ) );
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="edit.php?post_type=linkt&page=' . esc_attr( $this->parent->_token ) . '_settings">' . esc_html__( 'Settings', 'linkt' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$settings['standard'] = array(
			'title'					=> '', // __( 'Settings', 'linkt' ),
			'description'			=> '', // __( 'Customize the Linkt settings as you want', 'linkt' ),
			'fields'				=> array(
				array(
					'id' 			=> 'linkt_setting_enable_charts',
					'label'			=> __( 'Enable Google Charts', 'linkt' ),
					'description'	=> __( 'With new, updated charts api, you\'re required to manually enable them here. By enabling the charts, you consent to the Google <a href="https://developers.google.com/terms#section_2_using_our_apis" target="_blank">terms</a>', 'linkt' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'linkt_setting_dashwidget_layout',
					'label'			=> __( 'Display Dashboard Widget list as single links', 'linkt' ),
					'description'	=> '', // __( 'description', 'linkt' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'linkt_setting_track_admin',
					'label'			=> __( 'Track Administrator clicks too', 'linkt' ),
					'description'	=> '', // __( 'description', 'linkt' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id'          => 'linkt_setting_linkt_orderby',
					'label'       => __( 'Order the Dashboard Widget Linkt\'s by', 'linkt' ),
					'description' => '',
					'type'        => 'select',
					'options'     => array(
						'title'          => __( 'Linkt Title', 'linkt' ),
						'meta_value_num' => __( 'Click Count', 'linkt' ),
					),
					'default'     => 'meta_value_num',
				),
				array(
					'id'          => 'linkt_setting_linkt_order',
					'label'       => __( 'Order', 'linkt' ),
					'description' => '',
					'type'        => 'select',
					'options'     => array(
						'DESC' => __( 'Descending', 'linkt' ),
						'ASC'  => __( 'Ascending', 'linkt' ),
					),
					'default'     => 'DESC',
				),
				array(
					'id'          => 'linkt_setting_graph_display',
					'label'       => __( 'Select what the graph on each linkt displays', 'linkt' ),
					'description' => '',
					'type'        => 'select',
					'options'     => array(
						// 'linkt_3month' => 'Last 2 Months + Current Month',
						'linkt_2month' => __( 'Last Month + Current Month', 'linkt' ),
						'linkt_month'  => __( 'Current Month', 'linkt' ),
						'linkt_7days'  => __( 'Last 7 days', 'linkt' ),
					),
					'default'     => 'linkt_month',
				),
				array(
					'id' 			=> 'linkt_setting_url_ext',
					'label'			=> __( 'URL extension' , 'linkt' ),
					'description'	=> '', // __( 'description', 'linkt' ),
					'type'			=> 'text',
					'default'		=> 'go',
					'placeholder'	=> __( 'go', 'linkt' )
				),
				array(
					'id' 			=> 'linkt_setting_delete_all_data',
					'label'			=> __( 'Delete ALL data when Linkt is deleted', 'linkt' ),
					'description'	=> __( 'Use this ONLY IF you are permanently deleting Linkt', 'linkt' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				)
			)
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = sanitize_text_field( $_POST['tab'] );
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = sanitize_text_field( $_GET['tab'] );
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) break;
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . esc_html__( 'Linkt Settings' , 'linkt' ) . '</h2>' . "\n";

			$tab = '';
			if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				$tab .= sanitize_text_field( $_GET['tab'] );
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( ! isset( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
							$class .= ' nav-tab-active';
						}
					} else {
						if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							$class .= ' nav-tab-active';
						}
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if ( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . esc_url( $tab_link ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();

				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr__( 'Save Settings' , 'linkt' ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		ob_start(); ?>

			<div class="linkt-woocustomizer">
				<a href="https://woocustomizer.com/go/from-linkt/" target="_blank" class="linkt-woocustomizer-img"><img src="<?php echo esc_url( LINKT_PLUGIN_URL ); ?>/assets/images/woocustomizer.png" alt="WooCustomizer" /></a>
				<?php
				// Creates a button to Add New Linkts
				echo sprintf( __( 'Customize your WooCommerce store with our %1$sWooCustomizer plugin%2$s' , 'linkt' ), '<a href="https://woocustomizer.com/go/from-linkt/" target="_blank">', '</a>' ); ?>
			</div>

			<div class="linkt-donate">

				<p><?php esc_html_e( 'Did you find this plugin useful?', 'linkt' ); ?></p>
				
				<p>
					<?php
					/* translators: 1: 'install the premium theme'. */
					printf( esc_html__( 'Please support our work with a %1$s', 'overlay' ), wp_kses( __( '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PFZGBM92T8XSE&source=url" target="_blank">PayPal donation</a>', 'overlay' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ) ); ?>
				</p>

				<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PFZGBM92T8XSE&source=url" target="_blank" class="linkt-donate-img"><img src="<?php echo esc_url( LINKT_PLUGIN_URL ); ?>/assets/images/paypal-donate.png" alt="Donate With PayPal" /></a>
				
				<p><?php esc_html_e( 'Or even write an article about Linkt in your blog with a link to our site', 'linkt' ); ?> - <a href="https://kairaweb.com/wordpress-plugin/linkt/" target="_blank"><?php esc_html_e( 'Linkt Plugin', 'linkt' ); ?></a></p>

			</div>

		<?php
		$html .= ob_get_clean();

		echo $html;
	}

	/**
	 * Main Linkt_Settings Instance
	 *
	 * Ensures only one instance of Linkt_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Linkt()
	 * @return Main Linkt_Settings instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'linkt' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'linkt' ), $this->parent->_version );
	} // End __wakeup()

}
