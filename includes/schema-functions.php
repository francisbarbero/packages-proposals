<?php
/**
 * Schema Helper Functions
 * Centralized schema processing and retrieval functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a value from schema data array using dot notation.
 *
 * @param array  $data    The schema data array.
 * @param string $key     The key in dot notation (e.g. 'client.email').
 * @param mixed  $default Default value if key not found.
 * @return mixed The value or default.
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

/**
 * Set a value in schema data array using dot notation.
 *
 * @param array  &$data The schema data array (passed by reference).
 * @param string $key   The key in dot notation (e.g. 'client.email').
 * @param mixed  $value The value to set.
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
 * Generate input name for schema field.
 *
 * @param string $key The key in dot notation.
 * @return string The input name (e.g. 'schema[client][email]').
 */
function sfpp_schema_input_name( $key ) {
	$parts = explode( '.', $key );
	$name  = 'schema';
	foreach ( $parts as $part ) {
		$name .= '[' . $part . ']';
	}
	return $name;
}

/**
 * Process schema data from form submission.
 * Sanitizes and validates schema field values based on the schema definition.
 *
 * @param array $schema    Schema definition array.
 * @param array $raw_data  Raw schema data from request ($_POST['schema']).
 * @return array Processed and sanitized schema data array.
 */
function sfpp_process_schema_data( $schema, $raw_data ) {
	if ( ! is_array( $raw_data ) ) {
		return [];
	}

	$processed = [];

	// Process each group and field
	if ( ! empty( $schema['groups'] ) && is_array( $schema['groups'] ) ) {
		foreach ( $schema['groups'] as $group ) {
			$fields = $group['fields'] ?? [];
			if ( ! is_array( $fields ) ) {
				continue;
			}

			foreach ( $fields as $field ) {
				$key = $field['key'] ?? '';
				if ( ! $key ) {
					continue;
				}

				$default = $field['default'] ?? '';
				$value = sfpp_schema_get_value( $raw_data, $key, $default );
				$type = $field['type'] ?? 'text';

				// Sanitize based on field type
				if ( 'checkbox_multi' === $type ) {
					// Multi-select checkbox - array of values
					$value = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : [];
				} elseif ( 'textarea' === $type ) {
					// Textarea - allow some HTML
					$value = wp_kses_post( $value );
				} else {
					// Text, radio, select, checkbox - sanitize as text
					$value = sanitize_text_field( $value );
				}

				sfpp_schema_set_value( $processed, $key, $value );
			}
		}
	}

	return $processed;
}

/**
 * Get proposal schema definition.
 *
 * @return array Schema definition array.
 */
function sfpp_get_proposal_schema() {
	$path = dirname( __DIR__ ) . '/schemas/proposals-schema.php';
	if ( file_exists( $path ) ) {
		$schema = include $path;
		return is_array( $schema ) ? $schema : [];
	}
	return [];
}

/**
 * Get brochures schema definition.
 *
 * @return array Schema definition array.
 */
function sfpp_get_brochures_schema() {
	$path = dirname( __DIR__ ) . '/schemas/brochures-schema.php';
	if ( file_exists( $path ) ) {
		$schema = include $path;
		return is_array( $schema ) ? $schema : [];
	}
	return [];
}
