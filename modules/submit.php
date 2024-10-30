<?php
/**
 * A base module for [submit]
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_submit');

function cfredux_add_form_tag_submit()
{
    cfredux_add_form_tag('submit', 'cfredux_submit_form_tag_handler');
}

function cfredux_submit_form_tag_handler($tag)
{
    $class = cfredux_form_controls_class($tag->type);

    $atts = array();

    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);

    $value = isset($tag->values[0]) ? $tag->values[0] : '';

    if (empty($value)) {
        $value = __('Send', 'contact-form-redux');
    }

    $atts['type'] = 'submit';
    $atts['value'] = $value;

    $atts = cfredux_format_atts($atts);

    $html = sprintf('<input %1$s>', $atts);

    return $html;
}


/* Tag generator */

add_action('cfredux_admin_init', 'cfredux_add_tag_generator_submit', 55);

function cfredux_add_tag_generator_submit()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'submit', __('submit', 'contact-form-redux'),
        'cfredux_tag_generator_submit', array('nameless' => 1)
    );
}

function cfredux_tag_generator_submit($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());

    $description = __(
        "Generate a form tag for a submit button. For more details, see %s.", 
        'contact-form-redux'
    );

    $desc_link = cfredux_link(
        __(
            'https://cfr.backwoodsbytes.com/tags/submit-tags/', 
            'contact-form-redux'
        ), 
        __('Submit Tags', 'contact-form-redux')
    );

    ?>
<div class="control-box">
    <p class="cfrtaginfo">
        <?php echo sprintf(esc_html($description), $desc_link); ?>
    </p>
    
    <div class="inputgroup">
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-values'); ?>">
                <?php echo esc_html(__('Label:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="text" 
                name="values" 
                class="oneline" 
                id="<?php echo esc_attr($args['content'] . '-values'); ?>"
            >
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
        name="submit" 
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
</div>
    <?php
}
