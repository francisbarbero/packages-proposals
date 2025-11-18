<?php
// schemas/hosting-packages-schema.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'     => 'hosting_package',
    'label'  => 'Hosting Package Schema',
    'groups' => [
        [
            'id'    => 'resources',
            'label' => 'Resources & Limits',
            'fields' => [
                [
                    'key'         => 'resources.disk_space',
                    'label'       => 'Disk space',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. 10 GB SSD, 50 GB SSD, “Unmetered (fair use)”.',
                ],
                [
                    'key'         => 'resources.bandwidth',
                    'label'       => 'Bandwidth',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. “Unmetered”, “Up to 1 TB/month”, etc.',
                ],
                [
                    'key'         => 'resources.sites',
                    'label'       => 'Number of sites',
                    'type'        => 'number',
                    'default'     => 1,
                    'description' => 'How many WordPress installs are included.',
                ],
                [
                    'key'         => 'resources.emails_included',
                    'label'       => 'Email accounts included',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. “No email”, “Up to 5 accounts”, “Separate email provider”.',
                ],
            ],
        ],
        [
            'id'    => 'performance',
            'label' => 'Performance & Infrastructure',
            'fields' => [
                [
                    'key'         => 'performance.host_type',
                    'label'       => 'Host type',
                    'type'        => 'select',
                    'options'     => [
                        ''          => 'Select…',
                        'shared'    => 'Shared hosting',
                        'vps'       => 'VPS / Cloud',
                        'dedicated' => 'Dedicated',
                        'managed'   => 'Managed WP platform',
                    ],
                    'default'     => '',
                    'description' => 'Underlying hosting environment.',
                ],
                [
                    'key'         => 'performance.cdn_included',
                    'label'       => 'CDN included',
                    'type'        => 'checkbox',
                    'default'     => true,
                    'description' => 'Tick if a CDN (e.g. Cloudflare) is part of the package.',
                ],
                [
                    'key'         => 'performance.backups',
                    'label'       => 'Backups',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. “Daily, 7 days retention”, “Weekly, 4 copies kept”, etc.',
                ],
            ],
        ],
        [
            'id'    => 'management',
            'label' => 'Management & Support',
            'fields' => [
                [
                    'key'         => 'management.support_window',
                    'label'       => 'Support window',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. “Business hours PH time”, “Email only”, “Priority support”, etc.',
                ],
                [
                    'key'         => 'management.uptime_sla',
                    'label'       => 'Uptime SLA',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. “99.9% target (best effort)”.',
                ],
                [
                    'key'         => 'management.wp_core_updates',
                    'label'       => 'WP core & plugin updates handled',
                    'type'        => 'checkbox',
                    'default'     => true,
                    'description' => 'Tick if you handle core/plugin updates as part of this hosting package.',
                ],
            ],
        ],
        [
            'id'    => 'internal',
            'label' => 'Internal Notes',
            'fields' => [
                [
                    'key'         => 'notes_internal',
                    'label'       => 'Internal notes',
                    'type'        => 'textarea',
                    'default'     => '',
                    'description' => 'For your own notes about positioning, vendor, margins, etc.',
                ],
            ],
        ],
    ],
];
