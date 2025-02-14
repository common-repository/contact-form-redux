<?php

class CFREDUX_ContactFormTemplate
{

    public static function get_default($prop = 'form')
    {
        if ('form' == $prop) {
            $template = self::form();
        } elseif ('mail' == $prop) {
            $template = self::mail();
        } elseif ('mail_2' == $prop) {
            $template = self::mail_2();
        } elseif ('messages' == $prop) {
            $template = self::messages();
        } else {
            $template = null;
        }

        return apply_filters('cfredux_default_template', $template, $prop);
    }

    public static function form()
    {
        $template = sprintf(
            '<label> %2$s %1$s:' . "\n  " . '[text* your-name] </label>' . "\n\n" . 
            '<label> %3$s %1$s:' . "\n  " . '[email* your-email] </label>' . 
                "\n\n" . 
            '<label> %4$s:' . "\n  " . '[text your-subject] </label>' . "\n\n" . 
            '<label> %5$s:' . "\n  " . '[textarea your-message] </label>' . "\n\n" . 
            '[submit "%6$s"]',
            __('(required)', 'contact-form-redux'),
            __('Your Name', 'contact-form-redux'),
            __('Your Email', 'contact-form-redux'),
            __('Subject', 'contact-form-redux'),
            __('Your Message', 'contact-form-redux'),
            __('Send', 'contact-form-redux')
        );

        return trim($template);
    }

    public static function mail()
    {
        $template = array(
        'subject' =>
        /* translators: 1: blog name, 2: [your-subject] */
        sprintf(
            _x('%1$s "%2$s"', 'mail subject', 'contact-form-redux'),
            get_bloginfo('name'), '[your-subject]'
        ),
        'sender' => sprintf('[your-name] <%s>', self::from_email()),
        'body' =>
        /* translators: %s: [your-name] <[your-email]> */
        sprintf(
            __('From: %s', 'contact-form-redux'),
            '[your-name] <[your-email]>'
        ) . "\n"
        /* translators: %s: [your-subject] */
        . sprintf(
            __('Subject: %s', 'contact-form-redux'),
            '[your-subject]'
        ) . "\n\n"
        . __('Message Body:', 'contact-form-redux')
                    . "\n" . '[your-message]' . "\n\n"
        . '-- ' . "\n"
        /* translators: 1: blog name, 2: blog URL */
        . sprintf(
            __(
                'This e-mail was sent from a contact form on %1$s (%2$s)', 
                'contact-form-redux'
            ),
            get_bloginfo('name'),
            get_bloginfo('url')
        ) . "\n" . __('Remote IP: ', 'contact-form-redux') . '[_remote_ip]' 
        . "\n" . __('User Agent: ', 'contact-form-redux') . '[_user_agent]',
        'recipient' => get_option('admin_email'),
        'additional_headers' => 'Reply-To: [your-email]',
        'attachments' => '',
        'use_html' => 0,
        'exclude_blank' => 0,
        );

        return $template;
    }

    public static function mail_2()
    {
        $template = array(
        'active' => false,
        'subject' =>
        /* translators: 1: blog name, 2: [your-subject] */
        sprintf(
            _x('%1$s "%2$s"', 'mail subject', 'contact-form-redux'),
            get_bloginfo('name'), '[your-subject]'
        ),
        'sender' => sprintf(
            '%s <%s>',
            get_bloginfo('name'), self::from_email()
        ),
        'body' =>
        __('Message Body:', 'contact-form-redux')
                    . "\n" . '[your-message]' . "\n\n"
        . '-- ' . "\n"
        /* translators: 1: blog name, 2: blog URL */
        . sprintf(
            __(
                'This e-mail was sent from a contact form on %1$s (%2$s)', 
                'contact-form-redux'
            ),
            get_bloginfo('name'),
            get_bloginfo('url')
        ),
        'recipient' => '[your-email]',
        'additional_headers' => sprintf(
            'Reply-To: %s',
            get_option('admin_email')
        ),
        'attachments' => '',
        'use_html' => 0,
        'exclude_blank' => 0,
        );

        return $template;
    }

    public static function from_email()
    {
        $admin_email = get_option('admin_email');
        $sitename = strtolower($_SERVER['SERVER_NAME']);

        if (cfredux_is_localhost()) {
            return $admin_email;
        }

        if (substr($sitename, 0, 4) == 'www.') {
            $sitename = substr($sitename, 4);
        }

        if (strpbrk($admin_email, '@') == '@' . $sitename) {
            return $admin_email;
        }

        return 'system@' . $sitename;
    }

    public static function messages()
    {
        $messages = array();

        foreach (cfredux_messages() as $key => $arr) {
            $messages[$key] = $arr['default'];
        }

        return $messages;
    }
}

function cfredux_messages()
{
    $messages = array(
    'mail_sent_ok' => array(
    'description'
                => __(
                    "Sender's message was sent successfully", 
                    'contact-form-redux'
                ),
    'default'
                => __(
                    "Thank you for your message. It has been sent.", 
                    'contact-form-redux'
                ),
    ),

    'mail_sent_ng' => array(
    'description'
                => __("Sender's message failed to send", 'contact-form-redux'),
    'default'
                => __(
                    "There was an error trying to send your message. " . 
                        "Please try again later.", 
                    'contact-form-redux'
                ),
    ),

    'validation_error' => array(
    'description'
                => __("Validation errors occurred", 'contact-form-redux'),
    'default'
                => __(
                    "One or more fields have an error. Please check and try again.", 
                    'contact-form-redux'
                ),
    ),

    'spam' => array(
    'description'
                => __("Submission was referred to as spam", 'contact-form-redux'),
    'default'
                => __(
                    "There was an error trying to send your message. Please " . 
                        "try again later.", 
                    'contact-form-redux'
                ),
    ),

    'accept_terms' => array(
    'description'
                => __(
                    "There are terms that the sender must accept", 
                    'contact-form-redux'
                ),
    'default'
                => __(
                    "You must accept the terms and conditions before sending " . 
                        "your message.", 
                    'contact-form-redux'
                ),
    ),

    'invalid_required' => array(
    'description'
                => __(
                    "There is a field that the sender must fill in", 
                    'contact-form-redux'
                ),
    'default'
                => __("The field is required.", 'contact-form-redux'),
    ),

    'invalid_too_long' => array(
    'description'
                => __(
                    "There is a field with input that is longer than the maximum " . 
                        "allowed length", 
                    'contact-form-redux'
                ),
    'default'
                => __("The field is too long.", 'contact-form-redux'),
    ),

    'invalid_too_short' => array(
    'description'
                => __(
                    "There is a field with input that is shorter than the " . 
                        "minimum allowed length", 
                    'contact-form-redux'
                ),
    'default'
                => __("The field is too short.", 'contact-form-redux'),
    )
    );

    return apply_filters('cfredux_messages', $messages);
}
