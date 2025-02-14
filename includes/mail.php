<?php

class CFREDUX_Mail
{

    private static $current = null;

    private $name = '';
    private $locale = '';
    private $template = array();
    private $use_html = false;
    private $exclude_blank = false;

    public static function get_current()
    {
        return self::$current;
    }

    public static function send($template, $name = '')
    {
        self::$current = new self($name, $template);
        return self::$current->compose();
    }

    private function __construct($name, $template)
    {
        $this->name = trim($name);
        $this->use_html = ! empty($template['use_html']);
        $this->exclude_blank = ! empty($template['exclude_blank']);

        $this->template = wp_parse_args(
            $template, array(
            'subject' => '',
            'sender' => '',
            'body' => '',
            'recipient' => '',
            'additional_headers' => '',
            'attachments' => '',
            )
        );

        if ($submission = CFREDUX_Submission::get_instance()) {
            $contact_form = $submission->get_contact_form();
            $this->locale = $contact_form->locale();
            $this->failed_spamhaus_check_tag_only 
                = $submission->failed_spamhaus_check_tag_only;
        }
    }

    public function name()
    {
        return $this->name;
    }

    public function get($component, $replace_tags = false)
    {
        $use_html = ($this->use_html && 'body' == $component);
        $exclude_blank = ($this->exclude_blank && 'body' == $component);

        $template = $this->template;
        if (!isset($template[$component])) {
            $component = '';
        } else {
            if ($component == 'subject') {
                if ($this->failed_spamhaus_check_tag_only == true) {
                    $component = $template[$component] . 
                        esc_html(
                            __(' - FAILED SPAMHAUS CHECK', 'contact-form-redux')
                        );
                } else {
                    $component = $template[$component];
                }
            } else {
                $component = $template[$component];
            }
        }
        
        if ($replace_tags) {
            $component = $this->replace_tags(
                $component, array(
                'html' => $use_html,
                'exclude_blank' => $exclude_blank,
                )
            );

            if ($use_html
                && ! preg_match('%<html[>\s].*</html>%is', $component)
            ) {
                $component = $this->htmlize($component);
            }
        }

        return $component;
    }

    private function htmlize($body)
    {
        if ($this->locale) {
            $lang_atts = sprintf(
                ' %s',
                cfredux_format_atts(
                    array(
                    'dir' => cfredux_is_rtl($this->locale) ? 'rtl' : 'ltr',
                    'lang' => str_replace('_', '-', $this->locale),
                    )
                )
            );
        } else {
            $lang_atts = '';
        }

        $header = apply_filters(
            'cfredux_mail_html_header',
            '<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml"' . $lang_atts . '>
<head>
<title>' . esc_html($this->get('subject', true)) . '</title>
</head>
<body>
', $this
        );

        $footer = apply_filters(
            'cfredux_mail_html_footer',
            '</body>
</html>', $this
        );

        $html = $header . wpautop($body) . $footer;
        return $html;
    }

    private function compose($send = true)
    {
        $components = array(
        'subject' => $this->get('subject', true),
        'sender' => $this->get('sender', true),
        'body' => $this->get('body', true),
        'recipient' => $this->get('recipient', true),
        'additional_headers' => $this->get('additional_headers', true),
        'attachments' => $this->attachments(),
        );

        $components = apply_filters(
            'cfredux_mail_components',
            $components, cfredux_get_current_contact_form(), $this
        );

        if (! $send) {
            return $components;
        }

        $subject = cfredux_strip_newline($components['subject']);
        $sender = cfredux_strip_newline($components['sender']);
        $recipient = cfredux_strip_newline($components['recipient']);
        $body = $components['body'];
        $additional_headers = trim($components['additional_headers']);
        $attachments = $components['attachments'];

        $headers = "From: $sender\n";

        if ($this->use_html) {
            $headers .= "Content-Type: text/html\n";
            $headers .= "X-CFREDUX-Content-Type: text/html\n";
        } else {
            $headers .= "X-CFREDUX-Content-Type: text/plain\n";
        }

        if ($additional_headers) {
            $headers .= $additional_headers . "\n";
        }

        return wp_mail($recipient, $subject, $body, $headers, $attachments);
    }

