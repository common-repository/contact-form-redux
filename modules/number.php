<?php
/**
 * A base module for the following types of tags:
 * [number] and [number*]        # Number
 * [range] and [range*]        # Range
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_number');

function cfredux_add_form_tag_number()
{
    cfredux_add_form_tag(
        array('number', 'number*', 'range', 'range*'),
        'cfredux_number_form_tag_handler', array('name-attr' => true)
    );
}

function cfredux_number_form_tag_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    $validation_error = cfredux_get_validation_error($tag->name);

    $class = cfredux_form_controls_class($tag->type);

    $class .= ' cfredux-validates-as-number';

    if ($validation_error) {
        $class .= ' cfredux-not-valid';
    }

    $atts = array();

    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['min'] = $tag->get_option('min', 'signed_int', true);
    $atts['max'] = $tag->get_option('max', 'signed_int', true);
    $atts['step'] = $tag->get_option('step', 'int', true);

    if ($tag->has_option('readonly')) {
        $atts['readonly'] = 'readonly';
    }

    if ($tag->is_required()) {
        $atts['aria-required'] = 'true';
    }

    $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

    $value = (string) reset($tag->values);

    if ($tag->has_option('placeholder') || $tag->has_option('watermark')) {
        $atts['placeholder'] = $value;
        $value = '';
    }

    $value = $tag->get_default_option($value);

    $value = cfredux_get_hangover($tag->name, $value);

    $atts['value'] = $value;

    $atts['type'] = $tag->basetype;

    $atts['name'] = $tag->name;

    $atts = cfredux_format_atts($atts);

    $html = sprintf(
        '<span class="cfredux-form-control-wrap %1$s"><input %2$s>%3$s</span>',
        sanitize_html_class($tag->name), $atts, $validation_error
    );

    return $html;
}


/* Validation filter */

add_filter('cfredux_validate_number', 'cfredux_number_validation_filter', 10, 2);
add_filter('cfredux_validate_number*', 'cfredux_number_validation_filter', 10, 2);
add_filter('cfredux_validate_range', 'cfredux_number_validation_filter', 10, 2);
add_filter('cfredux_validate_range*', 'cfredux_number_validation_filter', 10, 2);

function cfredux_number_validation_filter($result, $tag)
{
    $name = $tag->name;

    if (isset($_POST[$name])) {
        $value = sanitize_text_field($_POST[$name]);
    } else {
        $value = '';
    }

    $min = $tag->get_option('min', 'signed_int', true);
    $max = $tag->get_option('max', 'signed_int', true);

    if ($tag->is_required() && '' == $value) {
        $result->invalidate($tag, cfredux_get_message('invalid_required'));
    } elseif ('' != $value && ! cfredux_is_number($value)) {
        $result->invalidate($tag, cfredux_get_message('invalid_number'));
    } elseif ('' != $value && '' != $min && (float) $value < (float) $min) {
        $result->invalidate($tag, cfredux_get_message('number_too_small'));
    } elseif ('' != $value && '' != $max && (float) $max < (float) $value) {
        $result->invalidate($tag, cfredux_get_message('number_too_large'));
    }

    return $result;
}


/* Messages */

add_filter('cfredux_messages', 'cfredux_number_messages');

function cfredux_number_messages($messages)
{
    return array_merge(
        $messages, array(
            'invalid_number' => array(
                'description' => __(
                    "Number format that the sender entered is invalid", 
                    'contact-form-redux'
                ),
                'default' => __(
                    "The number format is invalid.", 
                    'contact-form-redux'
                )
            ),
    
            'number_too_small' => array(
                'description' => __(
                    "Number is smaller than minimum limit", 
                    'contact-form-redux'
                ),
                'default' => __(
                    "The number is smaller than the minimum allowed.", 
                    'contact-form-redux'
                )
            ),
    
            'number_too_large' => array(
                'description' => __(
                    "Number is larger than maximum limit", 
                    'contact-form-redux'
                ),
                'default' => __(
                    "The number is larger than the maximum allowed.", 
                    'contact-form-redux'
                )
            ),
        )
    );
}


/* Tag generator */

add_action('cfredux_admin_init', 'cfredux_add_tag_generator_number', 18);

function cfredux_add_tag_generator_number()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'number', __('number', 'contact-form-redux'),
        'cfredux_tag_generator_number'
    );
}

function cfredux_tag_generator_number($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());
    $type = 'number';

    $description = __(
        "Generate a form tag for a field for numeric value input. For more " . 
            "details, see %s.", 'contact-form-redux'
    );

    $desc_link = cfredux_link(
        __(
            'https://cfr.backwoodsbytes.com/tags/number-tags/', 
            'contact-form-redux'
        ), 
        __('Number Tags', 'contact-form-redux')
    );

    ?>
<div class="control-box">
    <p class="cfrtaginfo">
        <?php echo sprintf(esc_html($description), $desc_link); ?>
    </p>
    
    <div class="inputgroup">
        <div class="inputrow">
            <label for="tagtype">
                <?php echo esc_html(__('Field Type:', 'contact-form-redux')); ?>
            </label>
            <select name="tagtype">
                <option value="number" selected>
                    <?php echo esc_html(__('Spinbox', 'contact-form-redux')); ?>
                </option>
                <option value="range">
                    <?php echo esc_html(__('Slider', 'contact-form-redux')); ?>
                </option>
            </select>
        </div>
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
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-values'); ?>">
                <?php echo esc_html(__('Default Value:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="text" 
                name="values" 
                class="oneline" 
                id="<?php echo esc_attr($args['content'] . '-values'); ?>"
            >
        </div>
        <div class="inputrow xmedia">
            <label for="placeholder">
                <?php 
                    echo esc_html(
                        __(
                            'Default as Placeholder:', 
                            'contact-form-redux'
                        )
                    ); 
                ?>
            </label>
            <label class="switch">
                <input type="checkbox" name="placeholder" class="option">
                <span class="slider round"></span>
            </label>
        </div>
        <div class="inputrow">
            <label for="min">
                <?php echo esc_html(__('Minimum:', 'contact-form-redux')); ?>
            </label>
            <input type="number" name="min" class="numeric option">
        </div>
        <div class="inputrow">
            <label for="max">
                <?php echo esc_html(__('Maximum:', 'contact-form-redux')); ?>
            </label>
            <input type="number" name="max" class="numeric option">
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
