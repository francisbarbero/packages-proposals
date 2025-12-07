<?php
// ui/Views/proposals-list.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch Proposals from the database.
$proposals = function_exists( 'sfpp_get_proposals' ) ? sfpp_get_proposals() : [];

// Simple notice if something just happened.
$notice = isset( $_GET['sfpp_notice'] ) ? sanitize_key( $_GET['sfpp_notice'] ) : '';
?>
<div class="sfpp-section sfpp-section--proposals">
    <h2>Proposals</h2>

    <?php if ( 'proposal_created' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            New Proposal created.
        </div>
    <?php elseif ( 'proposal_saved' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Proposal saved.
        </div>
    <?php elseif ( 'proposal_cloned' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Proposal cloned.
        </div>
    <?php elseif ( 'proposal_archived' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Proposal archived.
        </div>
    <?php elseif ( 'proposal_unarchived' === $notice ) : ?>
        <div class="sfpp-notice sfpp-notice--success">
            Proposal unarchived.
        </div>
    <?php endif; ?>

    <form method="post" class="sfpp-add-form">
        <?php wp_nonce_field( 'sfpp_add_proposal', 'sfpp_add_proposal_nonce' ); ?>
        <input type="hidden" name="sfpp_action" value="add_proposal" />
        <input type="hidden" name="sfpp_section" value="proposals" />
        <button type="submit" class="sfpp-button sfpp-button--primary">
            + Add new Proposal
        </button>
    </form>

    <?php if ( empty( $proposals ) ) : ?>

        <p>No proposals found yet.</p>

    <?php else : ?>

        <table class="sfpp-table sfpp-table--proposals">
            <thead>
                <tr>
                    <th>Proposal Name</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $proposals as $proposal ) :
                    $type_label = 'agreement' === ( $proposal->proposal_type ?? 'proposal' ) ? 'Agreement' : 'Proposal';
                    $type_class = 'agreement' === ( $proposal->proposal_type ?? 'proposal' ) ? 'sfpp-badge--agreement' : 'sfpp-badge--proposal';
                ?>
                    <tr>
                        <td><?php echo esc_html( $proposal->name ?? '' ); ?></td>
                        <td><?php echo esc_html( $proposal->client_name ?? '' ); ?></td>
                        <td><span class="sfpp-badge <?php echo esc_attr( $type_class ); ?>"><?php echo esc_html( $type_label ); ?></span></td>
                        <td><?php echo esc_html( $proposal->status ?? 'draft' ); ?></td>
                        <td>
                            <?php
                            $currency = $proposal->currency ?? 'PHP';
                            $amount   = number_format_i18n( (float) ( $proposal->total_amount ?? 0 ), 2 );
                            echo esc_html( $currency . ' ' . $amount );
                            ?>
                        </td>
                        <td class="sfpp-table-actions">
                            <?php
                            $base_url = remove_query_arg( [ 'sfpp_view', 'proposal_id', 'sfpp_notice' ] );
                            $edit_url = add_query_arg(
                                [
                                    'sfpp_section' => 'proposals',
                                    'sfpp_view'    => 'edit',
                                    'proposal_id'  => $proposal->id,
                                ],
                                $base_url
                            );
                            $print_url = add_query_arg(
                                [
                                    'action'      => 'print_proposal',
                                    'proposal_id' => $proposal->id,
                                ],
                                $base_url
                            );
                            ?>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="sfpp-link">
                                Edit
                            </a>

                            <a href="<?php echo esc_url( $print_url ); ?>" class="sfpp-link" target="_blank" rel="noopener">
                                Print
                            </a>

                            <!-- Clone -->
                            <form method="post" class="sfpp-inline-form">
                                <?php wp_nonce_field( 'sfpp_clone_proposal_' . $proposal->id, 'sfpp_clone_proposal_nonce' ); ?>
                                <input type="hidden" name="sfpp_action" value="clone_proposal">
                                <input type="hidden" name="proposal_id" value="<?php echo (int) $proposal->id; ?>">
                                <input type="hidden" name="sfpp_section" value="proposals">
                                <button type="submit" class="sfpp-link-button">Clone</button>
                            </form>

                            <!-- Archive / Unarchive -->
                            <form method="post" class="sfpp-inline-form">
                                <?php
                                $is_archived  = ( 'archived' === $proposal->status );
                                $nonce_action = $is_archived ? 'sfpp_unarchive_proposal_' . $proposal->id : 'sfpp_archive_proposal_' . $proposal->id;
                                $action_value = $is_archived ? 'unarchive_proposal' : 'archive_proposal';
                                ?>
                                <?php wp_nonce_field( $nonce_action, 'sfpp_proposal_status_nonce' ); ?>
                                <input type="hidden" name="sfpp_action" value="<?php echo esc_attr( $action_value ); ?>">
                                <input type="hidden" name="proposal_id" value="<?php echo (int) $proposal->id; ?>">
                                <input type="hidden" name="sfpp_section" value="proposals">
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
