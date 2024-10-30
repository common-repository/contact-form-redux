<?php
/**
 *  A base module for [checkbox], [checkbox*], and [radio]
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_checkbox');

function cfredux_add_form_tag_checkbox()
{
    cfredux_add_form_tag(
        array('checkbox', 'checkbox*', 'radio'),
        'cfredux_checkbox_form_tag_handler',
        array(
            'name-attr' => true,
            'selectable-values' => true,
            'multiple-controls-container' => true,
        )
    );
}

function cfredux_checkbox_form_tag_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    $validation_error = cfredux_get_validation_error($tag->name);

    $class = cfredux_form_controls_class($tag->type);

    if ($validation_error) {
        $class .= ' cfredux-not-valid';
    }

    $label_first = $tag->has_option('label_first');
    $use_label_element = $tag->has_option('use_label_element');
    $exclusive = $tag->has_option('exclusive');
    $free_text = $tag->has_option('free_text');
    $multiple = false;

    if ('checkbox' == $tag->basetype) {
        $multiple = ! $exclusive;
    } else { // radio
        $exclusive = false;
    }

    if ($exclusive) {
        $class .= ' cfredux-exclusive-checkbox';
    }

    $atts = array();

    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();

    $tabindex = $tag->get_option('tabindex', 'signed_int', true);

    if (false !== $tabindex) {
        $tabindex = (int) $tabindex;
    }

    $html = '';
    $count = 0;

    if ($data = (array) $tag->get_data_option()) {
        if ($free_text) {
            $tag->values = array_merge(
                array_slice($tag->values, 0, -1),
                array_values($data),
                array_slice($tag->values, -1)
            );
            $tag->labels = array_merge(
                array_slice($tag->labels, 0, -1),
                array_values($data),
                array_slice($tag->labels, -1)
            );
        } else {
            $tag->values = array_merge($tag->values, array_values($data));
            $tag->labels = array_merge($tag->labels, array_values($data));
        }
    }

    $values = $tag->values;
    $labels = $tag->labels;

    $default_choice = $tag->get_default_option(
        null, array(
        'multiple' => $multiple,
        )
    );

    $hangover = cfredux_get_hangover($tag->name, $multiple ? array() : '');

    foreach ($values as $key => $value) {
        if ($hangover) {
            $checked = in_array($value, (array) $hangover, true);
        } else {
            $checked = in_array($value, (array) $default_choice, true);
        }

        if (isset($labels[$key])) {
            $label = $labels[$key];
        } else {
            $label = $value;
        }

        $item_atts = array(
        'type' => $tag->basetype,
        'name' => $tag->name . ($multiple ? '[]' : ''),
        'value' => $value,
        'checked' => $checked ? 'checked' : '',
        'tabindex' => false !== $tabindex ? $tabindex : '',
        );

        $item_atts = cfredux_format_atts($item_atts);

        if ($label_first) { // put label first, input last
            $item = sprintf(
                '<span class="cfredux-list-item-label">%1$s</span><input %2$s>',
                esc_html($label), $item_atts
            );
        } else {
            $item = sprintf(
                '<input %2$s><span class="cfredux-list-item-label">%1$s</span>',
                esc_html($label), $item_atts
            );
        }

        if ($use_label_element) {
            $item = '<label>' . $item . '</label>';
        }

        if (false !== $tabindex && 0 < $tabindex) {
            $tabindex += 1;
        }

        $class = 'cfredux-list-item';
        $count += 1;

        if (1 == $count) {
            $class .= ' first';
        }

        if (count($values) == $count) { // last round
            $class .= ' last';

            if ($free_text) {
                $free_text_name = sprintf(
                    '_cfredux_%1$s_free_text_%2$s', $tag->basetype, $tag->name
                );

                $free_text_atts = array(
                'name' => $free_text_name,
                'class' => 'cfredux-free-text',
                'tabindex' => false !== $tabindex ? $tabindex : '',
                );

                if (cfredux_is_posted() && isset($_POST[$free_text_name])) {
                    $free_text_atts['value'] = wp_unslash(
                        sanitize_text_field($_POST[$free_text_name])
                    );
                }

                $free_text_atts = cfredux_format_atts($free_text_atts);

                $item .= sprintf(' <input type="text" %s>', $free_text_atts);

                $class .= ' has-free-text';
            }
        }

        $item = '<span class="' . esc_attr($class) . '">' . $item . '</span>';
        $html .= $item;
    }

    $atts = cfredux_format_atts($atts);

    $html = sprintf(
        '<span class="cfredux-form-control-wrap %1$s"><span %2$s>%3$s</span>' . 
            '%4$s</span>',
        sanitize_html_class($tag->name), $atts, $html, $validation_error
    );

    return $html;
}


/* Validation filter */

