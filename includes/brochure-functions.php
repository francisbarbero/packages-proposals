<?php
/**
 * Brochure Management Functions
 * Handles brochures with custom table (sf_brochures)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create database tables for brochures system.
 * Call this during plugin activation.
 */
function sfpp_create_brochures_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// Brochures table
	$brochures_sql = "CREATE TABLE IF NOT EXISTS sf_brochures (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		title varchar(255) NOT NULL DEFAULT '',
		status varchar(50) NOT NULL DEFAULT 'draft',
		category varchar(255) NOT NULL DEFAULT '',
		schema_json longtext NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY status (status),
		KEY category (category)
	) $charset_collate;";

	// Brochure items table
	$brochure_items_sql = "CREATE TABLE IF NOT EXISTS sf_brochure_items (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		brochure_id bigint(20) NOT NULL,
		package_id bigint(20) NOT NULL,
		sort_order int(11) NOT NULL DEFAULT 0,
		overrides_json longtext,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY brochure_id (brochure_id),
		KEY package_id (package_id),
		KEY sort_order (sort_order)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $brochures_sql );
	dbDelta( $brochure_items_sql );

	update_option( 'sfpp_brochures_db_version', '1.0' );
}

/**
 * Get a single brochure by ID.
 */
function sfpp_get_brochure( $id ) {
	global $wpdb;

	$id = (int) $id;
	if ( $id <= 0 ) {
		return null;
	}

	$sql = $wpdb->prepare(
		"SELECT * FROM sf_brochures WHERE id = %d",
		$id
	);

	$brochure = $wpdb->get_row( $sql );

	if ( $brochure ) {
		// Decode schema JSON
		$brochure->schema_data = $brochure->schema_json ? json_decode( $brochure->schema_json, true ) ?? [] : [];

		do_action( 'sfpp_brochure_loaded', $brochure );
	}

	return $brochure;
}

/**
 * Get list of brochures with optional filters.
 */
function sfpp_get_brochures( $args = [] ) {
	global $wpdb;

	$defaults = [
		'status'   => null,
		'category' => null,
	];

	$args = wp_parse_args( $args, $defaults );

	$where_conditions = [];
	$prepare_values = [];

	if ( $args['status'] ) {
		$where_conditions[] = "status = %s";
		$prepare_values[] = $args['status'];
	}

	if ( $args['category'] ) {
		$where_conditions[] = "category = %s";
		$prepare_values[] = $args['category'];
	}

	$where_clause = '';
	if ( ! empty( $where_conditions ) ) {
		$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
	}

	$sql = "SELECT * FROM sf_brochures {$where_clause} ORDER BY title ASC";

	if ( ! empty( $prepare_values ) ) {
		$sql = $wpdb->prepare( $sql, ...$prepare_values );
	}

	$brochures = $wpdb->get_results( $sql );

	// Decode schema_data for each brochure
	foreach ( $brochures as $brochure ) {
		$brochure->schema_data = $brochure->schema_json ? json_decode( $brochure->schema_json, true ) ?? [] : [];
	}

	return $brochures;
}

/**
 * Create a new brochure.
 */
function sfpp_create_brochure( $data = [] ) {
	global $wpdb;

	$defaults = [
		'title'       => 'New Brochure',
		'status'      => 'draft',
		'category'    => '',
		'schema_json' => null,
	];

	$brochure_data = wp_parse_args( $data, $defaults );

	// Add timestamps
	$brochure_data['created_at'] = current_time( 'mysql' );
	$brochure_data['updated_at'] = current_time( 'mysql' );

	// Sanitize basic fields
	$brochure_data['title'] = sanitize_text_field( $brochure_data['title'] );
	$brochure_data['status'] = sanitize_text_field( $brochure_data['status'] );
	$brochure_data['category'] = sanitize_text_field( $brochure_data['category'] );

	// schema_json should already be a JSON string, don't encode it
	// If it's an array, encode it
	if ( isset( $brochure_data['schema_json'] ) && is_array( $brochure_data['schema_json'] ) ) {
		$brochure_data['schema_json'] = wp_json_encode( $brochure_data['schema_json'] );
	}

	$result = $wpdb->insert( 'sf_brochures', $brochure_data );

	if ( $result === false ) {
		return false;
	}

	$brochure_id = $wpdb->insert_id;

	do_action( 'sfpp_brochure_created', $brochure_id, $brochure_data );

	return $brochure_id;
}

