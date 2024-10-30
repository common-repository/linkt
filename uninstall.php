<?php
/**
 * 
 * This file runs when the plugin in uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Ideally you will add all your clean-up scripts here
 * that will clean-up unused meta, options, etc. in the database.
 *
 */

// If plugin is not being uninstalled, exit (do nothing)
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete all data if setting to delete data is checked

if ( 'on' == get_option( 'wpt_linkt_setting_delete_all_data' ) ) {

	// Delete all Linkt db options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_linkt_count_stats_%';" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'linkt_license_%';" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wpt_linkt_setting_%';" );

	// Delete Linkt Posts + Data.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'linkt' );" );
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

	foreach ( array( 'linkt-cat' ) as $taxonomy ) {
		$wpdb->delete(
			$wpdb->term_taxonomy,
			array(
				'taxonomy' => $taxonomy,
			)
		);
	}

	// Clear any cached data that has been removed.
	wp_cache_flush();
}
