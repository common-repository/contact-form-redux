<?php

class CFREDUX_ContactForm
{

    const post_type = 'cfredux_contact_form';

    private static $found_items = 0;
    private static $current = null;

    private $id;
    private $name;
    private $title;
    private $locale;
    private $properties = array();
    private $unit_tag;
    private $responses_count = 0;
    private $scanned_form_tags;
    private $shortcode_atts = array();

    public static function count()
    {
        return self::$found_items;
    }

    public static function get_current()
    {
        return self::$current;
    }

    public static function register_post_type()
    {
        register_post_type(
            self::post_type, array(
            'labels' => array(
            'name' => __('Contact Forms', 'contact-form-redux'),
            'singular_name' => __('Contact Form', 'contact-form-redux'),
            ),
            'rewrite' => false,
            'query_var' => false,
            'public' => false,
            'capability_type' => 'page',
            )
        );
    }

    public static function find($args = '')
    {
        $defaults = array(
        'post_status' => 'any',
        'posts_per_page' => -1,
        'offset' => 0,
        'orderby' => 'ID',
        'order' => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);

        $args['post_type'] = self::post_type;

        $q = new WP_Query();
        $posts = $q->query($args);

        self::$found_items = $q->found_posts;

        $objs = array();

        foreach ((array) $posts as $post) {
            $objs[] = new self($post);
        }

        return $objs;
    }

    public static function get_template($args = '')
    {
        global $l10n;

        $defaults = array('locale' => null, 'title' => '');
        $args = wp_parse_args($args, $defaults);

        $locale = $args['locale'];
        $title = $args['title'];

        if ($locale) {
            $mo_orig = $l10n['contact-form-redux'];
            cfredux_load_textdomain($locale);
        }

        self::$current = $contact_form = new self;
        $contact_form->title =
        ($title ? $title : __('Untitled', 'contact-form-redux'));
        $contact_form->locale = ($locale ? $locale : get_user_locale());

        $properties = $contact_form->get_properties();

        foreach ($properties as $key => $value) {
            $properties[$key] = CFREDUX_ContactFormTemplate::get_default($key);
        }

        $contact_form->properties = $properties;

        $contact_form = apply_filters(
            'cfredux_contact_form_default_pack',
            $contact_form, $args
        );

        if (isset($mo_orig)) {
            $l10n['contact-form-redux'] = $mo_orig;
        }

        return $contact_form;
    }

    public static function get_instance($post)
    {
        $post = get_post($post);

        if (! $post || self::post_type != get_post_type($post)) {
            return false;
        }

        return self::$current = new self($post);
    }

    private static function get_unit_tag($id = 0)
    {
        static $global_count = 0;

        $global_count += 1;

        if (in_the_loop()) {
            $unit_tag = sprintf(
                'cfredux-f%1$d-p%2$d-o%3$d',
                absint($id), get_the_ID(), $global_count
            );
        } else {
            $unit_tag = sprintf(
                'cfredux-f%1$d-o%2$d',
                absint($id), $global_count
            );
        }

        return $unit_tag;
    }

    private function __construct($post = null)
    {
        $post = get_post($post);

        if ($post && self::post_type == get_post_type($post)) {
            $this->id = $post->ID;
            $this->name = $post->post_name;
            $this->title = $post->post_title;
            $this->locale = get_post_meta($post->ID, '_locale', true);

            $properties = $this->get_properties();

            foreach ($properties as $key => $value) {
                if (metadata_exists('post', $post->ID, '_' . $key)) {
                    $properties[$key] = get_post_meta($post->ID, '_' . $key, true);
                } elseif (metadata_exists('post', $post->ID, $key)) {
                    $properties[$key] = get_post_meta($post->ID, $key, true);
                }
            }

            $this->properties = $properties;
            $this->upgrade();
        }

        do_action('cfredux_contact_form', $this);
    }

