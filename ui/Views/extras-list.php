<?php
// ui/Views/extras-list.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch Extras from the database.
$extras = function_exists( 'sfpp_get_extras' ) ? sfpp_get_extras() : [];

// Simple notice if something just happened.
$notice = isset( $_GET['sfpp_notice'] ) ? sanitize_key( $_GET['sfpp_notice'] ) : '';
?>
<div class="sfpp-section sfpp-section--extras">
    <h2>Website Extras</h2>

    <?php if ( 'extra_created' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            New Extra created successfully.
        </div>
    <?php elseif ( 'extra_saved' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Extra saved successfully.
        </div>
    <?php elseif ( 'extra_deleted' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Extra deleted successfully.
        </div>
    <?php endif; ?>

    <?php
    // Create "Add New" URL
    $base_url = remove_query_arg( [ 'sfpp_view', 'extra_id', 'sfpp_notice' ] );
    $add_url = add_query_arg(
        [
            'sfpp_section' => 'extras',
            'sfpp_view'    => 'edit',
            'extra_id'     => 'new',
        ],
        $base_url
    );
    ?>
    <p>
        <a href="<?php echo esc_url( $add_url ); ?>" class="sfpp-button sfpp-button--primary">
            + Add New Extra
        </a>
    </p>

    <?php if ( empty( $extras ) ) : ?>

        <p>No extras found yet.</p>

    <?php else : ?>

        <table class="sfpp-table sfpp-table--extras">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Base Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $extras as $extra ) : ?>
                    <tr>
                        <td><?php echo esc_html( $extra->name ?? '' ); ?></td>
                        <td><?php echo esc_html( ucfirst( $extra->status ?? 'active' ) ); ?></td>
                        <td>
                            <?php
                            $price = number_format_i18n( (float) ( $extra->base_price ?? 0 ), 2 );
                            echo 'PHP ' . esc_html( $price );
                            ?>
                        </td>
                        <td class="sfpp-table-actions">
                            <?php
                            $edit_url = add_query_arg(
                                [
                                    'sfpp_section' => 'extras',
                                    'sfpp_view'    => 'edit',
                                    'extra_id'     => $extra->id,
                                ],
                                $base_url
                            );
                            ?>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="sfpp-link">
                                Edit
                            </a>

                            <!-- Delete -->
                            <form method="post" class="sfpp-inline-form">
                                <?php wp_nonce_field( 'sfpp_delete_extra_' . $extra->id, 'sfpp_delete_extra_nonce' ); ?>
                                <input type="hidden" name="sfpp_action" value="delete_extra">
                                <input type="hidden" name="extra_id" value="<?php echo (int) $extra->id; ?>">
                                <input type="hidden" name="sfpp_section" value="extras">
                                <button type="submit" class="sfpp-link-button" onclick="return confirm('Are you sure you want to delete this extra?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>
