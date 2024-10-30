<?php

add_action('cfredux_init', 'cfredux_add_form_tag_hidden');

function cfredux_add_form_tag_hidden()
{
    cfredux_add_form_tag(
        'hidden',
        'cfredux_hidden_form_tag_handler',
        array(
            'name-attr' => true,
            'display-hidden' => true,
        )
    );
}

function cfredux_hidden_form_tag_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    $atts = array();

    $class = cfredux_form_controls_class($tag->type);
    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();

    $value = (string) reset($tag->values);
    $value = $tag->get_default_option($value);
    $atts['value'] = $value;

    $atts['type'] = 'hidden';
    $atts['name'] = $tag->name;
    $atts = cfredux_format_atts($atts);

    $html = sprintf('<input %s>', $atts);
    return $html;
}

/*
    If you ever want to add a tag generator button for the hidden field to the 
    Admin edit contact form UI, you'll need to add a "Tag Generator" section for 
    it here, as used in the other modules that have Tag Generator buttons.
*/
