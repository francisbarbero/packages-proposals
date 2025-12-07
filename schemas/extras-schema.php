<?php
// schemas/extras-schema.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extras Schema Definition
 *
 * Defines the structure for extra metadata stored in schema_json.
 */
return [
	'id'     => 'extras',
	'label'  => 'Extras Schema',
	'groups' => [
		// GROUP 1: Details
		[
			'id'     => 'details',
			'label'  => 'Details',
			'fields' => [
				[
					'key'         => 'details.full_description',
					'label'       => 'Full Description',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'Detailed description of this extra',
				],
				[
					'key'         => 'details.notes',
					'label'       => 'Internal Notes',
					'type'        => 'textarea',
					'default'     => '',
					'description' => 'Internal notes (not shown to clients)',
				],
				[
					'key'         => 'details.category',
					'label'       => 'Category',
					'type'        => 'select',
					'options'     => [
						''          => 'None',
						'page'      => 'Additional Page',
						'feature'   => 'Feature',
						'service'   => 'Service',
						'training'  => 'Training',
						'support'   => 'Support',
					],
					'default'     => '',
					'description' => 'Type of extra',
				],
				[
					'key'         => 'details.duration',
					'label'       => 'Duration/Time Required',
					'type'        => 'text',
					'default'     => '',
					'description' => 'Estimated time or duration (e.g., "2 hours", "1 week")',
				],
			],
		],
	],
];
