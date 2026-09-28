<?php
/**
 * Uninstall routine for Campussian Core.
 *
 * Runs when the plugin is deleted from the Plugins screen. The plugin is not
 * loaded at this point, so everything here is self-contained.
 *
 * What it does:
 *  1. Moves every user off the Campussian roles onto a standard WordPress
 *     fallback role so nobody is left without a role (= locked out).
 *  2. Restores the role a user had before the plugin ever touched them.
 *  3. Removes the Campussian roles and the stored version option.
 *
 * @package Campussian
 * @author  WhyCodeBD
 * @since   1.0.0
 */

// Exit if uninstall was not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Make sure a fallback role exists, cloning caps from a school role if needed.
 *
 * @param string $slug        Fallback role slug.
 * @param string $display     Display name.
 * @param string $donor_slug  School role to copy capabilities from.
 * @return void
 */
function cmpsian_core_uninstall_ensure_role( $slug, $display, $donor_slug ) {
	if ( get_role( $slug ) ) {
		return;
	}

	$donor       = get_role( $donor_slug );
	$capabilities = $donor ? $donor->capabilities : array( 'read' => true );

	add_role( $slug, $display, $capabilities );
}

$cmpsian_core_roles = array(
	'system_admin'        => 'administrator',
	'school_admin'        => 'editor',
	'principal'           => 'editor',
	'teacher'             => 'subscriber',
	'guardian'            => 'subscriber',
	'student'             => 'subscriber',
	'campussian_principal' => 'editor',
);

// Fallbacks must exist before we reassign anyone.
cmpsian_core_uninstall_ensure_role( 'administrator', 'Administrator', 'system_admin' );
cmpsian_core_uninstall_ensure_role( 'editor', 'Editor', 'school_admin' );
cmpsian_core_uninstall_ensure_role( 'subscriber', 'Subscriber', 'guardian' );

global $wpdb;
$cmpsian_core_cap_key = $wpdb->prefix . 'capabilities';

$cmpsian_core_users = get_users(
	array(
		'number'     => 2000,
		'fields'     => array( 'ID' ),
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			array(
				'key'     => '_cmpsian_previous_role',
				'compare' => 'EXISTS',
			),
		),
	)
);

foreach ( $cmpsian_core_users as $cmpsian_core_user ) {
	$cmpsian_core_previous = get_user_meta( $cmpsian_core_user->ID, '_cmpsian_previous_role', true );
	if ( $cmpsian_core_previous && get_role( $cmpsian_core_previous ) ) {
		update_user_meta( $cmpsian_core_user->ID, $cmpsian_core_cap_key, array( $cmpsian_core_previous => true ) );
	}
	delete_user_meta( $cmpsian_core_user->ID, '_cmpsian_previous_role' );
}

// Reassign anyone still holding a school role.
foreach ( array_keys( $cmpsian_core_roles ) as $cmpsian_core_slug ) {
	$cmpsian_core_holders = get_users(
		array(
			'role'   => $cmpsian_core_slug,
			'number' => 2000,
			'fields' => array( 'ID' ),
		)
	);

	$cmpsian_core_fallback = $cmpsian_core_roles[ $cmpsian_core_slug ];

	foreach ( $cmpsian_core_holders as $cmpsian_core_holder ) {
		update_user_meta( $cmpsian_core_holder->ID, $cmpsian_core_cap_key, array( $cmpsian_core_fallback => true ) );
	}
}

// Finally drop the roles and the stored version.
foreach ( array_keys( $cmpsian_core_roles ) as $cmpsian_core_slug ) {
	remove_role( $cmpsian_core_slug );
}

delete_option( 'cmpsian_core_version' );