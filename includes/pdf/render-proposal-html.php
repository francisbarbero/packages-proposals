<?php
/**
 * Proposal PDF HTML Renderer
 * Converts proposal data to HTML for PDF generation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render proposal data as HTML for PDF output.
 *
 * @param array $ctx Context array containing:
 *                   - title: Proposal title/name
 *                   - proposal: Full proposal object
 *                   - schema: Schema definition
 *                   - schema_data: Decoded schema_json
 *                   - sections: Grouped sections array
 *                   - items: Proposal line items
 *                   - meta_fields: Top-level fields (date, client, etc.)
 * @return string HTML output.
 */
function sfpp_render_proposal_pdf_html( $ctx ) {
	$html = '';

	// Main title
	if ( ! empty( $ctx['title'] ) ) {
		$html .= '<h1>' . esc_html( $ctx['title'] ) . '</h1>';
	}

	// Top meta fields (Date, Client Name, Project Name, etc.)
	if ( ! empty( $ctx['meta_fields'] ) ) {
		foreach ( $ctx['meta_fields'] as $label => $value ) {
			if ( ! empty( $value ) || $value === '0' || $value === 0 ) {
				$html .= '<div class="field-label">' . esc_html( $label ) . '</div>';
				$html .= '<div class="field-value"><p>' . esc_html( $value ) . '</p></div>';
			}
		}
	}

	// Render cost breakdown table if items exist
	if ( ! empty( $ctx['items'] ) ) {
		$html .= sfpp_pdf_render_cost_breakdown( $ctx );
	}

	// Render schema sections
	if ( ! empty( $ctx['sections'] ) ) {
		foreach ( $ctx['sections'] as $section_label => $fields ) {
			// Check if section has any content
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

				// Handle special field types
				$field_type = $field_def['type'] ?? 'text';

				if ( 'payment_schedule' === $field_type ) {
					$html .= sfpp_pdf_render_payment_schedule( $value, $field_def );
				} elseif ( 'optional_extras' === $field_type ) {
					$html .= sfpp_pdf_render_optional_extras( $value, $field_def );
				} else {
					$label = $field_def['label'] ?? $field_key;
					$html .= sfpp_pdf_render_basic_field( $label, $value, $field_def );
				}
			}
		}
	}

	return $html;
}

/**
 * Render cost breakdown table for proposal.
 *
 * @param array $ctx Context array with items and proposal data.
 * @return string HTML table.
 */
function sfpp_pdf_render_cost_breakdown( $ctx ) {
	if ( empty( $ctx['items'] ) ) {
		return '';
	}

	$proposal = $ctx['proposal'] ?? null;
	$currency = $proposal->currency ?? 'PHP';

	$html = '<h2>Cost Breakdown</h2>';
	$html .= '<table class="cost-breakdown-table">';
	$html .= '<thead>';
	$html .= '<tr>';
	$html .= '<th>Item</th>';
	$html .= '<th>Description</th>';
	$html .= '<th>Amount</th>';
	$html .= '</tr>';
	$html .= '</thead>';
	$html .= '<tbody>';

	$total = 0;

	foreach ( $ctx['items'] as $item ) {
		$item_total = (float) ( $item->total_price ?? 0 );
		$total += $item_total;

		$html .= '<tr>';
		$html .= '<td>' . esc_html( $item->name ?? '' ) . '</td>';
		$html .= '<td>' . esc_html( $item->description ?? '' ) . '</td>';
		$html .= '<td>' . esc_html( number_format( $item_total, 2 ) ) . '</td>';
		$html .= '</tr>';
	}

	// Total row
	$html .= '<tr class="totals-row">';
	$html .= '<td colspan="2">Total (' . esc_html( $currency ) . ')</td>';
	$html .= '<td>' . esc_html( number_format( $total, 2 ) ) . '</td>';
	$html .= '</tr>';

	$html .= '</tbody>';
	$html .= '</table>';

	return $html;
}

/**
 * Render payment schedule table.
 *
 * @param mixed $value     Payment schedule data (could be array or string).
 * @param array $field_def Field definition.
 * @return string HTML output.
 */
function sfpp_pdf_render_payment_schedule( $value, $field_def = [] ) {
	if ( empty( $value ) ) {
		return '';
	}

	$label = $field_def['label'] ?? 'Payment Schedule';
	$html = '<div class="field-label">' . esc_html( $label ) . '</div>';
	$html .= '<div class="field-value">';

	// If value is already formatted as HTML or text, just output it
	if ( is_string( $value ) ) {
		$html .= wp_kses_post( wpautop( $value ) );
	} elseif ( is_array( $value ) ) {
		// If it's an array of payment milestones
		$html .= '<table>';
		$html .= '<thead><tr><th>Milestone</th><th>Amount</th><th>Due Date</th></tr></thead>';
		$html .= '<tbody>';
		foreach ( $value as $payment ) {
			if ( is_array( $payment ) ) {
				$html .= '<tr>';
				$html .= '<td>' . esc_html( $payment['milestone'] ?? '' ) . '</td>';
				$html .= '<td>' . esc_html( $payment['amount'] ?? '' ) . '</td>';
				$html .= '<td>' . esc_html( $payment['due_date'] ?? '' ) . '</td>';
				$html .= '</tr>';
			}
		}
		$html .= '</tbody>';
		$html .= '</table>';
	}

	$html .= '</div>';

	return $html;
}

/**
 * Render optional extras list.
 *
 * @param mixed $value     Optional extras data.
 * @param array $field_def Field definition.
 * @return string HTML output.
 */
function sfpp_pdf_render_optional_extras( $value, $field_def = [] ) {
	if ( empty( $value ) ) {
		return '';
	}

	$label = $field_def['label'] ?? 'Optional Extras';
	$html = '<div class="field-label">' . esc_html( $label ) . '</div>';
	$html .= '<div class="field-value">';

	// If value is a string, just output it
	if ( is_string( $value ) ) {
		$html .= wp_kses_post( wpautop( $value ) );
	} elseif ( is_array( $value ) ) {
		// If it's an array of extras
		$html .= '<ul>';
		foreach ( $value as $extra ) {
			if ( is_array( $extra ) ) {
				$extra_html = esc_html( $extra['name'] ?? '' );
				if ( ! empty( $extra['price'] ) ) {
					$extra_html .= ' - ' . esc_html( $extra['price'] );
				}
				$html .= '<li>' . $extra_html . '</li>';
			} else {
				$html .= '<li>' . esc_html( $extra ) . '</li>';
			}
		}
		$html .= '</ul>';
	}

	$html .= '</div>';

	return $html;
}
