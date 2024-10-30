<?php
/**
 * A base module for the following types of tags:
 *      [text] and [text*]        # Single-line text
 *      [email] and [email*]    # Email address
 *      [url] and [url*]        # URL
 *      [tel] and [tel*]        # Telephone number
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_text');

function cfredux_add_form_tag_text()
{
    cfredux_add_form_tag(
        array('text', 'text*', 'email', 'email*', 'url', 'url*', 'tel', 'tel*'),
        'cfredux_text_form_tag_handler', array('name-attr' => true)
    );
}

function cfredux_text_form_tag_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    $validation_error = cfredux_get_validation_error($tag->name);

    $class = cfredux_form_controls_class($tag->type, 'cfredux-text');

    if (in_array($tag->basetype, array('email', 'url', 'tel'))) {
        $class .= ' cfredux-validates-as-' . $tag->basetype;
    }

    if ($validation_error) {
        $class .= ' cfredux-not-valid';
    }

    $atts = array();

    $atts['size'] = $tag->get_size_option('40');
    $atts['maxlength'] = $tag->get_maxlength_option();
    $atts['minlength'] = $tag->get_minlength_option();

    if ($atts['maxlength'] && $atts['minlength']
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

add_filter('cfredux_validate_text', 'cfredux_text_validation_filter', 10, 2);
add_filter('cfredux_validate_text*', 'cfredux_text_validation_filter', 10, 2);
add_filter('cfredux_validate_email', 'cfredux_text_validation_filter', 10, 2);
add_filter('cfredux_validate_email*', 'cfredux_text_validation_filter', 10, 2);
add_filter('cfredux_validate_url', 'cfredux_text_validation_filter', 10, 2);
add_filter('cfredux_validate_url*', 'cfredux_text_validation_filter', 10, 2);
add_filter('cfredux_validate_tel', 'cfredux_text_validation_filter', 10, 2);
add_filter('cfredux_validate_tel*', 'cfredux_text_validation_filter', 10, 2);

function cfredux_text_validation_filter($result, $tag)
{
    $name = $tag->name;

    if (isset($_POST[$name])) {
        $value = sanitize_text_field($_POST[$name]);
    } else {
        $value = '';
    }

    if ('text' == $tag->basetype) {
        if ($tag->is_required() && '' == $value) {
            $result->invalidate($tag, cfredux_get_message('invalid_required'));
        }
    }

    if ('email' == $tag->basetype) {
        if ($tag->is_required() && '' == $value) {
            $result->invalidate($tag, cfredux_get_message('invalid_required'));
        } elseif ('' != $value && ! cfredux_is_email($value)) {
            $result->invalidate($tag, cfredux_get_message('invalid_email'));
        }
    }

    if ('url' == $tag->basetype) {
        if ($tag->is_required() && '' == $value) {
            $result->invalidate($tag, cfredux_get_message('invalid_required'));
        } elseif ('' != $value && ! cfredux_is_url($value)) {
            $result->invalidate($tag, cfredux_get_message('invalid_url'));
        }
    }

    if ('tel' == $tag->basetype) {
        if ($tag->is_required() && '' == $value) {
            $result->invalidate($tag, cfredux_get_message('invalid_required'));
        } elseif ('' != $value && ! cfredux_is_tel($value)) {
            $result->invalidate($tag, cfredux_get_message('invalid_tel'));
        }
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


/* Messages */

add_filter('cfredux_messages', 'cfredux_text_messages');

function cfredux_text_messages($messages)
{
    $messages = array_merge(
        $messages, array(
            'invalid_email' => array(
                'description' => __(
                    "Email address that the sender entered is invalid", 
                    'contact-form-redux'
                ),
                'default' => __(
                    "The e-mail address entered is invalid.", 
                    'contact-form-redux'
                ),
            ),
    
            'invalid_url' => array(
                'description' => __(
                    "URL that the sender entered is invalid", 
                    'contact-form-redux'
                ),
                'default' => __("The URL is invalid.", 'contact-form-redux'),
            ),
    
            'invalid_tel' => array(
                'description' => __(
                    "Telephone number that the sender entered is invalid", 
                    'contact-form-redux'
                ),
                'default' => __(
                    "The telephone number is invalid.", 
                    'contact-form-redux'
                ),
            ),
        )
    );

    return $messages;
}


