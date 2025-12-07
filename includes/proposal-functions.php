<?php
/**
 * Simplified Proposal Management Functions
 * Handles proposals and proposal items with custom tables (sf_proposals, sf_proposal_items)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create database tables for proposals system.
 * Call this during plugin activation.
 */
function sfpp_create_proposal_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Drop legacy sf_proposal_assets table if it exists
    $wpdb->query( "DROP TABLE IF EXISTS sf_proposal_assets" );

    // Proposals table
    $proposals_table = "CREATE TABLE IF NOT EXISTS sf_proposals (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL DEFAULT '',
        client_name varchar(255) NOT NULL DEFAULT '',
        project_name varchar(255) NOT NULL DEFAULT '',
        status varchar(20) NOT NULL DEFAULT 'draft',
        proposal_type varchar(20) NOT NULL DEFAULT 'proposal',
        total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        currency varchar(10) NOT NULL DEFAULT 'PHP',
        schema_json longtext,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY status (status),
        KEY proposal_type (proposal_type),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Proposal items table
    $items_table = "CREATE TABLE IF NOT EXISTS sf_proposal_items (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        proposal_id bigint(20) NOT NULL,
        item_type varchar(20) NOT NULL DEFAULT 'custom',
        item_id bigint(20) NULL,
        name varchar(255) NOT NULL DEFAULT '',
        description text,
        quantity int(11) NOT NULL DEFAULT 1,
        unit_price decimal(10,2) NOT NULL DEFAULT 0.00,
        total_price decimal(10,2) NOT NULL DEFAULT 0.00,
        sort_order int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY proposal_id (proposal_id),
        KEY item_type (item_type),
        KEY sort_order (sort_order)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $proposals_table );
    dbDelta( $items_table );

    update_option( 'sfpp_proposals_db_version', '1.0' );
}

/**
 * Create a new proposal.
 */
function sfpp_create_proposal( $data ) {
    global $wpdb;

    $defaults = [
        'name' => 'New Proposal',
        'client_name' => '',
        'project_name' => '',
        'status' => 'draft',
        'proposal_type' => 'proposal',
        'total_amount' => 0.00,
        'currency' => 'PHP',
    ];

    $proposal_data = wp_parse_args( $data, $defaults );

    // Add timestamps
    $proposal_data['created_at'] = current_time( 'mysql' );
    $proposal_data['updated_at'] = current_time( 'mysql' );

    // Sanitize data
    $proposal_data = map_deep( $proposal_data, 'sanitize_text_field' );

    // Special handling for numeric field
    $proposal_data['total_amount'] = floatval( $data['total_amount'] ?? 0 );

    $result = $wpdb->insert( 'sf_proposals', $proposal_data );

    if ( $result === false ) {
        return false;
    }

    $proposal_id = $wpdb->insert_id;

    do_action( 'sfpp_proposal_created', $proposal_id, $proposal_data );

    return $proposal_id;
}

/**
 * Update an existing proposal.
 */
function sfpp_update_proposal( $id, $data ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return false;
    }

    // Add updated timestamp
    $data['updated_at'] = current_time( 'mysql' );

    // Sanitize data
    $clean_data = map_deep( $data, 'sanitize_text_field' );

    // Special handling for numeric field
    if ( isset( $data['total_amount'] ) ) {
        $clean_data['total_amount'] = floatval( $data['total_amount'] );
    }

    $result = $wpdb->update(
        'sf_proposals',
        $clean_data,
        [ 'id' => $id ]
    );

    if ( $result !== false ) {
        do_action( 'sfpp_proposal_updated', $id, $clean_data );
    }

    return $result !== false;
}

/**
 * Get a proposal by ID (header only, no items).
 */
function sfpp_get_proposal( $id ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return null;
    }

    $sql = $wpdb->prepare(
        "SELECT * FROM sf_proposals WHERE id = %d",
        $id
    );

    $proposal = $wpdb->get_row( $sql );

    if ( $proposal ) {
        // Decode schema JSON
        $proposal->schema_data = $proposal->schema_json ? json_decode( $proposal->schema_json, true ) ?? [] : [];

        do_action( 'sfpp_proposal_loaded', $proposal );
    }

    return $proposal;
}

/**
 * Get proposals list, optionally filtered by status.
 */
