<?php
/**
 * PDF Print Loader
 * Hooks into template_redirect and dispatches PDF print actions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize PDF print system.
 * Hooks into template_redirect to intercept print requests.
 */
function sfpp_init_pdf_print_system() {
	add_action( 'template_redirect', 'sfpp_handle_pdf_print_request' );
}
add_action( 'init', 'sfpp_init_pdf_print_system' );

/**
 * Handle PDF print requests.
 * Intercepts requests with action=print_proposal or action=print_brochure.
 */
function sfpp_handle_pdf_print_request() {
	if ( empty( $_GET['action'] ) ) {
		return;
	}

	$action = sanitize_key( $_GET['action'] );

	if ( 'print_proposal' === $action ) {
		sfpp_handle_print_proposal();
	} elseif ( 'print_brochure' === $action ) {
		sfpp_handle_print_brochure();
	}
}

/**
 * Handle proposal PDF print request.
 */
function sfpp_handle_print_proposal() {
	// Get proposal ID
	$proposal_id = isset( $_GET['proposal_id'] ) ? (int) $_GET['proposal_id'] : 0;

	if ( $proposal_id <= 0 ) {
		wp_die( 'Invalid proposal ID.' );
	}

	// Load proposal
	if ( ! function_exists( 'sfpp_get_proposal' ) ) {
		wp_die( 'Proposal function not available.' );
	}

	$proposal = sfpp_get_proposal( $proposal_id );

	if ( ! $proposal ) {
		wp_die( 'Proposal not found.' );
	}

	// Load proposal items
	$items = function_exists( 'sfpp_get_proposal_items' ) ? sfpp_get_proposal_items( $proposal_id ) : [];

	// Load schema
	$schema = function_exists( 'sfpp_get_proposal_schema' ) ? sfpp_get_proposal_schema() : [];

	// Get schema data
	$schema_data = $proposal->schema_data ?? [];

	// Build sections
	$sections = sfpp_build_sections_from_schema( $schema, $schema_data );

	// Build meta fields
	$meta_fields = [
		'Date' => isset( $proposal->created_at ) ? date( 'F j, Y', strtotime( $proposal->created_at ) ) : '',
		'Client Name' => $proposal->client_name ?? '',
		'Project Name' => $proposal->project_name ?? '',
	];

	// Get selected assets (these could come from schema or proposal meta)
	// For now, we'll check schema_data for asset selections
	$assets = sfpp_extract_proposal_assets( $schema_data );

	// Determine if conforme should be included
	$include_conforme = ( 'agreement' === ( $proposal->proposal_type ?? 'proposal' ) );

	// Build context array
	$ctx = [
		'title' => $proposal->name ?? 'Proposal',
		'proposal' => $proposal,
		'schema' => $schema,
		'schema_data' => $schema_data,
		'sections' => $sections,
		'items' => $items,
		'meta_fields' => $meta_fields,
		'assets' => $assets,
		'include_conforme' => $include_conforme,
	];

	// Generate PDF
	sfpp_generate_proposal_pdf( $ctx );
}

/**
 * Handle brochure PDF print request.
 */
function sfpp_handle_print_brochure() {
	// Get brochure ID
	$brochure_id = isset( $_GET['brochure_id'] ) ? (int) $_GET['brochure_id'] : 0;

	if ( $brochure_id <= 0 ) {
		wp_die( 'Invalid brochure ID.' );
	}

	// Load brochure
	if ( ! function_exists( 'sfpp_get_brochure' ) ) {
		wp_die( 'Brochure function not available.' );
	}

	$brochure = sfpp_get_brochure( $brochure_id );

	if ( ! $brochure ) {
		wp_die( 'Brochure not found.' );
	}

	// Load schema
	$schema = function_exists( 'sfpp_get_brochures_schema' ) ? sfpp_get_brochures_schema() : [];

	// Get schema data
	$schema_data = isset( $brochure->schema_json ) ? json_decode( $brochure->schema_json, true ) : [];
	if ( ! is_array( $schema_data ) ) {
		$schema_data = [];
	}

	// Build sections
	$sections = sfpp_build_sections_from_schema( $schema, $schema_data );

	// Build context array
	$ctx = [
		'title' => $brochure->title ?? 'Brochure',
		'brochure' => $brochure,
		'schema' => $schema,
		'schema_data' => $schema_data,
		'sections' => $sections,
	];

	// Generate PDF
	sfpp_generate_brochure_pdf( $ctx );
}

