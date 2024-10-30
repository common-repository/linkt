<?php
/*
 * Plugin Name: Linkt
 * Version: 1.0.8
 * Plugin URI: https://kairaweb.com/wordpress-plugins/linkt/
 * Description: Simply create links that you want to track and see the statistics in your Dashboard.
 * Author: Kaira
 * Author URI: https://kairaweb.com/
 * Requires at least: 4.7
 * Tested up to: 5.9
 *
 * Text Domain: linkt
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Kaira
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LINKT_PLUGIN_VERSION', '1.0.8' );

define( 'LINKT__FILE__', __FILE__ );
define( 'LINKT_BASE', plugin_basename( LINKT__FILE__ ) );
define( 'LINKT_PLUGIN_URL', plugins_url( '', LINKT__FILE__ ) );

// Load plugin class files
require_once( 'includes/class-linkt.php' );
require_once( 'includes/class-linkt-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-linkt-admin.php' );
require_once( 'includes/lib/class-linkt-post-type.php' );
require_once( 'includes/lib/class-linkt-taxonomy.php' );

/**
 * Returns the main instance of Linkt to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Linkt
 */
function Linkt() {
	$instance = Linkt::instance( __FILE__, LINKT_PLUGIN_VERSION );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Linkt_Settings::instance( $instance );
	}

	add_action( 'wp_ajax_linkt_get_post_clicks_meta_byid', 'linkt_get_post_clicks_meta_byid' );

	return $instance;
}

Linkt();

// Create the Linkt Post Type and a Taxonomy for the Linkt Post Type
Linkt()->register_post_type( 'Linkt', __( 'Linkt\'s', 'linkt' ), __( 'Linkt\'s', 'linkt' ), esc_html( get_option( 'wpt_linkt_setting_url_ext', 'go' ) ) );
Linkt()->register_taxonomy( 'linkt-cat', __( 'Categories', 'linkt' ), __( 'Categories', 'linkt' ), 'linkt' );

/**
 * Fetches Post Meta for the clicks array.
 */
function linkt_get_post_clicks_meta_byid() {
	$post_id = esc_attr( $_POST['post_id'] );
	$graph_show = sanitize_text_field( $_POST['graph_show'] );

	$graph_array = linkt_meta_array_settings( $post_id, $graph_show );

	// Returns 1 column from the array
	$stats = array_column( $graph_array, 'date' );

	// Array Stats by Month
	// $stats_months = array();
	// foreach ($stats as $stat) {
	// 	$stats_months[] = date( "F", strtotime( $stat ) );
	// }
	// $stat_months = array_count_values( $stats_months );

	// Array Stats by Days
	// $stats_days = array();
	// foreach ($stats as $stat) {
	// 	$stats_days[] = date( "d", strtotime( $stat ) );
	// }
	// $stat_days = array_count_values( $stats_days );
	$stat_days = array_count_values( $stats );
	
	$all_days_stats = array();

	reset( $stat_days ); // Sets array position to start
	$key = key( $stat_days ); // Grabs the key
	$begin = new DateTime( $key ); // Sets the begin date for period to $begin
	
	end( $stat_days ); // Sets array to end key
	$key = key( $stat_days ); // Gets end key
	$end = new DateTime( $key ); // Sets end variable as last date
	$end = $end->modify( '+1 day' ); // Includes the last day by adding + 1 day

	$interval = new DateInterval( 'P1D' ); // Increases by one day (interval)
	$daterange = new DatePeriod( $begin, $interval ,$end ); // Gets the date range

	foreach( $daterange as $date ) {
		$date = $date->format( "Y-m-d" );
		if ( isset( $stat_days[$date] ) ) { // If date exists then set date to existing date / otherwise set to 0
			$all_days_stats[$date] = $stat_days[$date];
		} else {
			$all_days_stats[$date] = 0;
		}
	}

	if ( 'linkt_7days' == $graph_show )
		$all_days_stats = array_slice( $all_days_stats, -7, 7 );

	// echo sizeof( $count_vals );
	echo json_encode( $all_days_stats );

	die();
}

/**
 * Fetches Post Meta for the clicks array.
 */
function linkt_get_date_month( $myList ) {

	$a = array_keys( $myList );

	$flipped = array_flip( date("F", strtotime( $a )) );

	$b = array_values( $myList );
	$c = array_combine($flipped, $b);
	
	return $c;
}

function linkt_meta_array_settings( $post_id, $graph_show ) {

	$post_metas = get_post_custom( $post_id );
	$post_metarr = array();
	
	foreach ( $post_metas as $post_meta=>$value ) {
		if ( strpos( $post_meta, '_linkt_count_stats' ) !== false ) {
			$post_metarr[] = $post_meta;
		}
	}

	// Return if there have been no settings saved
	if ( empty( $post_metarr ) )
		return;

	switch( $graph_show ){
		case "linkt_7days":
			if ( count( $post_metarr ) >= 2 ) :
				$post_metaslice = array_slice( $post_metarr, -2, 2 );

				$graph_arr1 = get_post_meta( $post_id, $post_metaslice[0], true );
				$graph_arr2 = get_post_meta( $post_id, $post_metaslice[1], true );

				$graph_newarr_item = array_merge( $graph_arr1, $graph_arr2 );
			else :
				$graph_arr_item = end( $post_metarr );

				$graph_newarr_item = get_post_meta( $post_id, $graph_arr_item, true );
			endif;

			break;
		case "linkt_2month":
			
			if ( count( $post_metarr ) >= 2 ) :
				$post_metaslice = array_slice( $post_metarr, -2, 2 );

				$graph_arr1 = get_post_meta( $post_id, $post_metaslice[0], true );
				$graph_arr2 = get_post_meta( $post_id, $post_metaslice[1], true );

				$graph_newarr_item = array_merge( $graph_arr1, $graph_arr2 );
			else :
				$graph_arr_item = end( $post_metarr );

				$graph_newarr_item = get_post_meta( $post_id, $graph_arr_item, true );
			endif;

			break;
		default: // linkt_month
			$graph_arr_item = end( $post_metarr );

			$graph_newarr_item = get_post_meta( $post_id, $graph_arr_item, true );

			break;
	}
	
	return $graph_newarr_item;

}