function sfpp_get_proposals( $status = null ) {
    global $wpdb;

    $where_conditions = [];
    $prepare_values = [];

    if ( $status ) {
        $where_conditions[] = "status = %s";
        $prepare_values[] = $status;
    }

    $where_clause = '';
    if ( ! empty( $where_conditions ) ) {
        $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
    }

    $sql = "SELECT * FROM sf_proposals {$where_clause} ORDER BY created_at DESC";

    if ( ! empty( $prepare_values ) ) {
        $sql = $wpdb->prepare( $sql, ...$prepare_values );
    }

    $proposals = $wpdb->get_results( $sql );

    return $proposals;
}

/**
 * Archive a proposal.
 */
function sfpp_archive_proposal( $id ) {
    $result = sfpp_update_proposal( $id, [ 'status' => 'archived' ] );

    if ( $result ) {
        do_action( 'sfpp_proposal_archived', $id );
    }

    return $result;
}

/**
 * Unarchive a proposal.
 */
function sfpp_unarchive_proposal( $id ) {
    $result = sfpp_update_proposal( $id, [ 'status' => 'draft' ] );

    if ( $result ) {
        do_action( 'sfpp_proposal_unarchived', $id );
    }

    return $result;
}

/**
 * Clone a proposal with all its items.
 */
function sfpp_clone_proposal( $id ) {
    $original = sfpp_get_proposal( $id );

    if ( ! $original ) {
        return false;
    }

    // Prepare clone data
    $clone_data = [
        'name' => 'Copy of ' . ( $original->name ?? 'Proposal' ),
        'client_name' => $original->client_name ?? '',
        'project_name' => $original->project_name ?? '',
        'status' => 'draft',
        'proposal_type' => $original->proposal_type ?? 'proposal',
        'total_amount' => 0.00, // Will be recalculated after cloning items
        'currency' => $original->currency ?? 'PHP',
        'schema_json' => $original->schema_json ?? null,
    ];

    $clone_id = sfpp_create_proposal( $clone_data );

    if ( ! $clone_id ) {
        return false;
    }

    // Clone all items
    $original_items = sfpp_get_proposal_items( $original->id );

    if ( ! empty( $original_items ) ) {
        foreach ( $original_items as $item ) {
            $item_data = [
                'item_type' => $item->item_type ?? 'custom',
                'item_id' => $item->item_id,
                'name' => $item->name ?? '',
                'description' => $item->description ?? '',
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->unit_price ?? 0,
                'total_price' => $item->total_price ?? 0,
                'sort_order' => $item->sort_order ?? 0,
            ];

            sfpp_add_proposal_item( $clone_id, $item_data );
        }
    }

    do_action( 'sfpp_proposal_cloned', $clone_id, $id );

    return $clone_id;
}

/**
 * Get all items for a proposal.
 */
function sfpp_get_proposal_items( $proposal_id ) {
    global $wpdb;

    $proposal_id = (int) $proposal_id;
    if ( $proposal_id <= 0 ) {
        return [];
    }

    $sql = $wpdb->prepare(
        "SELECT * FROM sf_proposal_items WHERE proposal_id = %d ORDER BY sort_order ASC, id ASC",
        $proposal_id
    );

    return $wpdb->get_results( $sql );
}

/**
 * Add an item to a proposal.
 */
function sfpp_add_proposal_item( $proposal_id, $data ) {
    global $wpdb;

    $proposal_id = (int) $proposal_id;
    if ( $proposal_id <= 0 ) {
        return false;
    }

    $defaults = [
        'item_type' => 'custom',
        'item_id' => null,
        'name' => '',
        'description' => '',
        'quantity' => 1,
        'unit_price' => 0.00,
        'total_price' => 0.00,
        'sort_order' => 0,
    ];

    $item_data = wp_parse_args( $data, $defaults );
    $item_data['proposal_id'] = $proposal_id;
    $item_data['created_at'] = current_time( 'mysql' );

    // Sanitize data
    $item_data = map_deep( $item_data, 'sanitize_text_field' );

    // Special handling for numeric fields
    $item_data['quantity'] = (int) $item_data['quantity'];
    $item_data['unit_price'] = floatval( $item_data['unit_price'] );
    $item_data['total_price'] = floatval( $item_data['total_price'] );
    $item_data['sort_order'] = (int) $item_data['sort_order'];

    // Calculate total_price if not provided
    if ( $item_data['total_price'] === 0.00 && $item_data['unit_price'] > 0 ) {
        $item_data['total_price'] = $item_data['unit_price'] * $item_data['quantity'];
    }

    $result = $wpdb->insert( 'sf_proposal_items', $item_data );

    if ( $result === false ) {
        return false;
    }

    $item_id = $wpdb->insert_id;

    // Recalculate proposal totals
    sfpp_recalculate_proposal_totals( $proposal_id );

    do_action( 'sfpp_proposal_item_added', $item_id, $proposal_id, $item_data );

    return $item_id;
}

