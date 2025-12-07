<?php
// schemas/populates-schema.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Populates Schema Definition
 *
 * Defines the structure for populate metadata stored in schema_json.
 */
return [
	'id'     => 'populates',
	'label'  => 'Populates Schema',
	'groups' => [
		// GROUP 1: Details
		[
			'id'     => 'details',
			'label'  => 'Details',
			'fields' => [
				[
					'key'         => 'details.notes',
					'label'       => 'Internal Notes',
					'type'        => 'textarea',
					'rows'        => 3,
					'default'     => '',
					'description' => 'Internal notes (not shown in the output)',
				],
				[
					'key'         => 'details.tags',
					'label'       => 'Tags',
					'type'        => 'text',
					'default'     => '',
					'description' => 'Optional labels to help you find this snippet later.',
				],
			],
		],
	],
];
