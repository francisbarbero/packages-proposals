<?php
// schemas/proposals-schema.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Proposal Schema Definition
 *
 * Defines the structure for proposal metadata stored in schema_json.
 * This schema is rendered as tabs in the proposal edit screen.
 */
return [
	'id'     => 'proposal',
	'label'  => 'Proposal Schema',
	'groups' => [
		// GROUP 1: Setup & Client
		[
			'id'     => 'setup_client',
			'label'  => 'Setup & Client',
			'fields' => [
				[
					'key'         => 'setup.reference',
					'label'       => 'Internal Reference',
					'type'        => 'text',
					'default'     => '',
					'description' => 'Internal tracking code or reference number',
				],
				[
					'key'         => 'setup.version',
					'label'       => 'Version',
					'type'        => 'text',
					'default'     => '1.0',
					'description' => 'Proposal version (e.g., 1.0, 2.0)',
				],
				[
					'key'         => 'client.contact_person',
					'label'       => 'Client Contact Person',
					'type'        => 'text',
					'default'     => '',
					'description' => 'Primary contact at client organization',
				],
				[
					'key'         => 'client.email',
					'label'       => 'Client Email',
					'type'        => 'text',
					'default'     => '',
					'description' => 'Client email address',
				],
				[
					'key'         => 'client.phone',
					'label'       => 'Client Phone',
					'type'        => 'text',
					'default'     => '',
					'description' => 'Client phone number',
				],
				[
					'key'         => 'client.address',
					'label'       => 'Client Address',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'Full client address',
				],
			],
		],

		// GROUP 2: Project
		[
			'id'     => 'project',
			'label'  => 'Project',
			'fields' => [
				[
					'key'         => 'project.summary',
					'label'       => 'Project Summary',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'Brief overview of the project',
				],
				[
					'key'         => 'project.objectives',
					'label'       => 'Project Objectives',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'What the project aims to achieve',
				],
				[
					'key'         => 'project.scope',
					'label'       => 'Scope Overview',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'High-level scope and boundaries',
				],
				[
					'key'         => 'project.deliverables',
					'label'       => 'Key Deliverables',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'What will be delivered',
				],
				[
					'key'         => 'project.timeline_overview',
					'label'       => 'Timeline Overview',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'Estimated timeline and milestones',
				],
			],
		],

		// GROUP 3: Solution
		[
			'id'     => 'solution',
			'label'  => 'Solution',
			'fields' => [
				[
					'key'         => 'solution.approach',
					'label'       => 'Recommended Approach',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'How we will solve the problem',
				],
				[
					'key'         => 'solution.methodology',
					'label'       => 'Methodology',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'Process and methodology to be used',
				],
				[
					'key'         => 'solution.technologies',
					'label'       => 'Technologies & Tools',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'Technical stack and tools',
				],
				[
					'key'         => 'solution.team',
					'label'       => 'Team Structure',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'Who will be working on this project',
				],
			],
		],

		// GROUP 4: Financials
		[
			'id'     => 'financials',
			'label'  => 'Financials',
			'fields' => [
				[
					'key'               => 'financials.fee_summary',
					'label'             => 'Fee Summary',
					'type'              => 'textarea',
					'default'           => '',
					'description'       => 'High-level explanation of fees (line items are separate)',
					'enable_populates'  => true,
					// 'populate_category' => 'financial',
				],
				[
					'key'               => 'financials.payment_terms',
					'label'             => 'Payment Terms',
					'type'              => 'textarea',
					'default'           => '',
					'description'       => 'Payment schedule and terms (e.g., 50% upfront, 50% on completion)',
					'enable_populates'  => true,
					// 'populate_category' => 'financial',
				],
				[
					'key'               => 'financials.payment_methods',
					'label'             => 'Accepted Payment Methods',
					'type'              => 'textarea',
					'default'           => '',
					'description'       => 'How client can pay (bank transfer, PayPal, etc.)',
					'enable_populates'  => true,
					// 'populate_category' => 'financial',
				],
				[
					'key'         => 'financials.validity',
					'label'       => 'Proposal Validity',
					'type'        => 'text',
					'default'     => '30 days',
					'description' => 'How long this proposal is valid',
				],
				[
					'key'               => 'financials.discount_notes',
					'label'             => 'Discount Notes',
					'type'              => 'textarea',
					'default'           => '',
					'description'       => 'Any special pricing or discounts applied',
					'enable_populates'  => true,
					// 'populate_category' => 'financial',
				],
			],
		],

		// GROUP 5: Presentation & Legal
		[
			'id'     => 'presentation_legal',
			'label'  => 'Presentation & Legal',
			'fields' => [
				// Asset selections for PDF generation
				[
					'key'         => 'assets.cover_page',
					'label'       => 'Cover Page',
					'type'        => 'radio',
					'options'     => 'sfpp_get_assets_options_for_type:cover_page',
					'default'     => '',
					'description' => 'Select cover page design for PDF',
				],
				[
					'key'         => 'assets.body_background',
					'label'       => 'Body Background',
					'type'        => 'radio',
					'options'     => 'sfpp_get_assets_options_for_type:body_background',
					'default'     => '',
					'description' => 'Background template for body pages',
				],
				[
					'key'         => 'assets.about_pdf',
					'label'       => 'About Us PDFs',
					'type'        => 'checkbox_multi',
					'options'     => 'sfpp_get_assets_options_for_type:about_pdf',
					'default'     => [],
					'description' => 'Include these About Us documents',
				],
				[
					'key'         => 'assets.terms_pdf',
					'label'       => 'Terms & Conditions',
					'type'        => 'radio',
					'options'     => 'sfpp_get_assets_options_for_type:terms_pdf',
					'default'     => '',
					'description' => 'Terms and conditions document',
				],
				[
					'key'         => 'assets.appendix_pdf',
					'label'       => 'Appendix PDFs',
					'type'        => 'checkbox_multi',
					'options'     => 'sfpp_get_assets_options_for_type:appendix_pdf',
					'default'     => [],
					'description' => 'Additional appendix documents',
				],
				[
					'key'         => 'legal.confidentiality_note',
					'label'       => 'Confidentiality Note',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'Confidentiality disclaimer or NDA reference',
				],
				[
					'key'         => 'legal.acceptance_instructions',
					'label'       => 'Acceptance Instructions',
					'type'        => 'textarea',
					'default'     => 'To accept this proposal, please sign and return a copy.',
					'description' => 'How client should indicate acceptance',
				],
			],
		],
	],
];
