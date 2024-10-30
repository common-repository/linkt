<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Linkt_Post_Type {

	/**
	 * The name for the custom post type.
	 * @var 	string
	 * @access  public
	 * @since 	1.0.0
	 */
	public $post_type;

	/**
	 * The plural name for the custom post type posts.
	 * @var 	string
	 * @access  public
	 * @since 	1.0.0
	 */
	public $plural;

	/**
	 * The singular name for the custom post type posts.
	 * @var 	string
	 * @access  public
	 * @since 	1.0.0
	 */
	public $single;

	/**
	 * The slug_url of the custom post type.
	 * @var 	string
	 * @access  public
	 * @since 	1.0.0
	 */
	public $slug_url;

	/**
	 * The options of the custom post type.
	 * @var 	array
	 * @access  public
	 * @since 	1.0.0
	 */
	public $options;

	public function __construct( $post_type = '', $plural = '', $single = '', $slug_url = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		// Post type name and labels
		$this->post_type = $post_type;
		$this->slug = strtolower( $post_type );
		$this->plural = $plural;
		$this->single = $single;
		$this->slug_url = $slug_url;
		$this->options = $options;

		// Regsiter post type
		add_action( 'init' , array( $this, 'linkt_register_post_type' ) );

		add_action( 'admin_menu', array( $this, 'linkt_register_meta_box' ) );
		// add_filter( 'linkt_plugin_page_links_' . LINKT_BASE, array( $this, 'linkt_plugin_page_links' ) );

		add_filter( 'manage_edit-linkt_columns', array( $this, 'linkt_setup_admin_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'linkt_custom_columns_info' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'template_redirect', array( $this, 'linkt_redirect_count_click' ) );

		// Add Dashboard Widget
		add_action( 'wp_dashboard_setup', array( $this, 'linkt_dashboard_widget' ) );

		// Display custom update messages for posts edits
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_updated_messages' ), 10, 2 );
	}

	/**
	 * Register new post type
	 * @return void
	 */
	public function linkt_register_post_type() {
		$labels = array(
			'name' => $this->plural,
			'singular_name' => $this->single,
			'name_admin_bar' => $this->single,
			'add_new' => _x( 'Add New', $this->post_type , 'linkt' ),
			'add_new_item' => sprintf( __( 'Add New %s' , 'linkt' ), $this->single ),
			'edit_item' => sprintf( __( 'Edit %s' , 'linkt' ), $this->single ),
			'new_item' => sprintf( __( 'New %s' , 'linkt' ), $this->single ),
			'all_items' => sprintf( __( 'All %s' , 'linkt' ), $this->plural ),
			'view_item' => sprintf( __( 'View %s' , 'linkt' ), $this->single ),
			'search_items' => sprintf( __( 'Search %s' , 'linkt' ), $this->plural ),
			'not_found' =>  sprintf( __( 'No %s Found' , 'linkt' ), $this->plural ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash' , 'linkt' ), $this->plural ),
			'parent_item_colon' => sprintf( __( 'Parent %s' ), $this->single ),
			'menu_name' => $this->plural,
		);

		$args = array(
			'labels' => apply_filters( $this->post_type . '_labels', $labels ),
			// 'description' => $this->description,
			'public' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'query_var' => true,
			'can_export' => true,
			//'rewrite' => array( 'slug' => 'go' ),
			'rewrite' => array(
				'slug' => $this->slug_url,
				'with_front' => false,
			),
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'show_in_rest' => true,
	  		'rest_base' => $this->post_type,
	  		'rest_controller_class' => 'WP_REST_Posts_Controller',
			'supports' => array( 'title' ),
			'menu_position' => 110,
			'menu_icon' => 'dashicons-paperclip',
		);

		$args = array_merge( $args, $this->options );

		register_post_type( $this->post_type, apply_filters( $this->post_type . '_register_args', $args, $this->post_type ) );
		flush_rewrite_rules();
	}

	/**
	 * Create admin columns in the post type list
	 */
	public function linkt_setup_admin_columns( $columns ) {
		return array(
			'cb'               => '<input type="checkbox" />',
			'title'            => __( 'Title', 'linkt' ),
			'linkt_permalink'  => __( 'Track Link', 'linkt' ),
			'linkt_cats'       => __( 'Category', 'linkt' ),
			'linkt_clicks'     => __( 'Clicks', 'linkt' )
		);
	}

	/**
	 * Fill admin columns with info
	 */
	public function linkt_custom_columns_info( $column ) {
		global $post;
		
		switch ( $column ) {
			case 'linkt_url' :
				echo make_clickable( get_post_meta( $post->ID, '_linkt_redirect', true ) );
				break;
			case 'linkt_permalink' :
				$link_redirect = get_post_meta( $post->ID, '_linkt_redirect', true ); ?>
					<div class="linkt-list-block linkt-dw-url tooltip">
						<div class="linkt-dw-switch"></div>
						<input type="text" class="linkt-redirect-url" value="<?php echo esc_attr( get_permalink( $post->ID ) ); ?>" readonly />
						<span class="tooltiptext"><?php esc_html_e( 'Copied to clipboard', 'linkt' ); ?></span>
						<div class="linkt-redirect-to"><?php esc_html_e( 'Links to:', 'linkt' ); ?> <a href="<?php echo esc_url( $link_redirect ); ?>" target="_blank"><?php echo esc_url( $link_redirect ); ?></a></div>
					</div>
				<?php
				break;
			case 'linkt_cats':
				$linkt_cats = wp_get_post_terms( $post->ID, 'linkt-cat' );
				$linkt_cats_count = count( $linkt_cats );

				if ( $linkt_cats_count == 1 ) :
					foreach ( $linkt_cats as $linkt_cat ) :
						echo '<a href="edit.php?post_type=linkt&linkt-cat=' . esc_attr( $linkt_cat->slug ) . '">' . esc_html( $linkt_cat->name ) . '</a>';
					endforeach;
				elseif ( $linkt_cats_count >= 2 ) :
					foreach ( $linkt_cats as $linkt_cat ) :
						echo '<a href="edit.php?post_type=linkt&linkt-cat=' . esc_attr( $linkt_cat->slug ) . '">' . esc_html( $linkt_cat->name ) . '</a><i>, </i>';
					endforeach;
				else :
					echo esc_html( 'Uncategorized' );
				endif;

				break;
			case 'linkt_clicks' :
				echo absint( get_post_meta( $post->ID, '_linkt_count', true ) );
				break;
		}
	}

	/**
	 * Create Linkt post type meta box
	 */
	public function linkt_register_meta_box() {
		add_meta_box(
			'linkt-details',
			__( 'Redirect Link', 'linkt' ),
			array( &$this, 'linkt_render_meta_box' ),
			$this->post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Render the post type meta box
	 */
	public function linkt_render_meta_box( $post ) {
		wp_nonce_field( basename( __FILE__ ), '_linkt_meta_box_nonce' );
		
		$field_id = '_linkt_redirect';
		$field_exists = ( get_post_meta( $post->ID, $field_id, true ) ) ? 'linkt-metabox-on' : '';
		$field_val = ( get_permalink() ) ? get_permalink() : '';
		echo strtr( '<div class="linkt-metabox ' . sanitize_html_class( $field_exists ) . '">
						<h5><label for="{name}">{label}</label></h5>
						<input type="url" id="{name}" name="{name}" value="{value}" placeholder="{placeholder}" class="linkt-refer-link large-text" />
						<div id="linkt-metabox-show">
							<h5><label for="{name}">{refer_label}</label></h5>
							<div class="linkt-copy-box tooltip">
								<span class="tooltiptext">{tooltip_label}</span>
								<span class="linkt-copytext">{copytext_label}</span>
								<input type="text" class="linkt-redirect-url" id="linkt-js-link-update" value="' . esc_url( $field_val ) . '" readonly />
							</div>
						</div>
					</div>', array(
			'{label}' => esc_html__( 'Redirect the link to:', 'linkt' ),
			'{refer_label}' => esc_html__( 'Your new Refer Link:', 'linkt' ),
			'{tooltip_label}' => esc_html__( 'Copied to clipboard:', 'linkt' ),
			'{copytext_label}' => esc_html__( 'Copy to clipboard:', 'linkt' ),
			'{name}'  => $field_id,
			'{placeholder}' => esc_url( __( 'https://enter-your-link.com/', 'linkt' ) ),
			'{value}' => esc_attr( get_post_meta( $post->ID, $field_id, true ) ),
		) );

		$counter = absint( get_post_meta( $post->ID, '_linkt_count', true ) ); ?>
		<p class="description">
			<?php printf( __( 'This Linkt has been clicked on <strong>%d</strong> times in total.', 'linkt' ), $counter ); ?>
			
			<?php
			// Add a reset button for the count
			// if ( $counter >= 1 ) : ?>
				<!-- <button name="_linkt_reset" type="submit" title="<?php esc_attr_e( 'Reset count to 0', 'linkt' ); ?>"></button> -->
			<?php
			//endif; ?>
		</p>

		<?php
		$graph_show = get_option( 'wpt_linkt_setting_graph_display' ) ? get_option( 'wpt_linkt_setting_graph_display' ) : 'linkt_month';

		$graph_newarr_item = linkt_meta_array_settings( $post->ID, $graph_show );

		if (get_option( 'wpt_linkt_setting_enable_charts' )) {
			if ( !empty( $graph_newarr_item ) ) : ?>
				<div id="chart_div" data-postid='<?php esc_attr_e( $post->ID ); ?>' data-graphshow="<?php echo esc_attr( $graph_show ); ?>"></div>
			<?php
			else : ?>
				<div class="linkt-nostats"><?php esc_html_e( 'There are currently no graph statistics to show', 'linkt' ); ?></div>
			<?php
			endif;
		} ?>

		<div class="linkt-woocustomizer">
			<a href="https://woocustomizer.com/go/from-linkt/" target="_blank" class="linkt-woocustomizer-img"><img src="<?php echo esc_url( LINKT_PLUGIN_URL ); ?>/assets/images/woocustomizer.png" alt="WooCustomizer" /></a>
			<?php
			// Creates a button to Add New Linkts
			echo sprintf( __( 'Customize your WooCommerce store with our %1$sWooCustomizer plugin%2$s' , 'linkt' ), '<a href="https://woocustomizer.com/go/from-linkt/" target="_blank">', '</a>' ); ?>
		</div>
	<?php
	}

	/**
	 * Save clicks meta data
	 */
	public function save_post( $post_id ) {
		if ( ! isset( $_POST['_linkt_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['_linkt_meta_box_nonce'], basename( __FILE__ ) ) )
			return;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			return;

		if ( defined( 'DOING_CRON' ) && DOING_CRON )
			return;
		
		if ( isset( $_POST['_linkt_redirect'] ) ) :

			update_post_meta( $post_id, '_linkt_redirect', sanitize_text_field( $_POST['_linkt_redirect'] ) );
			
			if ( isset( $_POST['_linkt_reset'] ) ) :
				delete_post_meta( $post_id, '_linkt_count' );
			endif;
			
		else :
			delete_post_meta( $post_id, '_linkt_redirect' );
		endif;
	}

	/**
	 * Redirect the Linkt url and add to the click count
	 */
	public function linkt_redirect_count_click() {
		if ( ! is_singular( $this->slug ) ) {
			return;
		}

		$current_year = date( 'Y' );
		$current_month = date( 'm' );
		// $current_month = '10';

		$arr_countstats = get_post_meta( get_the_ID(), '_linkt_count_stats_'.$current_year.'_'.$current_month, true ) ? : array(); // Saves as '_linkt_count_stats_2019_07'

		$stats = array_column( $arr_countstats, 'date' );
		$stats_start = reset( $stats );
		$stat_month = date( 'm', strtotime( $stats_start ) );

		$stat_tosave = '_linkt_count_stats_'.$current_year.'_'.$current_month;

		if ( !empty( $arr_countstats ) ) {
			if ( $stat_month == $current_month ) {
				$stat_tosave = '_linkt_count_stats_'.$current_year.'_'.$stat_month;
				// echo '--- Stat Month == Current Month';
			} else {
				$stat_tosave = '_linkt_count_stats_'.$current_year.'_'.$current_month;
				// echo '--- Stat Month NOT == Current Month - inner else';
			}
		} else {
			$stat_tosave = '_linkt_count_stats_'.$current_year.'_'.$current_month;
			// echo '--- Create new meta field = Current Month';
		}

		$arr_countstats[] = array(
			'date' => esc_attr( date('Y-m-d') ),
			'time' => esc_attr( date('H:i:s') ),
			// 'timezone' => date_default_timezone_get()
		);

		// Don't count the clicks if Administrator
		if ( 'on' == get_option( 'wpt_linkt_setting_track_admin' ) ) {
			$counter = absint( get_post_meta( get_the_ID(), '_linkt_count', true ) );
			update_post_meta( get_the_ID(), '_linkt_count', ++$counter );
			update_post_meta( get_the_ID(), $stat_tosave, $arr_countstats );
		} else {
			if ( !current_user_can( 'manage_options' ) ) {
				$counter = absint( get_post_meta( get_the_ID(), '_linkt_count', true ) );
				update_post_meta( get_the_ID(), '_linkt_count', ++$counter );
				update_post_meta( get_the_ID(), $stat_tosave, $arr_countstats );
			}
		}

		// $array_new_instance = get_post_meta( get_the_ID(), '_linkt_count_stats_'.$current_year.'_'.$current_month, true );
		// echo '<pre><br />';
		// print_r( $array_new_instance );
		// echo '</pre>';

		$post_metas = get_post_custom( get_the_ID() );
		$post_metarr = array();
		
		foreach ( $post_metas as $post_meta=>$value ) {
			if ( strpos( $post_meta, '_linkt_count_stats' ) !== false ) {
				$post_metarr[] = $post_meta;
			}
		}
		if ( count( $post_metarr ) >= 5 ) {
			delete_post_meta( get_the_ID(), $post_metarr[0] );
		}
		// echo '<pre><br />';
		// print_r( $post_metarr );
		// echo '</pre>';

		$redirect_url = esc_url_raw( get_post_meta( get_the_ID(), '_linkt_redirect', true ) );
		
		if ( ! empty( $redirect_url ) )
			wp_redirect( $redirect_url, 301 );
		else
			wp_redirect( home_url(), 302 );
		die();
	}

	/**
	 * Create a Dashboard Widget for Linkt
	 */
	public function linkt_dashboard_widget() {
		wp_add_dashboard_widget(
			'linkt_dashboard_widget',
			__( 'Linkt - Click Statistics', 'linkt' ), array( $this, 'linkt_render_dashboard_widget' )
		);	
	}

	/**
	 * Render the Dashboard Widget info
	 */
	public function linkt_render_dashboard_widget() {
		$linkt_orderby = get_option( 'wpt_linkt_setting_linkt_orderby' ) ? strval( get_option( 'wpt_linkt_setting_linkt_orderby' ) ) : strval( 'meta_value_num' );
		$linkt_order = get_option( 'wpt_linkt_setting_linkt_order' ) ? strval( get_option( 'wpt_linkt_setting_linkt_order' ) ) : strval( 'DESC' );
		
		$posts = get_posts(
			array(
				'post_type' => $this->post_type,
				'post_status' => 'publish',
				'fields' => 'ids',
				'meta_key' => '_linkt_count',
				'orderby' => $linkt_orderby,
				'order' => $linkt_order,
				'posts_per_page' => 50,
			)
		);
		
		if ( empty( $posts ) ) {
			// If no Linkts then create button to create your first Linkt
			echo '<div class="no-linkts">' . sprintf( __( 'You haven\'t created any linkt\'s yet... %1$sCreate a %2$s now%3$s' , 'linkt' ), '<a href="' . esc_url( admin_url( 'post-new.php?post_type=linkt' ) ) . '">', esc_html( $this->post_type ), '</a>' ) . '</div>';
			return;
		} ?>
		<div class="linkt-dw-wrap">
			<?php if ( 'on' == get_option( 'wpt_linkt_setting_dashwidget_layout' ) ) :
				// Display Single Linkts list IF setting for this is checked ?>
				<div class="linkt-dw-block-wrap">
					<?php
					// loop through each post
					foreach ( $posts as $post_id ) :
						$link_title = get_the_title( $post_id );
						$link_redirect = get_post_meta( $post_id, '_linkt_redirect', true );
						$link_count = absint( get_post_meta( $post_id, '_linkt_count', true ) ); ?>
						<div class="linkt-dw-block linkt-dw-post-block">
							<div class="linkt-dw-block-in">
								<div class="linkt-dw-block-name">
									<?php echo esc_html( $link_title ); ?>
								</div>
								<div class="linkt-dw-block-stat">
									<?php echo esc_attr( $link_count ); ?>
								</div>
								<div class="linkt-dw-block-edit">
									<a target="_blank" class="linkt-dw-link-out" href="<?php echo esc_url( $link_redirect ); ?>" title="<?php esc_attr_e( 'Open referral link in a new tab', 'linkt' ); ?>"></a>

									<a class="linkt-dw-edit" href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" title="<?php esc_attr_e( 'Edit referral link', 'linkt' ); ?>"></a>
								</div>
							</div>
							<div class="linkt-dw-block-dropdown linkt-dw-url tooltip">
								<div class="linkt-dw-switch"></div>
								<input type="text" class="linkt-redirect-url" value="<?php echo esc_attr( get_permalink( $post_id ) ); ?>" readonly />
								<span class="tooltiptext"><?php _e( 'Copied to clipboard', 'linkt' ); ?></span>
								<div class="linkt-redirect-to"><?php _e( 'Links to:', 'linkt' ); ?> <a href="<?php echo esc_url( $link_redirect ); ?>" target="_blank"><?php echo esc_url( $link_redirect ); ?></a></div>
							</div>
						</div>
					<?php
					endforeach; ?>
				</div>
			<?php else :
				// Display Linkts grouped in their Categories | Further down - also diplay single Linkts not in a Category ?>
				<div class="linkt-dw-block-wrap">
					<?php
					$linkt_cats = get_terms( array( 'taxonomy' => 'linkt-cat', 'parent' => 0 ) );
					$linkt_cats_count = count( $linkt_cats );
					$linkt_excluded_cats = array();

					if ( $linkt_cats_count > 0 ) :

						// Group all the categorized posts together
						foreach ( $linkt_cats as $linkt_cat ) : ?>

							<div class="linkt-dw-block linkt-dw-cat-block">
								<div class="linkt-dw-block-in">
									<div class="linkt-dw-block-name">
										<?php echo esc_html( $linkt_cat->name ); ?>
									</div>
									<div class="linkt-dw-block-cat-total"></div>
								</div>
							<div class="linkt-dw-block-dropdown-cat linkt-dw-url">
								
								<?php
								$linkt_posts = get_posts(
									array(
										'post_type' => 'linkt',
										'post_parent' => 0,
										'posts_per_page' => -1,
										'meta_key' => '_linkt_count',
										'orderby' => $linkt_orderby,
										'order' => $linkt_order,
										'tax_query' => array(
											array(
												'taxonomy' => 'linkt-cat',
												'terms' => $linkt_cat->term_id,
												'include_children' => false
											)
										)
									)
								);
								if ( $linkt_posts ) : ?>
									
									<?php
									foreach ( $linkt_posts as $linkt_post ) :
										$link_title = get_the_title( $linkt_post->ID );
										$link_redirect = get_post_meta( $linkt_post->ID, '_linkt_redirect', true );
										$link_count = absint( get_post_meta( $linkt_post->ID, '_linkt_count', true ) ); ?>

										<div class="linkt-dw-block linkt-dw-post-block">
											<div class="linkt-dw-block-in">
												<div class="linkt-dw-block-name">
													<?php echo esc_html( $link_title ); ?>
												</div>
												<div class="linkt-dw-block-stat">
													<?php echo esc_attr( $link_count ); ?>
												</div>
												<div class="linkt-dw-block-edit">
													<a target="_blank" class="linkt-dw-link-out" href="<?php echo esc_url( $link_redirect ); ?>" title="<?php esc_attr_e( 'Open referral link in a new tab', 'linkt' ); ?>"></a>
													<a class="linkt-dw-edit" href="<?php echo esc_url( get_edit_post_link( $linkt_post->ID ) ); ?>" title="<?php esc_attr_e( 'Edit referral link', 'linkt' ); ?>"></a>
												</div>
											</div>
											<div class="linkt-dw-block-dropdown linkt-dw-url tooltip">
												<div class="linkt-dw-switch"></div>
												<input type="text" class="linkt-redirect-url" value="<?php echo esc_attr( get_permalink( $linkt_post->ID ) ); ?>" readonly />
												<span class="tooltiptext"><?php _e( 'Copied to clipboard', 'linkt' ); ?></span>
												<div class="linkt-redirect-to"><?php _e( 'Links to:', 'linkt' ); ?> <a href="<?php echo esc_url( $link_redirect ); ?>" target="_blank"><?php echo esc_url( $link_redirect ); ?></a></div>
											</div>
										</div>

									<?php
									endforeach; wp_reset_postdata(); ?>

								<?php
								endif; ?>
								
							</div>
						</div>
						<?php
						array_push( $linkt_excluded_cats, $linkt_cat->term_id );
						endforeach;

					endif;
					// print_r( $linkt_excluded_cats );
					// Display Linkts that are not grouped in a category ?>
					
					<div class="linkt-dw-blocks">
						<?php
						// Get all the single uncategorized posts
						$linkt_uncat_args = array(
							'post_type' => 'linkt',
							'post_status' => 'publish',
							'meta_key' => '_linkt_count',
							'orderby' => $linkt_orderby,
							'order' => $linkt_order,
							'tax_query' => array(
								array(
									'taxonomy' => 'linkt-cat',
									'terms'    => $linkt_excluded_cats,
									'operator' => 'NOT IN',
								),
							),
							'posts_per_page'  => 100,
						);
						$linkt_uncat_posts = new WP_Query( $linkt_uncat_args );
							
						while ( $linkt_uncat_posts->have_posts() ) : $linkt_uncat_posts->the_post();
							$link_title = get_the_title( get_the_ID() );
							$link_redirect = get_post_meta( get_the_ID(), '_linkt_redirect', true );
							$link_count = absint( get_post_meta( get_the_ID(), '_linkt_count', true ) ); ?>
							<div class="linkt-dw-block linkt-dw-post-block">
								<div class="linkt-dw-block-in">
									<div class="linkt-dw-block-name">
										<?php echo esc_html( $link_title ); ?>
									</div>
									<div class="linkt-dw-block-stat">
										<?php echo esc_attr( $link_count ); ?>
									</div>
									<div class="linkt-dw-block-edit">
										<a target="_blank" class="linkt-dw-link-out" href="<?php echo esc_url( $link_redirect ); ?>" title="<?php esc_attr_e( 'Open referral link in a new tab', 'linkt' ); ?>"></a>

										<a class="linkt-dw-edit" href="<?php echo esc_url( get_edit_post_link( get_the_ID() ) ); ?>" title="<?php esc_attr_e( 'Edit referral link', 'linkt' ); ?>"></a>
									</div>
								</div>
								<div class="linkt-dw-block-dropdown linkt-dw-url tooltip">
									<div class="linkt-dw-switch"></div>
									<input type="text" class="linkt-redirect-url" value="<?php echo esc_attr( get_permalink( get_the_ID() ) ); ?>" readonly />
									<span class="tooltiptext"><?php _e( 'Copied to clipboard', 'linkt' ); ?></span>
									<div class="linkt-redirect-to"><?php _e( 'Links to:', 'linkt' ); ?> <a href="<?php echo esc_url( $link_redirect ); ?>" target="_blank"><?php echo esc_url( $link_redirect ); ?></a></div>
								</div>
							</div>
						<?php
						endwhile; ?>
					</div>
				</div>
			<?php endif; ?>
			<div class="add-linkts">
				<?php
				// Creates a button to Add New Linkts
				echo sprintf( __( '%1$sLinkt Settings%2$s' , 'linkt' ), '<a href="' . admin_url( 'edit.php?post_type=linkt&page=linkt_settings' ) . '" class="linkt-txt">', '</a>' ); ?>
				
				<?php
				// Creates a button to Add New Linkts
				echo sprintf( __( '%1$sAdd a new Linkt%2$s' , 'linkt' ), '<a href="' . esc_url( admin_url( 'post-new.php?post_type=linkt' ) ) . '" class="linkt-button">', '</a>' ); ?>
			</div>
		</div><?php
	}

	/**
	 * Set up all admin messages for Linkt Post Type
	 * @param  array $messages Default message
	 * @return array Modified messages
	 */
	public function updated_messages( $messages = array() ) {
		global $post, $post_ID;
  
		$messages[ $this->post_type ] = array(
		  0 => '',
		  1 => sprintf( __( '%1$s updated. %2$sView %3$s%4$s.' , 'linkt' ), $this->single, '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->single, '</a>' ),
		  2 => __( 'Custom field updated.' , 'linkt' ),
		  3 => __( 'Custom field deleted.' , 'linkt' ),
		  4 => sprintf( __( '%1$s updated.' , 'linkt' ), $this->single ),
		  5 => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s.' , 'linkt' ), $this->single, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		  6 => sprintf( __( '%1$s published. %2$sView %3$s%4s.' , 'linkt' ), $this->single, '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->single, '</a>' ),
		  7 => sprintf( __( '%1$s saved.' , 'linkt' ), $this->single ),
		  8 => sprintf( __( '%1$s submitted. %2$sPreview post%3$s%4$s.' , 'linkt' ), $this->single, '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', $this->single, '</a>' ),
		  9 => sprintf( __( '%1$s scheduled for: %2$s. %3$sPreview %4$s%5$s.' , 'linkt' ), $this->single, '<strong>' . date_i18n( __( 'M j, Y @ G:i' , 'linkt' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', $this->single, '</a>' ),
		  10 => sprintf( __( '%1$s draft updated. %2$sPreview %3$s%4$s.' , 'linkt' ), $this->single, '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', $this->single, '</a>' ),
		);
  
		return $messages;
	  }
  
	  /**
	   * Set up bulk admin messages for Linkt Post Type
	   * @param  array  $bulk_messages Default bulk messages
	   * @param  array  $bulk_counts Counts of selected posts in each status
	   * @return array  Modified messages
	   */
	  public function bulk_updated_messages( $bulk_messages = array(), $bulk_counts = array() ) {
  
		  $bulk_messages[ $this->post_type ] = array(
			  'updated'   => sprintf( _n( '%1$s %2$s updated.', '%1$s %3$s updated.', $bulk_counts['updated'], 'linkt' ), $bulk_counts['updated'], $this->single, $this->plural ),
			  'locked'    => sprintf( _n( '%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $bulk_counts['locked'], 'linkt' ), $bulk_counts['locked'], $this->single, $this->plural ),
			  'deleted'   => sprintf( _n( '%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $bulk_counts['deleted'], 'linkt' ), $bulk_counts['deleted'], $this->single, $this->plural ),
			  'trashed'   => sprintf( _n( '%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $bulk_counts['trashed'], 'linkt' ), $bulk_counts['trashed'], $this->single, $this->plural ),
			  'untrashed' => sprintf( _n( '%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $bulk_counts['untrashed'], 'linkt' ), $bulk_counts['untrashed'], $this->single, $this->plural ),
		  );
  
		  return $bulk_messages;
	  }

}
