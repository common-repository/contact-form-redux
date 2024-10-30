<?php
/*
Plugin Name: Contact Form Redux
Plugin URI: https://cfr.backwoodsbytes.com/
Description: A simple but flexible contact form.
Author: linux4me2
Text Domain: contact-form-redux
Version: 1.3.7
License: GPL
*/

define('CFREDUX_VERSION', '1.3.7');

define('CFREDUX_REQUIRED_WP_VERSION', '5.0');

define('CFREDUX_PLUGIN', __FILE__);

define('CFREDUX_PLUGIN_BASENAME', plugin_basename(CFREDUX_PLUGIN));

define('CFREDUX_PLUGIN_NAME', trim(dirname(CFREDUX_PLUGIN_BASENAME), '/'));

define('CFREDUX_PLUGIN_DIR', untrailingslashit(dirname(CFREDUX_PLUGIN)));

define('CFREDUX_PLUGIN_MODULES_DIR', CFREDUX_PLUGIN_DIR . '/modules');

if (! defined('CFREDUX_LOAD_JS')) {
    define('CFREDUX_LOAD_JS', true);
}

if (! defined('CFREDUX_LOAD_CSS')) {
    define('CFREDUX_LOAD_CSS', true);
}

if (! defined('CFREDUX_AUTOP')) {
    define('CFREDUX_AUTOP', true);
}

if (! defined('CFREDUX_USE_PIPE')) {
    define('CFREDUX_USE_PIPE', true);
}

if (! defined('CFREDUX_ADMIN_READ_CAPABILITY')) {
    define('CFREDUX_ADMIN_READ_CAPABILITY', 'edit_posts');
}

if (! defined('CFREDUX_ADMIN_READ_WRITE_CAPABILITY')) {
    define('CFREDUX_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages');
}

if (! defined('CFREDUX_VERIFY_NONCE')) {
    define('CFREDUX_VERIFY_NONCE', false);
}

if (! defined('CFREDUX_USE_REALLY_SIMPLE_CAPTCHA')) {
    define('CFREDUX_USE_REALLY_SIMPLE_CAPTCHA', false);
}

if (! defined('CFREDUX_VALIDATE_CONFIGURATION')) {
    define('CFREDUX_VALIDATE_CONFIGURATION', true);
}

// Deprecated, not used in the plugin core. Use cfredux_plugin_url() instead.
define('CFREDUX_PLUGIN_URL', untrailingslashit(plugins_url('', CFREDUX_PLUGIN)));

require_once CFREDUX_PLUGIN_DIR . '/settings.php';
