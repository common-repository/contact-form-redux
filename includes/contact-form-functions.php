<?php

function cfredux_contact_form($id)
{
    return CFREDUX_ContactForm::get_instance($id);
}

function cfredux_get_contact_form_by_title($title)
{
    $query = new WP_Query(
        array(
            'post_type'              => 'cfredux_contact_form',
            'title'                  => $title,
            'post_status'            => 'all',
            'posts_per_page'         => 1,
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'orderby'                => 'post_date ID',
            'order'                  => 'ASC',
        )
    );
    
    if (!empty($query->post)) {
        $page_got_by_title = $query->post;
        return cfredux_contact_form($page_got_by_title->ID);
    } else {
        return null;
    }
}

function cfredux_get_current_contact_form()
{
    if ($current = CFREDUX_ContactForm::get_current()) {
        return $current;
    }
}

function cfredux_is_posted()
{
    if (! $contact_form = cfredux_get_current_contact_form()) {
        return false;
    }

    return $contact_form->is_posted();
}

function cfredux_get_hangover($name, $default = null)
{
    if (! cfredux_is_posted()) {
        return $default;
    }

    $submission = CFREDUX_Submission::get_instance();

    if (! $submission || $submission->is('mail_sent')) {
        return $default;
    }

    if (isset($_POST[$name])) {
        return wp_unslash(sanitize_text_field($_POST[$name]));
    } else {
        return $default;
    }
}

function cfredux_get_validation_error($name)
{
    if (! $contact_form = cfredux_get_current_contact_form()) {
        return '';
    }

    return $contact_form->validation_error($name);
}

function cfredux_get_message($status)
{
    if (! $contact_form = cfredux_get_current_contact_form()) {
        return '';
    }

    return $contact_form->message($status);
}

function cfredux_form_controls_class($type, $default = '')
{
    $type = trim($type);
    $default = array_filter(explode(' ', $default));

    $classes = array_merge(array('cfredux-form-control'), $default);

    $typebase = rtrim($type, '*');
    $required = ('*' == substr($type, -1));

    $classes[] = 'cfredux-' . $typebase;

    if ($required) {
        $classes[] = 'cfredux-validates-as-required';
    }

    $classes = array_unique($classes);

    return implode(' ', $classes);
}

function cfredux_contact_form_tag_func($atts, $content = null, $code = '')
{
    if (is_feed()) {
        return '[contact-form-redux]';
    }

    if ('contact-form-redux' == $code) {
        $atts = shortcode_atts(
            array(
            'id' => 0,
            'title' => '',
            'html_id' => '',
            'html_name' => '',
            'html_class' => '',
            'output' => 'form',
            ),
            $atts, 'cfredux'
        );
        
        $id = (int) $atts['id'];
        $title = trim($atts['title']);
        
        if (! $contact_form = cfredux_contact_form($id)) {
            $contact_form = cfredux_get_contact_form_by_title($title);
        }

    }

    if (! $contact_form) {
        return '[contact-form-redux 404 "Not Found"]';
    }

    return $contact_form->form_html($atts);
}

function cfredux_save_contact_form($args = '', $context = 'save')
{
    $args = wp_parse_args(
        $args, array(
        'id' => -1,
        'title' => null,
        'locale' => null,
        'form' => null,
        'mail' => null,
        'mail_2' => null,
        'messages' => null,
        'additional_settings' => null,
        ) 
    );

    $args['id'] = (int) $args['id'];

    if (-1 == $args['id']) {
        $contact_form = CFREDUX_ContactForm::get_template();
    } else {
        $contact_form = cfredux_contact_form($args['id']);
    }

    if (empty($contact_form)) {
        return false;
    }

    if (null !== $args['title']) {
        $contact_form->set_title($args['title']);
    }

    if (null !== $args['locale']) {
        $contact_form->set_locale($args['locale']);
    }

    $properties = $contact_form->get_properties();

    $properties['form'] = cfredux_sanitize_form(
        $args['form'], $properties['form'] 
    );

    $properties['mail'] = cfredux_sanitize_mail(
        $args['mail'], $properties['mail'] 
    );

    $properties['mail']['active'] = true;

    $properties['mail_2'] = cfredux_sanitize_mail(
        $args['mail_2'], $properties['mail_2'] 
    );

    $properties['messages'] = cfredux_sanitize_messages(
        $args['messages'], $properties['messages'] 
    );

    $properties['additional_settings'] = cfredux_sanitize_additional_settings(
        $args['additional_settings'], $properties['additional_settings'] 
    );

    $contact_form->set_properties($properties);

    do_action('cfredux_save_contact_form', $contact_form, $args, $context);

    if ('save' == $context) {
        $contact_form->save();
    }

    return $contact_form;
}

function cfredux_sanitize_form($input, $default = '')
{
    if (null === $input) {
        return $default;
    }
    /* 
        You have to allow <, >, [, ], and @ in the form, so you had to use 
        str_ireplace rather than a sanitize function...
    */
    $search = array('<script>', '</script>', '<?php', '?>');
    $output = trim(str_ireplace($search, '', $input));
    return $output;
}

function cfredux_sanitize_mail($input, $defaults = array())
{
    $defaults = wp_parse_args(
        $defaults, array(
        'active' => false,
        'subject' => '',
        'sender' => '',
        'recipient' => '',
        'body' => '',
        'additional_headers' => '',
        'attachments' => '',
        'use_html' => false,
        'exclude_blank' => false,
        ) 
    );
    
    $input = wp_parse_args($input, $defaults);

    $output = array();
    $output['active'] = (bool) $input['active'];
    $output['subject'] = trim(sanitize_text_field($input['subject']));
    /* 
        You have to allow <, >, [, ], and @ in the next few fields, so you had to 
        use str_ireplace rather than a sanitize function...
    */
    $search = array('<script>', '</script>', '<?php', '?>');
    $output['sender'] = trim(str_ireplace($search, '', $input['sender']));
    /* 
        The recipient allows a comma-separated list of email addresses, so you 
        couldn't use a straight email sanitization function.
    */
    $output['recipient'] = trim(str_ireplace($search, '', $input['recipient']));
    $output['body'] = trim(str_ireplace($search, '', $input['body']));
    $output['additional_headers'] = '';

    $headers = str_replace(
        "\r\n", 
        "\n", 
        sanitize_textarea_field($input['additional_headers'])
    );
    $headers = explode("\n", $headers);

    foreach ($headers as $header) {
        $header = trim($header);

        if ('' !== $header) {
            $output['additional_headers'] .= $header . "\n";
        }
    }

    $output['additional_headers'] = trim($output['additional_headers']);
    $output['attachments'] = trim(sanitize_textarea_field($input['attachments']));
    $output['use_html'] = (bool) $input['use_html'];
    $output['exclude_blank'] = (bool) $input['exclude_blank'];

    return $output;
}

function cfredux_sanitize_messages($input, $defaults = array())
{
    $output = array();

    foreach (cfredux_messages() as $key => $val) {
        if (isset($input[$key])) {
            $output[$key] = trim(sanitize_text_field($input[$key]));
        } elseif (isset($defaults[$key])) {
            $output[$key] = $defaults[$key];
        }
    }

    return $output;
}

function cfredux_sanitize_additional_settings($input, $default = '')
{
    if (null === $input) {
        return $default;
    }

    $output = trim(sanitize_textarea_field($input));
    return $output;
}
