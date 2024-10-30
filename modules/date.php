<?php
/**
 * A base module for the following types of tags:
 * [date] and [date*]        # Date
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_date');

function cfredux_add_form_tag_date()
{
    cfredux_add_form_tag(
        array('date', 'date*'),
        'cfredux_date_form_tag_handler', array('name-attr' => true)
    );
}

function cfredux_date_form_tag_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    $validation_error = cfredux_get_validation_error($tag->name);

    $class = cfredux_form_controls_class($tag->type);

    $class .= ' cfredux-validates-as-date';

    if ($validation_error) {
        $class .= ' cfredux-not-valid';
    }

    $atts = array();

    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['min'] = $tag->get_date_option('min');
    $atts['max'] = $tag->get_date_option('max');
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

add_filter('cfredux_validate_date', 'cfredux_date_validation_filter', 10, 2);
add_filter('cfredux_validate_date*', 'cfredux_date_validation_filter', 10, 2);

function cfredux_date_validation_filter($result, $tag)
{
    $name = $tag->name;

    $min = $tag->get_date_option('min');
    $max = $tag->get_date_option('max');

    if (isset($_POST[$name])) {
        $value = sanitize_text_field($_POST[$name]);
    } else {
        $value = '';
    }

    if ($tag->is_required() && '' == $value) {
        $result->invalidate($tag, cfredux_get_message('invalid_required'));
    } elseif ('' != $value && ! cfredux_is_date($value)) {
        $result->invalidate($tag, cfredux_get_message('invalid_date'));
    } elseif ('' != $value && ! empty($min) && $value < $min) {
        $result->invalidate($tag, cfredux_get_message('date_too_early'));
    } elseif ('' != $value && ! empty($max) && $max < $value) {
        $result->invalidate($tag, cfredux_get_message('date_too_late'));
    }

    return $result;
}


/* Messages */

add_filter('cfredux_messages', 'cfredux_date_messages');

function cfredux_date_messages($messages)
{
    return array_merge(
        $messages, array(
            'invalid_date' => array(
                'description' => __(
                    "Date format that the sender entered is invalid", 
                    'contact-form-redux'
                ),
                'default' => __(
                    "The date format is incorrect.", 
                    'contact-form-redux'
                )
            ),
    
            'date_too_early' => array(
                'description' => __(
                    "Date is earlier than minimum limit", 
                    'contact-form-redux'
                ),
                'default' => __(
                    "The date is before the earliest one allowed.", 
                    'contact-form-redux'
                )
            ),
    
            'date_too_late' => array(
                'description' => __(
                    "Date is later than maximum limit", 
                    'contact-form-redux'
                ),
                'default' => __(
                    "The date is after the latest one allowed.", 
                    'contact-form-redux'
                )
            ),
        )
    );
}


/* Tag generator */

add_action('cfredux_admin_init', 'cfredux_add_tag_generator_date', 19);

function cfredux_add_tag_generator_date()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'date', __('date', 'contact-form-redux'),
        'cfredux_tag_generator_date'
    );
}

function cfredux_tag_generator_date($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());
    $type = 'date';

    $description = __(
        "Generate a form tag for a date input field. For more details, see %s.", 
        'contact-form-redux'
    );

    $desc_link = cfredux_link(
        __('https://cfr.backwoodsbytes.com/tags/date-tags/', 'contact-form-redux'), 
        __('Date Tags', 'contact-form-redux')
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
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-values'); ?>">
                <?php echo esc_html(__('Default Value:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="date" 
                name="values" 
                class="date" 
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
            <input type="date" name="min" class="date option">
        </div>
        <div class="inputrow">
            <label for="max">
                <?php echo esc_html(__('Maximum:', 'contact-form-redux')); ?>
            </label>
            <input type="date" name="max" class="date option">
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
                            "To use the value input through this field in " . 
                                "a mail field, you need to insert the " . 
                                "corresponding mail tag (%s) into the field " . 
                                "on the Mail tab.", 
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
