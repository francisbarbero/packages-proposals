<?php
// schemas/maintenance-packages-schema.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'     => 'maintenance_package',
    'label'  => 'Maintenance Package Schema',
    'groups' => [
        [
            'id'    => 'coverage',
            'label' => 'Coverage & Scope',
            'fields' => [
                [
                    'key'         => 'coverage.updates',
                    'label'       => 'Updates included',
                    'type'        => 'textarea',
                    'default'     => "WordPress core updates\nPlugin updates\nTheme updates (if supported)",
                    'description' => 'List the types of updates included in this plan.',
                ],
                [
                    'key'         => 'coverage.monitoring',
                    'label'       => 'Monitoring',
                    'type'        => 'textarea',
                    'default'     => '',
                    'description' => 'e.g. uptime monitoring, security monitoring, performance checks.',
                ],
                [
                    'key'         => 'coverage.content_edits',
                    'label'       => 'Content edits included',
                    'type'        => 'textarea',
                    'default'     => '',
                    'description' => 'If this plan includes a small number of monthly content edits, document it here.',
                ],
            ],
        ],
        [
            'id'    => 'limits',
            'label' => 'Limits & Frequency',
            'fields' => [
                [
                    'key'         => 'limits.sites',
                    'label'       => 'Number of sites covered',
                    'type'        => 'number',
                    'default'     => 1,
                    'description' => 'How many WordPress installs are covered by this plan.',
                ],
                [
                    'key'         => 'limits.response_time',
                    'label'       => 'Target response time',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. “Within 1 business day”, “Same-day for critical issues”.',
                ],
                [
                    'key'         => 'limits.tasks_per_month',
                    'label'       => 'Included tasks per month',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. number of maintenance tasks or support tickets covered.',
                ],
            ],
        ],
        [
            'id'    => 'security',
            'label' => 'Security & Backups',
            'fields' => [
                [
                    'key'         => 'security.firewall',
                    'label'       => 'Firewall / security tools',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. “Wordfence configured”, “Cloudflare WAF”, etc.',
                ],
                [
                    'key'         => 'security.backups',
                    'label'       => 'Backups',
                    'type'        => 'text',
                    'default'     => '',
                    'description' => 'e.g. “Daily backups, 7 days retention”, “Weekly server snapshots”, etc.',
                ],
                [
                    'key'         => 'security.restore_included',
                    'label'       => 'Restore included',
                    'type'        => 'checkbox',
                    'default'     => true,
                    'description' => 'Tick if disaster recovery / restores are included in this plan.',
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
                    'description' => 'For your own notes about scope, boundaries, exclusions, etc.',
                ],
            ],
        ],
    ],
];