/**
 * Update a proposal item.
 */
function sfpp_update_proposal_item( $item_id, $data ) {
    global $wpdb;

    $item_id = (int) $item_id;
    if ( $item_id <= 0 ) {
        return false;
    }

    // Get the proposal_id for recalculation
    $current_item = $wpdb->get_row( $wpdb->prepare(
        "SELECT proposal_id FROM sf_proposal_items WHERE id = %d",
        $item_id
    ) );

    if ( ! $current_item ) {
        return false;
    }

    // Sanitize data
    $clean_data = map_deep( $data, 'sanitize_text_field' );

    // Special handling for numeric fields
    if ( isset( $data['quantity'] ) ) {
        $clean_data['quantity'] = (int) $data['quantity'];
    }
    if ( isset( $data['unit_price'] ) ) {
        $clean_data['unit_price'] = floatval( $data['unit_price'] );
    }
    if ( isset( $data['total_price'] ) ) {
        $clean_data['total_price'] = floatval( $data['total_price'] );
    }
    if ( isset( $data['sort_order'] ) ) {
        $clean_data['sort_order'] = (int) $data['sort_order'];
    }

    // Auto-calculate total_price if quantity or unit_price changed but total_price not provided
    if ( ( isset( $clean_data['quantity'] ) || isset( $clean_data['unit_price'] ) ) && ! isset( $clean_data['total_price'] ) ) {
        $current_full_item = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM sf_proposal_items WHERE id = %d",
            $item_id
        ) );

        $quantity = $clean_data['quantity'] ?? $current_full_item->quantity;
        $unit_price = $clean_data['unit_price'] ?? $current_full_item->unit_price;
        $clean_data['total_price'] = $quantity * $unit_price;
    }

    $result = $wpdb->update(
        'sf_proposal_items',
        $clean_data,
        [ 'id' => $item_id ]
    );

    if ( $result !== false ) {
        // Recalculate proposal totals
        sfpp_recalculate_proposal_totals( $current_item->proposal_id );

        do_action( 'sfpp_proposal_item_updated', $item_id, $current_item->proposal_id, $clean_data );
    }

    return $result !== false;
}

/**
 * Delete a proposal item.
 */
function sfpp_delete_proposal_item( $item_id ) {
    global $wpdb;

    $item_id = (int) $item_id;
    if ( $item_id <= 0 ) {
        return false;
    }

    // Get the proposal_id before deletion for recalculation
    $item = $wpdb->get_row( $wpdb->prepare(
        "SELECT proposal_id FROM sf_proposal_items WHERE id = %d",
        $item_id
    ) );

    if ( ! $item ) {
        return false;
    }

    $result = $wpdb->delete(
        'sf_proposal_items',
        [ 'id' => $item_id ]
    );

    if ( $result !== false && $result > 0 ) {
        // Recalculate proposal totals
        sfpp_recalculate_proposal_totals( $item->proposal_id );

        do_action( 'sfpp_proposal_item_deleted', $item_id, $item->proposal_id );
    }

    return $result !== false && $result > 0;
}

/**
 * Recalculate and update proposal total amount.
 */
function sfpp_recalculate_proposal_totals( $proposal_id ) {
    global $wpdb;

    $proposal_id = (int) $proposal_id;
    if ( $proposal_id <= 0 ) {
        return false;
    }

    $sql = $wpdb->prepare(
        "SELECT SUM(total_price) as total FROM sf_proposal_items WHERE proposal_id = %d",
        $proposal_id
    );

    $total = $wpdb->get_var( $sql );
    $total = $total ? floatval( $total ) : 0.00;

    return sfpp_update_proposal( $proposal_id, [ 'total_amount' => $total ] );
}

/**
 * Add selected packages as proposal items.
 *
 * For each package_id:
 * - Load package using existing package helper.
 * - Create a proposal item row with item_type='package', item_id=package id,
 *   name=package name, description=package short_description, quantity=1,
 *   unit_price=package base_price, and append at the end.
 * - Recalculate the proposal total.
 */
