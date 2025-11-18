<?php
/**
 * Plugin Name: Starfish Proposals & Packages
 * Description: Internal proposals, packages, and assets tool (front-end UI).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Simple autoloader placeholder for later.
spl_autoload_register( function ( $class ) {
    if ( strpos( $class, 'SFPP\\' ) !== 0 ) {
        return;
    }
    $relative = str_replace( [ 'SFPP\\', '\\' ], [ '', DIRECTORY_SEPARATOR ], $class );
    $path     = __DIR__ . DIRECTORY_SEPARATOR . $relative . '.php';

    if ( file_exists( $path ) ) {
        require_once $path;
    }
} );

// Front-end actions.
require_once __DIR__ . '/ui/front-actions.php';
// Schema form renderer.
require_once __DIR__ . '/ui/schema-form.php';

// Handle POST actions before rendering.
add_action( 'template_redirect', 'sfpp_handle_front_actions' );

/**
 * Shortcode to render the front-end app.
 */
add_shortcode( 'sfpp_app', function () {
    if ( ! defined( 'ABSPATH' ) ) {
        return '';
    }

    $section = isset( $_GET['sfpp_section'] ) ? sanitize_key( $_GET['sfpp_section'] ) : 'dashboard';

    $allowed_sections = [ 'dashboard', 'packages', 'hosting', 'maintenance', 'extras', 'proposals' ];
    if ( ! in_array( $section, $allowed_sections, true ) ) {
        $section = 'dashboard';
    }

    ob_start();
    ?>
    <div id="sfpp-app" class="sfpp-app">

        <?php sfpp_render_app_nav( $section ); ?>

        <div class="sfpp-app-content">
            <?php sfpp_render_app_section( $section ); ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
} );

/**
 * Top navigation.
 */
function sfpp_render_app_nav( $active_section ) {
    // Strip all SFPP routing params so tab clicks always go to list views.
    $base_url = remove_query_arg(
        [
            'sfpp_section',
            'sfpp_view',
            'package_id',
            'sfpp_notice',
            'sfpp_new_id',
        ]
    );

    $links = [
        'dashboard'   => 'Dashboard',
        'packages'    => 'Website Packages',
        'hosting'     => 'Hosting Packages',
        'maintenance' => 'Maintenance Packages',
        'extras'      => 'Website Extras',
        'proposals'   => 'Proposals',
    ];
    ?>
    <nav class="sfpp-nav">
        <ul class="sfpp-nav-list">
            <?php foreach ( $links as $section => $label ) :
                $url   = add_query_arg( 'sfpp_section', $section, $base_url );
                $class = ( $section === $active_section ) ? 'sfpp-nav-item sfpp-nav-item--active' : 'sfpp-nav-item';
            ?>
                <li class="<?php echo esc_attr( $class ); ?>">
                    <a href="<?php echo esc_url( $url ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
}

/**
 * Section router.
 */
function sfpp_render_app_section( $section ) {
    $base_dir = __DIR__ . '/ui/Views';

    switch ( $section ) {
        case 'packages':
            $view_type = isset( $_GET['sfpp_view'] ) ? sanitize_key( $_GET['sfpp_view'] ) : 'list';
            $view      = ( 'edit' === $view_type ) ? $base_dir . '/package-edit.php' : $base_dir . '/packages-list.php';
            break;

        case 'hosting':
            $view_type = isset( $_GET['sfpp_view'] ) ? sanitize_key( $_GET['sfpp_view'] ) : 'list';
            $view      = ( 'edit' === $view_type ) ? $base_dir . '/package-edit.php' : $base_dir . '/hosting-list.php';
            break;

        case 'maintenance':
            $view_type = isset( $_GET['sfpp_view'] ) ? sanitize_key( $_GET['sfpp_view'] ) : 'list';
            $view      = ( 'edit' === $view_type ) ? $base_dir . '/package-edit.php' : $base_dir . '/maintenance-list.php';
            break;

        case 'extras':
            $view = $base_dir . '/extras-list.php';
            break;

        case 'proposals':
            $view = $base_dir . '/proposals-placeholder.php';
            break;

        case 'dashboard':
        default:
            $view = $base_dir . '/dashboard.php';
            break;
    }

    if ( file_exists( $view ) ) {
        include $view;
    } else {
        echo '<p>View not found.</p>';
    }
}

