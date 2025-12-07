<?php
/**
 * Extra Management Functions
 * Handles extras (additional services/features) with custom table (sf_extras)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create database table for extras system.
 * Call this during plugin activation.
 */
function sfpp_create_extras_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS sf_extras (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL DEFAULT '',
        description text,
        status varchar(20) NOT NULL DEFAULT 'active',
        base_price decimal(10,2) NOT NULL DEFAULT 0.00,
        schema_json longtext,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY status (status)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'sfpp_extras_db_version', '1.0' );
}

/**
 * Get a single extra by ID.
 */
function sfpp_get_extra( $id ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return null;
    }

    $sql = $wpdb->prepare(
        "SELECT * FROM sf_extras WHERE id = %d",
        $id
    );

    $extra = $wpdb->get_row( $sql );

    if ( $extra ) {
        // Decode schema JSON
        $extra->schema_data = $extra->schema_json ? json_decode( $extra->schema_json, true ) ?? [] : [];

        do_action( 'sfpp_extra_loaded', $extra );
    }

    return $extra;
}

/**
 * Get list of extras with optional filters.
 */
function sfpp_get_extras( $args = [] ) {
    global $wpdb;

    $defaults = [
        'status' => null,
    ];

    $args = wp_parse_args( $args, $defaults );

    $where_conditions = [];
    $prepare_values = [];

    if ( $args['status'] ) {
        $where_conditions[] = "status = %s";
        $prepare_values[] = $args['status'];
    }

    $where_clause = '';
    if ( ! empty( $where_conditions ) ) {
        $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
    }

    $sql = "SELECT * FROM sf_extras {$where_clause} ORDER BY name ASC";

    if ( ! empty( $prepare_values ) ) {
        $sql = $wpdb->prepare( $sql, ...$prepare_values );
    }

    $extras = $wpdb->get_results( $sql );

    // Decode schema JSON for each extra
    foreach ( $extras as $extra ) {
        $extra->schema_data = $extra->schema_json ? json_decode( $extra->schema_json, true ) ?? [] : [];
    }

    return $extras;
}

/**
 * Create a new extra.
 */
function sfpp_create_extra( $data = [] ) {
    global $wpdb;

    $defaults = [
        'name' => 'New Extra',
        'description' => '',
        'status' => 'active',
        'base_price' => 0.00,
        'schema_json' => '{}',
    ];

    $extra_data = wp_parse_args( $data, $defaults );

    // Add timestamps
    $extra_data['created_at'] = current_time( 'mysql' );
    $extra_data['updated_at'] = current_time( 'mysql' );

    // Sanitize data
    $extra_data = map_deep( $extra_data, 'sanitize_text_field' );

    // Special handling for schema_json and numeric fields
    $extra_data['base_price'] = floatval( $data['base_price'] ?? 0 );
    if ( isset( $data['schema_json'] ) && is_array( $data['schema_json'] ) ) {
        $extra_data['schema_json'] = wp_json_encode( $data['schema_json'] );
    }

    $result = $wpdb->insert( 'sf_extras', $extra_data );

    if ( $result === false ) {
        return false;
    }

    $extra_id = $wpdb->insert_id;

    do_action( 'sfpp_extra_created', $extra_id, $extra_data );

    return $extra_id;
}

/**
 * Update an existing extra.
 */
function sfpp_update_extra( $id, $data ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return false;
    }

    // Add updated timestamp
    $data['updated_at'] = current_time( 'mysql' );

    // Sanitize data
    $clean_data = map_deep( $data, 'sanitize_text_field' );

    // Special handling for numeric and JSON fields
    if ( isset( $data['base_price'] ) ) {
        $clean_data['base_price'] = floatval( $data['base_price'] );
    }
    if ( isset( $data['schema_json'] ) && is_array( $data['schema_json'] ) ) {
        $clean_data['schema_json'] = wp_json_encode( $data['schema_json'] );
    }

    $result = $wpdb->update(
        'sf_extras',
        $clean_data,
        [ 'id' => $id ]
    );

    if ( $result !== false ) {
        do_action( 'sfpp_extra_updated', $id, $clean_data );
    }

    return $result !== false;
}

/**
 * Delete an extra.
 */
function sfpp_delete_extra( $id ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return false;
    }

    $result = $wpdb->delete(
        'sf_extras',
        [ 'id' => $id ]
    );

    if ( $result !== false && $result > 0 ) {
        do_action( 'sfpp_extra_deleted', $id );
    }

    return $result !== false && $result > 0;
}

/**
 * Create extra from form request data.
 */
function sfpp_create_extra_from_request( $request_data ) {
    // Get schema for extras
    $schema = sfpp_get_extras_schema();

    // Process schema data from form
    $schema_data = [];
    if ( isset( $request_data['schema'] ) && is_array( $request_data['schema'] ) ) {
        $schema_data = sfpp_process_schema_data( $schema, wp_unslash( $request_data['schema'] ) );
    }

    // Prepare extra data
    $extra_data = [
        'name' => sanitize_text_field( wp_unslash( $request_data['name'] ?? 'New Extra' ) ),
        'description' => sanitize_textarea_field( wp_unslash( $request_data['description'] ?? '' ) ),
        'status' => sanitize_text_field( wp_unslash( $request_data['status'] ?? 'active' ) ),
        'base_price' => floatval( $request_data['base_price'] ?? 0 ),
        'schema_json' => $schema_data,
    ];

    return sfpp_create_extra( $extra_data );
}

/**
 * Update extra from form request data.
 */
function sfpp_update_extra_from_request( $id, $request_data ) {
    $extra = sfpp_get_extra( $id );
    if ( ! $extra ) {
        return false;
    }

    // Get schema for extras
    $schema = sfpp_get_extras_schema();

    // Process schema data
    $schema_data = [];
    if ( isset( $request_data['schema'] ) && is_array( $request_data['schema'] ) ) {
        $schema_data = sfpp_process_schema_data( $schema, wp_unslash( $request_data['schema'] ) );
    }

    // Prepare update data
    $update_data = [
        'name' => sanitize_text_field( wp_unslash( $request_data['name'] ?? '' ) ),
        'description' => sanitize_textarea_field( wp_unslash( $request_data['description'] ?? '' ) ),
        'status' => sanitize_text_field( wp_unslash( $request_data['status'] ?? 'active' ) ),
        'base_price' => floatval( $request_data['base_price'] ?? 0 ),
        'schema_json' => $schema_data,
    ];

    return sfpp_update_extra( $id, $update_data );
}

/**
 * Get extras schema definition.
 */
function sfpp_get_extras_schema() {
    $path = dirname( __DIR__ ) . '/schemas/extras-schema.php';
    if ( file_exists( $path ) ) {
        $schema = include $path;
        return is_array( $schema ) ? $schema : [];
    }
    return [];
}
