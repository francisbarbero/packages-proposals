<?php
// ui/front-actions.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle front-end actions for the SFPP app (e.g. add package).
 *
 * This file is where we’ll keep ALL front-end POST action handlers
 * so the main plugin file stays small and easy to read.
 */
function sfpp_handle_front_actions() {
    // Only run on front-end.
    if ( is_admin() ) {
        return;
    }

    // Require login + capability (adjust capability if needed).
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Only care about POST.
    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
        return;
    }

    if ( empty( $_POST['sfpp_action'] ) ) {
        return;
    }

    $action = sanitize_text_field( wp_unslash( $_POST['sfpp_action'] ) );

     switch ( $action ) {
        case 'add_package':
            sfpp_action_add_package();
            break;

        case 'save_package':
            sfpp_action_save_package();
            break;

        case 'clone_package':
            sfpp_action_clone_package();
            break;

        case 'archive_package':
            sfpp_action_archive_package();
            break;

        case 'unarchive_package':
            sfpp_action_unarchive_package();
            break;
    }

}

/**
 * Action: add a new Website Package with a placeholder name.
 */
function sfpp_action_add_package() {
    if ( empty( $_POST['sfpp_add_package_nonce'] ) || ! wp_verify_nonce( $_POST['sfpp_add_package_nonce'], 'sfpp_add_package' ) ) {
        return;
    }

    global $wpdb;

    // Adjust this if you actually created the table with a prefix like wp_sf_packages.
    $table = 'sf_packages';

    $data = [
        'type'              => 'website',
        'name'              => 'New Website Package',
        'short_description' => '',
        'billing_model'     => 'one_off',
        'base_price'        => 0.00,
        'currency'          => 'PHP',
        'group_label'       => '',
        'status'            => 'active',
        'schema_json'       => null,
        'created_at'        => current_time( 'mysql' ),
        'updated_at'        => current_time( 'mysql' ),
    ];

    $wpdb->insert( $table, $data );
    $new_id = (int) $wpdb->insert_id;

    $referer = wp_get_referer();
    if ( ! $referer ) {
        $referer = home_url();
    }

    $redirect = add_query_arg(
        [
            'sfpp_section' => 'packages',
            'sfpp_notice'  => 'package_created',
            'sfpp_new_id'  => $new_id,
        ],
        $referer
    );

    wp_safe_redirect( $redirect );
    exit;
}


/**
 * Action: save an existing Website Package (top-level + schema_json via schema).
 */
function sfpp_action_save_package() {
    if ( empty( $_POST['sfpp_save_package_nonce'] ) || ! wp_verify_nonce( $_POST['sfpp_save_package_nonce'], 'sfpp_save_package' ) ) {
        return;
    }

    if ( empty( $_POST['package_id'] ) ) {
        return;
    }

    global $wpdb;
    $table = 'sf_packages'; // change if you used a prefix like wp_sf_packages

    $id = (int) $_POST['package_id'];

    // Top-level columns.
    $name              = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
    $short_description = sanitize_textarea_field( wp_unslash( $_POST['short_description'] ?? '' ) );
    $group_label       = sanitize_text_field( wp_unslash( $_POST['group_label'] ?? '' ) );
    $billing_model     = sanitize_text_field( wp_unslash( $_POST['billing_model'] ?? 'one_off' ) );
    $base_price        = isset( $_POST['base_price'] ) ? (float) $_POST['base_price'] : 0;
    $currency          = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'PHP' ) );
    $status_input      = isset( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : 'active';
    $status            = in_array( $status_input, [ 'active', 'archived' ], true ) ? $status_input : 'active';

    // Schema-driven fields (Website-specific).
    $schema_def   = function_exists( 'sfpp_get_website_package_schema' ) ? sfpp_get_website_package_schema() : [];
    $raw_schema   = isset( $_POST['schema'] ) && is_array( $_POST['schema'] ) ? $_POST['schema'] : [];
    $schema_data  = [];

    if ( ! empty( $schema_def['groups'] ) && is_array( $schema_def['groups'] ) ) {
        foreach ( $schema_def['groups'] as $group ) {
            $fields = $group['fields'] ?? [];
            if ( empty( $fields ) || ! is_array( $fields ) ) {
                continue;
            }

            foreach ( $fields as $field ) {
                if ( empty( $field['key'] ) ) {
                    continue;
                }

                $key     = $field['key'];
                $default = $field['default'] ?? '';
                $type    = $field['type'] ?? 'text';

                // Get the submitted value from nested $_POST['schema'][...]
                $raw_value = sfpp_schema_get_value( $raw_schema, $key, $default );

                // Normalise by field type.
                if ( 'checkbox' === $type ) {
                    $value = $raw_value ? 1 : 0;
                } elseif ( 'number' === $type ) {
                    $value = is_numeric( $raw_value ) ? ( 0 + $raw_value ) : 0;
                } elseif ( 'textarea' === $type ) {
                    $value = is_string( $raw_value ) ? sanitize_textarea_field( wp_unslash( $raw_value ) ) : '';
                } else {
                    // text, select, etc.
                    $value = is_string( $raw_value ) ? sanitize_text_field( wp_unslash( $raw_value ) ) : $raw_value;
                }

                sfpp_schema_set_value( $schema_data, $key, $value );
            }
        }
    }

    $data = [
        'name'              => $name,
        'short_description' => $short_description,
        'group_label'       => $group_label,
        'billing_model'     => $billing_model,
        'base_price'        => $base_price,
        'currency'          => $currency,
        'status'            => $status,
        'schema_json'       => wp_json_encode( $schema_data ),
        'updated_at'        => current_time( 'mysql' ),
    ];

    $wpdb->update(
        $table,
        $data,
        [ 'id' => $id ],
        [ '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' ],
        [ '%d' ]
    );

    $referer = wp_get_referer();
    if ( ! $referer ) {
        $referer = home_url();
    }

    $redirect = add_query_arg(
        [
            'sfpp_section' => 'packages',
            'sfpp_notice'  => 'package_saved',
        ],
        $referer
    );

    wp_safe_redirect( $redirect );
    exit;
}


