<?php
/**
 * Proposal PDF Generation Pipeline
 * Generates PDF output for proposals with all assets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Mpdf\Mpdf;

/**
 * Generate proposal PDF from context array.
 *
 * @param array $ctx Context array containing:
 *                   - title: Proposal name
 *                   - proposal: Full proposal object
 *                   - schema: Schema definition
 *                   - schema_data: Decoded schema_json
 *                   - sections: Grouped sections array
 *                   - items: Proposal line items
 *                   - meta_fields: Top-level metadata
 *                   - assets: Array of selected asset IDs:
 *                     - cover: Cover page asset ID
 *                     - portfolios: Array of portfolio asset IDs
 *                     - mockup: Mockup asset ID
 *                     - terms: Terms & conditions asset ID
 *                     - ending: Ending page asset ID
 *                   - include_conforme: Whether to include conforme page
 * @return void Outputs PDF and exits.
 */
function sfpp_generate_proposal_pdf( $ctx ) {
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

		// Set metadata
		$title = $ctx['title'] ?? 'Proposal';
		$mpdf->SetTitle( $title );
		$mpdf->SetAuthor( get_bloginfo( 'name' ) );

		// Step 1: Insert cover page if selected
		if ( ! empty( $ctx['assets']['cover'] ) ) {
			$cover_file = sfpp_get_asset_file_path( $ctx['assets']['cover'] );
			if ( $cover_file ) {
				sfpp_insert_cover_page( $mpdf, $cover_file, $title );
			}
		}

		// Step 2: Set letterhead template based on proposal type
		$proposal_type = $ctx['proposal']->proposal_type ?? 'proposal';
		sfpp_set_proposal_letterhead( $mpdf, $proposal_type );

		// Step 3: Load CSS
		$css_file = plugin_dir_path( __FILE__ ) . '../../assets/pdf/proposal.css';
		if ( file_exists( $css_file ) ) {
			$css = file_get_contents( $css_file );
			$mpdf->WriteHTML( $css, \Mpdf\HTMLParserMode::HEADER_CSS );
		}

		// Step 4: Add main content page
		$mpdf->AddPage();

		// Step 5: Render proposal HTML
		$html = sfpp_render_proposal_pdf_html( $ctx );
		$mpdf->WriteHTML( $html );

		// Step 6: Append portfolio PDFs
		if ( ! empty( $ctx['assets']['portfolios'] ) && is_array( $ctx['assets']['portfolios'] ) ) {
			foreach ( $ctx['assets']['portfolios'] as $portfolio_id ) {
				$portfolio_file = sfpp_get_asset_file_path( $portfolio_id );
				if ( $portfolio_file ) {
					sfpp_append_pdf( $mpdf, $portfolio_file, 'Portfolio PDF not found: ID ' . $portfolio_id );
				}
			}
		}

		// Step 7: Append mockup PDF
		if ( ! empty( $ctx['assets']['mockup'] ) ) {
			$mockup_file = sfpp_get_asset_file_path( $ctx['assets']['mockup'] );
			if ( $mockup_file ) {
				sfpp_append_pdf( $mpdf, $mockup_file, 'Mockup PDF not found: ID ' . $ctx['assets']['mockup'] );
			}
		}

		// Step 8: Append terms & conditions
		if ( ! empty( $ctx['assets']['terms'] ) ) {
			$terms_file = sfpp_get_asset_file_path( $ctx['assets']['terms'] );
			if ( $terms_file ) {
				sfpp_append_pdf( $mpdf, $terms_file, 'Terms PDF not found: ID ' . $ctx['assets']['terms'] );
			}
		}

		// Step 9: For agreements, append agreement document or render from CPT
		if ( 'agreement' === $proposal_type ) {
			if ( ! empty( $ctx['assets']['agreement_pdf'] ) ) {
				// If agreement is a PDF asset, append it
				$agreement_file = sfpp_get_asset_file_path( $ctx['assets']['agreement_pdf'] );
				if ( $agreement_file ) {
					sfpp_append_pdf( $mpdf, $agreement_file, 'Agreement PDF not found: ID ' . $ctx['assets']['agreement_pdf'] );
				}
			} elseif ( ! empty( $ctx['agreement_cpt_id'] ) ) {
				// If agreement is a CPT (custom post type), render it
				$agreement_html = sfpp_render_agreement_from_cpt( $ctx['agreement_cpt_id'] );
				if ( $agreement_html ) {
					$mpdf->AddPage();
					$mpdf->WriteHTML( $agreement_html );
				}
			}
		}

		// Step 10: Append conforme page
		if ( ! empty( $ctx['include_conforme'] ) ) {
			sfpp_append_conforme( $mpdf, $ctx );
		}

		// Step 11: Append ending page
		if ( ! empty( $ctx['assets']['ending'] ) ) {
			$ending_file = sfpp_get_asset_file_path( $ctx['assets']['ending'] );
			if ( $ending_file ) {
				sfpp_append_pdf( $mpdf, $ending_file, 'Ending PDF not found: ID ' . $ctx['assets']['ending'] );
			}
		}

		// Output PDF
		$filename = sanitize_file_name( $title ) . '.pdf';
		$mpdf->Output( $filename, 'I' ); // 'I' = inline display in browser

		exit;

	} catch ( Exception $e ) {
		wp_die( 'Error generating proposal PDF: ' . esc_html( $e->getMessage() ) );
	}
}

/**
 * Set letterhead template for proposal PDF.
 *
 * @param \Mpdf\Mpdf $mpdf          The mPDF instance.
 * @param string     $proposal_type Type: 'proposal', 'agreement', or 'cost_estimate'.
 * @return void
 */
function sfpp_set_proposal_letterhead( $mpdf, $proposal_type ) {
	// Define letterhead templates based on proposal type
	// This could load different header/footer templates
	// For now, we'll keep it simple with basic headers

	$header_text = 'Proposal';
	if ( 'agreement' === $proposal_type ) {
		$header_text = 'Agreement';
	} elseif ( 'cost_estimate' === $proposal_type ) {
		$header_text = 'Cost Estimate';
	}

	// Set header
	$mpdf->SetHeader( $header_text . '||{PAGENO}' );

	// Set footer
	$mpdf->SetFooter( get_bloginfo( 'name' ) . '||{DATE j-m-Y}' );
}

/**
 * Render agreement content from custom post type.
 *
 * @param int $cpt_id Custom post type ID.
 * @return string HTML content or empty string.
 */
function sfpp_render_agreement_from_cpt( $cpt_id ) {
	$post = get_post( $cpt_id );

	if ( ! $post ) {
		return '';
	}

	$html = '<h2>' . esc_html( $post->post_title ) . '</h2>';
	$html .= apply_filters( 'the_content', $post->post_content );

	return $html;
}
