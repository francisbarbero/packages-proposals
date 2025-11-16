<?php
// ui/Views/package-edit.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$package_id = isset( $_GET['package_id'] ) ? (int) $_GET['package_id'] : 0;
$package    = ( $package_id && function_exists( 'sfpp_get_website_package' ) )
    ? sfpp_get_website_package( $package_id )
    : null;

if ( ! $package ) : ?>
    <div class="sfpp-section sfpp-section--packages-edit">
        <h2>Edit Website Package</h2>
        <p>Package not found.</p>
    </div>
    <?php
    return;
endif;

// Decode schema JSON (website-specific details).
$schema = [];
if ( ! empty( $package->schema_json ) ) {
    $decoded = json_decode( $package->schema_json, true );
    if ( is_array( $decoded ) ) {
        $schema = $decoded;
    }
}

// Basic fields.
$name        = $package->name;
$description = $package->short_description;
$group       = $package->group_label;
$billing     = $package->billing_model;
$price       = $package->base_price;
$currency    = $package->currency;
$status      = $package->status;

// Website-specific schema fields.
$pages_included    = isset( $schema['pages']['included_count'] ) ? (int) $schema['pages']['included_count'] : 0;
$pages_description = isset( $schema['pages']['included_description'] ) ? $schema['pages']['included_description'] : '';
$design_level      = isset( $schema['design']['level'] ) ? $schema['design']['level'] : '';
$content_level     = isset( $schema['content']['involvement'] ) ? $schema['content']['involvement'] : 'none';
$notes_internal    = isset( $schema['notes_internal'] ) ? $schema['notes_internal'] : '';
?>
<div class="sfpp-section sfpp-section--packages-edit">
    <h2>Edit Website Package</h2>

  



<form method="post" class="sfpp-form">
    <?php wp_nonce_field( 'sfpp_save_package', 'sfpp_save_package_nonce' ); ?>
    <input type="hidden" name="sfpp_action" value="save_package">
    <input type="hidden" name="package_id" value="<?php echo esc_attr( $package_id ); ?>">

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
        <label for="sfpp_pkg_billing">Billing model</label>
        <select id="sfpp_pkg_billing" name="billing_model">
            <option value="one_off" <?php selected( $billing, 'one_off' ); ?>>One-off</option>
            <option value="monthly" <?php selected( $billing, 'monthly' ); ?>>Monthly</option>
            <option value="yearly"  <?php selected( $billing, 'yearly' ); ?>>Yearly</option>
            <option value="hybrid"  <?php selected( $billing, 'hybrid' ); ?>>Hybrid</option>
        </select>
    </div>

    <div class="sfpp-field">
        <label for="sfpp_pkg_price">Base price</label>
        <input type="number" step="0.01" id="sfpp_pkg_price" name="base_price"
               value="<?php echo esc_attr( $price ); ?>">
    </div>

    <div class="sfpp-field">
        <label for="sfpp_pkg_currency">Currency</label>
        <input type="text" id="sfpp_pkg_currency" name="currency" class="small-text"
               value="<?php echo esc_attr( $currency ); ?>">
    </div>

    <div class="sfpp-field">
        <label for="sfpp_pkg_status">Status</label>
        <select id="sfpp_pkg_status" name="status">
            <option value="active"   <?php selected( $status, 'active' ); ?>>Active</option>
            <option value="archived" <?php selected( $status, 'archived' ); ?>>Archived</option>
        </select>
    </div>

    <hr>

    <?php
    // Website-specific groups & fields from website-packages-schema.php
    if ( function_exists( 'sfpp_render_schema_groups' ) ) {
        sfpp_render_schema_groups( $schema_def, $schema_data );
    }
    ?>

    <p>
        <button type="submit" class="sfpp-button sfpp-button--primary">
            Save package
        </button>
        <a href="<?php echo esc_url( add_query_arg( 'sfpp_section', 'packages', remove_query_arg( [ 'sfpp_view', 'package_id', 'sfpp_notice' ] ) ) ); ?>"
           class="sfpp-button sfpp-button--secondary">
            Back to list
        </a>
    </p>
</form>



  
</div>