add_filter('cfredux_validate_checkbox', 'cfredux_checkbox_validation_filter', 10, 2);
add_filter(
    'cfredux_validate_checkbox*', 
    'cfredux_checkbox_validation_filter', 
    10, 
    2
);
add_filter('cfredux_validate_radio', 'cfredux_checkbox_validation_filter', 10, 2);

function cfredux_checkbox_validation_filter($result, $tag)
{
    $name = $tag->name;
    $is_required = $tag->is_required() || 'radio' == $tag->type;
    $value = isset($_POST[$name]) ? sanitize_text_field($_POST[$name]) : '';

    if ($is_required && empty($value)) {
        $result->invalidate($tag, cfredux_get_message('invalid_required'));
    }

    return $result;
}


/* Adding free text field */

add_filter('cfredux_posted_data', 'cfredux_checkbox_posted_data');

function cfredux_checkbox_posted_data($posted_data)
{
    $tags = cfredux_scan_form_tags(
        array('type' => array('checkbox', 'checkbox*', 'radio'))
    );

    if (empty($tags)) {
        return $posted_data;
    }

    foreach ($tags as $tag) {
        if (! isset($posted_data[$tag->name])) {
            continue;
        }

        $posted_items = (array) $posted_data[$tag->name];

        if ($tag->has_option('free_text')) {
            if (CFREDUX_USE_PIPE) {
                $values = $tag->pipes->collect_afters();
            } else {
                $values = $tag->values;
            }

            $last = array_pop($values);
            $last = html_entity_decode($last, ENT_QUOTES, 'UTF-8');

            if (in_array($last, $posted_items)) {
                $posted_items = array_diff($posted_items, array($last));

                $free_text_name = sprintf(
                    '_cfredux_%1$s_free_text_%2$s', $tag->basetype, $tag->name
                );

                $free_text = $posted_data[$free_text_name];

                if (! empty($free_text)) {
                       $posted_items[] = trim($last . ' ' . $free_text);
                } else {
                    $posted_items[] = $last;
                }
            }
        }

        $posted_data[$tag->name] = $posted_items;
    }

    return $posted_data;
}


/* Tag generator */

add_action(
    'cfredux_admin_init',
    'cfredux_add_tag_generator_checkbox_and_radio', 30
);

function cfredux_add_tag_generator_checkbox_and_radio()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'checkbox', __('checkboxes', 'contact-form-redux'),
        'cfredux_tag_generator_checkbox'
    );
    $tag_generator->add(
        'radio', __('radio buttons', 'contact-form-redux'),
        'cfredux_tag_generator_checkbox'
    );
}

function cfredux_tag_generator_checkbox($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());
    $type = $args['id'];

    if ('radio' != $type) {
        $type = 'checkbox';
    }

    if ('checkbox' == $type) {
        $description = __(
            "Generate a form tag for a group of checkboxes. For more details, " . 
                "see %s.", 'contact-form-redux'
        );
        $desc_link = cfredux_link(
            __(
                'https://cfr.backwoodsbytes.com/tags/checkbox-tags/', 
                'contact-form-redux'
            ), 
            __('Checkbox Tags', 'contact-form-redux')
        );
    } elseif ('radio' == $type) {
        $description = __(
            "Generate a form tag for a group of radio buttons. For more " . 
                "details, see %s.", 'contact-form-redux'
        );
        $desc_link = cfredux_link(
            __(
                'https://cfr.backwoodsbytes.com/tags/radio-button-tags/', 
                'contact-form-redux'
            ), 
            __('Radio Button Tags', 'contact-form-redux')
        );
    }

    ?>
