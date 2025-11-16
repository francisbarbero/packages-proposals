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
    // We'll wire this properly later.
    if ( strpos( $class, 'SFPP\\' ) !== 0 ) {
        return;
    }
    // Example: SFPP\App\Domain\Package -> /app/Domain/Package.php
    $relative = str_replace( [ 'SFPP\\', '\\' ], [ '', DIRECTORY_SEPARATOR ], $class );
    $path     = __DIR__ . DIRECTORY_SEPARATOR . $relative . '.php';

    if ( file_exists( $path ) ) {
        require_once $path;
    }
} );


// Front-end actions (handles things like creating packages from the UI).
require_once __DIR__ . '/ui/front-actions.php';

// Ensure front-end POST actions are handled before rendering the page.
add_action( 'template_redirect', 'sfpp_handle_front_actions' );



/**
 * Shortcode to render the front-end app.
 * You can create a WP Page and drop [sfpp_app] in the content.
 */
add_shortcode( 'sfpp_app', function () {
    if ( ! defined( 'ABSPATH' ) ) {
        return '';
    }

    // Determine which section to show: dashboard, packages, extras, proposals.
    $section = isset( $_GET['sfpp_section'] ) ? sanitize_key( $_GET['sfpp_section'] ) : 'dashboard';

    // Basic whitelist.
    $allowed_sections = [ 'dashboard', 'packages', 'extras', 'proposals' ];
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
 * Renders the top navigation inside the app (front-end).
 */
function sfpp_render_app_nav( $active_section ) {
    // Get the base URL of the current page (without sfpp_section).
    $base_url = remove_query_arg( 'sfpp_section' );

    $links = [
        'dashboard' => 'Dashboard',
        'packages'  => 'Website Packages',
        'extras'    => 'Website Extras',
        'proposals' => 'Proposals',
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
 * Includes the appropriate view file for the current section.
 * For now, these are just stubs in /ui/Views/.
 */
function sfpp_render_app_section( $section ) {
    $base_dir = __DIR__ . '/ui/Views';

    switch ( $section ) {
        case 'packages':
            // Decide between list view and edit view.
            $view_type  = isset( $_GET['sfpp_view'] ) ? sanitize_key( $_GET['sfpp_view'] ) : 'list';
            $package_id = isset( $_GET['package_id'] ) ? (int) $_GET['package_id'] : 0;

            if ( 'edit' === $view_type ) {
                $view = $base_dir . '/package-edit.php';
            } else {
                $view = $base_dir . '/packages-list.php';
            }
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


add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'sfpp-app',
        plugins_url( 'public/css/app.css', __FILE__ ),
        [],
        '0.1'
    );
} );



/**
 * Fetch active Website Packages from the sf_packages table.
 * For now this is a simple helper; later we can move it to a repository class.
 *
 * @return array of stdClass rows from the database.
 */
function sfpp_get_website_packages() {
    global $wpdb;

    $table = 'sf_packages';

    $sql = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE type = %s AND status = %s ORDER BY name ASC",
        'website',
        'active'
    );

    return $wpdb->get_results( $sql );
}

/**
 * Fetch a single Website Package by ID.
 *
 * @param int $id
 * @return object|null
 */
function sfpp_get_website_package( $id ) {
    global $wpdb;

    $id    = (int) $id;
    $table = 'sf_packages'; // adjust if you used a prefix like wp_sf_packages

    if ( $id <= 0 ) {
        return null;
    }

    $sql = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d AND type = %s",
        $id,
        'website'
    );

    return $wpdb->get_row( $sql );
}

/**
 * Get the schema definition for Website Packages.
 *
 * This reads schemas/website-packages-schema.php and returns the array.
 *
 * @return array
 */
function sfpp_get_website_package_schema() {
    $path = __DIR__ . '/schemas/website-packages-schema.php';

    if ( file_exists( $path ) ) {
        $schema = include $path;
        if ( is_array( $schema ) ) {
            return $schema;
        }
    }

    return [];
}


/**
 * Get a value from a nested schema array using dot notation.
 * Example: key "pages.included_count" => $data['pages']['included_count']
 */
function sfpp_schema_get_value( $data, $key, $default = '' ) {
    if ( ! is_array( $data ) ) {
        return $default;
    }

    $parts   = explode( '.', $key );
    $current = $data;

    foreach ( $parts as $part ) {
        if ( ! is_array( $current ) || ! array_key_exists( $part, $current ) ) {
            return $default;
        }
        $current = $current[ $part ];
    }

    return $current;
}

/**
 * Set a value in a nested schema array using dot notation.
 */
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

/**
 * Build an input name for a schema field key using nested array syntax.
 * "pages.included_count" => schema[pages][included_count]
 */
function sfpp_schema_input_name( $key ) {
    $parts = explode( '.', $key );
    $name  = 'schema';
    foreach ( $parts as $part ) {
        $name .= '[' . $part . ']';
    }
    return $name;
}