function sfpp_add_packages_to_proposal_items( $proposal_id, $package_ids ) {
    global $wpdb;

    $proposal_id = (int) $proposal_id;
    if ( $proposal_id <= 0 || empty( $package_ids ) || ! is_array( $package_ids ) ) {
        return false;
    }

    // Normalise package IDs.
    $package_ids = array_filter( array_map( 'intval', $package_ids ) );
    if ( empty( $package_ids ) ) {
        return false;
    }

    // Determine starting sort_order (max existing + 10).
    $max_sort = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT MAX(sort_order) FROM sf_proposal_items WHERE proposal_id = %d",
            $proposal_id
        )
    );
    $sort_order = $max_sort + 10;

    foreach ( $package_ids as $package_id ) {
        // Use the existing package getter function.
        $package = sfpp_get_package_by_id( $package_id );
        if ( ! $package ) {
            continue;
        }

        $name        = isset( $package->name ) ? $package->name : '';
        $description = isset( $package->short_description ) ? $package->short_description : '';
        $unit_price  = isset( $package->base_price ) ? (float) $package->base_price : 0;
        $quantity    = 1;
        $total       = $quantity * $unit_price;

        $wpdb->insert(
            'sf_proposal_items',
            [
                'proposal_id' => $proposal_id,
                'item_type'   => 'package',
                'item_id'     => $package_id,
                'name'        => $name,
                'description' => $description,
                'quantity'    => $quantity,
                'unit_price'  => $unit_price,
                'total_price' => $total,
                'sort_order'  => $sort_order,
                'created_at'  => current_time( 'mysql' ),
            ]
        );

        $sort_order += 10;
    }

    // Recalculate totals after adding items.
    sfpp_recalculate_proposal_totals( $proposal_id );

    do_action( 'sfpp_proposal_items_packages_added', $proposal_id, $package_ids );

    return true;
}

/**
 * Add selected extras as proposal items.
 *
 * For each extra_id:
 * - Load extra from sf_extras.
 * - Create a proposal item row with item_type='extra', item_id=extra id,
 *   name=extra name, description=extra description, quantity=1,
 *   unit_price=extra base_price, and append at the end.
 * - Recalculate the proposal total.
 */
function sfpp_add_extras_to_proposal_items( $proposal_id, $extra_ids ) {
    global $wpdb;

    $proposal_id = (int) $proposal_id;
    if ( $proposal_id <= 0 || empty( $extra_ids ) || ! is_array( $extra_ids ) ) {
        return 0;
    }

    // Normalise extra IDs.
    $extra_ids = array_filter( array_map( 'intval', $extra_ids ) );
    if ( empty( $extra_ids ) ) {
        return 0;
    }

    // Determine starting sort_order (max existing + 10).
    $max_sort = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT MAX(sort_order) FROM sf_proposal_items WHERE proposal_id = %d",
            $proposal_id
        )
    );
    $sort_order = $max_sort + 10;

    $added_count = 0;

    foreach ( $extra_ids as $extra_id ) {
        // Load the extra.
        $extra = function_exists( 'sfpp_get_extra' ) ? sfpp_get_extra( $extra_id ) : null;
        if ( ! $extra ) {
            continue;
        }

        $name        = isset( $extra->name ) ? $extra->name : '';
        $description = isset( $extra->description ) ? $extra->description : '';
        $unit_price  = isset( $extra->base_price ) ? (float) $extra->base_price : 0;
        $quantity    = 1;
        $total       = $quantity * $unit_price;

        $result = $wpdb->insert(
            'sf_proposal_items',
            [
                'proposal_id' => $proposal_id,
                'item_type'   => 'extra',
                'item_id'     => $extra_id,
                'name'        => $name,
                'description' => $description,
                'quantity'    => $quantity,
                'unit_price'  => $unit_price,
                'total_price' => $total,
                'sort_order'  => $sort_order,
                'created_at'  => current_time( 'mysql' ),
            ]
        );

        if ( $result !== false ) {
            $added_count++;
            $sort_order += 10;
        }
    }

    // Recalculate totals after adding items.
    if ( $added_count > 0 ) {
        sfpp_recalculate_proposal_totals( $proposal_id );
        do_action( 'sfpp_proposal_items_extras_added', $proposal_id, $extra_ids, $added_count );
    }

    return $added_count;
}

/**
 * Create proposal from form request data.
 */
