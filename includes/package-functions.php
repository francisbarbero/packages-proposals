<?php
/**
 * Simplified Package Management Functions
 * Replaces the complex domain models and service layer with straightforward WordPress functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get default values for a package type.
 */
function sfpp_get_package_defaults( $type ) {
    $defaults = [
        'website' => [
            'type' => 'website',
            'name' => 'New Website Package',
            'billing_model' => 'one_off',
            'currency' => 'PHP',
            'base_price' => 0,
            'status' => 'active',
            'schema_json' => '{}',
        ],
        'hosting' => [
            'type' => 'hosting',
            'name' => 'New Hosting Package',
            'billing_model' => 'monthly',
            'currency' => 'PHP',
            'base_price' => 0,
            'status' => 'active',
            'schema_json' => '{}',
        ],
        'maintenance' => [
            'type' => 'maintenance',
            'name' => 'New Maintenance Package',
            'billing_model' => 'monthly',
            'currency' => 'PHP',
            'base_price' => 0,
            'status' => 'active',
            'schema_json' => '{}',
        ],
    ];

    return $defaults[ $type ] ?? $defaults['website'];
}

/**
 * Create a new package.
 */
function sfpp_create_package( $type, $data = [] ) {
    global $wpdb;

    $defaults = sfpp_get_package_defaults( $type );
    $package_data = wp_parse_args( $data, $defaults );

    // Add timestamps
    $package_data['created_at'] = current_time( 'mysql' );
    $package_data['updated_at'] = current_time( 'mysql' );

    // Sanitize data
    $package_data = map_deep( $package_data, 'sanitize_text_field' );

    // Special handling for schema_json and numeric fields
    $package_data['base_price'] = floatval( $data['base_price'] ?? 0 );
    if ( isset( $data['schema_json'] ) ) {
        $package_data['schema_json'] = wp_json_encode( $data['schema_json'] );
    }

    $result = $wpdb->insert( 'sf_packages', $package_data );

    if ( $result === false ) {
        return false;
    }

    $package_id = $wpdb->insert_id;

    // WordPress action for extensibility
    do_action( 'sfpp_package_created', $package_id, $package_data );

    return $package_id;
}

/**
 * Update an existing package.
 */
function sfpp_update_package( $id, $data ) {
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
        'sf_packages',
        $clean_data,
        [ 'id' => $id ]
    );

    if ( $result !== false ) {
        do_action( 'sfpp_package_updated', $id, $clean_data );
    }

    return $result !== false;
}

/**
 * Get a package by ID.
 */
function sfpp_get_package( $id ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return null;
    }

    $sql = $wpdb->prepare(
        "SELECT * FROM sf_packages WHERE id = %d",
        $id
    );

    $package = $wpdb->get_row( $sql );

    if ( $package ) {
        // Decode schema JSON
        $package->schema_data = $package->schema_json ? json_decode( $package->schema_json, true ) ?? [] : [];

        do_action( 'sfpp_package_loaded', $package );
    }

    return $package;
}

/**
 * Get packages by type and status.
 */
function sfpp_get_packages( $type = null, $status = 'active' ) {
    global $wpdb;

    $where_conditions = [];
    $prepare_values = [];

    if ( $type ) {
        $where_conditions[] = "type = %s";
        $prepare_values[] = $type;
    }

    if ( $status ) {
        $where_conditions[] = "status = %s";
        $prepare_values[] = $status;
    }

    $where_clause = '';
    if ( ! empty( $where_conditions ) ) {
        $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
    }

    $sql = "SELECT * FROM sf_packages {$where_clause} ORDER BY name ASC";

    if ( ! empty( $prepare_values ) ) {
        $sql = $wpdb->prepare( $sql, ...$prepare_values );
    }

    $packages = $wpdb->get_results( $sql );

    // Decode schema JSON for each package
    foreach ( $packages as $package ) {
        $package->schema_data = $package->schema_json ? json_decode( $package->schema_json, true ) ?? [] : [];
    }

    return $packages;
}

/**
 * Archive a package (set status to archived).
 */
function sfpp_archive_package( $id ) {
    $result = sfpp_update_package( $id, [ 'status' => 'archived' ] );

    if ( $result ) {
        do_action( 'sfpp_package_archived', $id );
    }

    return $result;
}

/**
 * Unarchive a package (set status to active).
 */
function sfpp_unarchive_package( $id ) {
    $result = sfpp_update_package( $id, [ 'status' => 'active' ] );

    if ( $result ) {
        do_action( 'sfpp_package_unarchived', $id );
    }

    return $result;
}

/**
 * Clone a package.
 */
function sfpp_clone_package( $id ) {
    $original = sfpp_get_package( $id );

    if ( ! $original ) {
        return false;
    }

    // Convert to array and prepare for cloning
    $clone_data = (array) $original;
    unset( $clone_data['id'] );
    unset( $clone_data['created_at'] );
    unset( $clone_data['updated_at'] );

    // Update name
    $clone_data['name'] = $original->name . ' (Copy)';

    $clone_id = sfpp_create_package( $original->type, $clone_data );

    if ( $clone_id ) {
        do_action( 'sfpp_package_cloned', $clone_id, $id );
    }

    return $clone_id;
}

