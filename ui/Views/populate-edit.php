<?php
// ui/Views/populate-edit.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load schema form renderer
require_once __DIR__ . '/../schema-form.php';

$populate_id = isset( $_GET['populate_id'] ) ? $_GET['populate_id'] : 0;
$is_new = ( 'new' === $populate_id );

// Load populate if editing
$populate = null;
if ( ! $is_new && $populate_id ) {
	$populate_id = (int) $populate_id;
	$populate = function_exists( 'sfpp_get_populate' ) ? sfpp_get_populate( $populate_id ) : null;

	if ( ! $populate ) : ?>
		<div class="sfpp-section sfpp-section--populates-edit">
			<h2>Edit Snippet</h2>
			<p>Snippet not found.</p>
		</div>
		<?php
		return;
	endif;
}

// Get schema definition
$schema_def = function_exists( 'sfpp_get_populates_schema' ) ? sfpp_get_populates_schema() : [];
$groups = ( isset( $schema_def['groups'] ) && is_array( $schema_def['groups'] ) ) ? $schema_def['groups'] : [];

// Decode schema JSON
$schema_data = [];
if ( $populate && ! empty( $populate->schema_json ) ) {
	$decoded = json_decode( $populate->schema_json, true );
	if ( is_array( $decoded ) ) {
		$schema_data = $decoded;
	}
}

// Basic fields (top-level columns)
$title    = $is_new ? '' : ( $populate->title ?? '' );
$category = $is_new ? '' : ( $populate->category ?? '' );
$content  = $is_new ? '' : ( $populate->content ?? '' );

// Back link base URL (strip edit params)
$back_url = add_query_arg(
	'sfpp_section',
	'populates',
	remove_query_arg( [ 'sfpp_view', 'populate_id', 'sfpp_notice' ] )
);

$heading = $is_new ? 'Add New Snippet' : 'Edit Snippet';
?>
<div class="sfpp-section sfpp-section--populates-edit">
	<h2><?php echo esc_html( $heading ); ?></h2>

	<form method="post" class="sfpp-form">
		<?php wp_nonce_field( 'sfpp_save_populate', 'sfpp_save_populate_nonce' ); ?>
		<input type="hidden" name="sfpp_action" value="save_populate">
		<?php if ( ! $is_new ) : ?>
			<input type="hidden" name="populate_id" value="<?php echo esc_attr( $populate_id ); ?>">
		<?php endif; ?>
		<input type="hidden" name="sfpp_section" value="populates">

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
						<label for="sfpp_populate_title">Title *</label>
						<input type="text" id="sfpp_populate_title" name="title" class="regular-text"
							   value="<?php echo esc_attr( $title ); ?>" required>
					</div>

					<div class="sfpp-field">
						<label for="sfpp_populate_category">Category</label>
						<input type="text" id="sfpp_populate_category" name="category" class="regular-text"
							   value="<?php echo esc_attr( $category ); ?>">
						<p class="description">Optional category for organizing snippets.</p>
					</div>

					<div class="sfpp-field">
						<label for="sfpp_populate_content">Content</label>
						<textarea id="sfpp_populate_content" name="content" rows="8" class="large-text sfpp-codemirror"><?php
							echo esc_textarea( $content );
						?></textarea>
						<p class="description">This is the reusable snippet content. It will be inserted into the proposal editor.</p>
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
				Save Snippet
			</button>
			<a href="<?php echo esc_url( $back_url ); ?>" class="sfpp-button">
				Cancel
			</a>
		</p>
	</form>
</div>
