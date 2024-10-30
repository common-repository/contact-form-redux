<?php
/**
 * A base module for [acceptance]
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_acceptance');

function cfredux_add_form_tag_acceptance()
{
    cfredux_add_form_tag(
        'acceptance',
        'cfredux_acceptance_form_tag_handler',
        array(
        'name-attr' => true,
        )
    );
}

function cfredux_acceptance_form_tag_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    $validation_error = cfredux_get_validation_error($tag->name);

    $class = cfredux_form_controls_class($tag->type);

    if ($validation_error) {
        $class .= ' cfredux-not-valid';
    }

    if ($tag->has_option('invert')) {
        $class .= ' invert';
    }

    if ($tag->has_option('optional')) {
        $class .= ' optional';
    }

    $atts = array(
    'class' => trim($class),
    );

    $item_atts = array();

    $item_atts['type'] = 'checkbox';
    $item_atts['name'] = $tag->name;
    $item_atts['value'] = '1';
    $item_atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $item_atts['aria-invalid'] = $validation_error ? 'true' : 'false';

    if ($tag->has_option('default:on')) {
        $item_atts['checked'] = 'checked';
    }

    $item_atts['class'] = $tag->get_class_option();
    $item_atts['id'] = $tag->get_id_option();

    $item_atts = cfredux_format_atts($item_atts);

    $content = empty($tag->content)
    ? (string) reset($tag->values)
    : $tag->content;

    $content = trim($content);

    if ($content) {
        $html = sprintf(
            '<span class="cfredux-list-item"><label><input %1$s><span ' . 
                'class="cfredux-list-item-label">%2$s</span></label></span>',
            $item_atts, $content
        );
    } else {
        $html = sprintf(
            '<span class="cfredux-list-item"><input %1$s></span>',
            $item_atts
        );
    }

    $atts = cfredux_format_atts($atts);

    $html = sprintf(
        '<span class="cfredux-form-control-wrap %1$s"><span %2$s>%3$s</span>' . 
            '%4$s</span>',
        sanitize_html_class($tag->name), 
        $atts, 
        $html, 
        $validation_error
    );

    return $html;
}


/* Validation filter */

add_filter(
    'cfredux_validate_acceptance',
    'cfredux_acceptance_validation_filter', 10, 2
);

function cfredux_acceptance_validation_filter($result, $tag)
{
    if (! cfredux_acceptance_as_validation()) {
        return $result;
    }

    if ($tag->has_option('optional')) {
        return $result;
    }

    $name = $tag->name;
    $value = (! empty($_POST[$name]) ? 1 : 0);

    $invert = $tag->has_option('invert');

    if ($invert && $value || ! $invert && ! $value) {
        $result->invalidate($tag, cfredux_get_message('accept_terms'));
    }

    return $result;
}


/* Acceptance filter */

add_filter('cfredux_acceptance', 'cfredux_acceptance_filter', 10, 2);

function cfredux_acceptance_filter($accepted, $submission)
{
    $tags = cfredux_scan_form_tags(array('type' => 'acceptance'));

    foreach ($tags as $tag) {
        $name = $tag->name;

        if (empty($name)) {
            continue;
        }

        $value = (! empty($_POST[$name]) ? 1 : 0);

        $content = empty($tag->content)
        ? (string) reset($tag->values)
        : $tag->content;

        $content = trim($content);

        if ($value && $content) {
            $submission->add_consent($name, $content);
        }

        if ($tag->has_option('optional')) {
            continue;
        }

        $invert = $tag->has_option('invert');

        if ($invert && $value || ! $invert && ! $value) {
            $accepted = false;
        }
    }

    return $accepted;
}

add_filter('cfredux_form_class_attr', 'cfredux_acceptance_form_class_attr');

function cfredux_acceptance_form_class_attr($class)
{
    if (cfredux_acceptance_as_validation()) {
        return $class . ' cfredux-acceptance-as-validation';
    }

    return $class;
}

function cfredux_acceptance_as_validation()
{
    if (! $contact_form = cfredux_get_current_contact_form()) {
        return false;
    }

    return $contact_form->is_true('acceptance_as_validation');
}

add_filter(
    'cfredux_mail_tag_replaced_acceptance',
    'cfredux_acceptance_mail_tag', 10, 4
);

function cfredux_acceptance_mail_tag($replaced, $submitted, $html, $mail_tag)
{
    $form_tag = $mail_tag->corresponding_form_tag();

    if (! $form_tag) {
        return $replaced;
    }

    if (! empty($submitted)) {
        $replaced = __('Consented', 'contact-form-redux');
    } else {
        $replaced = __('Not consented', 'contact-form-redux');
    }

    $content = empty($form_tag->content)
    ? (string) reset($form_tag->values)
    : $form_tag->content;

    if (! $html) {
        $content = wp_strip_all_tags($content);
    }

    $content = trim($content);

    if ($content) {
        /* translators: 1: 'Consented' or 'Not consented', 2: conditions */
        $replaced = sprintf(
            _x(
                '%1$s: %2$s', 'mail output for acceptance checkboxes',
                'contact-form-redux'
            ),
            $replaced,
            $content
        );
    }

    return $replaced;
}


/* Tag generator */

add_action('cfredux_admin_init', 'cfredux_add_tag_generator_acceptance', 35);

function cfredux_add_tag_generator_acceptance()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'acceptance', __('acceptance', 'contact-form-redux'),
        'cfredux_tag_generator_acceptance'
    );
}

function cfredux_tag_generator_acceptance($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());
    $type = 'acceptance';

    $description = __(
        "Generate a form tag for an acceptance checkbox. For more details, " . 
            "see %s.", 
        'contact-form-redux'
    );

    $desc_link = cfredux_link(
        __(
            'https://cfr.backwoodsbytes.com/tags/acceptance-tags/', 
            'contact-form-redux'
        ), 
        __('Acceptance Tags', 'contact-form-redux')
    );

    ?>
<div class="control-box">
    <p class="cfrtaginfo">
        <?php echo sprintf(esc_html($description), $desc_link); ?>
    </p>
    
    <div class="inputgroup">
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
            <label for="<?php echo esc_attr($args['content'] . '-content'); ?>">
                <?php echo esc_html(__('Condition:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="text" 
                name="content" 
                class="oneline large-text" 
                id="<?php echo esc_attr($args['content'] . '-content'); ?>"
            >
        </div>
        <div class="inputrow xmedia">
            <label for="optional">
                <?php 
                    echo esc_html(
                        __(
                            'Optional:', 
                            'contact-form-redux'
                        )
                    ); 
                ?>
            </label>
            <label class="switch">
                <input type="checkbox" name="optional" class="option" checked>
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
</div>
    <?php
}
