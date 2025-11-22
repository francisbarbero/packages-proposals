<?php
/**
 * Simplified Front-end Action Handlers
 * Uses the new simplified package management functions
 */

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

    // Only logged-in users can use the system
    if ( ! is_user_logged_in() ) {
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

    // Section whitelist guard
    $section = isset( $_POST['sfpp_section'] ) ? sanitize_key( $_POST['sfpp_section'] ) : '';

    if ( ! in_array(
            $section,
            [ 'packages', 'hosting', 'maintenance', 'proposals', 'assets' ],
            true
        ) ) {
        return;
    }

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

        // Proposal actions
        case 'add_proposal':
            sfpp_action_add_proposal();
            break;

        case 'save_proposal':
            sfpp_action_save_proposal();
            break;

        case 'clone_proposal':
            sfpp_action_clone_proposal();
            break;

        case 'archive_proposal':
            sfpp_action_archive_proposal();
            break;

        case 'unarchive_proposal':
            sfpp_action_unarchive_proposal();
            break;

        case 'add_packages_to_proposal':
            sfpp_action_add_packages_to_proposal();
            break;

        // Asset actions
        case 'add_asset':
            sfpp_action_add_asset();
            break;

        case 'save_asset':
            sfpp_action_save_asset();
            break;

        case 'delete_asset':
            sfpp_action_delete_asset();
            break;
    }
}

/**
 * CREATE A NEW PACKAGE (Website / Hosting / Maintenance)
 * Uses simplified package management functions.
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

    // Add action to POST data for type determination
    $_POST['sfpp_action'] = $action;

    // Create package using simplified functions
    $package_id = sfpp_create_package_from_request( $_POST );

    if ( ! $package_id ) {
        return;
    }

    // Get the package to determine redirect section
    $package = sfpp_get_package( $package_id );
    $section = sfpp_get_redirect_section_for_type( $package->type );

    // Redirect into the edit screen
    $url = add_query_arg(
        [
            'sfpp_section' => $section,
            'sfpp_view'    => 'edit',
            'package_id'   => $package_id,
            'sfpp_notice'  => 'package_created',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * SAVE (Website / Hosting / Maintenance)
 * Uses simplified package management functions.
 */
