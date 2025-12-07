<?php
// ui/Views/brochure-edit.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load schema form renderer
require_once __DIR__ . '/../schema-form.php';

$brochure_id = isset( $_GET['brochure_id'] ) ? $_GET['brochure_id'] : 0;
$is_new = ( 'new' === $brochure_id );

// Load brochure if editing
$brochure = null;
if ( ! $is_new && $brochure_id ) {
	$brochure_id = (int) $brochure_id;
	$brochure = function_exists( 'sfpp_get_brochure' ) ? sfpp_get_brochure( $brochure_id ) : null;

	if ( ! $brochure ) : ?>
		<div class="sfpp-section sfpp-section--brochures-edit">
			<h2>Edit Brochure</h2>
			<p>Brochure not found.</p>
		</div>
		<?php
		return;
	endif;
}

// Get brochure schema
$schema = function_exists( 'sfpp_get_brochures_schema' ) ? sfpp_get_brochures_schema() : [];
$schema_data = $is_new ? [] : ( $brochure->schema_data ?? [] );

// Basic fields (top-level columns)
$title    = $is_new ? '' : ( $brochure->title ?? '' );
$status   = $is_new ? 'draft' : ( $brochure->status ?? 'draft' );
$category = $is_new ? '' : ( $brochure->category ?? '' );

// Back link base URL (strip edit params)
$back_url = add_query_arg(
	'sfpp_section',
	'brochures',
	remove_query_arg( [ 'sfpp_view', 'brochure_id', 'sfpp_notice' ] )
);

$heading = $is_new ? 'Add New Brochure' : 'Edit Brochure';
?>
<div class="sfpp-section sfpp-section--brochures-edit">
	<h2><?php echo esc_html( $heading ); ?></h2>

	<form method="post" class="sfpp-form">
		<?php wp_nonce_field( 'sfpp_save_brochure', 'sfpp_save_brochure_nonce' ); ?>
		<input type="hidden" name="sfpp_action" value="save_brochure">
		<?php if ( ! $is_new ) : ?>
			<input type="hidden" name="brochure_id" value="<?php echo esc_attr( $brochure_id ); ?>">
		<?php endif; ?>
		<input type="hidden" name="sfpp_section" value="brochures">

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
			</ul>

			<!-- Tab Panels -->
			<div class="sfpp-tabs-panels">
				<!-- BASIC INFO PANEL -->
				<div class="sfpp-tab-panel is-active" data-tab-id="basic_info">
					<h3 class="sfpp-tab-panel__title">Basic Information</h3>

					<div class="sfpp-field">
						<label for="sfpp_brochure_title">Title *</label>
						<input type="text" id="sfpp_brochure_title" name="title" class="regular-text"
							   value="<?php echo esc_attr( $title ); ?>" required>
					</div>

					<div class="sfpp-field">
						<label for="sfpp_brochure_status">Status</label>
						<select id="sfpp_brochure_status" name="status">
							<option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option>
							<option value="active" <?php selected( $status, 'active' ); ?>>Active</option>
							<option value="archived" <?php selected( $status, 'archived' ); ?>>Archived</option>
						</select>
					</div>

					<div class="sfpp-field">
						<label for="sfpp_brochure_category">Category</label>
						<input type="text" id="sfpp_brochure_category" name="category" class="regular-text"
							   value="<?php echo esc_attr( $category ); ?>">
						<p class="description">Optional category for organizing brochures.</p>
					</div>
				</div>

				<!-- SCHEMA-DRIVEN PANELS -->
				<?php
				if ( ! empty( $schema['groups'] ) && function_exists( 'sfpp_render_schema_groups' ) ) {
					sfpp_render_schema_groups( $schema, $schema_data );
				}
				?>
			</div>
		</div>

		<!-- Save Button -->
		<p>
			<button type="submit" class="sfpp-button sfpp-button--primary">
				Save Brochure
			</button>
			<a href="<?php echo esc_url( $back_url ); ?>" class="sfpp-button">
				Cancel
			</a>
		</p>
	</form>
</div>
