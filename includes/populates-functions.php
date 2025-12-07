<?php
/**
 * Populates Management Functions
 * Handles reusable content snippets with custom table (sf_populates)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create database table for populates system.
 * Call this during plugin activation.
 */
function sfpp_create_populates_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS sf_populates (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL DEFAULT '',
        category varchar(100) NOT NULL DEFAULT '',
        content longtext,
        schema_json longtext,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY category (category)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'sfpp_populates_db_version', '1.0' );
}

/**
 * Get a single populate by ID.
 */
function sfpp_get_populate( $id ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return null;
    }

    $table = 'sf_populates';

    $sql = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $id
    );

    $populate = $wpdb->get_row( $sql );

    if ( $populate ) {
        // Decode schema JSON
        $populate->schema_data = $populate->schema_json ? json_decode( $populate->schema_json, true ) ?? [] : [];

        do_action( 'sfpp_populate_loaded', $populate );
    }

    return $populate;
}

/**
 * Get list of populates with optional filters.
 */
function sfpp_get_populates( $args = [] ) {
    global $wpdb;

    $defaults = [
        'category' => null,
    ];

    $args = wp_parse_args( $args, $defaults );

    $table = 'sf_populates';

    $where_conditions = [];
    $prepare_values = [];

    if ( $args['category'] ) {
        $where_conditions[] = "category = %s";
        $prepare_values[] = $args['category'];
    }

    $where_clause = '';
    if ( ! empty( $where_conditions ) ) {
        $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
    }

    $sql = "SELECT * FROM {$table} {$where_clause} ORDER BY title ASC";

    if ( ! empty( $prepare_values ) ) {
        $sql = $wpdb->prepare( $sql, ...$prepare_values );
    }

    $populates = $wpdb->get_results( $sql );

    // Decode schema JSON for each populate
    foreach ( $populates as $populate ) {
        $populate->schema_data = $populate->schema_json ? json_decode( $populate->schema_json, true ) ?? [] : [];
    }

    return $populates;
}

/**
 * Create a new populate.
 */
function sfpp_create_populate( $data = [] ) {
    global $wpdb;

    $defaults = [
        'title'       => 'New Snippet',
        'category'    => '',
        'content'     => '',
        'schema_json' => '{}',
    ];

    $populate_data = wp_parse_args( $data, $defaults );

    // Add timestamps
    $populate_data['created_at'] = current_time( 'mysql' );
    $populate_data['updated_at'] = current_time( 'mysql' );

    // Sanitize data
    $populate_data = map_deep( $populate_data, 'sanitize_text_field' );

    // Special handling for content and schema_json
    if ( isset( $data['content'] ) ) {
        $populate_data['content'] = wp_kses_post( $data['content'] );
    }
    if ( isset( $data['schema_json'] ) && is_array( $data['schema_json'] ) ) {
        $populate_data['schema_json'] = wp_json_encode( $data['schema_json'] );
    }

    $table = 'sf_populates';

    $result = $wpdb->insert( $table, $populate_data );

    if ( $result === false ) {
        return false;
    }

    $populate_id = $wpdb->insert_id;

    do_action( 'sfpp_populate_created', $populate_id, $populate_data );

    return $populate_id;
}

/**
 * Update an existing populate.
 */
function sfpp_update_populate( $id, $data ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return false;
    }

    // Add updated timestamp
    $data['updated_at'] = current_time( 'mysql' );

    // Sanitize data
    $clean_data = map_deep( $data, 'sanitize_text_field' );

    // Special handling for content and schema_json
    if ( isset( $data['content'] ) ) {
        $clean_data['content'] = wp_kses_post( $data['content'] );
    }
    if ( isset( $data['schema_json'] ) && is_array( $data['schema_json'] ) ) {
        $clean_data['schema_json'] = wp_json_encode( $data['schema_json'] );
    }

    $table = 'sf_populates';

    $result = $wpdb->update(
        $table,
        $clean_data,
        [ 'id' => $id ]
    );

    if ( $result !== false ) {
        do_action( 'sfpp_populate_updated', $id, $clean_data );
    }

    return $result !== false;
}

/**
 * Delete a populate.
 */
function sfpp_delete_populate( $id ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return false;
    }

    $table = 'sf_populates';

    $result = $wpdb->delete(
        $table,
        [ 'id' => $id ]
    );

    if ( $result !== false && $result > 0 ) {
        do_action( 'sfpp_populate_deleted', $id );
    }

    return $result !== false && $result > 0;
}

/**
 * Create populate from form request data.
 */
function sfpp_create_populate_from_request( $request_data ) {
    // Get schema for populates
    $schema = sfpp_get_populates_schema();

    // Process schema data from form
    $schema_data = [];
    if ( isset( $request_data['schema'] ) && is_array( $request_data['schema'] ) ) {
        $schema_data = sfpp_process_schema_data( $schema, wp_unslash( $request_data['schema'] ) );
    }

    // Prepare populate data
    $populate_data = [
        'title'       => sanitize_text_field( wp_unslash( $request_data['title'] ?? 'New Snippet' ) ),
        'category'    => sanitize_text_field( wp_unslash( $request_data['category'] ?? '' ) ),
        'content'     => wp_kses_post( wp_unslash( $request_data['content'] ?? '' ) ),
        'schema_json' => wp_json_encode( $schema_data ),
    ];

    return sfpp_create_populate( $populate_data );
}

/**
 * Update populate from form request data.
 */
function sfpp_update_populate_from_request( $id, $request_data ) {
    $populate = sfpp_get_populate( $id );
    if ( ! $populate ) {
        return false;
    }

    // Get schema for populates
    $schema = sfpp_get_populates_schema();

    // Process schema data
    $schema_data = [];
    if ( isset( $request_data['schema'] ) && is_array( $request_data['schema'] ) ) {
        $schema_data = sfpp_process_schema_data( $schema, wp_unslash( $request_data['schema'] ) );
    }

    // Prepare update data
    $update_data = [
        'title'       => sanitize_text_field( wp_unslash( $request_data['title'] ?? '' ) ),
        'category'    => sanitize_text_field( wp_unslash( $request_data['category'] ?? '' ) ),
        'content'     => wp_kses_post( wp_unslash( $request_data['content'] ?? '' ) ),
        'schema_json' => wp_json_encode( $schema_data ),
    ];

    return sfpp_update_populate( $id, $update_data );
}

/**
 * Get populates schema definition.
 */
function sfpp_get_populates_schema() {
    $path = dirname( __DIR__ ) . '/schemas/populates-schema.php';
    if ( file_exists( $path ) ) {
        $schema = include $path;
        return is_array( $schema ) ? $schema : [];
    }
    return [];
}