    public function __get($name)
    {
        $message = __(
            '<code>%1$s</code> property of a <code>CFREDUX_ContactForm</code> ' . 
                'object is <strong>no longer accessible</strong>. Use ' . 
                '<code>%2$s</code> method instead.', 'contact-form-redux'
        );

        if ('id' == $name) {
            if (WP_DEBUG) {
                trigger_error(sprintf($message, 'id', 'id()'));
            }

            return $this->id;
        } elseif ('title' == $name) {
            if (WP_DEBUG) {
                trigger_error(sprintf($message, 'title', 'title()'));
            }

            return $this->title;
        } elseif ($prop = $this->prop($name)) {
            if (WP_DEBUG) {
                trigger_error(
                    sprintf($message, $name, 'prop(\'' . $name . '\')')
                );
            }

            return $prop;
        }
    }

    public function initial()
    {
        return empty($this->id);
    }

    public function prop($name)
    {
        $props = $this->get_properties();
        return isset($props[$name]) ? $props[$name] : null;
    }

    public function get_properties()
    {
        $properties = (array) $this->properties;

        $properties = wp_parse_args(
            $properties, array(
            'form' => '',
            'mail' => array(),
            'mail_2' => array(),
            'messages' => array(),
            'additional_settings' => '',
            )
        );

        $properties = (array) apply_filters(
            'cfredux_contact_form_properties',
            $properties, $this
        );

        return $properties;
    }

    public function set_properties($properties)
    {
        $defaults = $this->get_properties();

        $properties = wp_parse_args($properties, $defaults);
        $properties = array_intersect_key($properties, $defaults);

        $this->properties = $properties;
    }

    public function id()
    {
        return $this->id;
    }

    public function name()
    {
        return $this->name;
    }

    public function title()
    {
        return $this->title;
    }

    public function set_title($title)
    {
        $title = strip_tags($title);
        $title = trim($title);

        if ('' === $title) {
            $title = __('Untitled', 'contact-form-redux');
        }

        $this->title = $title;
    }

    public function locale()
    {
        if (cfredux_is_valid_locale($this->locale)) {
            return $this->locale;
        } else {
            return '';
        }
    }

    public function set_locale($locale)
    {
        $locale = trim($locale);

        if (cfredux_is_valid_locale($locale)) {
            $this->locale = $locale;
        } else {
            $this->locale = 'en_US';
        }
    }

    public function shortcode_attr($name)
    {
        if (isset($this->shortcode_atts[$name])) {
            return (string) $this->shortcode_atts[$name];
        }
    }

    // Return true if this form is the same one as currently POSTed.
    public function is_posted()
    {
        if (! CFREDUX_Submission::get_instance()) {
            return false;
        }

        if (empty($_POST['_cfredux_unit_tag'])) {
            return false;
        }

        $unit_tag = sanitize_text_field($_POST['_cfredux_unit_tag']);
        return $this->unit_tag == $unit_tag;
    }

    /* Generating Form HTML */