/**
 * Action: clone a Website Package (creates an archived copy).
 */
function sfpp_action_clone_package() {
    if ( empty( $_POST['sfpp_clone_package_nonce'] ) ) {
        return;
    }

    $id = isset( $_POST['package_id'] ) ? (int) $_POST['package_id'] : 0;
    if ( $id <= 0 ) {
        return;
    }

    $nonce_action = 'sfpp_clone_package_' . $id;
    if ( ! wp_verify_nonce( $_POST['sfpp_clone_package_nonce'], $nonce_action ) ) {
        return;
    }

    global $wpdb;
    $table = 'sf_packages'; // adjust if needed

    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
    if ( ! $row ) {
        return;
    }

    $wpdb->insert(
        $table,
        [
            'type'              => $row->type,
            'name'              => $row->name . ' (Copy)',
            'short_description' => $row->short_description,
            'billing_model'     => $row->billing_model,
            'base_price'        => $row->base_price,
            'currency'          => $row->currency,
            'group_label'       => $row->group_label,
            'status'            => 'archived', // clones start archived
            'schema_json'       => $row->schema_json,
            'created_at'        => current_time( 'mysql' ),
            'updated_at'        => current_time( 'mysql' ),
        ]
    );

    $referer = wp_get_referer();
    if ( ! $referer ) {
        $referer = home_url();
    }

    $redirect = add_query_arg(
        [
            'sfpp_section' => 'packages',
            'sfpp_notice'  => 'package_cloned',
        ],
        $referer
    );

    wp_safe_redirect( $redirect );
    exit;
}

/**
 * Action: archive a Website Package.
 */
function sfpp_action_archive_package() {
    sfpp_update_package_status_and_redirect( 'archived', 'package_archived' );
}

/**
 * Action: unarchive a Website Package.
 */
function sfpp_action_unarchive_package() {
    sfpp_update_package_status_and_redirect( 'active', 'package_unarchived' );
}

/**
 * Helper: update status and redirect with a notice.
 *
 * @param string $new_status  'active' or 'archived'
 * @param string $notice_key  e.g. 'package_archived'
 */
function sfpp_update_package_status_and_redirect( $new_status, $notice_key ) {
    if ( empty( $_POST['package_id'] ) || empty( $_POST['sfpp_package_status_nonce'] ) ) {
        return;
    }

    $id = (int) $_POST['package_id'];
    if ( $id <= 0 ) {
        return;
    }

    $nonce_action = ( 'archived' === $new_status )
        ? 'sfpp_archive_package_' . $id
        : 'sfpp_unarchive_package_' . $id;

    if ( ! wp_verify_nonce( $_POST['sfpp_package_status_nonce'], $nonce_action ) ) {
        return;
    }

    global $wpdb;
    $table = 'sf_packages'; // adjust if needed

    $wpdb->update(
        $table,
        [
            'status'     => $new_status,
            'updated_at' => current_time( 'mysql' ),
        ],
        [ 'id' => $id ],
        [ '%s', '%s' ],
        [ '%d' ]
    );

    $referer = wp_get_referer();
    if ( ! $referer ) {
        $referer = home_url();
    }

    $redirect = add_query_arg(
        [
            'sfpp_section' => 'packages',
        'sfpp_notice'  => $notice_key,
        ],
        $referer
    );

    wp_safe_redirect( $redirect );
    exit;
}
