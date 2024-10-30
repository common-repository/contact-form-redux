<?php

class CFREDUX_Submission
{

    private static $instance;

    private $contact_form;
    private $status = 'init';
    private $posted_data = array();
    private $uploaded_files = array();
    private $skip_mail = false;
    private $response = '';
    private $invalid_fields = array();
    private $meta = array();
    private $consent = array();
    public $failed_spamhaus_check_tag_only = false;

    private function __construct()
    {
    }

    public static function get_instance(
        CFREDUX_ContactForm $contact_form = null, 
        $args = ''
    ) {
        $args = wp_parse_args(
            $args, array(
            'skip_mail' => false,
            )
        );

        if (empty(self::$instance)) {
            if (null == $contact_form) {
                return null;
            }

            self::$instance = new self;
            self::$instance->contact_form = $contact_form;
            self::$instance->skip_mail = (bool) $args['skip_mail'];
            self::$instance->setup_posted_data();
            self::$instance->submit();
        } elseif (null != $contact_form) {
            return null;
        }

        return self::$instance;
    }

    public static function is_restful()
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    public function get_status()
    {
        return $this->status;
    }

    public function set_status($status)
    {
        if (preg_match('/^[a-z][0-9a-z_]+$/', $status)) {
            $this->status = $status;
            return true;
        }

        return false;
    }

    public function is($status)
    {
        return $this->status == $status;
    }

    public function get_response()
    {
        return $this->response;
    }

    public function set_response($response)
    {
        $this->response = $response;
        return true;
    }

    public function get_contact_form()
    {
        return $this->contact_form;
    }

    public function get_invalid_field($name)
    {
        if (isset($this->invalid_fields[$name])) {
            return $this->invalid_fields[$name];
        } else {
            return false;
        }
    }

    public function get_invalid_fields()
    {
        return $this->invalid_fields;
    }

    public function get_posted_data($name = '')
    {
        if (! empty($name)) {
            if (isset($this->posted_data[$name])) {
                return $this->posted_data[$name];
            } else {
                return null;
            }
        }

        return $this->posted_data;
    }

    private function setup_posted_data()
    {
        $posted_data = (array) $_POST;
        $posted_data = array_diff_key($posted_data, array('_wpnonce' => ''));
        $posted_data = $this->sanitize_posted_data($posted_data);

        $tags = $this->contact_form->scan_form_tags();

        foreach ((array) $tags as $tag) {
            if (empty($tag->name)) {
                continue;
            }

            $type = $tag->type;
            $name = $tag->name;
            $pipes = $tag->pipes;

            $value_orig = $value = '';

            if (isset($posted_data[$name])) {
                $value_orig = $value = $posted_data[$name];
            }

            if (CFREDUX_USE_PIPE
                && $pipes instanceof CFREDUX_Pipes
                && ! $pipes->zero()
            ) {
                if (is_array($value_orig)) {
                    $value = array();

                    foreach ($value_orig as $v) {
                        $value[] = $pipes->do_pipe(wp_unslash($v));
                    }
                } else {
                    $value = $pipes->do_pipe(wp_unslash($value_orig));
                }
            }

            $value = apply_filters(
                "cfredux_posted_data_{$type}", $value,
                $value_orig, $tag
            );

            $posted_data[$name] = $value;

            if ($tag->has_option('consent_for:storage')
                && empty($posted_data[$name])
            ) {
                   $this->meta['do_not_store'] = true;
            }
        }

        $this->posted_data = apply_filters('cfredux_posted_data', $posted_data);

        return $this->posted_data;
    }

    private function sanitize_posted_data($value)
    {
        if (is_array($value)) {
            $value = array_map(array($this, 'sanitize_posted_data'), $value);
        } elseif (is_string($value)) {
            $value = wp_check_invalid_utf8($value);
            $value = wp_kses_no_null($value);
        }

        return $value;
    }

