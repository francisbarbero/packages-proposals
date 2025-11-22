<?php
// ui/Views/proposal-edit.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$proposal_id = isset( $_GET['proposal_id'] ) ? (int) $_GET['proposal_id'] : 0;

// Fetch proposal and items
$proposal = ( $proposal_id && function_exists( 'sfpp_get_proposal' ) )
    ? sfpp_get_proposal( $proposal_id )
    : null;

if ( ! $proposal ) : ?>
    <div class="sfpp-section sfpp-section--proposals-edit">
        <h2>Edit Proposal</h2>
        <p>Proposal not found.</p>
        <p>
            <a href="<?php echo esc_url( add_query_arg( 'sfpp_section', 'proposals', remove_query_arg( [ 'sfpp_view', 'proposal_id', 'sfpp_notice' ] ) ) ); ?>" class="sfpp-link">
                Back to Proposals
            </a>
        </p>
    </div>
    <?php
    return;
endif;

// Get proposal items
$items = function_exists( 'sfpp_get_proposal_items' ) ? sfpp_get_proposal_items( $proposal_id ) : [];

// Back link
$back_url = add_query_arg(
    'sfpp_section',
    'proposals',
    remove_query_arg( [ 'sfpp_view', 'proposal_id', 'sfpp_notice' ] )
);