function sfpp_action_save_package() {

    if ( empty( $_POST['sfpp_save_package_nonce'] ) ||
         ! wp_verify_nonce( $_POST['sfpp_save_package_nonce'], 'sfpp_save_package' ) ) {
        return;
    }

    if ( empty( $_POST['package_id'] ) ) {
        return;
    }

    $id = (int) $_POST['package_id'];

    // Update package using simplified functions
    $success = sfpp_update_package_from_request( $id, $_POST );

    if ( ! $success ) {
        return;
    }

    // Get the package to determine redirect section
    $package = sfpp_get_package( $id );
    $section = sfpp_get_redirect_section_for_type( $package->type );

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
 * CLONE A PACKAGE
 * Uses simplified package management functions.
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

    // Clone package using simplified functions
    $cloned_id = sfpp_clone_package( $id );

    if ( ! $cloned_id ) {
        return;
    }

    // Get the cloned package to determine redirect section
    $package = sfpp_get_package( $cloned_id );
    $section = sfpp_get_redirect_section_for_type( $package->type );

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
 * ARCHIVE A PACKAGE
 */
function sfpp_action_archive_package() {
    sfpp_update_package_status_and_redirect( 'archived', 'package_archived' );
}

/**
 * UNARCHIVE A PACKAGE
 */
function sfpp_action_unarchive_package() {
    sfpp_update_package_status_and_redirect( 'active', 'package_unarchived' );
}

/**
 * Update package status and redirect.
 */
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

    // Update package status using simplified functions
    $success = ( $new_status === 'archived' )
        ? sfpp_archive_package( $id )
        : sfpp_unarchive_package( $id );

    if ( ! $success ) {
        return;
    }

    // Get updated package to determine section
    $package = sfpp_get_package( $id );
    $section = $package ? sfpp_get_redirect_section_for_type( $package->type ) : 'packages';

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

/**
 * CREATE A NEW PROPOSAL
 * Uses simplified proposal management functions.
 */
function sfpp_action_add_proposal() {

    if ( empty( $_POST['sfpp_add_proposal_nonce'] ) ||
         ! wp_verify_nonce( $_POST['sfpp_add_proposal_nonce'], 'sfpp_add_proposal' ) ) {
        return;
    }

    // Create proposal using simplified functions
    $proposal_id = sfpp_create_proposal_from_request( $_POST );

    if ( ! $proposal_id ) {
        return;
    }

    // Redirect to the edit screen
    $url = add_query_arg(
        [
            'sfpp_section' => 'proposals',
            'sfpp_view'    => 'edit',
            'proposal_id'  => $proposal_id,
            'sfpp_notice'  => 'proposal_created',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * SAVE PROPOSAL
 * Uses simplified proposal management functions.
 */
function sfpp_action_save_proposal() {

    if ( empty( $_POST['sfpp_save_proposal_nonce'] ) ||
         ! wp_verify_nonce( $_POST['sfpp_save_proposal_nonce'], 'sfpp_save_proposal' ) ) {
        return;
    }

    if ( empty( $_POST['proposal_id'] ) ) {
        return;
    }

    $id = (int) $_POST['proposal_id'];

    // Update proposal using simplified functions
    $success = sfpp_update_proposal_from_request( $id, $_POST );

    if ( ! $success ) {
        return;
    }

    $url = add_query_arg(
        [
            'sfpp_section' => 'proposals',
            'sfpp_view'    => 'edit',
            'proposal_id'  => $id,
            'sfpp_notice'  => 'proposal_saved',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * CLONE A PROPOSAL
 * Uses simplified proposal management functions.
 */
function sfpp_action_clone_proposal() {

    if ( empty( $_POST['sfpp_clone_proposal_nonce'] ) ||
         empty( $_POST['proposal_id'] ) ) {
        return;
    }

    $id = (int) $_POST['proposal_id'];

    if ( ! wp_verify_nonce( $_POST['sfpp_clone_proposal_nonce'], 'sfpp_clone_proposal_' . $id ) ) {
        return;
    }

    // Clone proposal using simplified functions
    $cloned_id = sfpp_clone_proposal( $id );

    if ( ! $cloned_id ) {
        return;
    }

    $url = add_query_arg(
        [
            'sfpp_section' => 'proposals',
            'sfpp_view'    => 'edit',
            'proposal_id'  => $cloned_id,
            'sfpp_notice'  => 'proposal_cloned',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * ARCHIVE A PROPOSAL
 */
function sfpp_action_archive_proposal() {
    sfpp_update_proposal_status_and_redirect( 'archived', 'proposal_archived' );
}

/**
 * UNARCHIVE A PROPOSAL
 */
function sfpp_action_unarchive_proposal() {
    sfpp_update_proposal_status_and_redirect( 'draft', 'proposal_unarchived' );
}

/**
 * Update proposal status and redirect.
 */
function sfpp_update_proposal_status_and_redirect( $new_status, $notice_key ) {

    if ( empty( $_POST['sfpp_proposal_status_nonce'] ) ||
         empty( $_POST['proposal_id'] ) ) {
        return;
    }

    $id = (int) $_POST['proposal_id'];

    $expected_nonce = ( $new_status === 'archived' )
        ? 'sfpp_archive_proposal_' . $id
        : 'sfpp_unarchive_proposal_' . $id;

    if ( ! wp_verify_nonce( $_POST['sfpp_proposal_status_nonce'], $expected_nonce ) ) {
        return;
    }

    // Update proposal status using simplified functions
    $success = ( $new_status === 'archived' )
        ? sfpp_archive_proposal( $id )
        : sfpp_unarchive_proposal( $id );

    if ( ! $success ) {
        return;
    }

    $url = add_query_arg(
        [
            'sfpp_section' => 'proposals',
            'sfpp_notice'  => $notice_key,
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * Add selected packages as new proposal items.
 */
function sfpp_action_add_packages_to_proposal() {
    if ( empty( $_POST['sfpp_add_packages_nonce'] ) ||
         ! wp_verify_nonce( $_POST['sfpp_add_packages_nonce'], 'sfpp_add_packages_to_proposal' ) ) {
        return;
    }

    if ( empty( $_POST['proposal_id'] ) ) {
        return;
    }

    $proposal_id = (int) $_POST['proposal_id'];
    if ( $proposal_id <= 0 ) {
        return;
    }

    $package_ids = [];
    if ( ! empty( $_POST['package_ids'] ) && is_array( $_POST['package_ids'] ) ) {
        $package_ids = array_map( 'intval', $_POST['package_ids'] );
    }

    if ( ! empty( $package_ids ) && function_exists( 'sfpp_add_packages_to_proposal_items' ) ) {
        sfpp_add_packages_to_proposal_items( $proposal_id, $package_ids );
    }

    // Redirect back to the same proposal edit screen.
    $url = add_query_arg(
        [
            'sfpp_section' => 'proposals',
            'sfpp_view'    => 'edit',
            'proposal_id'  => $proposal_id,
            'sfpp_notice'  => 'proposal_packages_added',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * CREATE A NEW ASSET
 * Uses simplified asset management functions.
 */
function sfpp_action_add_asset() {

    if ( empty( $_POST['sfpp_add_asset_nonce'] ) ||
         ! wp_verify_nonce( $_POST['sfpp_add_asset_nonce'], 'sfpp_add_asset' ) ) {
        return;
    }

    // Create asset using simplified functions
    $asset_id = sfpp_create_asset_from_request( $_POST );

    if ( ! $asset_id ) {
        return;
    }

    $url = add_query_arg(
        [
            'sfpp_section' => 'assets',
            'sfpp_notice'  => 'asset_created',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * SAVE ASSET
 * Uses simplified asset management functions.
 */
function sfpp_action_save_asset() {

    if ( empty( $_POST['sfpp_save_asset_nonce'] ) ||
         ! wp_verify_nonce( $_POST['sfpp_save_asset_nonce'], 'sfpp_save_asset' ) ) {
        return;
    }

    if ( empty( $_POST['asset_id'] ) ) {
        return;
    }

    $id = (int) $_POST['asset_id'];

    // Update asset using simplified functions
    $success = sfpp_update_asset_from_request( $id, $_POST );

    if ( ! $success ) {
        return;
    }

    $url = add_query_arg(
        [
            'sfpp_section' => 'assets',
            'sfpp_notice'  => 'asset_saved',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}

/**
 * DELETE AN ASSET
 */
function sfpp_action_delete_asset() {

    if ( empty( $_POST['sfpp_delete_asset_nonce'] ) ||
         empty( $_POST['asset_id'] ) ) {
        return;
    }

    $id = (int) $_POST['asset_id'];

    if ( ! wp_verify_nonce( $_POST['sfpp_delete_asset_nonce'], 'sfpp_delete_asset_' . $id ) ) {
        return;
    }

    // Delete asset using simplified functions
    $success = sfpp_delete_asset( $id );

    if ( ! $success ) {
        return;
    }

    $url = add_query_arg(
        [
            'sfpp_section' => 'assets',
            'sfpp_notice'  => 'asset_deleted',
        ],
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect( $url );
    exit;
}