    private function submit()
    {
        if (! $this->is('init')) {
            return $this->status;
        }
        
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 254);
        } else {
            $user_agent = '';
        }
        
        if (isset($_POST['_cfredux_unit_tag'])) {
            $unit_tag = sanitize_text_field($_POST['_cfredux_unit_tag']);
        } else {
            $unit_tag = '';
        }
        
        if (isset($_POST['_cfredux_container_post'])) {
            $container_post_id = filter_input(
                INPUT_POST, 
                '_cfredux_container_post', 
                FILTER_SANITIZE_NUMBER_INT
            );
        } else {
            $container_post_id = 0;
        }

        $this->meta = array_merge(
            $this->meta, 
            array(
                'remote_ip' => $this->get_remote_ip_addr(),
                'user_agent' => $user_agent,
                'url' => $this->get_request_url(),
                'timestamp' => current_time('timestamp'),
                'unit_tag' => $unit_tag,
                'container_post_id' => $container_post_id,
                'current_user_id' => get_current_user_id(),
            )
        );
        
        $contact_form = $this->contact_form;

        if ($contact_form->is_true('do_not_store')) {
            $this->meta['do_not_store'] = true;
        }
        
        
        if (! $this->validate()) { // Validation error occured
            $this->set_status('validation_failed');
            $this->set_response($contact_form->message('validation_error'));

        } elseif (! $this->accepted()) { // Not accepted terms
            $this->set_status('acceptance_missing');
            $this->set_response($contact_form->message('accept_terms'));

        } elseif ($this->spam()) { // Spam!
            $this->set_status('spam');
            $this->set_response($contact_form->message('spam'));
            
        } elseif (! $this->before_send_mail()) {
            if ('init' == $this->get_status()) {
                $this->set_status('aborted');
            }

            if ('' === $this->get_response()) {
                $this->set_response(
                    $contact_form->filter_message(
                        __("Sending mail has been aborted.", 'contact-form-redux')
                    )
                );
            }

        } elseif ($this->mail()) {
            $this->set_status('mail_sent');
            $this->set_response($contact_form->message('mail_sent_ok'));

            do_action('cfredux_mail_sent', $contact_form);

        } else {
            $this->set_status('mail_failed');
            $this->set_response($contact_form->message('mail_sent_ng'));

            do_action('cfredux_mail_failed', $contact_form);
        }

        $this->remove_uploaded_files();

        return $this->status;
    }

    private function get_remote_ip_addr()
    {
        $ip_addr = '';

        if (isset($_SERVER['REMOTE_ADDR'])
            && WP_Http::is_ip_address($_SERVER['REMOTE_ADDR'])
        ) {
            $ip_addr = $_SERVER['REMOTE_ADDR'];
        }

        return apply_filters('cfredux_remote_ip_addr', $ip_addr);
    }

    private function get_request_url()
    {
        $home_url = untrailingslashit(home_url());

        if (self::is_restful()) {
            $referer = isset($_SERVER['HTTP_REFERER'])
            ? trim($_SERVER['HTTP_REFERER']) : '';

            if ($referer && 0 === strpos($referer, $home_url)) {
                return esc_url_raw($referer);
            }
        }

        $url = preg_replace('%(?<!:|/)/.*$%', '', $home_url)
        . cfredux_get_request_uri();

        return $url;
    }

    private function validate()
    {
        if ($this->invalid_fields) {
            return false;
        }

        include_once CFREDUX_PLUGIN_DIR . '/includes/validation.php';
        $result = new CFREDUX_Validation();

        $tags = $this->contact_form->scan_form_tags();

        foreach ($tags as $tag) {
            $type = $tag->type;
            $result = apply_filters("cfredux_validate_{$type}", $result, $tag);
        }

        $result = apply_filters('cfredux_validate', $result, $tags);

        $this->invalid_fields = $result->get_invalid_fields();

        return $result->is_valid();
    }

    private function accepted()
    {
        return apply_filters('cfredux_acceptance', true, $this);
    }

    public function add_consent($name, $conditions)
    {
        $this->consent[$name] = $conditions;
        return true;
    }

    public function collect_consent()
    {
        return (array) $this->consent;
    }

    private function spam()
    {
        $spam = false;
        
        if ($this->contact_form->is_true('subscribers_only')
            && current_user_can('cfredux_submit', $this->contact_form->id())
        ) {
            return $spam;
        }

        $user_agent = (string) $this->get_meta('user_agent');

        if (strlen($user_agent) < 2) {
            $spam = true;
        }

        if (! $this->verify_nonce()) {
            $spam = true;
        }

        if ($this->is_blacklisted()) {
            $spam = true;
        }
        
        if (!isset($_POST['_cfredux_txn_id']) 
            && CFREDUX::get_option('cfredux_javascript_submission_only') == 1
        ) {
            $spam = true;
        }
        
        // Only run the IP check if spam is still false.
        if ($spam == false) {
            $ip = $this->get_remote_ip_addr();
            if (!empty($ip) 
                && CFREDUX::get_option('cfredux_use_spamhaus_rbls') == 1
            ) {
                $blacklists = array('sbl.spamhaus.org', 'xbl.spamhaus.org');
                $suspect_ip = false;
                foreach ($blacklists as $blacklist) {
                    $url = implode(".", array_reverse(explode(".", $ip))) . "." . 
                        $blacklist;
                    $record = dns_get_record($url);
                    if (is_array($record) && !empty($record)) {
                        $suspect_ip = true;
                        break;
                    }
                }
                if ($suspect_ip === true) {
                    If (CFREDUX::get_option('cfredux_spamhaus_tag_only') == 1) {
                        /* 
                            Just tag the subject in mail.php by setting the 
                            following to true in mail.php.
                        */
                        $this->failed_spamhaus_check_tag_only = true;
                    } else {
                        $spam = true;
                    }
                }
            }
        }
        return apply_filters('cfredux_spam', $spam);
    }

    private function verify_nonce()
    {
        if (! $this->contact_form->nonce_is_active()) {
            return true;
        }

        return cfredux_verify_nonce(sanitize_text_field($_POST['_wpnonce']));
    }

    private function is_blacklisted()
    {
        $target = cfredux_array_flatten($this->posted_data);
        $target[] = $this->get_meta('remote_ip');
        $target[] = $this->get_meta('user_agent');
        $target = implode("\n", $target);

        return (bool) apply_filters(
            'cfredux_submission_is_blacklisted',
            cfredux_blacklist_check($target), $this
        );
    }

    /* Mail */

    private function before_send_mail()
    {
        $abort = false;

        do_action_ref_array(
            'cfredux_before_send_mail', array(
            $this->contact_form,
            &$abort,
            $this,
            )
        );

        return ! $abort;
    }

    private function mail()
    {
        $contact_form = $this->contact_form;

        $skip_mail = apply_filters(
            'cfredux_skip_mail',
            $this->skip_mail, $contact_form
        );

        if ($skip_mail) {
            return true;
        }
        
        $result = CFREDUX_Mail::send($contact_form->prop('mail'), 'mail');

        if ($result) {
            $additional_mail = array();

            if (($mail_2 = $contact_form->prop('mail_2')) && $mail_2['active']) {
                $additional_mail['mail_2'] = $mail_2;
            }

            $additional_mail = apply_filters(
                'cfredux_additional_mail',
                $additional_mail, $contact_form
            );

            foreach ($additional_mail as $name => $template) {
                CFREDUX_Mail::send($template, $name);
            }

            return true;
        }

        return false;
    }

    public function uploaded_files()
    {
        return $this->uploaded_files;
    }

    public function add_uploaded_file($name, $file_path)
    {
        $this->uploaded_files[$name] = $file_path;

        if (empty($this->posted_data[$name])) {
            $this->posted_data[$name] = basename($file_path);
        }
    }

    public function remove_uploaded_files()
    {
        foreach ((array) $this->uploaded_files as $name => $path) {
            cfredux_rmdir_p($path);

            if (($dir = dirname($path))
                && false !== ($files = scandir($dir))
                && ! array_diff($files, array('.', '..'))
            ) {
                // remove parent dir if it's empty.
                rmdir($dir);
            }
        }
    }

    public function get_meta($name)
    {
        if (isset($this->meta[$name])) {
            return $this->meta[$name];
        }
    }
}