// Notice
$notice = isset( $_GET['sfpp_notice'] ) ? sanitize_key( $_GET['sfpp_notice'] ) : '';
?>
<div class="sfpp-section sfpp-section--proposals-edit">
    <h2>Edit Proposal</h2>

    <?php if ( 'proposal_saved' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Proposal saved successfully.
        </div>
    <?php elseif ( 'proposal_packages_added' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Selected packages were added as line items.
        </div>
    <?php endif; ?>

    <form method="post" class="sfpp-form">
        <?php wp_nonce_field( 'sfpp_save_proposal', 'sfpp_save_proposal_nonce' ); ?>
        <input type="hidden" name="sfpp_action" value="save_proposal">
        <input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal_id ); ?>">
        <input type="hidden" name="sfpp_section" value="proposals">

        <div class="sfpp-tabs">
            <ul class="sfpp-tabs-nav">
                <li class="sfpp-tabs-nav-item is-active">
                    <a href="#" data-tab-target="general">General</a>
                </li>
                <li class="sfpp-tabs-nav-item">
                    <a href="#" data-tab-target="costs">Costs</a>
                </li>
                <li class="sfpp-tabs-nav-item">
                    <a href="#" data-tab-target="assets">Assets</a>
                </li>
            </ul>

            <div class="sfpp-tabs-panels">
                <!-- GENERAL TAB -->
                <div class="sfpp-tab-panel is-active" data-tab-id="general">
                    <h3 class="sfpp-tab-panel__title">General Information</h3>

                    <div class="sfpp-field">
                        <label for="sfpp_proposal_name">Proposal Name</label>
                        <input type="text" id="sfpp_proposal_name" name="name" class="regular-text"
                               value="<?php echo esc_attr( $proposal->name ?? '' ); ?>" required>
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_proposal_client">Client Name</label>
                        <input type="text" id="sfpp_proposal_client" name="client_name" class="regular-text"
                               value="<?php echo esc_attr( $proposal->client_name ?? '' ); ?>">
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_proposal_project">Project Name</label>
                        <input type="text" id="sfpp_proposal_project" name="project_name" class="regular-text"
                               value="<?php echo esc_attr( $proposal->project_name ?? '' ); ?>">
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_proposal_status">Status</label>
                        <select id="sfpp_proposal_status" name="status">
                            <option value="draft" <?php selected( $proposal->status ?? 'draft', 'draft' ); ?>>Draft</option>
                            <option value="sent" <?php selected( $proposal->status ?? 'draft', 'sent' ); ?>>Sent</option>
                            <option value="active" <?php selected( $proposal->status ?? 'draft', 'active' ); ?>>Active</option>
                            <option value="accepted" <?php selected( $proposal->status ?? 'draft', 'accepted' ); ?>>Accepted</option>
                            <option value="rejected" <?php selected( $proposal->status ?? 'draft', 'rejected' ); ?>>Rejected</option>
                            <option value="archived" <?php selected( $proposal->status ?? 'draft', 'archived' ); ?>>Archived</option>
                        </select>
                    </div>

                    <div class="sfpp-field">
                        <label for="sfpp_proposal_currency">Currency</label>
                        <input type="text" id="sfpp_proposal_currency" name="currency" class="small-text"
                               value="<?php echo esc_attr( $proposal->currency ?? 'PHP' ); ?>">
                        <p class="description">Example: PHP, USD, EUR</p>
                    </div>

                    <div class="sfpp-field">
                        <label>Total Amount (Calculated)</label>
                        <p class="sfpp-field-value">
                            <?php
                            $currency = $proposal->currency ?? 'PHP';
                            $total    = number_format_i18n( (float) ( $proposal->total_amount ?? 0 ), 2 );
                            echo esc_html( $currency . ' ' . $total );
                            ?>
                        </p>
                        <p class="description">This is automatically calculated from line items in the Costs tab.</p>
                    </div>
                </div>

                <!-- COSTS TAB -->
                <div class="sfpp-tab-panel" data-tab-id="costs">
                    <h3 class="sfpp-tab-panel__title">Costs & Line Items</h3>

                    <h4>Line Items</h4>

                    <table class="sfpp-table sfpp-table--items">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Existing items
                            foreach ( $items as $index => $item ) : ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[<?php echo $index; ?>][id]" value="<?php echo (int) $item->id; ?>">
                                        <input type="text" name="items[<?php echo $index; ?>][name]" class="regular-text"
                                               value="<?php echo esc_attr( $item->name ?? '' ); ?>">
                                    </td>
                                    <td>
                                        <textarea name="items[<?php echo $index; ?>][description]" rows="2" class="large-text"><?php
                                            echo esc_textarea( $item->description ?? '' );
                                        ?></textarea>
                                    </td>
                                    <td>
                                        <input type="number" name="items[<?php echo $index; ?>][quantity]" class="small-text"
                                               value="<?php echo esc_attr( $item->quantity ?? 1 ); ?>" min="1">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="items[<?php echo $index; ?>][unit_price]" class="small-text"
                                               value="<?php echo esc_attr( $item->unit_price ?? 0 ); ?>" min="0">
                                    </td>
                                    <td>
                                        <?php echo esc_html( number_format_i18n( (float) ( $item->total_price ?? 0 ), 2 ) ); ?>
                                    </td>
                                </tr>
                            <?php endforeach;

                            // Blank rows for adding new items
                            $start_index = count( $items );
                            $extra_rows  = 3;

                            for ( $i = 0; $i < $extra_rows; $i++ ) :
                                $index = $start_index + $i;
                                ?>
                                <tr>
                                    <td>
                                        <input type="text" name="items[<?php echo $index; ?>][name]" class="regular-text" value="">
                                    </td>
                                    <td>
                                        <textarea name="items[<?php echo $index; ?>][description]" rows="2" class="large-text"></textarea>
                                    </td>
                                    <td>
                                        <input type="number" name="items[<?php echo $index; ?>][quantity]" class="small-text" value="1" min="1">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="items[<?php echo $index; ?>][unit_price]" class="small-text" value="0" min="0">
                                    </td>
                                    <td>
                                        —
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>

                    <p class="description">To delete a line, clear the Name field and save.</p>
                </div>

                <!-- ASSETS TAB -->
                <div class="sfpp-tab-panel" data-tab-id="assets">
                    <h3 class="sfpp-tab-panel__title">Assets</h3>
                    <p class="description">Select which assets to attach to this proposal (letterheads, guides, terms, signature blocks, etc.).</p>

                    <?php
                    // Get all assets
                    $all_assets = function_exists( 'sfpp_get_assets' ) ? sfpp_get_assets() : [];

                    // Get currently linked asset IDs
                    $linked_asset_ids = function_exists( 'sfpp_get_proposal_asset_ids' ) ? sfpp_get_proposal_asset_ids( $proposal_id ) : [];

                    // Group assets by type
                    $assets_by_type = [
                        'letterhead_pdf' => [],
                        'about_pdf' => [],
                        'terms_pdf' => [],
                        'guide_html' => [],
                        'signature_block' => [],
                    ];

                    foreach ( $all_assets as $asset ) {
                        $asset_type = $asset->asset_type ?? '';
                        if ( isset( $assets_by_type[ $asset_type ] ) ) {
                            $assets_by_type[ $asset_type ][] = $asset;
                        }
                    }

                    // Type labels
                    $type_labels = [
                        'letterhead_pdf' => 'Letterhead PDFs',
                        'about_pdf' => 'About PDFs',
                        'terms_pdf' => 'Terms & Conditions PDFs',
                        'guide_html' => 'Guides (HTML)',
                        'signature_block' => 'Signature Blocks',
                    ];

                    // Render checkboxes grouped by type
                    foreach ( $assets_by_type as $type => $assets ) :
                        if ( empty( $assets ) ) {
                            continue;
                        }
                        ?>
                        <div class="sfpp-field">
                            <label><?php echo esc_html( $type_labels[ $type ] ); ?></label>
                            <?php foreach ( $assets as $asset ) : ?>
                                <label class="sfpp-checkbox-label">
                                    <input type="checkbox" name="asset_ids[]" value="<?php echo (int) $asset->id; ?>"
                                        <?php checked( in_array( $asset->id, $linked_asset_ids, true ) ); ?>>
                                    <?php echo esc_html( $asset->name ?? '' ); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ( empty( $all_assets ) ) : ?>
                        <p><em>No assets available yet. <a href="<?php echo esc_url( add_query_arg( 'sfpp_section', 'assets', remove_query_arg( [ 'sfpp_view', 'proposal_id', 'sfpp_notice' ] ) ) ); ?>">Create assets</a> to attach them to proposals.</em></p>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <p>
            <button type="submit" class="sfpp-button sfpp-button--primary">
                Save Proposal
            </button>
            <a href="<?php echo esc_url( $back_url ); ?>"
               class="sfpp-button sfpp-button--secondary">
                Back to list
            </a>
        </p>
    </form>

    <!-- Add Packages as Line Items (separate form) -->
    <div class="sfpp-add-packages-section">
        <h3>Add Packages</h3>
        <p class="description">
            Select existing packages to add as new line items to this proposal.
            Each selected package will become a 1× line item using its name, short description and base price.
        </p>

        <?php
        // Fetch available packages.
        $all_packages = function_exists( 'sfpp_get_packages' ) ? sfpp_get_packages() : [];
        ?>

        <?php if ( ! empty( $all_packages ) ) : ?>
            <form method="post" class="sfpp-form sfpp-form--inline-block">
                <?php wp_nonce_field( 'sfpp_add_packages_to_proposal', 'sfpp_add_packages_nonce' ); ?>
                <input type="hidden" name="sfpp_action" value="add_packages_to_proposal">
                <input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal_id ); ?>">
                <input type="hidden" name="sfpp_section" value="proposals">

                <div class="sfpp-field">
                    <?php foreach ( $all_packages as $package ) : ?>
                        <label class="sfpp-checkbox-label">
                            <input type="checkbox" name="package_ids[]" value="<?php echo (int) $package->id; ?>">
                            <?php
                            echo esc_html( $package->name );
                            if ( ! empty( $package->group_label ) ) {
                                echo ' (' . esc_html( $package->group_label ) . ')';
                            }
                            ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>

                <p>
                    <button type="submit" class="sfpp-button sfpp-button--secondary">
                        Add selected packages as line items
                    </button>
                </p>
            </form>
        <?php else : ?>
            <p><em>No packages available yet.</em></p>
        <?php endif; ?>
    </div>

</div>
