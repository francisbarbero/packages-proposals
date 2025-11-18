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
                ?>
                <div class="sfpp-field">
                    <?php if ( 'checkbox' !== $type ) : ?>
                        <label for="<?php echo esc_attr( $field_id ); ?>">
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endif; ?>

                    <?php if ( 'textarea' === $type ) : ?>

                        <textarea id="<?php echo esc_attr( $field_id ); ?>"
                                  name="<?php echo esc_attr( $name ); ?>"
                                  rows="3"
                                  class="large-text"><?php echo esc_textarea( $value ); ?></textarea>

                    <?php elseif ( 'select' === $type ) : ?>

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