/* Tag generator */

add_action('cfredux_admin_init', 'cfredux_add_tag_generator_text', 15);

function cfredux_add_tag_generator_text()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'text', __('text', 'contact-form-redux'),
        'cfredux_tag_generator_text'
    );
    $tag_generator->add(
        'email', __('email', 'contact-form-redux'),
        'cfredux_tag_generator_text'
    );
    $tag_generator->add(
        'url', __('URL', 'contact-form-redux'),
        'cfredux_tag_generator_text'
    );
    $tag_generator->add(
        'tel', __('tel', 'contact-form-redux'),
        'cfredux_tag_generator_text'
    );
}

function cfredux_tag_generator_text($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());
    $type = $args['id'];

    if (! in_array($type, array('email', 'url', 'tel'))) {
        $type = 'text';
    }

    if ('text' == $type) {
        $description = __(
            "Generate a form tag for a single-line plain text input field. For " . 
                "more details, see %s.", 
            'contact-form-redux'
        );
        $desc_link = cfredux_link(
            __(
                'https://cfr.backwoodsbytes.com/tags/text-tags/', 
                'contact-form-redux'
            ), 
            __('Text Tags', 'contact-form-redux')
        );
    } elseif ('email' == $type) {
        $description = __(
            "Generate a form tag for a single-line email address input field. " . 
                "For more details, see %s.", 
            'contact-form-redux'
        );
        $desc_link = cfredux_link(
            __(
                'https://cfr.backwoodsbytes.com/tags/email-tags/', 
                'contact-form-redux'
            ), 
            __('Email Tags', 'contact-form-redux')
        );
    } elseif ('url' == $type) {
        $description = __(
            "Generate a form tag for a single-line URL input field. For more " . 
                "details, see %s.", 
            'contact-form-redux'
        );
        $desc_link = cfredux_link(
            __(
                'https://cfr.backwoodsbytes.com/tags/url-tags/', 
                'contact-form-redux'
            ), 
            __('URL Tags', 'contact-form-redux')
        );
    } elseif ('tel' == $type) {
        $description = __(
            "Generate a form tag for a single-line telephone number input field. " . 
                "For more details, see %s.", 
            'contact-form-redux'
        );
        $desc_link = cfredux_link(
            __(
                'https://cfr.backwoodsbytes.com/tags/telephone-tags/', 
                'contact-form-redux'
            ), 
            __('Telephone Tags', 'contact-form-redux')
        );
    }
    ?>
<div class="control-box">
    <p class="cfrtaginfo">
        <?php echo sprintf(esc_html($description), $desc_link); ?>
    </p>
    <p>
    </p>

    <div class="inputgroup">
        <div class="inputrow xmedia">
            <label for="required">
                <?php echo esc_html(__('Required Field:', 'contact-form-redux')); ?>
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
        <?php
        if (in_array($type, array('text', 'email', 'url'))) {
            ?>
            <div class="inputrow xmedia">
                <?php
                switch($type) {
                case 'text':
                    ?>
                    <label for="akismet:author">
                        <?php 
                            echo esc_html(
                                __(
                                    'Akismet:', 
                                    'contact-form-redux'
                                )
                            ); 
                        ?>
                    </label>
                    <label class="switch">
                        <input type="checkbox" name="akismet:author" class="option">
                        <span class="slider round"></span>
                    </label>
                    <?php
                    break;
                case 'email':
                    ?>
                    <label for="akismet:author_email">
                        <?php 
                            echo esc_html(
                                __(
                                    'Akismet:', 
                                    'contact-form-redux'
                                )
                            ); 
                        ?>
                    </label>
                    <label class="switch">
                        <input 
                            type="checkbox" 
                            name="akismet:author_email" 
                            class="option"
                        >
                        <span class="slider round"></span>
                    </label>
                    <?php 
                    break;
                case 'url':
                    ?>
                    <label for="akismet:author_url">
                        <?php 
                            echo esc_html(
                                __(
                                    'Akismet:', 
                                    'contact-form-redux'
                                )
                            ); 
                        ?>
                    </label>
                    <label class="switch">
                        <input 
                            type="checkbox" 
                            name="akismet:author_url" 
                            class="option"
                        >
                        <span class="slider round"></span>
                    </label>
                    <?php 
                    break;
                }
                ?>    
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