/**
 * Build sections array from schema definition.
 * Groups fields by section markers.
 *
 * @param array $schema      Schema definition.
 * @param array $schema_data Schema data values.
 * @return array Sections array: [ section_label => [ field_key => field_def, ... ], ... ]
 */
function sfpp_build_sections_from_schema( $schema, $schema_data ) {
	$sections = [];
	$current_section = '__default__';

	if ( empty( $schema['groups'] ) ) {
		return $sections;
	}

	foreach ( $schema['groups'] as $group ) {
		if ( empty( $group['fields'] ) ) {
			continue;
		}

		foreach ( $group['fields'] as $field ) {
			$field_type = $field['type'] ?? 'text';

			// If field is a section marker, start new section
			if ( 'section' === $field_type ) {
				$current_section = $field['label'] ?? '__unnamed_section__';

				// Check if section label should be hidden
				if ( isset( $field['label_hidden_in_pdf'] ) && $field['label_hidden_in_pdf'] ) {
					$current_section = '__hidden_' . $current_section;
				}

				// Initialize section if not exists
				if ( ! isset( $sections[ $current_section ] ) ) {
					$sections[ $current_section ] = [];
				}
				continue;
			}

			// Skip fields marked as hidden in PDF
			if ( isset( $field['hidden_in_pdf'] ) && $field['hidden_in_pdf'] ) {
				continue;
			}

			// Add field to current section
			if ( ! isset( $sections[ $current_section ] ) ) {
				$sections[ $current_section ] = [];
			}

			$field_key = $field['key'] ?? '';
			if ( $field_key ) {
				$sections[ $current_section ][ $field_key ] = $field;
			}
		}
	}

	return $sections;
}

/**
 * Extract asset IDs from proposal schema data.
 *
 * @param array $schema_data Schema data array.
 * @return array Asset IDs array with keys: cover, portfolios, mockup, terms, ending, agreement_pdf.
 */
function sfpp_extract_proposal_assets( $schema_data ) {
	$assets = [
		'cover' => null,
		'portfolios' => [],
		'mockup' => null,
		'terms' => null,
		'ending' => null,
		'agreement_pdf' => null,
	];

	// Look for asset fields in schema data
	// Common field keys used in legacy system
	$asset_field_map = [
		'cover' => [ 'assets.cover', 'cover_page', 'cover' ],
		'mockup' => [ 'assets.mockup', 'mockup_pdf', 'mockup' ],
		'terms' => [ 'assets.terms', 'terms_conditions', 'terms' ],
		'ending' => [ 'assets.ending', 'ending_page', 'ending' ],
		'agreement_pdf' => [ 'assets.agreement', 'agreement_pdf' ],
	];

	// Portfolio is usually an array
	$portfolio_keys = [ 'assets.portfolios', 'portfolio_pdfs', 'portfolios' ];

	foreach ( $asset_field_map as $asset_type => $possible_keys ) {
		foreach ( $possible_keys as $key ) {
			$value = sfpp_schema_get_value( $schema_data, $key, null );
			if ( $value ) {
				$assets[ $asset_type ] = (int) $value;
				break;
			}
		}
	}

	// Handle portfolios (array)
	foreach ( $portfolio_keys as $key ) {
		$value = sfpp_schema_get_value( $schema_data, $key, null );
		if ( $value && is_array( $value ) ) {
			$assets['portfolios'] = array_map( 'intval', $value );
			break;
		}
	}

	return $assets;
}
