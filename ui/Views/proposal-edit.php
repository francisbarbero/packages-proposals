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

// Get proposal schema
$schema = function_exists( 'sfpp_get_proposal_schema' ) ? sfpp_get_proposal_schema() : [];
$schema_data = $proposal->schema_data ?? [];

// Back link
$back_url = add_query_arg(
	'sfpp_section',
	'proposals',
	remove_query_arg( [ 'sfpp_view', 'proposal_id', 'sfpp_notice' ] )
);

$print_url = add_query_arg(
	[
		'action'      => 'print_proposal',
		'proposal_id' => $proposal_id,
	],
	$back_url
);

// Notice
$notice = isset( $_GET['sfpp_notice'] ) ? sanitize_key( $_GET['sfpp_notice'] ) : '';
?>
<div class="sfpp-section sfpp-section--proposals-edit">
	<h2>
		Edit <?php echo 'agreement' === ( $proposal->proposal_type ?? 'proposal' ) ? 'Agreement' : 'Proposal'; ?>:
		<?php echo esc_html( $proposal->name ?? 'Untitled' ); ?>
		<?php if ( 'agreement' === ( $proposal->proposal_type ?? 'proposal' ) ) : ?>
			<span class="sfpp-badge sfpp-badge--agreement">Agreement</span>
		<?php endif; ?>
	</h2>

	<p>
		<a href="<?php echo esc_url( $back_url ); ?>" class="sfpp-link">
			&larr; Back to Proposals
		</a>
		|
		<a href="<?php echo esc_url( $print_url ); ?>" class="sfpp-link" target="_blank" rel="noopener">
			Print PDF
		</a>
	</p>

	<?php if ( 'proposal_saved' === $notice ) : ?>
		<div class="sfpp-notice sfpp-notice--success">
			<?php echo 'agreement' === ( $proposal->proposal_type ?? 'proposal' ) ? 'Agreement' : 'Proposal'; ?> saved successfully.
		</div>
	<?php elseif ( 'proposal_packages_added' === $notice ) : ?>
		<div class="sfpp-notice sfpp-notice--success">
			Selected packages were added as line items.
		</div>
	<?php endif; ?>

	<!-- MAIN PROPOSAL FORM (unified form for all data) -->
	<form method="post" class="sfpp-form">
		<?php wp_nonce_field( 'sfpp_save_proposal', 'sfpp_save_proposal_nonce' ); ?>
		<input type="hidden" name="sfpp_action" value="save_proposal">
		<input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal_id ); ?>">
		<input type="hidden" name="sfpp_section" value="proposals">

		<div class="sfpp-tabs">
			<!-- Tab Navigation -->
			<ul class="sfpp-tabs-nav">
				<!-- Basic Info Tab -->
				<li class="sfpp-tabs-nav-item is-active">
					<a href="#" data-tab-target="basic_info">Basic Info</a>
				</li>

				<!-- Schema-driven tabs -->
				<?php
				if ( ! empty( $schema['groups'] ) ) :
					foreach ( $schema['groups'] as $group ) :
						$group_id = $group['id'] ?? '';
						$group_label = $group['label'] ?? '';
						if ( $group_id && $group_label ) :
				?>
				<li class="sfpp-tabs-nav-item">
					<a href="#" data-tab-target="<?php echo esc_attr( $group_id ); ?>">
						<?php echo esc_html( $group_label ); ?>
					</a>
				</li>
				<?php
						endif;
					endforeach;
				endif;
				?>

				<!-- Packages Tab -->
				<li class="sfpp-tabs-nav-item">
					<a href="#" data-tab-target="packages">Packages</a>
				</li>

				<!-- Line Items Tab -->
				<li class="sfpp-tabs-nav-item">
					<a href="#" data-tab-target="line_items">Line Items</a>
				</li>
			</ul>

			<!-- Tab Panels -->
			<div class="sfpp-tabs-panels">
				<!-- BASIC INFO PANEL -->
				<div class="sfpp-tab-panel is-active" data-tab-id="basic_info">
					<h3 class="sfpp-tab-panel__title">Basic Information</h3>

					<div class="sfpp-field">
						<label for="sfpp_proposal_name">Proposal Name *</label>
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
						<label for="sfpp_proposal_type">Type</label>
						<select id="sfpp_proposal_type" name="proposal_type">
							<option value="proposal" <?php selected( $proposal->proposal_type ?? 'proposal', 'proposal' ); ?>>Proposal</option>
							<option value="agreement" <?php selected( $proposal->proposal_type ?? 'proposal', 'agreement' ); ?>>Agreement</option>
						</select>
						<p class="description">Proposals are standard quotes; Agreements are for signed contracts.</p>
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
						<p class="description">This is automatically calculated from line items.</p>
					</div>
				</div>

				<!-- SCHEMA-DRIVEN PANELS -->
				<?php
				if ( ! empty( $schema['groups'] ) && function_exists( 'sfpp_render_schema_groups' ) ) {
					sfpp_render_schema_groups( $schema, $schema_data );
				}
				?>

				<!-- PACKAGES PANEL (for adding packages as line items) -->
				<div class="sfpp-tab-panel" data-tab-id="packages">
					<h3 class="sfpp-tab-panel__title">Add Packages as Line Items</h3>
					<p class="description">
						Select existing packages to add as new line items to this proposal.
						Each selected package will become a 1× line item using its name, short description and base price.
					</p>

					<?php
					// Fetch available packages
					$all_packages = function_exists( 'sfpp_get_packages' ) ? sfpp_get_packages() : [];

					if ( ! empty( $all_packages ) ) :
						// Group packages by type
						$packages_by_type = [
							'website' => [],
							'hosting' => [],
							'maintenance' => [],
							'extra' => [],
						];

						foreach ( $all_packages as $package ) {
							$type = $package->type ?? 'website';
							if ( ! isset( $packages_by_type[ $type ] ) ) {
								$packages_by_type[ $type ] = [];
							}
							$packages_by_type[ $type ][] = $package;
						}

						$type_labels = [
							'website' => 'Website Packages',
							'hosting' => 'Hosting Packages',
							'maintenance' => 'Maintenance Packages',
							'extra' => 'Extra Packages',
						];

						foreach ( $packages_by_type as $type => $packages ) :
							if ( empty( $packages ) ) {
								continue;
							}
					?>
							<h4><?php echo esc_html( $type_labels[ $type ] ?? ucfirst( $type ) ); ?></h4>
							<div class="sfpp-field">
								<?php foreach ( $packages as $package ) : ?>
									<label class="sfpp-checkbox-label">
										<input type="checkbox" name="packages_to_add[]" value="<?php echo (int) $package->id; ?>">
										<?php
										echo esc_html( $package->name );
										if ( ! empty( $package->group_label ) ) {
											echo ' (' . esc_html( $package->group_label ) . ')';
										}
										if ( ! empty( $package->base_price ) ) {
											echo ' — ' . esc_html( $package->currency ?? 'PHP' ) . ' ' . number_format_i18n( (float) $package->base_price, 2 );
										}
										?>
									</label><br>
								<?php endforeach; ?>
							</div>
					<?php
						endforeach;
					else :
					?>
						<p><em>No packages available yet.</em></p>
					<?php endif; ?>

					<p class="description">
						Note: Selected packages will be added as new line items when you save this proposal.
						You can edit the line items in the "Line Items" tab after saving.
					</p>

					<hr style="margin: 20px 0;">

					<h3>Add Extras as Line Items</h3>
					<p class="description">
						Select extras to add as new line items to this proposal.
						Each selected extra will become a 1× line item using its name, description and base price.
					</p>

					<?php
					// Fetch available extras
					$all_extras = function_exists( 'sfpp_get_extras' ) ? sfpp_get_extras( [ 'status' => 'active' ] ) : [];

					if ( ! empty( $all_extras ) ) :
					?>
						<div class="sfpp-field">
							<?php foreach ( $all_extras as $extra ) : ?>
								<label class="sfpp-checkbox-label">
									<input type="checkbox" name="extras_to_add[]" value="<?php echo (int) $extra->id; ?>">
									<?php
									echo esc_html( $extra->name );
									if ( ! empty( $extra->description ) ) {
										echo ' — ' . esc_html( wp_trim_words( $extra->description, 10 ) );
									}
									if ( ! empty( $extra->base_price ) ) {
										echo ' — PHP ' . number_format_i18n( (float) $extra->base_price, 2 );
									}
									?>
								</label><br>
							<?php endforeach; ?>
						</div>
					<?php
					else :
					?>
						<p><em>No extras available yet.</em></p>
					<?php endif; ?>

					<p class="description">
						Note: Selected extras will be added as new line items when you save this proposal.
						You can edit the line items in the "Line Items" tab after saving.
					</p>
				</div>

				<!-- LINE ITEMS PANEL -->
				<div class="sfpp-tab-panel" data-tab-id="line_items">
					<h3 class="sfpp-tab-panel__title">Line Items</h3>

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

					<p class="description">To delete a line item, clear the Name field and save.</p>
				</div>

			</div>
		</div>

		<!-- Save Button -->
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

	<!-- Separate form for adding packages (triggers different handler) -->
	<div class="sfpp-add-packages-section" style="display:none;">
		<!-- This section is now integrated into the Packages tab above -->
	</div>

</div>
