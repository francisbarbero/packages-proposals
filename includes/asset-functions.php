<?php
/**
 * Simplified Asset Management Functions
 * Handles assets with custom table (sf_assets)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create database table for assets system.
 * Call this during plugin activation.
 */
function sfpp_create_assets_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS sf_assets (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL DEFAULT '',
        slug varchar(100) NOT NULL DEFAULT '',
        asset_type varchar(50) NOT NULL DEFAULT '',
        attachment_id bigint(20) DEFAULT NULL,
        content longtext,
        metadata_json longtext,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY asset_type (asset_type)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'sfpp_assets_db_version', '1.0' );
}

/**
 * Create a new asset.
 */
function sfpp_create_asset( $data ) {
    global $wpdb;

    $defaults = [
        'name' => '',
        'slug' => '',
        'asset_type' => '',
        'attachment_id' => null,
        'content' => '',
        'metadata_json' => '',
    ];

    $asset_data = wp_parse_args( $data, $defaults );

    // Add timestamps
    $asset_data['created_at'] = current_time( 'mysql' );
    $asset_data['updated_at'] = current_time( 'mysql' );

    // Sanitize text fields
    $asset_data['name'] = sanitize_text_field( $asset_data['name'] );
    $asset_data['slug'] = sanitize_text_field( $asset_data['slug'] );
    $asset_data['asset_type'] = sanitize_text_field( $asset_data['asset_type'] );

    // Sanitize content (allow basic HTML)
    $asset_data['content'] = wp_kses_post( $asset_data['content'] );

    // Handle attachment_id
    if ( isset( $data['attachment_id'] ) && ! empty( $data['attachment_id'] ) ) {
        $asset_data['attachment_id'] = (int) $data['attachment_id'];
    } else {
        $asset_data['attachment_id'] = null;
    }

    // Handle metadata
    if ( isset( $data['metadata'] ) && is_array( $data['metadata'] ) ) {
        $asset_data['metadata_json'] = wp_json_encode( $data['metadata'] );
    } elseif ( isset( $data['metadata_json'] ) ) {
        $asset_data['metadata_json'] = $data['metadata_json'];
    }

    $result = $wpdb->insert( 'sf_assets', $asset_data );

    if ( $result === false ) {
        return false;
    }

    $asset_id = $wpdb->insert_id;

    do_action( 'sfpp_asset_created', $asset_id, $asset_data );

    return $asset_id;
}

/**
 * Update an existing asset.
 */
function sfpp_update_asset( $id, $data ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return false;
    }

    // Add updated timestamp
    $data['updated_at'] = current_time( 'mysql' );

    $clean_data = [];

    // Sanitize text fields
    if ( isset( $data['name'] ) ) {
        $clean_data['name'] = sanitize_text_field( $data['name'] );
    }
    if ( isset( $data['slug'] ) ) {
        $clean_data['slug'] = sanitize_text_field( $data['slug'] );
    }
    if ( isset( $data['asset_type'] ) ) {
        $clean_data['asset_type'] = sanitize_text_field( $data['asset_type'] );
    }

    // Sanitize content (allow basic HTML)
    if ( isset( $data['content'] ) ) {
        $clean_data['content'] = wp_kses_post( $data['content'] );
    }

    // Handle attachment_id
    if ( isset( $data['attachment_id'] ) ) {
        if ( ! empty( $data['attachment_id'] ) ) {
            $clean_data['attachment_id'] = (int) $data['attachment_id'];
        } else {
            $clean_data['attachment_id'] = null;
        }
    }

    // Handle metadata
    if ( isset( $data['metadata'] ) && is_array( $data['metadata'] ) ) {
        $clean_data['metadata_json'] = wp_json_encode( $data['metadata'] );
    } elseif ( isset( $data['metadata_json'] ) ) {
        $clean_data['metadata_json'] = $data['metadata_json'];
    }

    // Always update timestamp
    $clean_data['updated_at'] = current_time( 'mysql' );

    $result = $wpdb->update(
        'sf_assets',
        $clean_data,
        [ 'id' => $id ]
    );

    if ( $result !== false ) {
        do_action( 'sfpp_asset_updated', $id, $clean_data );
    }

    return $result !== false;
}

