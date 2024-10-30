<?php

require_once CFREDUX_PLUGIN_DIR . '/includes/functions.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/l10n.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/formatting.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/pipe.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/form-tag.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/form-tags-manager.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/capabilities.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/contact-form-template.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/contact-form.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/contact-form-functions.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/mail.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/special-mail-tags.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/submission.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/upgrade.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/integration.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/config-validator.php';
require_once CFREDUX_PLUGIN_DIR . '/includes/rest-api.php';

if (is_admin()) {
    include_once CFREDUX_PLUGIN_DIR . '/admin/admin.php';
} else {
    include_once CFREDUX_PLUGIN_DIR . '/includes/controller.php';
}

class CFREDUX
{

    public static function load_modules()
    {
        self::load_module('acceptance');
        self::load_module('akismet');
        self::load_module('checkbox');
        self::load_module('count');
        self::load_module('date');
        self::load_module('file');
        self::load_module('number');
        self::load_module('quiz');
        self::load_module('really-simple-captcha');
        self::load_module('recaptcha');
        self::load_module('response');
        self::load_module('select');
        self::load_module('submit');
        self::load_module('text');
        self::load_module('textarea');
        self::load_module('hidden');
    }

    protected static function load_module($mod)
    {
        $dir = CFREDUX_PLUGIN_MODULES_DIR;

        if (empty($dir) || ! is_dir($dir)) {
            return false;
        }

        $file = path_join($dir, $mod . '.php');

        if (file_exists($file)) {
            include_once $file;
        }
    }

    public static function get_option($name, $default = false)
    {
        $option = get_option('cfredux');

        if (false === $option) {
            return $default;
        }

        if (isset($option[$name])) {
            return $option[$name];
        } else {
            return $default;
        }
    }

    public static function update_option($name, $value)
    {
        $option = get_option('cfredux');
        $option = (false === $option) ? array() : (array) $option;
        $option = array_merge($option, array($name => $value));
        $result = update_option('cfredux', $option);
        return $result;
    }
}

add_action('plugins_loaded', 'cfredux');

function cfredux()
{
    cfredux_load_textdomain();
    CFREDUX::load_modules();

    /* Shortcodes */
    add_shortcode('contact-form-redux', 'cfredux_contact_form_tag_func');
    add_shortcode('contact-form', 'cfredux_contact_form_tag_func');
}

add_action('init', 'cfredux_init');

function cfredux_init()
{
    cfredux_get_request_uri();
    cfredux_register_post_types();

    do_action('cfredux_init');
}

add_action('admin_init', 'cfredux_upgrade');

function cfredux_upgrade()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $old_ver = CFREDUX::get_option('version', '0');
    $new_ver = CFREDUX_VERSION;

    if ($old_ver == $new_ver) {
        return;
    }

    do_action('cfredux_upgrade', $new_ver, $old_ver);

    CFREDUX::update_option('version', $new_ver);
}

/* Install and default settings */

add_action('activate_' . CFREDUX_PLUGIN_BASENAME, 'cfredux_install');

function cfredux_install()
{
    if ($opt = get_option('cfredux')) {
        return;
    }

    cfredux_load_textdomain();
    cfredux_register_post_types();
    cfredux_upgrade();

    if (get_posts(array('post_type' => 'cfredux_contact_form'))) {
        return;
    }

    $contact_form = CFREDUX_ContactForm::get_template(
    /* 
          Translators: title of your first contact form. %d: number fixed to '1' 
    */
        array(
            'title' => sprintf(__('Contact form %d', 'contact-form-redux'), 1),
        )
    );

    $contact_form->save();

    CFREDUX::update_option(
        'bulk_validate',
        array(
            'timestamp' => current_time('timestamp'),
            'version' => CFREDUX_VERSION,
            'count_valid' => 1,
            'count_invalid' => 0,
        )
    );
}
