<?php
// ui/Views/extra-edit.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load schema form renderer
require_once __DIR__ . '/../schema-form.php';

$extra_id = isset( $_GET['extra_id'] ) ? $_GET['extra_id'] : 0;
$is_new = ( 'new' === $extra_id );

// Load extra if editing
$extra = null;
if ( ! $is_new && $extra_id ) {
    $extra_id = (int) $extra_id;
    $extra = function_exists( 'sfpp_get_extra' ) ? sfpp_get_extra( $extra_id ) : null;

    if ( ! $extra ) : ?>
        <div class="sfpp-section sfpp-section--extras-edit">
            <h2>Edit Extra</h2>
            <p>Extra not found.</p>
        </div>
        <?php
        return;
    endif;
}

// Get schema definition
$schema_def = function_exists( 'sfpp_get_extras_schema' ) ? sfpp_get_extras_schema() : [];
$groups = ( isset( $schema_def['groups'] ) && is_array( $schema_def['groups'] ) ) ? $schema_def['groups'] : [];

// Decode schema JSON
$schema_data = [];
if ( $extra && ! empty( $extra->schema_json ) ) {
    $decoded = json_decode( $extra->schema_json, true );
    if ( is_array( $decoded ) ) {
        $schema_data = $decoded;
    }
}

// Basic fields (top-level columns)
$name        = $is_new ? '' : ( $extra->name ?? '' );
$description = $is_new ? '' : ( $extra->description ?? '' );
$price       = $is_new ? 0 : ( $extra->base_price ?? 0 );
$status      = $is_new ? 'active' : ( $extra->status ?? 'active' );

// Back link base URL (strip edit params)
$back_url = add_query_arg(
    'sfpp_section',
    'extras',
    remove_query_arg( [ 'sfpp_view', 'extra_id', 'sfpp_notice' ] )
);

$heading = $is_new ? 'Add New Extra' : 'Edit Extra';
?>
<div class="sfpp-section sfpp-section--extras-edit">
    <h2><?php echo esc_html( $heading ); ?></h2>

    <form method="post" class="sfpp-form">
        <?php wp_nonce_field( 'sfpp_save_extra', 'sfpp_save_extra_nonce' ); ?>
        <input type="hidden" name="sfpp_action" value="save_extra">
        <?php if ( ! $is_new ) : ?>
            <input type="hidden" name="extra_id" value="<?php echo esc_attr( $extra_id ); ?>">
        <?php endif; ?>
        <input type="hidden" name="sfpp_section" value="extras">

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
                        <label for="sfpp_extra_name">Name</label>
                        <input type="text" id="sfpp_extra_name" name="name" class="regular-text"
                               value="<?php echo esc_attr( $name ); ?>" required>
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_extra_description">Short Description</label>
                        <textarea id="sfpp_extra_description" name="description" rows="3" class="large-text"><?php
                            echo esc_textarea( $description );
                        ?></textarea>
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_extra_price">Base Price</label>
                        <input type="number" step="0.01" id="sfpp_extra_price" name="base_price"
                               value="<?php echo esc_attr( $price ); ?>">
                        <p class="description">Base price for this extra.</p>
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_extra_status">Status</label>
                        <select id="sfpp_extra_status" name="status">
                            <option value="active"   <?php selected( $status, 'active' ); ?>>Active</option>
                            <option value="inactive" <?php selected( $status, 'inactive' ); ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- SCHEMA-DRIVEN TABS -->
                <?php
                if ( function_exists( 'sfpp_render_schema_groups' ) ) {
                    sfpp_render_schema_groups( $schema_def, $schema_data );
                }
                ?>
            </div>
        </div>

        <!-- Save Button -->
        <p>
            <button type="submit" class="sfpp-button sfpp-button--primary">
                Save Extra
            </button>
            <a href="<?php echo esc_url( $back_url ); ?>" class="sfpp-button">
                Cancel
            </a>
        </p>
    </form>
</div>
