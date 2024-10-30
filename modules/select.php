<?php
/**
 * A base module for [select] and [select*]
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_select');

function cfredux_add_form_tag_select()
{
    cfredux_add_form_tag(
        array('select', 'select*'),
        'cfredux_select_form_tag_handler',
        array(
            'name-attr' => true,
            'selectable-values' => true,
        )
    );
}

function cfredux_select_form_tag_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    $validation_error = cfredux_get_validation_error($tag->name);

    $class = cfredux_form_controls_class($tag->type);

    if ($validation_error) {
        $class .= ' cfredux-not-valid';
    }

    $atts = array();

    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);

    if ($tag->is_required()) {
        $atts['aria-required'] = 'true';
    }

    $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

    $multiple = $tag->has_option('multiple');
    $include_blank = $tag->has_option('include_blank');
    $first_as_label = $tag->has_option('first_as_label');

    if ($tag->has_option('size')) {
        $size = $tag->get_option('size', 'int', true);

        if ($size) {
            $atts['size'] = $size;
        } elseif ($multiple) {
            $atts['size'] = 4;
        } else {
            $atts['size'] = 1;
        }
    }

    if ($data = (array) $tag->get_data_option()) {
        $tag->values = array_merge($tag->values, array_values($data));
        $tag->labels = array_merge($tag->labels, array_values($data));
    }

    $values = $tag->values;
    $labels = $tag->labels;

    $default_choice = $tag->get_default_option(
        null, array(
        'multiple' => $multiple,
        'shifted' => $include_blank,
        )
    );

    if ($include_blank || empty($values)) {
        array_unshift($labels, '---');
        array_unshift($values, '');
    } elseif ($first_as_label) {
        $values[0] = '';
    }

    $html = '';
    $hangover = cfredux_get_hangover($tag->name);

    foreach ($values as $key => $value) {
        if ($hangover) {
            $selected = in_array($value, (array) $hangover, true);
        } else {
            $selected = in_array($value, (array) $default_choice, true);
        }

        $item_atts = array(
        'value' => $value,
        'selected' => $selected ? 'selected' : '',
        );

        $item_atts = cfredux_format_atts($item_atts);

        $label = isset($labels[$key]) ? $labels[$key] : $value;

        $html .= sprintf(
            '<option %1$s>%2$s</option>',
            $item_atts, esc_html($label)
        );
    }

    if ($multiple) {
        $atts['multiple'] = 'multiple';
    }

    $atts['name'] = $tag->name . ($multiple ? '[]' : '');

    $atts = cfredux_format_atts($atts);

    $html = sprintf(
        '<span class="cfredux-form-control-wrap %1$s"><select %2$s>%3$s' . 
            '</select>%4$s</span>',
        sanitize_html_class($tag->name), $atts, $html, $validation_error
    );

    return $html;
}


/* Validation filter */

add_filter('cfredux_validate_select', 'cfredux_select_validation_filter', 10, 2);
add_filter('cfredux_validate_select*', 'cfredux_select_validation_filter', 10, 2);

function cfredux_select_validation_filter($result, $tag)
{
    $name = $tag->name;

    if (isset($_POST[$name]) && is_array($_POST[$name])) {
        foreach ($_POST[$name] as $key => $value) {
            if ('' === $value) {
                unset($_POST[$name][$key]);
            }
        }
    }

    $empty = ! isset($_POST[$name]) || empty($_POST[$name]) && '0' !== $_POST[$name];

    if ($tag->is_required() && $empty) {
        $result->invalidate($tag, cfredux_get_message('invalid_required'));
    }

    return $result;
}


/* Tag generator */

add_action('cfredux_admin_init', 'cfredux_add_tag_generator_menu', 25);

function cfredux_add_tag_generator_menu()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'menu', __('select', 'contact-form-redux'),
        'cfredux_tag_generator_menu'
    );
}

function cfredux_tag_generator_menu($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());

    $description = __(
        "Generate a form tag for a drop-down menu. For more details, see %s.", 
        'contact-form-redux'
    );

    $desc_link = cfredux_link(
        __(
            'https://cfr.backwoodsbytes.com/tags/select-tags/', 
            'contact-form-redux'
        ), 
        __('Select Tags', 'contact-form-redux')
    );

    ?>
<div class="control-box">
    <p class="cfrtaginfo">
        <?php echo sprintf(esc_html($description), $desc_link); ?>
    </p>
    
    <div class="inputgroup">
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
            'One option per line.&#10;The Select Tag can also display one value, ' . 
                'and submit a second when the form is sent. This is accomplished' . 
                ' by creating pipe-separated text/value pairs.', 
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
            <label for="multiple">
                <?php 
                    echo esc_html(
                        __(
                            'Allow Multiple Selections:', 
                            'contact-form-redux'
                        )
                    ); 
                ?>
            </label>
            <label class="switch">
                <input type="checkbox" name="multiple" class="option">
                <span class="slider round"></span>
            </label>
        </div>
        <div class="inputrow xmedia">
            <label for="include_blank">
                <?php 
                    echo esc_html(
                        __(
                            'First Option Blank:', 
                            'contact-form-redux'
                        )
                    ); 
                ?>
            </label>
            <label class="switch">
                <input type="checkbox" name="include_blank" class="option">
                <span class="slider round"></span>
            </label>
        </div>
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
        name="select" 
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
                                "mail field, you need to insert the correspond" . 
                                "ing mail tag (%s) into the field on the Mail tab.", 
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
