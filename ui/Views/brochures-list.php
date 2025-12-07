<?php
// ui/Views/brochures-list.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch Brochures from the database.
$brochures = function_exists( 'sfpp_get_brochures' ) ? sfpp_get_brochures() : [];

// Simple notice if something just happened.
$notice = isset( $_GET['sfpp_notice'] ) ? sanitize_key( $_GET['sfpp_notice'] ) : '';
?>
<div class="sfpp-section sfpp-section--brochures">
	<h2>Brochures</h2>

	<?php if ( 'brochure_created' === $notice ) : ?>
		<div class="sfpp-notice sfpp-notice--success">
			Brochure created successfully.
		</div>
	<?php elseif ( 'brochure_saved' === $notice ) : ?>
		<div class="sfpp-notice sfpp-notice--success">
			Brochure saved successfully.
		</div>
	<?php elseif ( 'brochure_deleted' === $notice ) : ?>
		<div class="sfpp-notice sfpp-notice--success">
			Brochure deleted successfully.
		</div>
	<?php endif; ?>

	<?php
	// Create "Add New" URL
	$base_url = remove_query_arg( [ 'sfpp_view', 'brochure_id', 'sfpp_notice' ] );
	$add_url = add_query_arg(
		[
			'sfpp_section' => 'brochures',
			'sfpp_view'    => 'edit',
			'brochure_id'  => 'new',
		],
		$base_url
	);
	?>
	<p>
		<a href="<?php echo esc_url( $add_url ); ?>" class="sfpp-button sfpp-button--primary">
			+ Add New Brochure
		</a>
	</p>

	<?php if ( empty( $brochures ) ) : ?>

		<p>No brochures found yet.</p>

	<?php else : ?>

		<table class="sfpp-table sfpp-table--brochures">
			<thead>
				<tr>
					<th>Title</th>
					<th>Status</th>
					<th>Category</th>
					<th>Last Updated</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $brochures as $brochure ) : ?>
					<tr>
						<td><?php echo esc_html( $brochure->title ?? '' ); ?></td>
						<td><?php echo esc_html( ucfirst( $brochure->status ?? 'draft' ) ); ?></td>
						<td><?php echo esc_html( $brochure->category ?? '' ); ?></td>
						<td><?php echo esc_html( $brochure->updated_at ?? '' ); ?></td>
						<td class="sfpp-table-actions">
							<?php
							$edit_url = add_query_arg(
								[
									'sfpp_section' => 'brochures',
									'sfpp_view'    => 'edit',
									'brochure_id'  => $brochure->id,
								],
								$base_url
							);
							?>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="sfpp-link">
								Edit
							</a>

							<!-- Delete -->
							<form method="post" class="sfpp-inline-form">
								<?php wp_nonce_field( 'sfpp_delete_brochure_' . $brochure->id, 'sfpp_delete_brochure_nonce' ); ?>
								<input type="hidden" name="sfpp_action" value="delete_brochure">
								<input type="hidden" name="brochure_id" value="<?php echo (int) $brochure->id; ?>">
								<input type="hidden" name="sfpp_section" value="brochures">
								<button type="submit" class="sfpp-link-button" onclick="return confirm('Are you sure you want to delete this brochure?');">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php endif; ?>
</div>
