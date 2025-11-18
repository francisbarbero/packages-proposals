<?php
// ui/Views/package-edit.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load schema form renderer (uses helpers defined in main plugin file).
require_once __DIR__ . '/../schema-form.php';

$package_id = isset( $_GET['package_id'] ) ? (int) $_GET['package_id'] : 0;

// Use generic helper so we can load any type (website, hosting, etc.)
$package = ( $package_id && function_exists( 'sfpp_get_package_by_id' ) )
    ? sfpp_get_package_by_id( $package_id )
    : null;

if ( ! $package ) : ?>
    <div class="sfpp-section sfpp-section--packages-edit">
        <h2>Edit Package</h2>
        <p>Package not found.</p>
    </div>
    <?php
    return;
endif;

// Determine type + labels + schema.
$type = isset( $package->type ) ? $package->type : 'website';

if ( 'hosting' === $type ) {
    $section    = 'hosting';
    $heading    = 'Edit Hosting Package';
    $price_hint = 'Monthly hosting fee in PHP.';
    $schema_def = function_exists( 'sfpp_get_hosting_package_schema' ) ? sfpp_get_hosting_package_schema() : [];
} elseif ( 'maintenance' === $type ) {
    $section    = 'maintenance';
    $heading    = 'Edit Maintenance Package';
    $price_hint = 'Monthly maintenance fee in PHP.';
    $schema_def = function_exists( 'sfpp_get_maintenance_package_schema' ) ? sfpp_get_maintenance_package_schema() : [];
}
 else {
    // Default to website.
    $section    = 'packages';
    $heading    = 'Edit Website Package';
    $price_hint = 'One-off project fee in PHP.';
    $schema_def = function_exists( 'sfpp_get_website_package_schema' ) ? sfpp_get_website_package_schema() : [];
}

$groups = ( isset( $schema_def['groups'] ) && is_array( $schema_def['groups'] ) )
    ? $schema_def['groups']
    : [];

// Decode schema JSON.
$schema_data = [];
if ( ! empty( $package->schema_json ) ) {
    $decoded = json_decode( $package->schema_json, true );
    if ( is_array( $decoded ) ) {
        $schema_data = $decoded;
    }
}

// Basic fields (top-level columns).
$name        = $package->name ?? '';
$description = $package->short_description ?? '';
$group       = $package->group_label ?? '';
$price       = $package->base_price ?? 0;
$status      = $package->status ?? 'active';

// We still track these in DB but keep them invisible in the UI.
$billing  = $package->billing_model ?: ( 'hosting' === $type ? 'monthly' : 'one_off' );
$currency = $package->currency ?: 'PHP';

// Back link base URL (strip edit params).
$back_url = add_query_arg(
    'sfpp_section',
    $section,
    remove_query_arg( [ 'sfpp_view', 'package_id', 'sfpp_notice' ] )
);
?>
<div class="sfpp-section sfpp-section--packages-edit">
    <h2><?php echo esc_html( $heading ); ?></h2>

    <form method="post" class="sfpp-form">
        <?php wp_nonce_field( 'sfpp_save_package', 'sfpp_save_package_nonce' ); ?>
        <input type="hidden" name="sfpp_action" value="save_package">
        <input type="hidden" name="package_id" value="<?php echo esc_attr( $package_id ); ?>">
        <input type="hidden" name="sfpp_section" value="<?php echo esc_attr( $section ); ?>">

        <!-- Preserve billing model & currency silently -->
        <input type="hidden" name="billing_model" value="<?php echo esc_attr( $billing ); ?>">
        <input type="hidden" name="currency" value="<?php echo esc_attr( $currency ); ?>">

        <div class="sfpp-tabs">
            <ul class="sfpp-tabs-nav">
                <li class="sfpp-tabs-nav-item is-active">
                    <a href="#" data-tab-target="general">General</a>
                </li>
                <?php foreach ( $groups as $group_def ) :
                    $gid   = $group_def['id'] ?? '';
                    $label = $group_def['label'] ?? $gid;
                    if ( ! $gid ) {
                        continue;
                    }
                    ?>
                    <li class="sfpp-tabs-nav-item">
                        <a href="#" data-tab-target="<?php echo esc_attr( $gid ); ?>">
                            <?php echo esc_html( $label ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="sfpp-tabs-panels">
                <!-- GENERAL TAB -->
                <div class="sfpp-tab-panel is-active" data-tab-id="general">
                    <div class="sfpp-field">
                        <label for="sfpp_pkg_name">Name</label>
                        <input type="text" id="sfpp_pkg_name" name="name" class="regular-text"
                               value="<?php echo esc_attr( $name ); ?>" required>
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_pkg_description">Short description</label>
                        <textarea id="sfpp_pkg_description" name="short_description" rows="3" class="large-text"><?php
                            echo esc_textarea( $description );
                        ?></textarea>
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_pkg_group">Group (Range/Collection)</label>
                        <input type="text" id="sfpp_pkg_group" name="group_label" class="regular-text"
                               value="<?php echo esc_attr( $group ); ?>">
                        <p class="description">Example: Value Range, Starter Collection, SME Collection.</p>
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_pkg_price">Price (PHP)</label>
                        <input type="number" step="0.01" id="sfpp_pkg_price" name="base_price"
                               value="<?php echo esc_attr( $price ); ?>">
                        <p class="description"><?php echo esc_html( $price_hint ); ?></p>
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_pkg_status">Status</label>
                        <select id="sfpp_pkg_status" name="status">
                            <option value="active"   <?php selected( $status, 'active' ); ?>>Active</option>
                            <option value="archived" <?php selected( $status, 'archived' ); ?>>Archived</option>
                        </select>
                    </div>
                </div>

                <!-- SCHEMA GROUP TABS -->
                <?php
                if ( function_exists( 'sfpp_render_schema_groups' ) ) {
                    $schema_def_safe  = is_array( $schema_def ) ? $schema_def : [];
                    $schema_data_safe = is_array( $schema_data ) ? $schema_data : [];
                    sfpp_render_schema_groups( $schema_def_safe, $schema_data_safe );
                }
                ?>
            </div><!-- .sfpp-tabs-panels -->
        </div><!-- .sfpp-tabs -->

        <p>
            <button type="submit" class="sfpp-button sfpp-button--primary">
                Save package
            </button>
            <a href="<?php echo esc_url( $back_url ); ?>"
               class="sfpp-button sfpp-button--secondary">
                Back to list
            </a>
        </p>
    </form>
</div>