<div class="control-box">
    <p class="cfrtaginfo">
        <?php echo sprintf(esc_html($description), $desc_link); ?>
    </p>
    
    <div class="inputgroup">
        <?php
        if ($type == 'checkbox') {
            ?>
            <div class="inputrow xmedia">
                <label for="required">
                    <?php 
                        echo esc_html(
                            __(
                                'Required:', 
                                'contact-form-redux'
                            )
                        ); 
                    ?>
                </label>
                <label class="switch">
                    <input type="checkbox" name="required">
                    <span class="slider round"></span>
                </label>
            </div>
            <?php
        }
        ?>
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-name'); ?>">
                <?php echo esc_html(__('Name:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="text" 
                name="name" 
                class="tg-name oneline" 
                id="<?php echo esc_attr($args['content'] . '-name'); ?>"
            >
        </div>
    </div>
    <p>
        <?php 
            echo esc_html(__('Options:', 'contact-form-redux')); 
            $str = __(
                'One option per line.', 
                'contact-form-redux'
            );
        ?>
        <span 
            class="cfrinfo" 
            title="<?php echo esc_attr($str); ?>"
        >&#8505;</span>
    </p>
    <p class="cfrinfopopup"><?php echo esc_html($str); ?></p>
    <textarea 
        name="values" 
        class="values" 
        id="<?php echo esc_attr($args['content'] . '-values'); ?>"
    ></textarea>
    <br><br>
    <div class="inputgroup">
        <div class="inputrow xmedia">
            <label for="label_first">
                <?php 
                    echo esc_html(
                        __(
                            'Label First:', 
                            'contact-form-redux'
                        )
                    ); 
                ?>
            </label>
            <label class="switch">
                <input type="checkbox" name="label_first" class="option">
                <span class="slider round"></span>
            </label>
        </div>
        <div class="inputrow xmedia">
            <label for="use_label_element">
                <?php 
                    echo esc_html(
                        __(
                            'Wrap with Label:', 
                            'contact-form-redux'
                        )
                    ); 
                ?>
            </label>
            <label class="switch">
                <input type="checkbox" name="use_label_element" class="option">
                <span class="slider round"></span>
            </label>
        </div>
        <?php
        if ($type == 'checkbox') {
            ?>
            <div class="inputrow xmedia">
                <label for="exclusive">
                <?php 
                    echo esc_html(
                        __(
                            'Checkboxes Exclusive:', 
                            'contact-form-redux'
                        )
                    ); 
                ?>
            </label>
            <label class="switch">
                <input type="checkbox" name="exclusive" class="option">
                <span class="slider round"></span>
            </label>
            </div>
            <?php
        }
        ?>
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-id'); ?>">
                <?php echo esc_html(__('ID Attribute:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="text" 
                name="id" 
                class="idvalue oneline option" 
                id="<?php echo esc_attr($args['content'] . '-id'); ?>"
            >
        </div>
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-class'); ?>">
                <?php echo esc_html(__('Class Attribute:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="text" 
                name="class" 
                class="classvalue oneline option" 
                id="<?php echo esc_attr($args['content'] . '-class'); ?>"
            >
        </div>
    </div>
</div>

<div class="insert-box">
    <input 
        type="text" 
        name="<?php echo $type; ?>" 
        class="tag code" 
        readonly
    >

    <div class="submitbox">
    <input 
        type="button" 
        class="button button-primary insert-tag" 
        value="<?php echo esc_attr(__('Insert Tag', 'contact-form-redux')); ?>"
    >
    </div>

    <br class="clear">

    <p class="description mail-tag">
        <label for="<?php echo esc_attr($args['content'] . '-mailtag'); ?>">
            <?php 
                echo sprintf(
                    esc_html(
                        __(
                            "To use the value input through this field in a " . 
                                "mail field, you need to insert the correspo" . 
                                "nding mail tag (%s) into the field on the Mail " .
                                "tab.", 
                            'contact-form-redux'
                        )
                    ), 
                    '<strong><span class="mail-tag"></span></strong>'
                ); 
            ?>
            <input 
                type="text" 
                class="mail-tag code hidden" 
                readonly 
                id="<?php echo esc_attr($args['content'] . '-mailtag'); ?>"
            >
        </label>
    </p>
</div>
    <?php
}
