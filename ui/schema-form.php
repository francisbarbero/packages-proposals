<?php
// ui/schema-form.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render schema groups as tab panels.
 *
 * Each group becomes:
 * <div class="sfpp-tab-panel sfpp-schema-group ..." data-tab-id="{group_id}">
 *   ...
 * </div>
 */
function sfpp_render_schema_groups( array $schema_def, array $schema_data ) {
    if ( empty( $schema_def['groups'] ) || ! is_array( $schema_def['groups'] ) ) {
        return;
    }

    foreach ( $schema_def['groups'] as $group ) {
        $group_id    = $group['id'] ?? '';
        $group_label = $group['label'] ?? '';
        $fields      = $group['fields'] ?? [];

        if ( ! $group_id || empty( $fields ) || ! is_array( $fields ) ) {
            continue;
        }
        ?>
        <div class="sfpp-tab-panel sfpp-schema-group sfpp-schema-group--<?php echo esc_attr( $group_id ); ?>"
             data-tab-id="<?php echo esc_attr( $group_id ); ?>">

            <?php if ( $group_label ) : ?>
                <h3 class="sfpp-tab-panel__title"><?php echo esc_html( $group_label ); ?></h3>
            <?php endif; ?>

            <?php
            foreach ( $fields as $field ) :
                if ( empty( $field['key'] ) ) {
                    continue;
                }

                $key         = $field['key'];
                $label       = $field['label'] ?? $key;
                $type        = $field['type'] ?? 'text';
                $default     = $field['default'] ?? '';
                $description = $field['description'] ?? '';
                $options     = $field['options'] ?? [];

                $name  = sfpp_schema_input_name( $key );
                $value = sfpp_schema_get_value( $schema_data, $key, $default );

                $field_id = 'sfpp_' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $key );

                // Populates sidebar opt-in flags
                $enable_populates = ! empty( $field['enable_populates'] );
                $populate_category = ! empty( $field['populate_category'] ) ? $field['populate_category'] : '';

                // Handle dynamic options (function calls)
                if ( is_string( $options ) && strpos( $options, ':' ) !== false ) {
                    list( $func_name, $func_param ) = explode( ':', $options, 2 );
                    if ( function_exists( $func_name ) ) {
                        $options = call_user_func( $func_name, $func_param );
                    } else {
                        $options = [];
                    }
                }
                ?>
                <div class="sfpp-field <?php echo ( 'textarea' === $type && $enable_populates ) ? 'sfpp-field--with-sidebar' : ''; ?>">
                    <?php if ( 'textarea' === $type ) : ?>
                        <?php if ( $enable_populates ) : ?>
                            <div class="sfpp-field__main">
                        <?php endif; ?>

                            <label for="<?php echo esc_attr( $field_id ); ?>">
                                <?php echo esc_html( $label ); ?>
                            </label>
                            <textarea id="<?php echo esc_attr( $field_id ); ?>"
                                      name="<?php echo esc_attr( $name ); ?>"
                                      rows="<?php echo isset( $field['rows'] ) ? (int) $field['rows'] : 3; ?>"
                                      class="large-text"><?php echo esc_textarea( $value ); ?></textarea>

                        <?php if ( $enable_populates && function_exists( 'sfpp_render_populates_sidebar' ) ) : ?>
                            </div>
                            <?php sfpp_render_populates_sidebar( $field_id, [
                                'category' => $populate_category,
                            ] ); ?>
                        <?php endif; ?>

                    <?php elseif ( 'checkbox' !== $type ) : ?>
                        <label for="<?php echo esc_attr( $field_id ); ?>">
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endif; ?>

                    <?php if ( 'select' === $type ) : ?>

                        <select id="<?php echo esc_attr( $field_id ); ?>"
                                name="<?php echo esc_attr( $name ); ?>">
                            <?php foreach ( $options as $opt_value => $opt_label ) : ?>
                                <option value="<?php echo esc_attr( $opt_value ); ?>" <?php selected( $value, $opt_value ); ?>>
                                    <?php echo esc_html( $opt_label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ( 'checkbox' === $type ) : ?>

                        <label>
                            <input type="checkbox"
                                   id="<?php echo esc_attr( $field_id ); ?>"
                                   name="<?php echo esc_attr( $name ); ?>"
                                   value="1" <?php checked( (bool) $value, true ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </label>

                    <?php elseif ( 'radio' === $type ) : ?>

                        <div class="sfpp-radio-group">
                            <?php foreach ( $options as $opt_value => $opt_label ) : ?>
                                <label class="sfpp-radio-label">
                                    <input type="radio"
                                           name="<?php echo esc_attr( $name ); ?>"
                                           value="<?php echo esc_attr( $opt_value ); ?>"
                                           <?php checked( $value, $opt_value ); ?>>
                                    <?php echo esc_html( $opt_label ); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ( 'checkbox_multi' === $type ) : ?>

                        <div class="sfpp-checkbox-group">
                            <?php
                            $selected_values = is_array( $value ) ? $value : [];
                            foreach ( $options as $opt_value => $opt_label ) :
                                if ( empty( $opt_value ) ) {
                                    continue; // Skip "None" option for multi-select
                                }
                            ?>
                                <label class="sfpp-checkbox-label">
                                    <input type="checkbox"
                                           name="<?php echo esc_attr( $name ); ?>[]"
                                           value="<?php echo esc_attr( $opt_value ); ?>"
                                           <?php checked( in_array( $opt_value, $selected_values, true ) || in_array( (string) $opt_value, $selected_values, true ) ); ?>>
                                    <?php echo esc_html( $opt_label ); ?>
                                </label><br>
                            <?php endforeach; ?>
                            <?php if ( empty( $options ) || ( count( $options ) === 1 && isset( $options[''] ) ) ) : ?>
                                <p><em>No options available</em></p>
                            <?php endif; ?>
                        </div>

                    <?php else : ?>

                        <input type="<?php echo esc_attr( $type ); ?>"
                               id="<?php echo esc_attr( $field_id ); ?>"
                               name="<?php echo esc_attr( $name ); ?>"
                               value="<?php echo esc_attr( $value ); ?>">

                    <?php endif; ?>

                    <?php if ( $description ) : ?>
                        <p class="description"><?php echo esc_html( $description ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

/**
 * Render a populates (text snippets) sidebar for easy insertion.
 *
 * @param string $target_field_id The ID of the textarea to insert into.
 * @param array  $args            Optional args: ['category' => 'financial']
 */
function sfpp_render_populates_sidebar( $target_field_id = '', $args = [] ) {
    if ( empty( $target_field_id ) ) {
        return;
    }

    $defaults = [
        'category' => '',
    ];
    $args = wp_parse_args( $args, $defaults );

    // Use existing helper function with category filter
    if ( function_exists( 'sfpp_get_populates' ) ) {
        $populates = sfpp_get_populates( [
            'category' => $args['category'] ?: null,
        ] );
    } else {
        $populates = [];
    }

    if ( empty( $populates ) ) {
        return;
    }
    ?>
    <div class="sfpp-populates-sidebar" data-target="<?php echo esc_attr( $target_field_id ); ?>">
        <div class="sfpp-populates-sidebar__header">
            <strong>Text Snippets</strong>
            <span class="sfpp-populates-sidebar__hint">Click to insert</span>
        </div>
        <ul class="sfpp-populates-sidebar__list">
            <?php foreach ( $populates as $populate ) :
                $preview = wp_strip_all_tags( $populate->content ?? '' );
                $preview = wp_trim_words( $preview, 8 );
                $content = $populate->content ?? '';
                ?>
                <li class="sfpp-populate-insert"
                    data-content="<?php echo esc_attr( $content ); ?>"
                    data-target="<?php echo esc_attr( $target_field_id ); ?>"
                    title="<?php echo esc_attr( $populate->title ?? '' ); ?>">
                    <span class="sfpp-populate-title"><?php echo esc_html( $populate->title ?? '' ); ?></span>
                    <span class="sfpp-populate-preview"><?php echo esc_html( $preview ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}
