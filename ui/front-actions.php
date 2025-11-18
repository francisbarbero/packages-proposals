<?php
// ui/front-actions.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * MASTER HANDLER — Executes all POST-based actions
 */
function sfpp_handle_front_actions() {

    if ( is_admin() ) {
        return;
    }

    // Only logged-in admins can use the system
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Only POST requests
    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
        return;
    }

    if ( empty( $_POST['sfpp_action'] ) ) {
        return;
    }

    $action = sanitize_text_field( wp_unslash( $_POST['sfpp_action'] ) );

    switch ( $action ) {

        // Backwards-compatible: all of these mean "add a package"
        case 'add_package':
        case 'add_website_package':
        case 'add_hosting_package':
        case 'add_maintenance_package':
            sfpp_action_add_package( $action );
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
 * CREATE A NEW PACKAGE (Website / Hosting / Maintenance)
 *
 * @param string $action The sfpp_action value from the POST (for BC).
 */
function sfpp_action_add_package( $action = 'add_package' ) {

    // Accept old + new nonce names
    $nonce_map = [
        'sfpp_add_package_nonce'           => 'sfpp_add_package',
        'sfpp_add_website_package_nonce'   => 'sfpp_add_website_package',
        'sfpp_add_hosting_package_nonce'   => 'sfpp_add_hosting_package',
        'sfpp_add_maintenance_package_nonce' => 'sfpp_add_maintenance_package',
    ];

    $nonce_ok = false;
    foreach ( $nonce_map as $field => $nonce_action ) {
        if ( ! empty( $_POST[ $field ] ) && wp_verify_nonce( $_POST[ $field ], $nonce_action ) ) {
            $nonce_ok = true;
            break;
        }
    }

    if ( ! $nonce_ok ) {
        return;
    }

    global $wpdb;
    $table = 'sf_packages';

    // Figure out package type
    $package_type = null;

    // 1) Explicit hidden field wins
    if ( isset( $_POST['package_type'] ) ) {
        $package_type = sanitize_key( $_POST['package_type'] );
    }

    // 2) Otherwise infer from action name
    if ( ! $package_type ) {
        if ( $action === 'add_hosting_package' ) {
            $package_type = 'hosting';
        } elseif ( $action === 'add_maintenance_package' ) {
            $package_type = 'maintenance';
        } elseif ( $action === 'add_website_package' ) {
            $package_type = 'website';
        }
    }

    // 3) Otherwise infer from sfpp_section
    if ( ! $package_type && ! empty( $_POST['sfpp_section'] ) ) {
        $section = sanitize_key( $_POST['sfpp_section'] );
        if ( $section === 'hosting' ) {
            $package_type = 'hosting';
        } elseif ( $section === 'maintenance' ) {
            $package_type = 'maintenance';
        } else {
            $package_type = 'website';
        }
    }

    // Final fallback
    if ( ! $package_type ) {
        $package_type = 'website';
    }

    // Decide defaults per type
    if ( $package_type === 'hosting' ) {
        $default_name  = 'New Hosting Package';
        $billing_model = 'monthly';
        $section       = 'hosting';
    } elseif ( $package_type === 'maintenance' ) {
        $default_name  = 'New Maintenance Package';
        $billing_model = 'monthly';
        $section       = 'maintenance';
    } else {
        $default_name  = 'New Website Package';
        $billing_model = 'one_off';
        $section       = 'packages';
    }

    // Insert new placeholder row
    $wpdb->insert(
        $table,
        [
            'type'             => $package_type,
            'name'             => $default_name,
            'short_description'=> '',
            'base_price'       => 0,
            'currency'         => 'PHP',
            'group_label'      => '',
            'billing_model'    => $billing_model,
            'status'           => 'active',
            'schema_json'      => '{}',
            'created_at'       => current_time( 'mysql' ),
            'updated_at'       => current_time( 'mysql' ),
        ]
    );

    $new_id = $wpdb->insert_id;

    // Redirect into the edit screen
    $url = add_query_arg(
        [
            'sfpp_section' => $section,
            'sfpp_view'    => 'edit',
            'package_id'   => $new_id,
            'sfpp_notice'  => 'package_created',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * SAVE (Website / Hosting / Maintenance)
 */
function sfpp_action_save_package() {

    if ( empty( $_POST['sfpp_save_package_nonce'] ) ||
         ! wp_verify_nonce( $_POST['sfpp_save_package_nonce'], 'sfpp_save_package' ) ) {
        return;
    }

    if ( empty( $_POST['package_id'] ) ) {
        return;
    }

    global $wpdb;
    $table = 'sf_packages';
    $id    = (int) $_POST['package_id'];

    // Load existing row
    $row = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
    );

    if ( ! $row ) {
        return;
    }

    $type = $row->type;

    // Pick schema based on type
    if ( $type === 'hosting' ) {
        $schema_def = function_exists( 'sfpp_get_hosting_package_schema' ) ? sfpp_get_hosting_package_schema() : [];
    } elseif ( $type === 'maintenance' ) {
        $schema_def = function_exists( 'sfpp_get_maintenance_package_schema' ) ? sfpp_get_maintenance_package_schema() : [];
    } else {
        $schema_def = function_exists( 'sfpp_get_website_package_schema' ) ? sfpp_get_website_package_schema() : [];
    }

    // Basic fields
    $name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
    $description = sanitize_textarea_field( wp_unslash( $_POST['short_description'] ?? '' ) );
    $group       = sanitize_text_field( wp_unslash( $_POST['group_label'] ?? '' ) );
    $price       = floatval( $_POST['base_price'] ?? 0 );
    $status      = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );

    // Billing & currency – preserved unless explicitly sent
    $billing  = sanitize_text_field( wp_unslash( $_POST['billing_model'] ?? $row->billing_model ) );
    $currency = sanitize_text_field( wp_unslash( $_POST['currency']      ?? $row->currency ) );

    // Raw posted schema data
    $posted_schema = isset( $_POST['schema'] ) && is_array( $_POST['schema'] )
        ? wp_unslash( $_POST['schema'] )
        : [];

    // Build clean schema array
    $clean_schema = [];

    if ( ! empty( $schema_def['groups'] ) ) {
        foreach ( $schema_def['groups'] as $group_def ) {
            if ( empty( $group_def['fields'] ) ) {
                continue;
            }

            foreach ( $group_def['fields'] as $field ) {
                if ( empty( $field['key'] ) ) {
                    continue;
                }

                $key     = $field['key'];
                $default = $field['default'] ?? '';

                $value = sfpp_schema_get_value( $posted_schema, $key, $default );
                sfpp_schema_set_value( $clean_schema, $key, $value );
            }
        }
    }

    // Save row
    $wpdb->update(
        $table,
        [
            'name'             => $name,
            'short_description'=> $description,
            'group_label'      => $group,
            'billing_model'    => $billing,
            'base_price'       => $price,
            'currency'         => $currency,
            'status'           => $status,
            'schema_json'      => wp_json_encode( $clean_schema ),
            'updated_at'       => current_time( 'mysql' ),
        ],
        [ 'id' => $id ]
    );

    // Redirect back to correct section
    $section = $_POST['sfpp_section'] ?? (
        $type === 'hosting' ? 'hosting' :
        ($type === 'maintenance' ? 'maintenance' : 'packages')
    );

    $url = add_query_arg(
        [
            'sfpp_section' => $section,
            'sfpp_notice'  => 'package_saved',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * CLONE PACKAGE (any type)
 */
function sfpp_action_clone_package() {

    if ( empty( $_POST['sfpp_clone_package_nonce'] ) ||
         empty( $_POST['package_id'] ) ) {
        return;
    }

    $id = (int) $_POST['package_id'];

    if ( ! wp_verify_nonce( $_POST['sfpp_clone_package_nonce'], 'sfpp_clone_package_' . $id ) ) {
        return;
    }

    global $wpdb;
    $table = 'sf_packages';

    $original = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
        ARRAY_A
    );

    if ( ! $original ) {
        return;
    }

    unset( $original['id'] );

    $original['name']       = $original['name'] . ' (Copy)';
    $original['status']     = 'active';
    $original['created_at'] = current_time( 'mysql' );
    $original['updated_at'] = current_time( 'mysql' );

    $wpdb->insert( $table, $original );

    $section = $_POST['sfpp_section'] ?? 'packages';

    $url = add_query_arg(
        [
            'sfpp_section' => $section,
            'sfpp_notice'  => 'package_cloned',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * ARCHIVE / UNARCHIVE HANDLERS
 */
function sfpp_action_archive_package() {
    sfpp_update_package_status_and_redirect( 'archived', 'package_archived' );
}

function sfpp_action_unarchive_package() {
    sfpp_update_package_status_and_redirect( 'active', 'package_unarchived' );
}

function sfpp_update_package_status_and_redirect( $new_status, $notice_key ) {

    if ( empty( $_POST['sfpp_package_status_nonce'] ) ||
         empty( $_POST['package_id'] ) ) {
        return;
    }

    $id = (int) $_POST['package_id'];

    $expected_nonce = ( $new_status === 'archived' )
        ? 'sfpp_archive_package_' . $id
        : 'sfpp_unarchive_package_' . $id;

    if ( ! wp_verify_nonce( $_POST['sfpp_package_status_nonce'], $expected_nonce ) ) {
        return;
    }

    global $wpdb;
    $table = 'sf_packages';

    $wpdb->update(
        $table,
        [
            'status'     => $new_status,
            'updated_at' => current_time( 'mysql' ),
        ],
        [ 'id' => $id ]
    );

    $section = $_POST['sfpp_section'] ?? 'packages';

    $url = add_query_arg(
        [
            'sfpp_section' => $section,
            'sfpp_notice'  => $notice_key,
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}