/**
 * Get an asset by ID.
 */
function sfpp_get_asset( $id ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return null;
    }

    $sql = $wpdb->prepare(
        "SELECT * FROM sf_assets WHERE id = %d",
        $id
    );

    $asset = $wpdb->get_row( $sql );

    if ( $asset ) {
        do_action( 'sfpp_asset_loaded', $asset );
    }

    return $asset;
}

/**
 * Get an asset by slug.
 */
function sfpp_get_asset_by_slug( $slug ) {
    global $wpdb;

    $slug = sanitize_text_field( $slug );
    if ( empty( $slug ) ) {
        return null;
    }

    $sql = $wpdb->prepare(
        "SELECT * FROM sf_assets WHERE slug = %s",
        $slug
    );

    $asset = $wpdb->get_row( $sql );

    if ( $asset ) {
        do_action( 'sfpp_asset_loaded', $asset );
    }

    return $asset;
}

/**
 * Get assets list, optionally filtered by type.
 */
function sfpp_get_assets( $type = null ) {
    global $wpdb;

    $where_conditions = [];
    $prepare_values = [];

    if ( $type ) {
        $where_conditions[] = "asset_type = %s";
        $prepare_values[] = $type;
    }

    $where_clause = '';
    if ( ! empty( $where_conditions ) ) {
        $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
    }

    $sql = "SELECT * FROM sf_assets {$where_clause} ORDER BY name ASC";

    if ( ! empty( $prepare_values ) ) {
        $sql = $wpdb->prepare( $sql, ...$prepare_values );
    }

    $assets = $wpdb->get_results( $sql );

    return $assets;
}

/**
 * Delete an asset.
 */
function sfpp_delete_asset( $id ) {
    global $wpdb;

    $id = (int) $id;
    if ( $id <= 0 ) {
        return false;
    }

    $result = $wpdb->delete(
        'sf_assets',
        [ 'id' => $id ]
    );

    if ( $result !== false && $result > 0 ) {
        do_action( 'sfpp_asset_deleted', $id );
    }

    return $result !== false && $result > 0;
}

/**
 * Create asset from form request data.
 */
function sfpp_create_asset_from_request( $post ) {
    // Basic asset fields
    $asset_data = [
        'name' => sanitize_text_field( wp_unslash( $post['name'] ?? '' ) ),
        'slug' => sanitize_text_field( wp_unslash( $post['slug'] ?? '' ) ),
        'asset_type' => sanitize_text_field( wp_unslash( $post['asset_type'] ?? '' ) ),
        'content' => isset( $post['content'] ) ? wp_kses_post( wp_unslash( $post['content'] ) ) : '',
    ];

    // Handle attachment_id
    if ( isset( $post['attachment_id'] ) && ! empty( $post['attachment_id'] ) ) {
        $asset_data['attachment_id'] = (int) $post['attachment_id'];
    }

    // Handle metadata
    if ( isset( $post['metadata'] ) && is_array( $post['metadata'] ) ) {
        $asset_data['metadata'] = map_deep( $post['metadata'], 'sanitize_text_field' );
    }

    return sfpp_create_asset( $asset_data );
}

/**
 * Update asset from form request data.
 */
function sfpp_update_asset_from_request( $id, $post ) {
    $asset = sfpp_get_asset( $id );
    if ( ! $asset ) {
        return false;
    }

    // Update basic asset fields
    $update_data = [
        'name' => sanitize_text_field( wp_unslash( $post['name'] ?? '' ) ),
        'slug' => sanitize_text_field( wp_unslash( $post['slug'] ?? '' ) ),
        'asset_type' => sanitize_text_field( wp_unslash( $post['asset_type'] ?? '' ) ),
        'content' => isset( $post['content'] ) ? wp_kses_post( wp_unslash( $post['content'] ) ) : '',
    ];

    // Handle attachment_id
    if ( isset( $post['attachment_id'] ) ) {
        if ( ! empty( $post['attachment_id'] ) ) {
            $update_data['attachment_id'] = (int) $post['attachment_id'];
        } else {
            $update_data['attachment_id'] = null;
        }
    }

    // Handle metadata
    if ( isset( $post['metadata'] ) && is_array( $post['metadata'] ) ) {
        $update_data['metadata'] = map_deep( $post['metadata'], 'sanitize_text_field' );
    }

    return sfpp_update_asset( $id, $update_data );
}

