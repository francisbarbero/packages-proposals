<?php
/**
 * PDF Helper Functions
 * Shared utilities for PDF generation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Append a PDF file to the current mPDF document.
 *
 * @param \Mpdf\Mpdf $mpdf          The mPDF instance.
 * @param string     $file          Full path to the PDF file to append.
 * @param string     $error_message Error message to display if file not found.
 * @return bool True on success, false on failure.
 */
function sfpp_append_pdf( $mpdf, $file, $error_message = '' ) {
	if ( ! file_exists( $file ) ) {
		if ( $error_message ) {
			error_log( 'SFPP PDF Error: ' . $error_message );
		}
		return false;
	}

	try {
		$page_count = $mpdf->SetSourceFile( $file );

		for ( $i = 1; $i <= $page_count; $i++ ) {
			$template_id = $mpdf->ImportPage( $i );
			$mpdf->AddPage();
			$mpdf->UseTemplate( $template_id );
		}

		return true;
	} catch ( Exception $e ) {
		error_log( 'SFPP PDF Append Error: ' . $e->getMessage() );
		return false;
	}
}

/**
 * Insert cover page at the beginning of the PDF.
 *
 * @param \Mpdf\Mpdf $mpdf        The mPDF instance.
 * @param string     $cover_file  Full path to cover PDF file.
 * @param string     $cover_title Title to display on cover (optional, for text-based covers).
 * @return bool True on success, false on failure.
 */
function sfpp_insert_cover_page( $mpdf, $cover_file, $cover_title = '' ) {
	if ( ! file_exists( $cover_file ) ) {
		error_log( 'SFPP PDF Error: Cover file not found at ' . $cover_file );
		return false;
	}

	try {
		$page_count = $mpdf->SetSourceFile( $cover_file );

		for ( $i = 1; $i <= $page_count; $i++ ) {
			$template_id = $mpdf->ImportPage( $i );
			$mpdf->AddPage();
			$mpdf->UseTemplate( $template_id );
		}

		return true;
	} catch ( Exception $e ) {
		error_log( 'SFPP PDF Cover Error: ' . $e->getMessage() );
		return false;
	}
}

/**
 * Append conforme/signature page to proposal PDF.
 *
 * @param \Mpdf\Mpdf $mpdf The mPDF instance.
 * @param array      $ctx  Context array containing proposal data.
 * @return void
 */
function sfpp_append_conforme( $mpdf, $ctx ) {
	$mpdf->AddPage();

	// Build conforme HTML
	$html = '<div style="font-family: sans-serif; font-size: 12px; line-height: 1.6;">';
	$html .= '<h2 style="color: #AB1D1C; border-bottom: 1px solid #000; padding-bottom: 5px;">Conforme</h2>';
	$html .= '<p>By signing below, the client acknowledges and agrees to the terms and conditions outlined in this proposal.</p>';
	$html .= '<br><br>';
	$html .= '<table style="width: 100%; border: none;">';
	$html .= '<tr>';
	$html .= '<td style="width: 50%; border: none; vertical-align: bottom;">';
	$html .= '<div style="border-bottom: 1px solid #000; width: 80%; margin-bottom: 5px;">&nbsp;</div>';
	$html .= '<p style="margin: 0;"><strong>Client Signature</strong></p>';
	$html .= '</td>';
	$html .= '<td style="width: 50%; border: none; vertical-align: bottom;">';
	$html .= '<div style="border-bottom: 1px solid #000; width: 80%; margin-bottom: 5px;">&nbsp;</div>';
	$html .= '<p style="margin: 0;"><strong>Date</strong></p>';
	$html .= '</td>';
	$html .= '</tr>';
	$html .= '</table>';
	$html .= '<br><br>';
	$html .= '<table style="width: 100%; border: none;">';
	$html .= '<tr>';
	$html .= '<td style="width: 50%; border: none; vertical-align: bottom;">';
	$html .= '<div style="border-bottom: 1px solid #000; width: 80%; margin-bottom: 5px;">&nbsp;</div>';
	$html .= '<p style="margin: 0;"><strong>Printed Name</strong></p>';
	$html .= '</td>';
	$html .= '<td style="width: 50%; border: none; vertical-align: bottom;">';
	$html .= '<div style="border-bottom: 1px solid #000; width: 80%; margin-bottom: 5px;">&nbsp;</div>';
	$html .= '<p style="margin: 0;"><strong>Position/Title</strong></p>';
	$html .= '</td>';
	$html .= '</tr>';
	$html .= '</table>';
	$html .= '</div>';

	$mpdf->WriteHTML( $html );
}

/**
 * Render a basic schema field as HTML.
 *
 * @param string $label Field label.
 * @param mixed  $value Field value.
 * @param array  $field Field definition from schema.
 * @return string HTML output.
 */
function sfpp_pdf_render_basic_field( $label, $value, $field = [] ) {
	if ( empty( $value ) && $value !== '0' && $value !== 0 ) {
		return '';
	}

	$type = $field['type'] ?? 'text';
	$html = '';

	$html .= '<div class="field-label">' . esc_html( $label ) . '</div>';
	$html .= '<div class="field-value">';

	if ( 'textarea' === $type || 'wysiwyg' === $type ) {
		// Allow HTML for textarea/wysiwyg fields
		$html .= wp_kses_post( wpautop( $value ) );
	} elseif ( 'checkbox_multi' === $type && is_array( $value ) ) {
		$html .= '<ul>';
		foreach ( $value as $item ) {
			$html .= '<li>' . esc_html( $item ) . '</li>';
		}
		$html .= '</ul>';
	} else {
		$html .= '<p>' . esc_html( $value ) . '</p>';
	}

	$html .= '</div>';

	return $html;
}
