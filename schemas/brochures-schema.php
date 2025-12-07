<?php
// schemas/brochures-schema.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brochure Schema Definition
 *
 * Defines the structure for brochure content stored in schema_json.
 * Simple schema with just heading and subheading.
 */
return [
	'id'     => 'brochure',
	'label'  => 'Brochure Schema',
	'groups' => [
		// Single content group
		[
			'id'     => 'content',
			'label'  => 'Content',
			'fields' => [
				[
					'key'         => 'content.heading',
					'label'       => 'Heading',
					'type'        => 'textarea',
					'rows'        => 2,
					'default'     => '',
					'description' => 'Main heading for the brochure',
				],
				[
					'key'         => 'content.subheading',
					'label'       => 'Subheading',
					'type'        => 'textarea',
					'rows'        => 3,
					'default'     => '',
					'description' => 'Subheading or short introduction',
				],
			],
		],
	],
];
