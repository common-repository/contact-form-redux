<?php
/**
 * A base module for [count], Twitter-like character count
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_count');

function cfredux_add_form_tag_count()
{
    cfredux_add_form_tag(
        'count',
        'cfredux_count_form_tag_handler',
        array(
            'name-attr' => true,
            'zero-controls-container' => true,
            'not-for-mail' => true,
        )
    );
}

function cfredux_count_form_tag_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    $targets = cfredux_scan_form_tags(array('name' => $tag->name));
    $maxlength = $minlength = null;

    while ($targets) {
        $target = array_shift($targets);

        if ('count' != $target->type) {
            $maxlength = $target->get_maxlength_option();
            $minlength = $target->get_minlength_option();
            break;
        }
    }

    if ($maxlength && $minlength && $maxlength < $minlength) {
        $maxlength = $minlength = null;
    }

    if ($tag->has_option('down')) {
        $value = (int) $maxlength;
        $class = 'cfredux-character-count down';
    } else {
        $value = '0';
        $class = 'cfredux-character-count up';
    }

    $atts = array();
    $atts['id'] = $tag->get_id_option();
    $atts['class'] = $tag->get_class_option($class);
    $atts['data-target-name'] = $tag->name;
    $atts['data-starting-value'] = $value;
    $atts['data-current-value'] = $value;
    $atts['data-maximum-value'] = $maxlength;
    $atts['data-minimum-value'] = $minlength;
    $atts = cfredux_format_atts($atts);

    $html = sprintf('<span %1$s>%2$s</span>', $atts, $value);

    return $html;
}