// CSS + JS.
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'sfpp-app',
        plugins_url( 'public/css/app.css', __FILE__ ),
        [],
        '0.1'
    );

    wp_enqueue_script(
        'sfpp-app-js',
        plugins_url( 'public/js/app.js', __FILE__ ),
        [ 'jquery' ],
        '0.1',
        true
    );
} );

/**
 * Generic helpers.
 */
function sfpp_get_packages_by_type( $type, $status = 'active' ) {
    global $wpdb;

    $table = 'sf_packages';

    $sql = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE type = %s AND status = %s ORDER BY name ASC",
        $type,
        $status
    );

    return $wpdb->get_results( $sql );
}

function sfpp_get_package_by_id( $id ) {
    global $wpdb;

    $id    = (int) $id;
    $table = 'sf_packages';

    if ( $id <= 0 ) {
        return null;
    }

    $sql = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $id
    );

    return $wpdb->get_row( $sql );
}

/**
 * Type-specific helpers.
 */
function sfpp_get_website_packages() {
    return sfpp_get_packages_by_type( 'website', 'active' );
}

function sfpp_get_website_package( $id ) {
    $row = sfpp_get_package_by_id( $id );
    return ( $row && $row->type === 'website' ) ? $row : null;
}

function sfpp_get_hosting_packages() {
    return sfpp_get_packages_by_type( 'hosting', 'active' );
}

function sfpp_get_hosting_package( $id ) {
    $row = sfpp_get_package_by_id( $id );
    return ( $row && $row->type === 'hosting' ) ? $row : null;
}

function sfpp_get_maintenance_packages() {
    return sfpp_get_packages_by_type( 'maintenance', 'active' );
}

function sfpp_get_maintenance_package( $id ) {
    $row = sfpp_get_package_by_id( $id );
    return ( $row && $row->type === 'maintenance' ) ? $row : null;
}

/**
 * Schema helpers.
 */
function sfpp_schema_get_value( $data, $key, $default = '' ) {
    if ( ! is_array( $data ) ) {
        return $default;
    }

    $parts   = explode( '.', $key );
    $current = $data;

    foreach ( $parts as $part ) {
        if ( ! isset( $current[ $part ] ) ) {
            return $default;
        }
        $current = $current[ $part ];
    }

    return $current;
}

function sfpp_schema_set_value( &$data, $key, $value ) {
    if ( ! is_array( $data ) ) {
        $data = [];
    }

    $parts = explode( '.', $key );
    $last  = array_pop( $parts );

    $current =& $data;

    foreach ( $parts as $part ) {
        if ( ! isset( $current[ $part ] ) || ! is_array( $current[ $part ] ) ) {
            $current[ $part ] = [];
        }
        $current =& $current[ $part ];
    }

    $current[ $last ] = $value;
}

function sfpp_schema_input_name( $key ) {
    $parts = explode( '.', $key );
    $name  = 'schema';
    foreach ( $parts as $part ) {
        $name .= '[' . $part . ']';
    }
    return $name;
}

/**
 * Schema getters.
 */
function sfpp_get_website_package_schema() {
    $path = __DIR__ . '/schemas/website-packages-schema.php';
    if ( file_exists( $path ) ) {
        $schema = include $path;
        return is_array( $schema ) ? $schema : [];
    }
    return [];
}

function sfpp_get_hosting_package_schema() {
    $path = __DIR__ . '/schemas/hosting-packages-schema.php';
    if ( file_exists( $path ) ) {
        $schema = include $path;
        return is_array( $schema ) ? $schema : [];
    }
    return [];
}

function sfpp_get_maintenance_package_schema() {
    $path = __DIR__ . '/schemas/maintenance-packages-schema.php';
    if ( file_exists( $path ) ) {
        $schema = include $path;
        return is_array( $schema ) ? $schema : [];
    }
    return [];
}