/**
 * Create package from form request data.
 * Processes POST data and creates appropriate package type.
 */
function sfpp_create_package_from_request( $request_data ) {
    // Determine package type
    $type = sfpp_determine_package_type( $request_data );

    // Get schema for validation and processing
    $schema = sfpp_get_schema_for_type( $type );

    // Process schema data from form
    $schema_data = [];
    if ( isset( $request_data['schema'] ) && is_array( $request_data['schema'] ) ) {
        $schema_data = sfpp_process_schema_data( $schema, wp_unslash( $request_data['schema'] ) );
    }

    // Prepare package data
    $package_data = [
        'type' => $type,
        'schema_json' => $schema_data,
    ];

    // Add any other fields from request
    $allowed_fields = [ 'name', 'short_description', 'base_price', 'currency', 'group_label', 'billing_model', 'status' ];
    foreach ( $allowed_fields as $field ) {
        if ( isset( $request_data[ $field ] ) ) {
            $package_data[ $field ] = $request_data[ $field ];
        }
    }

    return sfpp_create_package( $type, $package_data );
}

/**
 * Update package from form request data.
 */
function sfpp_update_package_from_request( $id, $request_data ) {
    $package = sfpp_get_package( $id );
    if ( ! $package ) {
        return false;
    }

    // Get schema for this package type
    $schema = sfpp_get_schema_for_type( $package->type );

    // Process schema data
    $schema_data = [];
    if ( isset( $request_data['schema'] ) && is_array( $request_data['schema'] ) ) {
        $schema_data = sfpp_process_schema_data( $schema, wp_unslash( $request_data['schema'] ) );
    }

    // Prepare update data
    $update_data = [
        'name' => sanitize_text_field( wp_unslash( $request_data['name'] ?? '' ) ),
        'short_description' => sanitize_textarea_field( wp_unslash( $request_data['short_description'] ?? '' ) ),
        'group_label' => sanitize_text_field( wp_unslash( $request_data['group_label'] ?? '' ) ),
        'base_price' => floatval( $request_data['base_price'] ?? 0 ),
        'status' => sanitize_text_field( wp_unslash( $request_data['status'] ?? 'active' ) ),
        'billing_model' => sanitize_text_field( wp_unslash( $request_data['billing_model'] ?? $package->billing_model ) ),
        'currency' => sanitize_text_field( wp_unslash( $request_data['currency'] ?? $package->currency ) ),
        'schema_json' => $schema_data,
    ];

    return sfpp_update_package( $id, $update_data );
}

/**
 * Determine package type from request data.
 */
function sfpp_determine_package_type( $request_data ) {
    // 1) Explicit package_type field
    if ( ! empty( $request_data['package_type'] ) ) {
        return sanitize_key( $request_data['package_type'] );
    }

    // 2) Infer from action
    if ( ! empty( $request_data['sfpp_action'] ) ) {
        $action = $request_data['sfpp_action'];
        if ( $action === 'add_hosting_package' ) {
            return 'hosting';
        }
        if ( $action === 'add_maintenance_package' ) {
            return 'maintenance';
        }
        if ( $action === 'add_website_package' ) {
            return 'website';
        }
    }

    // 3) Infer from section
    if ( ! empty( $request_data['sfpp_section'] ) ) {
        $section = sanitize_key( $request_data['sfpp_section'] );
        if ( $section === 'hosting' ) {
            return 'hosting';
        }
        if ( $section === 'maintenance' ) {
            return 'maintenance';
        }
    }

    // Default fallback
    return 'website';
}

/**
 * Get schema definition for package type.
 */
function sfpp_get_schema_for_type( $type ) {
    switch ( $type ) {
        case 'hosting':
            return function_exists( 'sfpp_get_hosting_package_schema' ) ? sfpp_get_hosting_package_schema() : [];
        case 'maintenance':
            return function_exists( 'sfpp_get_maintenance_package_schema' ) ? sfpp_get_maintenance_package_schema() : [];
        default:
            return function_exists( 'sfpp_get_website_package_schema' ) ? sfpp_get_website_package_schema() : [];
    }
}

/**
 * Process schema data from form submission.
 */
function sfpp_process_schema_data( $schema, $posted_data ) {
    $clean_schema = [];

    if ( ! empty( $schema['groups'] ) ) {
        foreach ( $schema['groups'] as $group ) {
            if ( empty( $group['fields'] ) ) {
                continue;
            }

            foreach ( $group['fields'] as $field ) {
                if ( empty( $field['key'] ) ) {
                    continue;
                }

                $key = $field['key'];
                $default = $field['default'] ?? '';
                $value = sfpp_schema_get_value( $posted_data, $key, $default );
                sfpp_schema_set_value( $clean_schema, $key, $value );
            }
        }
    }

    return $clean_schema;
}

/**
 * Get redirect section for package type.
 */
function sfpp_get_redirect_section_for_type( $type ) {
    switch ( $type ) {
        case 'hosting':
            return 'hosting';
        case 'maintenance':
            return 'maintenance';
        default:
            return 'packages';
    }
}