    public function replace_tags($content, $args = '')
    {
        if (true === $args) {
            $args = array('html' => true);
        }

        $args = wp_parse_args(
            $args, array(
            'html' => false,
            'exclude_blank' => false,
            )
        );

        return cfredux_mail_replace_tags($content, $args);
    }

    private function attachments($template = null)
    {
        if (! $template) {
            $template = $this->get('attachments');
        }

        $attachments = array();

        if ($submission = CFREDUX_Submission::get_instance()) {
            $uploaded_files = $submission->uploaded_files();

            foreach ((array) $uploaded_files as $name => $path) {
                //if (false !== strpos($template, "[${name}]")
                if (false !== strpos($template, "[{$name}]")
                    && ! empty($path)
                ) {
                    $attachments[] = $path;
                }
            }
        }

        foreach (explode("\n", $template) as $line) {
            $line = trim($line);

            if ('[' == substr($line, 0, 1)) {
                continue;
            }

            $path = path_join(WP_CONTENT_DIR, $line);

            if (! cfredux_is_file_path_in_content_dir($path)) {
                // $path is out of WP_CONTENT_DIR
                continue;
            }

            if (is_readable($path) && is_file($path)) {
                $attachments[] = $path;
            }
        }

        return $attachments;
    }
}

function cfredux_mail_replace_tags($content, $args = '')
{
    $args = wp_parse_args(
        $args, array(
        'html' => false,
        'exclude_blank' => false,
        )
    );

    if (is_array($content)) {
        foreach ($content as $key => $value) {
            $content[$key] = cfredux_mail_replace_tags($value, $args);
        }

        return $content;
    }

    $content = explode("\n", $content);

    foreach ($content as $num => $line) {
        $line = new CFREDUX_MailTaggedText($line, $args);
        $replaced = $line->replace_tags();

        if ($args['exclude_blank']) {
            $replaced_tags = $line->get_replaced_tags();

            if (empty($replaced_tags) || array_filter($replaced_tags)) {
                $content[$num] = $replaced;
            } else {
                unset($content[$num]); // Remove a line.
            }
        } else {
            $content[$num] = $replaced;
        }
    }

    $content = implode("\n", $content);

    return $content;
}

add_action('phpmailer_init', 'cfredux_phpmailer_init');

function cfredux_phpmailer_init($phpmailer)
{
    $custom_headers = $phpmailer->getCustomHeaders();
    $phpmailer->clearCustomHeaders();
    $cfredux_content_type = false;

    foreach ((array) $custom_headers as $custom_header) {
        $name = $custom_header[0];
        $value = $custom_header[1];

        if ('X-CFREDUX-Content-Type' === $name) {
            $cfredux_content_type = trim($value);
        } else {
            $phpmailer->addCustomHeader($name, $value);
        }
    }

    if ('text/html' === $cfredux_content_type) {
        $phpmailer->msgHTML($phpmailer->Body);
    } elseif ('text/plain' === $cfredux_content_type) {
        $phpmailer->AltBody = '';
    }
}

class CFREDUX_MailTaggedText
{

    private $html = false;
    private $callback = null;
    private $content = '';
    private $replaced_tags = array();

    public function __construct($content, $args = '')
    {
        $args = wp_parse_args(
            $args, array(
            'html' => false,
            'callback' => null,
            )
        );

        $this->html = (bool) $args['html'];

        if (null !== $args['callback'] && is_callable($args['callback'])) {
            $this->callback = $args['callback'];
        } elseif ($this->html) {
            $this->callback = array($this, 'replace_tags_callback_html');
        } else {
            $this->callback = array($this, 'replace_tags_callback');
        }

        $this->content = $content;
    }

    public function get_replaced_tags()
    {
        return $this->replaced_tags;
    }

    public function replace_tags()
    {
        $regex = '/(\[?)\[[\t ]*'
        . '([a-zA-Z_][0-9a-zA-Z:._-]*)' // [2] = name
        . '((?:[\t ]+"[^"]*"|[\t ]+\'[^\']*\')*)' // [3] = values
        . '[\t ]*\](\]?)/';

        return preg_replace_callback($regex, $this->callback, $this->content);
    }

