<?php

add_action('parse_request', 'cfredux_control_init', 20);

function cfredux_control_init()
{
    if (CFREDUX_Submission::is_restful()) {
        return;
    }

    if (isset($_POST['_cfredux'])) {
        $contact_form = cfredux_contact_form(
            filter_input(INPUT_POST, '_cfredux', FILTER_SANITIZE_NUMBER_INT)
        );
        if ($contact_form) {
            $contact_form->submit();
        }
    }
}

if (CFREDUX::get_option('html_widget_shortcodes') == 1) {
    add_filter('widget_text', 'cfredux_widget_text_filter', 9);
}

function cfredux_widget_text_filter($content)
{
    $pattern = '/\[[\r\n\t ]*contact-form-redux?[\r\n\t ].*?\]/';

    if (! preg_match($pattern, $content)) {
        return $content;
    }

    $content = do_shortcode($content);

    return $content;
}

add_action('wp_enqueue_scripts', 'cfredux_do_enqueue_scripts');

function cfredux_do_enqueue_scripts()
{
    if (cfredux_load_js()) {
        cfredux_enqueue_scripts();
    }

    if (cfredux_load_css()) {
        cfredux_enqueue_styles();
    }
}

function cfredux_enqueue_scripts()
{
    $in_footer = true;

    if ('header' === cfredux_load_js()) {
        $in_footer = false;
    }
    
    $load = true;
    $only_load_scripts = CFREDUX::get_option('only_load_scripts');
    if (!empty($only_load_scripts)) {
        $only_load_scripts = explode(',', $only_load_scripts);
        if (is_array($only_load_scripts)) {
            $page_id = get_queried_object_id();
            if (!in_array($page_id, $only_load_scripts)) {
                $load = false;
                return;
            }
        }    
    }
    
    if ($load === true) {
        $use_minified = CFREDUX::get_option('use_minified');
        $min = '';
        if ($use_minified === 1) {
            $min = '-min';
        }
        wp_enqueue_script(
            'contact-form-redux', 
            cfredux_plugin_url('includes/js/scripts' . $min . '.js'), 
            array('jquery'), 
            CFREDUX_VERSION, 
            $in_footer
        );
    }

    $cfredux = array(
    'apiSettings' => array(
    'root' => esc_url_raw(rest_url('contact-form-redux/v1')),
    'namespace' => 'contact-form-redux/v1',
    ),
    'recaptcha' => array(
    'messages' => array(
        'empty' =>
            __('Please verify that you are not a robot.', 'contact-form-redux'),
    ),
    ),
    );

    if (defined('WP_CACHE') && WP_CACHE) {
        $cfredux['cached'] = 1;
    }

    wp_localize_script('contact-form-redux', 'cfredux', $cfredux);

    do_action('cfredux_enqueue_scripts');
}

function cfredux_script_is()
{
    return wp_script_is('contact-form-redux');
}

function cfredux_enqueue_styles()
{
    $load = true;
    $only_load_scripts = CFREDUX::get_option('only_load_scripts');
    if (!empty($only_load_scripts)) {
        $only_load_scripts = explode(',', $only_load_scripts);
        if (is_array($only_load_scripts)) {
            $page_id = get_queried_object_id();
            if (!in_array($page_id, $only_load_scripts)) {
                $load = false;
                return;
            }
        }    
    }
    
    if ($load === true) {
        $use_minified = CFREDUX::get_option('use_minified');
        $min = '';
        if ($use_minified == 1) {
            $min = '-min';
        }
        wp_enqueue_style(
            'contact-form-redux', 
            cfredux_plugin_url('includes/css/styles' . $min . '.css'), 
            array(), 
            CFREDUX_VERSION, 
            'all'
        );
        if (cfredux_is_rtl()) {
            wp_enqueue_style(
                'contact-form-redux-rtl', 
                cfredux_plugin_url('includes/css/styles-rtl' . $min . '.css'), 
                array(), 
                CFREDUX_VERSION, 
                'all'
            );
        }
    }

    do_action('cfredux_enqueue_styles');
}

function cfredux_style_is()
{
    return wp_style_is('contact-form-redux');
}