    public function form_html($args = '')
    {
        $args = wp_parse_args(
            $args, array(
            'html_id' => '',
            'html_name' => '',
            'html_class' => '',
            'output' => 'form',
            )
        );

        $this->shortcode_atts = $args;

        if ('raw_form' == $args['output']) {
            return '<pre class="cfredux-raw-form"><code>'
            . esc_html($this->prop('form')) . '</code></pre>';
        }

        if ($this->is_true('subscribers_only')
            && ! current_user_can('cfredux_submit', $this->id())
        ) {
            $notice = __(
                "This contact form is available only for logged in users.",
                'contact-form-redux'
            );
            $notice = sprintf(
                '<p class="cfredux-subscribers-only">%s</p>',
                esc_html($notice)
            );

            return apply_filters('cfredux_subscribers_only_notice', $notice, $this);
        }

        $this->unit_tag = self::get_unit_tag($this->id);

        $lang_tag = str_replace('_', '-', $this->locale);

        if (preg_match('/^([a-z]+-[a-z]+)-/i', $lang_tag, $matches)) {
            $lang_tag = $matches[1];
        }

        $html = sprintf(
            '<div %s>',
            cfredux_format_atts(
                array(
                'role' => 'form',
                'class' => 'cfredux',
                'id' => $this->unit_tag,
                (get_option('html_type') == 'text/html') ? 'lang' : 'xml:lang'
                    => $lang_tag,
                'dir' => cfredux_is_rtl($this->locale) ? 'rtl' : 'ltr',
                )
            )
        );

        $html .= "\n" . $this->screen_reader_response() . "\n";

        $url = cfredux_get_request_uri();

        if ($frag = strstr($url, '#')) {
            $url = substr($url, 0, -strlen($frag));
        }

        $url .= '#' . $this->unit_tag;

        $url = apply_filters('cfredux_form_action_url', $url);

        $id_attr = apply_filters(
            'cfredux_form_id_attr',
            preg_replace('/[^A-Za-z0-9:._-]/', '', $args['html_id'])
        );

        $name_attr = apply_filters(
            'cfredux_form_name_attr',
            preg_replace('/[^A-Za-z0-9:._-]/', '', $args['html_name'])
        );

        $class = 'cfredux-form';

        if ($this->is_posted()) {
            $submission = CFREDUX_Submission::get_instance();

            switch ($submission->get_status()) {
            case 'validation_failed':
                $class .= ' invalid';
                break;
            case 'acceptance_missing':
                $class .= ' unaccepted';
                break;
            case 'spam':
                   $class .= ' spam';
                break;
            case 'aborted':
                $class .= ' aborted';
                break;
            case 'mail_sent':
                $class .= ' sent';
                break;
            case 'mail_failed':
                $class .= ' failed';
                break;
            default:
                $class .= sprintf(
                    ' custom-%s',
                    preg_replace('/[^0-9a-z]+/i', '-', $submission->get_status())
                );
            }
        }

        if ($args['html_class']) {
            $class .= ' ' . $args['html_class'];
        }

        if ($this->in_demo_mode()) {
            $class .= ' demo';
        }

        $class = explode(' ', $class);
        $class = array_map('sanitize_html_class', $class);
        $class = array_filter($class);
        $class = array_unique($class);
        $class = implode(' ', $class);
        $class = apply_filters('cfredux_form_class_attr', $class);

        $enctype = apply_filters('cfredux_form_enctype', '');
        $autocomplete = apply_filters('cfredux_form_autocomplete', '');

        $atts = array(
            'action' => esc_url($url),
            'method' => 'post',
            'class' => $class,
            'enctype' => cfredux_enctype_value($enctype),
            'autocomplete' => $autocomplete
        );

        if ('' !== $id_attr) {
            $atts['id'] = $id_attr;
        }

        if ('' !== $name_attr) {
            $atts['name'] = $name_attr;
        }

        $atts = cfredux_format_atts($atts);

        $html .= sprintf('<form %s>', $atts) . "\n";
        $html .= $this->form_hidden_fields();
        $html .= $this->form_elements();

        if (! $this->responses_count) {
            $html .= $this->form_response_output();
        }

        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    private function form_hidden_fields()
    {
        $hidden_fields = array(
        '_cfredux' => $this->id(),
        '_cfredux_version' => CFREDUX_VERSION,
        '_cfredux_locale' => $this->locale(),
        '_cfredux_unit_tag' => $this->unit_tag,
        '_cfredux_container_post' => 0,
        );

        if (in_the_loop()) {
            $hidden_fields['_cfredux_container_post'] = (int) get_the_ID();
        }

        if ($this->nonce_is_active()) {
            $hidden_fields['_wpnonce'] = cfredux_create_nonce();
        }

        $hidden_fields += (array) apply_filters(
            'cfredux_form_hidden_fields', array()
        );

        $content = '';

        foreach ($hidden_fields as $name => $value) {
            $content .= sprintf(
                '<input type="hidden" name="%1$s" value="%2$s" />',
                esc_attr($name), esc_attr($value)
            ) . "\n";
        }

        return '<div style="display: none;">' . "\n" . $content . '</div>' . "\n";
    }

    public function form_response_output()
    {
        $class = 'cfredux-response-output';
        $role = '';
        $content = '';

        if ($this->is_posted()) { // Post response output for non-AJAX
            $role = 'alert';

            $submission = CFREDUX_Submission::get_instance();
            $content = $submission->get_response();

            switch ($submission->get_status()) {
            case 'validation_failed':
                $class .= ' cfredux-validation-errors';
                break;
            case 'acceptance_missing':
                $class .= ' cfredux-acceptance-missing';
                break;
            case 'spam':
                   $class .= ' cfredux-spam-blocked';
                break;
            case 'aborted':
                $class .= ' cfredux-aborted';
                break;
            case 'mail_sent':
                $class .= ' cfredux-mail-sent-ok';
                break;
            case 'mail_failed':
                $class .= ' cfredux-mail-sent-ng';
                break;
            default:
                $class .= sprintf(
                    ' cfredux-custom-%s',
                    preg_replace('/[^0-9a-z]+/i', '-', $submission->get_status())
                );
            }
        } else {
            $class .= ' cfredux-display-none';
        }

        $atts = array(
        'class' => trim($class),
        'role' => trim($role),
        );

        $atts = cfredux_format_atts($atts);

        $output = sprintf(
            '<div %1$s>%2$s</div>',
            $atts, esc_html($content)
        );

        $output = apply_filters(
            'cfredux_form_response_output',
            $output, $class, $content, $this
        );

        $this->responses_count += 1;

        return $output;
    }

    public function screen_reader_response()
    {
        $class = 'screen-reader-response';
        $role = '';
        $content = '';

        if ($this->is_posted()) { // Post response output for non-AJAX
            $role = 'alert';

            $submission = CFREDUX_Submission::get_instance();

            if ($response = $submission->get_response()) {
                $content = esc_html($response);
            }

            if ($invalid_fields = $submission->get_invalid_fields()) {
                $content .= "\n" . '<ul>' . "\n";

                foreach ((array) $invalid_fields as $name => $field) {
                    if ($field['idref']) {
                        $link = sprintf(
                            '<a href="#%1$s">%2$s</a>',
                            esc_attr($field['idref']),
                            esc_html($field['reason'])
                        );
                        $content .= sprintf('<li>%s</li>', $link);
                    } else {
                        $content .= sprintf(
                            '<li>%s</li>',
                            esc_html($field['reason'])
                        );
                    }

                    $content .= "\n";
                }

                $content .= '</ul>' . "\n";
            }
        }

        $atts = array(
        'class' => trim($class),
        'role' => trim($role));

        $atts = cfredux_format_atts($atts);

        $output = sprintf(
            '<div %1$s>%2$s</div>',
            $atts, $content
        );

        return $output;
    }

    public function validation_error($name)
    {
        $error = '';

        if ($this->is_posted()) {
            $submission = CFREDUX_Submission::get_instance();

            if ($invalid_field = $submission->get_invalid_field($name)) {
                $error = trim($invalid_field['reason']);
            }
        }

        if (! $error) {
            return $error;
        }

        $error = sprintf(
            '<span role="alert" class="cfredux-not-valid-tip">%s</span>',
            esc_html($error)
        );

        return apply_filters('cfredux_validation_error', $error, $name, $this);
    }

    /* Form Elements */

    public function replace_all_form_tags()
    {
        $manager = CFREDUX_FormTagsManager::get_instance();
        $form = $this->prop('form');

        if (cfredux_autop_or_not()) {
            $form = $manager->normalize($form);
            $form = cfredux_autop($form);
        }

        $form = $manager->replace_all($form);
        $this->scanned_form_tags = $manager->get_scanned_tags();

        return $form;
    }

    public function form_do_shortcode()
    {
        cfredux_deprecated_function(
            __METHOD__, '4.6',
            'CFREDUX_ContactForm::replace_all_form_tags'
        );

        return $this->replace_all_form_tags();
    }

    public function scan_form_tags($cond = null)
    {
        $manager = CFREDUX_FormTagsManager::get_instance();

        if (empty($this->scanned_form_tags)) {
            $this->scanned_form_tags = $manager->scan($this->prop('form'));
        }

        $tags = $this->scanned_form_tags;

        return $manager->filter($tags, $cond);
    }

    public function form_scan_shortcode($cond = null)
    {
        cfredux_deprecated_function(
            __METHOD__, '4.6',
            'CFREDUX_ContactForm::scan_form_tags'
        );

        return $this->scan_form_tags($cond);
    }

    public function form_elements()
    {
        return apply_filters(
            'cfredux_form_elements',
            $this->replace_all_form_tags()
        );
    }

    public function collect_mail_tags($args = '')
    {
        $manager = CFREDUX_FormTagsManager::get_instance();

        $args = wp_parse_args(
            $args, array(
            'include' => array(),
            'exclude' => $manager->collect_tag_types('not-for-mail'),
            )
        );

        $tags = $this->scan_form_tags();
        $mailtags = array();

        foreach ((array) $tags as $tag) {
            $type = $tag->basetype;

            if (empty($type)) {
                continue;
            } elseif (! empty($args['include'])) {
                if (! in_array($type, $args['include'])) {
                    continue;
                }
            } elseif (! empty($args['exclude'])) {
                if (in_array($type, $args['exclude'])) {
                    continue;
                }
            }

            $mailtags[] = $tag->name;
        }

        $mailtags = array_unique(array_filter($mailtags));

        return apply_filters('cfredux_collect_mail_tags', $mailtags, $args, $this);
    }

    public function suggest_mail_tags($for = 'mail')
    {
        $mail = wp_parse_args(
            $this->prop($for),
            array(
            'active' => false,
            'recipient' => '',
            'sender' => '',
            'subject' => '',
            'body' => '',
            'additional_headers' => '',
            'attachments' => '',
            'use_html' => false,
            'exclude_blank' => false,
            )
        );

        $mail = array_filter($mail);

        foreach ((array) $this->collect_mail_tags() as $mail_tag) {
            $pattern = sprintf(
                '/\[(_[a-z]+_)?%s([ \t]+[^]]+)?\]/',
                preg_quote($mail_tag, '/')
            );
            $used = preg_grep($pattern, $mail);

            echo sprintf(
                '<span class="%1$s">[%2$s]</span>',
                'mailtag code ' . ($used ? 'used' : 'unused'),
                esc_html($mail_tag)
            );
        }
    }

    public function submit($args = '')
    {
        $args = wp_parse_args(
            $args, array(
            'skip_mail' =>
            ($this->in_demo_mode()
            || $this->is_true('skip_mail')
            || ! empty($this->skip_mail)),
            )
        );

        if ($this->is_true('subscribers_only')
            && ! current_user_can('cfredux_submit', $this->id())
        ) {
            $result = array(
            'contact_form_id' => $this->id(),
            'status' => 'error',
            'message' => __(
                "This contact form is available only for logged in users.",
                'contact-form-redux'
            ),
            );

            return $result;
        }

        $submission = CFREDUX_Submission::get_instance(
            $this, array(
            'skip_mail' => $args['skip_mail'],
            )
        );

        $result = array(
        'contact_form_id' => $this->id(),
        'status' => $submission->get_status(),
        'message' => $submission->get_response(),
        'demo_mode' => $this->in_demo_mode(),
        );

        if ($submission->is('validation_failed')) {
            $result['invalid_fields'] = $submission->get_invalid_fields();
        }

        do_action('cfredux_submit', $this, $result);

        return $result;
    }

    /* Message */

    public function message($status, $filter = true)
    {
        $messages = $this->prop('messages');
        $message = isset($messages[$status]) ? $messages[$status] : '';

        if ($filter) {
            $message = $this->filter_message($message, $status);
        }

        return $message;
    }

    public function filter_message($message, $status = '')
    {
        $message = wp_strip_all_tags($message);
        $message = cfredux_mail_replace_tags($message, array('html' => true));
        $message = apply_filters('cfredux_display_message', $message, $status);

        return $message;
    }

    /* Additional settings */

    public function additional_setting($name, $max = 1)
    {
        $settings = (array) explode("\n", $this->prop('additional_settings'));

        $pattern = '/^([a-zA-Z0-9_]+)[\t ]*:(.*)$/';
        $count = 0;
        $values = array();

        foreach ($settings as $setting) {
            if (preg_match($pattern, $setting, $matches)) {
                if ($matches[1] != $name) {
                    continue;
                }

                if (! $max || $count < (int) $max) {
                    $values[] = trim($matches[2]);
                    $count += 1;
                }
            }
        }

        return $values;
    }

    public function is_true($name)
    {
        $settings = $this->additional_setting($name, false);

        foreach ($settings as $setting) {
            if (in_array($setting, array('on', 'true', '1'))) {
                return true;
            }
        }

        return false;
    }

    public function in_demo_mode()
    {
        return $this->is_true('demo_mode');
    }

    public function nonce_is_active()
    {
        $is_active = CFREDUX_VERIFY_NONCE;

        if ($this->is_true('subscribers_only')) {
            $is_active = true;
        }

        return (bool) apply_filters('cfredux_verify_nonce', $is_active, $this);
    }

    /* Upgrade */

    private function upgrade()
    {
        $mail = $this->prop('mail');

        if (is_array($mail) && ! isset($mail['recipient'])) {
            $mail['recipient'] = get_option('admin_email');
        }

        $this->properties['mail'] = $mail;

        $messages = $this->prop('messages');

        if (is_array($messages)) {
            foreach (cfredux_messages() as $key => $arr) {
                if (! isset($messages[$key])) {
                    $messages[$key] = $arr['default'];
                }
            }
        }

        $this->properties['messages'] = $messages;
    }

    /* Save */

    public function save()
    {
        $props = $this->get_properties();

        $post_content = implode("\n", cfredux_array_flatten($props));

        if ($this->initial()) {
            $post_id = wp_insert_post(
                array(
                'post_type' => self::post_type,
                'post_status' => 'publish',
                'post_title' => $this->title,
                'post_content' => trim($post_content),
                )
            );
        } else {
            $post_id = wp_update_post(
                array(
                'ID' => (int) $this->id,
                'post_status' => 'publish',
                'post_title' => $this->title,
                'post_content' => trim($post_content),
                )
            );
        }

        if ($post_id) {
            foreach ($props as $prop => $value) {
                update_post_meta(
                    $post_id, '_' . $prop,
                    cfredux_normalize_newline_deep($value)
                );
            }

            if (cfredux_is_valid_locale($this->locale)) {
                update_post_meta($post_id, '_locale', $this->locale);
            }

            if ($this->initial()) {
                $this->id = $post_id;
                do_action('cfredux_after_create', $this);
            } else {
                do_action('cfredux_after_update', $this);
            }

            do_action('cfredux_after_save', $this);
        }

        return $post_id;
    }

    public function copy()
    {
        $new = new self;
        $new->title = $this->title . '_copy';
        $new->locale = $this->locale;
        $new->properties = $this->properties;

        return apply_filters('cfredux_copy', $new, $this);
    }

    public function delete()
    {
        if ($this->initial()) {
            return;
        }

        if (wp_delete_post($this->id, true)) {
            $this->id = 0;
            return true;
        }

        return false;
    }

    public function shortcode($args = '')
    {
        //$args = wp_parse_args($args, array('use_old_format' => false));
        $args = wp_parse_args($args);

        $title = str_replace(array('"', '[', ']'), '', $this->title);

        $shortcode = sprintf(
            '[contact-form-redux id="%1$d" title="%2$s"]', 
            $this->id, 
            $title
        );
        
        return apply_filters(
            'cfredux_contact_form_shortcode', 
            $shortcode, 
            $args, 
            $this
        );
    }
}
