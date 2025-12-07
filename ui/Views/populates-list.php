<?php
// ui/Views/populates-list.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch Populates from the database.
$populates = function_exists( 'sfpp_get_populates' ) ? sfpp_get_populates() : [];

// Simple notice if something just happened.
$notice = isset( $_GET['sfpp_notice'] ) ? sanitize_key( $_GET['sfpp_notice'] ) : '';
?>
<div class="sfpp-section sfpp-section--populates">
	<h2>Text Snippets</h2>

	<?php if ( 'populate_created' === $notice ) : ?>
		<div class="sfpp-notice sfpp-notice--success">
			New snippet created successfully.
		</div>
	<?php elseif ( 'populate_saved' === $notice ) : ?>
		<div class="sfpp-notice sfpp-notice--success">
			Snippet saved successfully.
		</div>
	<?php elseif ( 'populate_deleted' === $notice ) : ?>
		<div class="sfpp-notice sfpp-notice--success">
			Snippet deleted successfully.
		</div>
	<?php endif; ?>

	<?php
	// Create "Add New" URL
	$base_url = remove_query_arg( [ 'sfpp_view', 'populate_id', 'sfpp_notice' ] );
	$add_url = add_query_arg(
		[
			'sfpp_section' => 'populates',
			'sfpp_view'    => 'edit',
			'populate_id'  => 'new',
		],
		$base_url
	);
	?>
	<p>
		<a href="<?php echo esc_url( $add_url ); ?>" class="sfpp-button sfpp-button--primary">
			+ Add New Snippet
		</a>
	</p>

	<?php if ( empty( $populates ) ) : ?>

		<p>No snippets found yet.</p>

	<?php else : ?>

		<table class="sfpp-table sfpp-table--populates">
			<thead>
				<tr>
					<th>Title</th>
					<th>Category</th>
					<th>Preview</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $populates as $populate ) : ?>
					<tr>
						<td><?php echo esc_html( $populate->title ?? '' ); ?></td>
						<td><?php echo esc_html( $populate->category ?? '' ); ?></td>
						<td>
							<?php
							$preview = wp_strip_all_tags( $populate->content ?? '' );
							echo esc_html( wp_trim_words( $preview, 12 ) );
							?>
						</td>
						<td class="sfpp-table-actions">
							<?php
							$edit_url = add_query_arg(
								[
									'sfpp_section' => 'populates',
									'sfpp_view'    => 'edit',
									'populate_id'  => $populate->id,
								],
								$base_url
							);
							?>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="sfpp-link">
								Edit
							</a>

							<!-- Delete -->
							<form method="post" class="sfpp-inline-form">
								<?php wp_nonce_field( 'sfpp_delete_populate_' . $populate->id, 'sfpp_delete_populate_nonce' ); ?>
								<input type="hidden" name="sfpp_action" value="delete_populate">
								<input type="hidden" name="populate_id" value="<?php echo (int) $populate->id; ?>">
								<input type="hidden" name="sfpp_section" value="populates">
								<button type="submit" class="sfpp-link-button" onclick="return confirm('Are you sure you want to delete this snippet?');">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php endif; ?>
</div>
