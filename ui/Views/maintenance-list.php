<?php
// ui/Views/maintenance-list.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$packages = function_exists( 'sfpp_get_maintenance_packages' ) ? sfpp_get_maintenance_packages() : [];

$notice = isset( $_GET['sfpp_notice'] ) ? sanitize_key( $_GET['sfpp_notice'] ) : '';
?>
<div class="sfpp-section sfpp-section--maintenance">
    <h2>Maintenance Packages</h2>

    <?php if ( 'package_created' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            New Maintenance Package created with placeholder name.
        </div>
    <?php elseif ( 'package_saved' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Maintenance Package saved.
        </div>
    <?php elseif ( 'package_cloned' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Maintenance Package cloned.
        </div>
    <?php elseif ( 'package_archived' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Maintenance Package archived.
        </div>
    <?php elseif ( 'package_unarchived' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Maintenance Package unarchived.
        </div>
    <?php endif; ?>

    <form method="post" class="sfpp-add-form">
        <?php wp_nonce_field( 'sfpp_add_package', 'sfpp_add_package_nonce' ); ?>
        <input type="hidden" name="sfpp_action" value="add_package" />
        <input type="hidden" name="package_type" value="maintenance" />
        <input type="hidden" name="sfpp_section" value="maintenance" />
        <button type="submit" class="sfpp-button sfpp-button--primary">
            + Add new Maintenance Package
        </button>
    </form>

    <?php if ( empty( $packages ) ) : ?>

        <p>No Maintenance Packages found yet.</p>

    <?php else : ?>

        <table class="sfpp-table sfpp-table--packages">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Short description</th>
                    <th>Base price</th>
                    <th>Group</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $packages as $pkg ) : ?>
                    <tr>
                        <td><?php echo esc_html( $pkg->name ); ?></td>
                        <td><?php echo esc_html( $pkg->short_description ); ?></td>
                        <td>
                            <?php
                            $currency = $pkg->currency ?: 'PHP';
                            $price    = number_format_i18n( (float) $pkg->base_price, 2 );
                            echo esc_html( $currency . ' ' . $price );
                            ?>
                        </td>
                        <td><?php echo esc_html( $pkg->group_label ); ?></td>
                        <td class="sfpp-table-actions">
                            <?php
                            $base_url = remove_query_arg( [ 'sfpp_view', 'package_id', 'sfpp_notice', 'sfpp_new_id' ] );
                            $edit_url = add_query_arg(
                                [
                                    'sfpp_section' => 'maintenance',
                                    'sfpp_view'    => 'edit',
                                    'package_id'   => $pkg->id,
                                ],
                                $base_url
                            );
                            ?>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="sfpp-link">
                                Edit
                            </a>

                            <!-- Clone -->
                            <form method="post" class="sfpp-inline-form">
                                <?php wp_nonce_field( 'sfpp_clone_package_' . $pkg->id, 'sfpp_clone_package_nonce' ); ?>
                                <input type="hidden" name="sfpp_action" value="clone_package">
                                <input type="hidden" name="package_id" value="<?php echo (int) $pkg->id; ?>">
                                <input type="hidden" name="sfpp_section" value="maintenance">
                                <button type="submit" class="sfpp-link-button">Clone</button>
                            </form>

                            <!-- Archive / Unarchive -->
                            <form method="post" class="sfpp-inline-form">
                                <?php
                                $is_archived  = ( 'archived' === $pkg->status );
                                $nonce_action = $is_archived ? 'sfpp_unarchive_package_' . $pkg->id : 'sfpp_archive_package_' . $pkg->id;
                                $action_value = $is_archived ? 'unarchive_package' : 'archive_package';
                                ?>
                                <?php wp_nonce_field( $nonce_action, 'sfpp_package_status_nonce' ); ?>
                                <input type="hidden" name="sfpp_action" value="<?php echo esc_attr( $action_value ); ?>">
                                <input type="hidden" name="package_id" value="<?php echo (int) $pkg->id; ?>">
                                <input type="hidden" name="sfpp_section" value="maintenance">
                                <button type="submit" class="sfpp-link-button">
                                    <?php echo $is_archived ? 'Unarchive' : 'Archive'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>
