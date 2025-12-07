<?php
/**
 * Brochure PDF Generation Pipeline
 * Generates PDF output for brochures
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Mpdf\Mpdf;

/**
 * Generate brochure PDF from context array.
 *
 * @param array $ctx Context array containing:
 *                   - title: Brochure title
 *                   - brochure: Full brochure object
 *                   - schema: Schema definition
 *                   - schema_data: Decoded schema_json
 *                   - sections: Grouped sections array
 * @return void Outputs PDF and exits.
 */
function sfpp_generate_brochure_pdf( $ctx ) {
	try {
		// Create mPDF instance
		$mpdf = new Mpdf([
			'mode' => 'utf-8',
			'format' => 'A4',
			'margin_top' => 30,
			'margin_bottom' => 30,
			'margin_left' => 18,
			'margin_right' => 18,
		]);

		// Load CSS
		$css_file = plugin_dir_path( __FILE__ ) . '../../assets/pdf/proposal.css';
		if ( file_exists( $css_file ) ) {
			$css = file_get_contents( $css_file );
			$mpdf->WriteHTML( $css, \Mpdf\HTMLParserMode::HEADER_CSS );
		}

		// Set metadata
		$title = $ctx['title'] ?? 'Brochure';
		$mpdf->SetTitle( $title );
		$mpdf->SetAuthor( get_bloginfo( 'name' ) );

		// Render HTML content
		$html = sfpp_render_brochure_pdf_html( $ctx );

		// Write HTML to PDF
		$mpdf->WriteHTML( $html );

		// Output PDF
		$filename = sanitize_file_name( $title ) . '.pdf';
		$mpdf->Output( $filename, 'I' ); // 'I' = inline display in browser

		exit;

	} catch ( Exception $e ) {
		wp_die( 'Error generating brochure PDF: ' . esc_html( $e->getMessage() ) );
	}
}
