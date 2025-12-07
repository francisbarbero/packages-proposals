<?php
// ui/Views/assets-list.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch Assets from the database.
$assets = function_exists( 'sfpp_get_assets' ) ? sfpp_get_assets() : [];

// Simple notice if something just happened.
$notice = isset( $_GET['sfpp_notice'] ) ? sanitize_key( $_GET['sfpp_notice'] ) : '';
?>
<div class="sfpp-section sfpp-section--assets">
    <h2>Assets</h2>

    <?php if ( 'asset_created' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Asset created successfully.
        </div>
    <?php elseif ( 'asset_saved' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Asset saved successfully.
        </div>
    <?php elseif ( 'asset_deleted' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Asset deleted successfully.
        </div>
    <?php endif; ?>

    <!-- Add Asset Form -->
    <form method="post" class="sfpp-add-form">
        <?php wp_nonce_field( 'sfpp_add_asset', 'sfpp_add_asset_nonce' ); ?>
        <input type="hidden" name="sfpp_action" value="add_asset" />
        <input type="hidden" name="sfpp_section" value="assets" />

        <div class="sfpp-field">
            <label for="sfpp_asset_name">Name</label>
            <input type="text" id="sfpp_asset_name" name="name" class="regular-text" required>
        </div>

        <div class="sfpp-field">
            <label for="sfpp_asset_slug">Slug</label>
            <input type="text" id="sfpp_asset_slug" name="slug" class="regular-text" required>
            <p class="description">Unique identifier (e.g., company-letterhead)</p>
        </div>

        <div class="sfpp-field">
            <label for="sfpp_asset_type">Type</label>
            <select id="sfpp_asset_type" name="asset_type" required>
                <option value="">Select Type</option>
                <option value="cover_page">Cover Page (PDF)</option>
                <option value="body_background">Body Background (PDF)</option>
                <option value="about_pdf">About PDF</option>
                <option value="terms_pdf">Terms & Conditions (PDF)</option>
                <option value="appendix_pdf">Appendix PDF</option>
            </select>
        </div>

        <div class="sfpp-field">
            <label for="sfpp_asset_attachment">Attachment ID</label>
            <input type="number" id="sfpp_asset_attachment" name="attachment_id" class="small-text">
            <p class="description">Media Library attachment ID (required for PDF-based types like letterhead_pdf, about_pdf, terms_pdf).</p>
        </div>

        <div class="sfpp-field">
            <label for="sfpp_asset_content">Content</label>
            <textarea id="sfpp_asset_content" name="content" rows="5" class="large-text"></textarea>
            <p class="description">Optional HTML or description. Required for HTML-based types (guide_html, signature_block).</p>
        </div>

        <button type="submit" class="sfpp-button sfpp-button--primary">
            Add Asset
        </button>
    </form>

    <!-- Assets List -->
    <?php if ( empty( $assets ) ) : ?>

        <p>No assets found yet.</p>

    <?php else : ?>

        <table class="sfpp-table sfpp-table--assets">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Type</th>
                    <th>Attachment ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $assets as $asset ) : ?>
                    <?php
                    // Define current asset types
                    $current_types = [
                        'cover_page'       => 'Cover Page (PDF)',
                        'body_background'  => 'Body Background (PDF)',
                        'about_pdf'        => 'About PDF',
                        'terms_pdf'        => 'Terms & Conditions (PDF)',
                        'appendix_pdf'     => 'Appendix PDF',
                    ];

                    $asset_type = $asset->asset_type ?? '';
                    ?>
                    <tr>
                        <td>
                            <form method="post" class="sfpp-inline-form">
                                <?php wp_nonce_field( 'sfpp_save_asset', 'sfpp_save_asset_nonce' ); ?>
                                <input type="hidden" name="sfpp_action" value="save_asset">
                                <input type="hidden" name="asset_id" value="<?php echo (int) $asset->id; ?>">
                                <input type="hidden" name="sfpp_section" value="assets">
                                <input type="hidden" name="slug" value="<?php echo esc_attr( $asset->slug ?? '' ); ?>">

                                <input type="text" name="name" class="regular-text"
                                       value="<?php echo esc_attr( $asset->name ?? '' ); ?>" required>
                        </td>
                        <td>
                            <?php echo esc_html( $asset->slug ?? '' ); ?>
                        </td>
                        <td>
                            <select name="asset_type" required>
                                <?php foreach ( $current_types as $type_value => $type_label ) : ?>
                                    <option value="<?php echo esc_attr( $type_value ); ?>" <?php selected( $asset_type, $type_value ); ?>>
                                        <?php echo esc_html( $type_label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="attachment_id" class="small-text"
                                   value="<?php echo esc_attr( $asset->attachment_id ?? '' ); ?>">
                        </td>
                        <td class="sfpp-table-actions">
                            <button type="submit" class="sfpp-button sfpp-button--small">Save</button>
                            </form>

                            <!-- Delete -->
                            <form method="post" class="sfpp-inline-form">
                                <?php wp_nonce_field( 'sfpp_delete_asset_' . $asset->id, 'sfpp_delete_asset_nonce' ); ?>
                                <input type="hidden" name="sfpp_action" value="delete_asset">
                                <input type="hidden" name="asset_id" value="<?php echo (int) $asset->id; ?>">
                                <input type="hidden" name="sfpp_section" value="assets">
                                <button type="submit" class="sfpp-link-button">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>