function sfpp_create_proposal_from_request( $post ) {
    // Basic proposal fields
    $proposal_data = [
        'name' => sanitize_text_field( wp_unslash( $post['name'] ?? 'New Proposal' ) ),
        'client_name' => sanitize_text_field( wp_unslash( $post['client_name'] ?? '' ) ),
        'project_name' => sanitize_text_field( wp_unslash( $post['project_name'] ?? '' ) ),
        'status' => sanitize_text_field( wp_unslash( $post['status'] ?? 'draft' ) ),
        'proposal_type' => sanitize_text_field( wp_unslash( $post['proposal_type'] ?? 'proposal' ) ),
        'currency' => sanitize_text_field( wp_unslash( $post['currency'] ?? 'PHP' ) ),
    ];

    $proposal_id = sfpp_create_proposal( $proposal_data );

    if ( ! $proposal_id ) {
        return false;
    }

    // Add items if provided
    if ( isset( $post['items'] ) && is_array( $post['items'] ) ) {
        foreach ( $post['items'] as $item_data ) {
            if ( ! empty( $item_data['name'] ) ) {
                $clean_item = [
                    'item_type' => sanitize_text_field( wp_unslash( $item_data['item_type'] ?? 'custom' ) ),
                    'item_id' => ! empty( $item_data['item_id'] ) ? (int) $item_data['item_id'] : null,
                    'name' => sanitize_text_field( wp_unslash( $item_data['name'] ) ),
                    'description' => sanitize_textarea_field( wp_unslash( $item_data['description'] ?? '' ) ),
                    'quantity' => (int) ( $item_data['quantity'] ?? 1 ),
                    'unit_price' => floatval( $item_data['unit_price'] ?? 0 ),
                ];

                sfpp_add_proposal_item( $proposal_id, $clean_item );
            }
        }
    }

    return $proposal_id;
}

/**
 * Update proposal from form request data.
 */
function sfpp_update_proposal_from_request( $id, $post ) {
    $proposal = sfpp_get_proposal( $id );
    if ( ! $proposal ) {
        return false;
    }

    // Update basic proposal fields
    $update_data = [
        'name' => sanitize_text_field( wp_unslash( $post['name'] ?? '' ) ),
        'client_name' => sanitize_text_field( wp_unslash( $post['client_name'] ?? '' ) ),
        'project_name' => sanitize_text_field( wp_unslash( $post['project_name'] ?? '' ) ),
        'status' => sanitize_text_field( wp_unslash( $post['status'] ?? 'draft' ) ),
        'proposal_type' => sanitize_text_field( wp_unslash( $post['proposal_type'] ?? 'proposal' ) ),
        'currency' => sanitize_text_field( wp_unslash( $post['currency'] ?? 'PHP' ) ),
    ];

    // Handle schema data
    if ( isset( $post['schema'] ) && is_array( $post['schema'] ) ) {
        $schema = sfpp_get_proposal_schema();
        $schema_data = sfpp_process_proposal_schema( $schema, wp_unslash( $post['schema'] ) );
        $update_data['schema_json'] = wp_json_encode( $schema_data );
    }

    $success = sfpp_update_proposal( $id, $update_data );

    if ( ! $success ) {
        return false;
    }

    // Replace all items if provided
    if ( isset( $post['items'] ) && is_array( $post['items'] ) ) {
        // Delete existing items
        $existing_items = sfpp_get_proposal_items( $id );
        foreach ( $existing_items as $item ) {
            sfpp_delete_proposal_item( $item->id );
        }

        // Add new items
        foreach ( $post['items'] as $item_data ) {
            if ( ! empty( $item_data['name'] ) ) {
                $clean_item = [
                    'item_type' => sanitize_text_field( wp_unslash( $item_data['item_type'] ?? 'custom' ) ),
                    'item_id' => ! empty( $item_data['item_id'] ) ? (int) $item_data['item_id'] : null,
                    'name' => sanitize_text_field( wp_unslash( $item_data['name'] ) ),
                    'description' => sanitize_textarea_field( wp_unslash( $item_data['description'] ?? '' ) ),
                    'quantity' => (int) ( $item_data['quantity'] ?? 1 ),
                    'unit_price' => floatval( $item_data['unit_price'] ?? 0 ),
                ];

                sfpp_add_proposal_item( $id, $clean_item );
            }
        }
    }

    return true;
}

/**
 * Process schema data from form submission for proposals.
 */
function sfpp_process_proposal_schema( $schema, $posted_data ) {
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
                $type = $field['type'] ?? 'text';

                // Get value from posted data
                $value = sfpp_schema_get_value( $posted_data, $key, $default );

                // For checkbox_multi fields, ensure it's an array
                if ( 'checkbox_multi' === $type && ! is_array( $value ) ) {
                    $value = ! empty( $value ) ? [ $value ] : [];
                }

                sfpp_schema_set_value( $clean_schema, $key, $value );
            }
        }
    }

    return $clean_schema;
}