/**
 * Update an existing brochure.
 */
function sfpp_update_brochure( $id, $data ) {
	global $wpdb;

	$id = (int) $id;
	if ( $id <= 0 ) {
		return false;
	}

	// Add updated timestamp
	$data['updated_at'] = current_time( 'mysql' );

	// Sanitize basic fields
	if ( isset( $data['title'] ) ) {
		$data['title'] = sanitize_text_field( $data['title'] );
	}
	if ( isset( $data['status'] ) ) {
		$data['status'] = sanitize_text_field( $data['status'] );
	}
	if ( isset( $data['category'] ) ) {
		$data['category'] = sanitize_text_field( $data['category'] );
	}

	// schema_json should already be a JSON string, don't encode it
	// If it's an array, encode it
	if ( isset( $data['schema_json'] ) && is_array( $data['schema_json'] ) ) {
		$data['schema_json'] = wp_json_encode( $data['schema_json'] );
	}

	$result = $wpdb->update(
		'sf_brochures',
		$data,
		[ 'id' => $id ]
	);

	if ( $result !== false ) {
		do_action( 'sfpp_brochure_updated', $id, $data );
	}

	return $result !== false;
}

/**
 * Delete a brochure.
 */
function sfpp_delete_brochure( $id ) {
	global $wpdb;

	$id = (int) $id;
	if ( $id <= 0 ) {
		return false;
	}

	$result = $wpdb->delete(
		'sf_brochures',
		[ 'id' => $id ]
	);

	if ( $result !== false && $result > 0 ) {
		do_action( 'sfpp_brochure_deleted', $id );
	}

	return $result !== false && $result > 0;
}

/**
 * Create brochure from form request data.
 */
function sfpp_create_brochure_from_request( $request_data ) {
	// Get schema for brochures
	$schema = sfpp_get_brochures_schema();

	// Process schema data from form
	$schema_data = [];
	if ( isset( $request_data['schema'] ) && is_array( $request_data['schema'] ) ) {
		$schema_data = sfpp_process_schema_data( $schema, wp_unslash( $request_data['schema'] ) );
	}

	// Prepare brochure data
	$brochure_data = [
		'title'       => sanitize_text_field( wp_unslash( $request_data['title'] ?? 'New Brochure' ) ),
		'status'      => sanitize_text_field( wp_unslash( $request_data['status'] ?? 'draft' ) ),
		'category'    => sanitize_text_field( wp_unslash( $request_data['category'] ?? '' ) ),
		'schema_json' => wp_json_encode( $schema_data ),
	];

	return sfpp_create_brochure( $brochure_data );
}

/**
 * Update brochure from form request data.
 */
function sfpp_update_brochure_from_request( $id, $request_data ) {
	$brochure = sfpp_get_brochure( $id );
	if ( ! $brochure ) {
		return false;
	}

	// Get schema for brochures
	$schema = sfpp_get_brochures_schema();

	// Process schema data from form
	$schema_data = [];
	if ( isset( $request_data['schema'] ) && is_array( $request_data['schema'] ) ) {
		$schema_data = sfpp_process_schema_data( $schema, wp_unslash( $request_data['schema'] ) );
	}

	// Prepare update data
	$update_data = [
		'title'       => sanitize_text_field( wp_unslash( $request_data['title'] ?? '' ) ),
		'status'      => sanitize_text_field( wp_unslash( $request_data['status'] ?? 'draft' ) ),
		'category'    => sanitize_text_field( wp_unslash( $request_data['category'] ?? '' ) ),
		'schema_json' => wp_json_encode( $schema_data ),
	];

	return sfpp_update_brochure( $id, $update_data );
}
