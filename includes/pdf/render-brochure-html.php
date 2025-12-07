<?php
/**
 * Brochure PDF HTML Renderer
 * Converts brochure data to HTML for PDF generation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render brochure data as HTML for PDF output.
 *
 * @param array $ctx Context array containing:
 *                   - title: Brochure title
 *                   - schema: Schema definition
 *                   - schema_data: Decoded schema_json
 *                   - sections: Grouped sections array
 * @return string HTML output.
 */
function sfpp_render_brochure_pdf_html( $ctx ) {
	$html = '';

	// Title
	if ( ! empty( $ctx['title'] ) ) {
		$html .= '<h1>' . esc_html( $ctx['title'] ) . '</h1>';
	}

	// Render schema sections
	if ( ! empty( $ctx['sections'] ) ) {
		foreach ( $ctx['sections'] as $section_label => $fields ) {
			// Only render section if it has content
			$section_has_content = false;
			foreach ( $fields as $field_key => $field_def ) {
				$value = sfpp_schema_get_value( $ctx['schema_data'], $field_key, '' );
				if ( ! empty( $value ) || $value === '0' || $value === 0 ) {
					$section_has_content = true;
					break;
				}
			}

			if ( ! $section_has_content ) {
				continue;
			}

			// Section heading (only if not hidden)
			$hide_section_label = false;
			foreach ( $fields as $field_def ) {
				if ( isset( $field_def['label_hidden_in_pdf'] ) && $field_def['label_hidden_in_pdf'] ) {
					$hide_section_label = true;
					break;
				}
			}

			if ( ! $hide_section_label && $section_label !== '__default__' ) {
				$html .= '<h2>' . esc_html( $section_label ) . '</h2>';
			}

			// Render fields in this section
			foreach ( $fields as $field_key => $field_def ) {
				$value = sfpp_schema_get_value( $ctx['schema_data'], $field_key, '' );

				// Skip empty values
				if ( empty( $value ) && $value !== '0' && $value !== 0 ) {
					continue;
				}

				// Skip hidden fields
				if ( isset( $field_def['hidden_in_pdf'] ) && $field_def['hidden_in_pdf'] ) {
					continue;
				}

				$label = $field_def['label'] ?? $field_key;
				$html .= sfpp_pdf_render_basic_field( $label, $value, $field_def );
			}
		}
	}

	return $html;
}