/**
 * Get assets grouped by type.
 * Returns an associative array: [ 'cover_page' => [...], 'body_background' => [...], ... ]
 */
function sfpp_get_assets_grouped_by_type() {
    $all_assets = sfpp_get_assets();

    $grouped = [
        'cover_page' => [],
        'body_background' => [],
        'about_pdf' => [],
        'terms_pdf' => [],
        'appendix_pdf' => [],
    ];

    foreach ( $all_assets as $asset ) {
        $type = $asset->asset_type ?? '';
        if ( isset( $grouped[ $type ] ) ) {
            $grouped[ $type ][] = $asset;
        }
    }

    return $grouped;
}

/**
 * Get asset options for a specific type (for use in select/radio fields).
 * Returns array like: [ '' => 'None', '1' => 'Asset Name', '2' => 'Other Asset', ... ]
 */
function sfpp_get_assets_options_for_type( $type ) {
    $assets = sfpp_get_assets( $type );

    $options = [ '' => '— None —' ];

    if ( empty( $assets ) ) {
        $options[''] = '— No assets available —';
        return $options;
    }

    foreach ( $assets as $asset ) {
        $options[ (string) $asset->id ] = $asset->name ?? 'Unnamed Asset';
    }

    return $options;
}

/**
 * Get file path for an asset by ID.
 * This resolves the asset to its actual file path on disk.
 *
 * @param int $asset_id Asset ID
 * @return string|false File path or false if not found
 */
function sfpp_get_asset_file_path( $asset_id ) {
    $asset = sfpp_get_asset( $asset_id );

    if ( ! $asset ) {
        return false;
    }

    // If asset has an attachment_id, get WordPress upload path
    if ( ! empty( $asset->attachment_id ) ) {
        $file_path = get_attached_file( $asset->attachment_id );
        if ( $file_path && file_exists( $file_path ) ) {
            return $file_path;
        }
    }

    // If asset has metadata with file_path
    if ( ! empty( $asset->metadata_json ) ) {
        $metadata = json_decode( $asset->metadata_json, true );
        if ( ! empty( $metadata['file_path'] ) ) {
            $file_path = $metadata['file_path'];

            // Convert relative to absolute if needed
            if ( ! file_exists( $file_path ) ) {
                // Try relative to plugin directory
                $plugin_path = dirname( dirname( __FILE__ ) ) . '/' . ltrim( $file_path, '/' );
                if ( file_exists( $plugin_path ) ) {
                    return $plugin_path;
                }

                // Try relative to uploads directory
                $upload_dir = wp_upload_dir();
                $upload_path = $upload_dir['basedir'] . '/' . ltrim( $file_path, '/' );
                if ( file_exists( $upload_path ) ) {
                    return $upload_path;
                }
            } else {
                return $file_path;
            }
        }
    }

    return false;
}

/**
 * Get multiple asset file paths from array of IDs.
 *
 * @param array $asset_ids Array of asset IDs
 * @return array Array of file paths (indexed by asset ID)
 */
function sfpp_get_asset_file_paths( $asset_ids ) {
    if ( empty( $asset_ids ) || ! is_array( $asset_ids ) ) {
        return [];
    }

    $file_paths = [];

    foreach ( $asset_ids as $asset_id ) {
        $file_path = sfpp_get_asset_file_path( $asset_id );
        if ( $file_path ) {
            $file_paths[ $asset_id ] = $file_path;
        }
    }

    return $file_paths;
}