    private function replace_tags_callback_html($matches)
    {
        return $this->replace_tags_callback($matches, true);
    }

    private function replace_tags_callback($matches, $html = false)
    {
        // allow [[foo]] syntax for escaping a tag
        if ($matches[1] == '[' && $matches[4] == ']') {
            return substr($matches[0], 1, -1);
        }

        $tag = $matches[0];
        $tagname = $matches[2];
        $values = $matches[3];

        $mail_tag = new CFREDUX_MailTag($tag, $tagname, $values);
        $field_name = $mail_tag->field_name();

        $submission = CFREDUX_Submission::get_instance();
        $submitted = $submission
        ? $submission->get_posted_data($field_name)
        : null;

        if (null !== $submitted) {

            if ($mail_tag->get_option('do_not_heat')) {
                if (isset($_POST[$field_name])) {
                    $submitted = sanitize_text_field($_POST[$field_name]);
                } else {
                    $submitted = '';
                }
            }

            $replaced = $submitted;

            if ($format = $mail_tag->get_option('format')) {
                $replaced = $this->format($replaced, $format);
            }

            $replaced = cfredux_flat_join($replaced);

            if ($html) {
                $replaced = esc_html($replaced);
                $replaced = wptexturize($replaced);
            }

            if ($form_tag = $mail_tag->corresponding_form_tag()) {
                $type = $form_tag->type;

                $replaced = apply_filters(
                    "cfredux_mail_tag_replaced_{$type}", $replaced,
                    $submitted, $html, $mail_tag
                );
            }

            $replaced = apply_filters(
                'cfredux_mail_tag_replaced', $replaced,
                $submitted, $html, $mail_tag
            );

            $replaced = wp_unslash(trim($replaced));

            $this->replaced_tags[$tag] = $replaced;
            return $replaced;
        }

        $special = apply_filters(
            'cfredux_special_mail_tags', null,
            $mail_tag->tag_name(), $html, $mail_tag
        );

        if (null !== $special) {
            $this->replaced_tags[$tag] = $special;
            return $special;
        }

        return $tag;
    }

    public function format($original, $format)
    {
        $original = (array) $original;

        foreach ($original as $key => $value) {
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)) {
                $original[$key] = mysql2date($format, $value);
            }
        }

        return $original;
    }
}

class CFREDUX_MailTag
{

    private $tag;
    private $tagname = '';
    private $name = '';
    private $options = array();
    private $values = array();
    private $form_tag = null;

    public function __construct($tag, $tagname, $values)
    {
        $this->tag = $tag;
        $this->name = $this->tagname = $tagname;

        $this->options = array(
        'do_not_heat' => false,
        'format' => '',
        );

        if (! empty($values)) {
            preg_match_all('/"[^"]*"|\'[^\']*\'/', $values, $matches);
            $this->values = cfredux_strip_quote_deep($matches[0]);
        }

        if (preg_match('/^_raw_(.+)$/', $tagname, $matches)) {
            $this->name = trim($matches[1]);
            $this->options['do_not_heat'] = true;
        }

        if (preg_match('/^_format_(.+)$/', $tagname, $matches)) {
            $this->name = trim($matches[1]);
            $this->options['format'] = $this->values[0];
        }
    }

    public function tag_name()
    {
        return $this->tagname;
    }

    public function field_name()
    {
        return $this->name;
    }

    public function get_option($option)
    {
        return $this->options[$option];
    }

    public function values()
    {
        return $this->values;
    }

    public function corresponding_form_tag()
    {
        if ($this->form_tag instanceof CFREDUX_FormTag) {
            return $this->form_tag;
        }

        if ($submission = CFREDUX_Submission::get_instance()) {
            $contact_form = $submission->get_contact_form();
            $tags = $contact_form->scan_form_tags(
                array(
                'name' => $this->name,
                'feature' => '! zero-controls-container',
                )
            );

            if ($tags) {
                $this->form_tag = $tags[0];
            }
        }

        return $this->form_tag;
    }
}
