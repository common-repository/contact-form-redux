<?php
/**
 * A base module for [textarea] and [textarea*]
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_textarea');

function cfredux_add_form_tag_textarea()
{
    cfredux_add_form_tag(
        array('textarea', 'textarea*'),
        'cfredux_textarea_form_tag_handler', array('name-attr' => true)
    );
}

function cfredux_textarea_form_tag_handler($tag)
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

    $atts['cols'] = $tag->get_cols_option('40');
    $atts['rows'] = $tag->get_rows_option('10');
    $atts['maxlength'] = $tag->get_maxlength_option();
    $atts['minlength'] = $tag->get_minlength_option();

    if ($atts['maxlength'] 
        && $atts['minlength'] 
        && $atts['maxlength'] < $atts['minlength']
    ) {
        unset($atts['maxlength'], $atts['minlength']);
    }

    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);

    $atts['autocomplete'] = $tag->get_option(
        'autocomplete',
        '[-0-9a-zA-Z]+', true
    );

    if ($tag->has_option('readonly')) {
        $atts['readonly'] = 'readonly';
    }

    if ($tag->is_required()) {
        $atts['aria-required'] = 'true';
    }

    $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

    $value = empty($tag->content)
    ? (string) reset($tag->values)
    : $tag->content;

    if ($tag->has_option('placeholder') || $tag->has_option('watermark')) {
        $atts['placeholder'] = $value;
        $value = '';
    }

    $value = $tag->get_default_option($value);

    $value = cfredux_get_hangover($tag->name, $value);

    $atts['name'] = $tag->name;

    $atts = cfredux_format_atts($atts);

    $html = sprintf(
        '<span class="cfredux-form-control-wrap %1$s"><textarea %2$s>%3$s' . 
            '</textarea>%4$s</span>',
        sanitize_html_class($tag->name), $atts,
        esc_textarea($value), $validation_error
    );

    return $html;
}


/* Validation filter */

add_filter('cfredux_validate_textarea', 'cfredux_textarea_validation_filter', 10, 2);
add_filter(
    'cfredux_validate_textarea*', 
    'cfredux_textarea_validation_filter', 
    10, 
    2
);

function cfredux_textarea_validation_filter($result, $tag)
{
    $type = $tag->type;
    $name = $tag->name;

    if (isset($_POST[$name])) {
        $value = sanitize_text_field($_POST[$name]);
    } else {
        $value = '';
    }

    if ($tag->is_required() && '' == $value) {
        $result->invalidate($tag, cfredux_get_message('invalid_required'));
    }

    if ('' !== $value) {
        $maxlength = $tag->get_maxlength_option();
        $minlength = $tag->get_minlength_option();

        if ($maxlength && $minlength && $maxlength < $minlength) {
            $maxlength = $minlength = null;
        }

        $code_units = cfredux_count_code_units(stripslashes($value));

        if (false !== $code_units) {
            if ($maxlength && $maxlength < $code_units) {
                $result->invalidate($tag, cfredux_get_message('invalid_too_long'));
            } elseif ($minlength && $code_units < $minlength) {
                $result->invalidate($tag, cfredux_get_message('invalid_too_short'));
            }
        }
    }

    return $result;
}


/* Tag generator */

add_action('cfredux_admin_init', 'cfredux_add_tag_generator_textarea', 20);

function cfredux_add_tag_generator_textarea()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'textarea', __('text area', 'contact-form-redux'),
        'cfredux_tag_generator_textarea'
    );
}

function cfredux_tag_generator_textarea($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());
    $type = 'textarea';

    $description = __(
        "Generate a form tag for a multi-line text input field. For more " . 
            "details, see %s.", 'contact-form-redux'
    );

    $desc_link = cfredux_link(
        __(
            'https://cfr.backwoodsbytes.com/tags/text-area-tags/', 
            'contact-form-redux'
        ), 
        __('Textarea Tags', 'contact-form-redux')
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
