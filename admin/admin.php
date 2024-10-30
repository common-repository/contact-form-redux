<?php

require_once CFREDUX_PLUGIN_DIR . '/admin/includes/admin-functions.php';
require_once CFREDUX_PLUGIN_DIR . '/admin/includes/help-tabs.php';
require_once CFREDUX_PLUGIN_DIR . '/admin/includes/tag-generator.php';
require_once CFREDUX_PLUGIN_DIR . '/admin/includes/welcome-panel.php';

add_action('admin_init', 'cfredux_admin_init');

function cfredux_admin_init()
{
    if (current_user_can('manage_options')) {
        do_action('cfredux_admin_init');
    }
}

add_action('admin_menu', 'cfredux_admin_menu', 9);

function cfredux_admin_menu()
{
    global $_wp_last_object_menu;

    $_wp_last_object_menu++;

    add_menu_page(
        __('Contact Form Redux', 'contact-form-redux'),
        __('Contact', 'contact-form-redux'),
        'cfredux_read_contact_forms', 'cfredux',
        'cfredux_admin_management_page', 'dashicons-email',
        $_wp_last_object_menu
    );

    $edit = add_submenu_page(
        'cfredux',
        __('Edit Contact Form', 'contact-form-redux'),
        __('Contact Forms', 'contact-form-redux'),
        'cfredux_read_contact_forms', 'cfredux',
        'cfredux_admin_management_page'
    );

    add_action('load-' . $edit, 'cfredux_load_contact_form_admin');

    $addnew = add_submenu_page(
        'cfredux',
        __('Add New Contact Form', 'contact-form-redux'),
        __('Add New', 'contact-form-redux'),
        'cfredux_edit_contact_forms', 'cfredux-new',
        'cfredux_admin_add_new_page'
    );

    add_action('load-' . $addnew, 'cfredux_load_contact_form_admin');

    $integration = CFREDUX_Integration::get_instance();

    if ($integration->service_exists()) {
        $integration = add_submenu_page(
            'cfredux',
            __('Integration with Other Services', 'contact-form-redux'),
            __('Integration', 'contact-form-redux'),
            'cfredux_manage_integration', 'cfredux-integration',
            'cfredux_admin_integration_page'
        );

        add_action('load-' . $integration, 'cfredux_load_integration_page');
    }
    
    $options = add_submenu_page(
        'cfredux', 
        __('Set Options', 'contact-form-redux'), 
        __('Options', 'contact-form-redux'), 
        'cfredux_manage_options', 'cfredux-options', 
        'cfredux_admin_options_page'
    );
        
    add_action('load-' . $options, 'cfredux_load_admin_options_page');
}

add_filter('set-screen-option', 'cfredux_set_screen_options', 10, 3);

function cfredux_set_screen_options($result, $option, $value)
{
    $cfredux_screens = array(
    'cfredux_contact_forms_per_page');

    if (in_array($option, $cfredux_screens)) {
        $result = $value;
    }

    return $result;
}

function cfredux_load_contact_form_admin()
{
    global $plugin_page;

    $action = cfredux_current_action();

    if ('save' == $action) {
        $id = isset($_POST['post_ID']) 
            ? filter_input(INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT) : '-1';
        check_admin_referer('cfredux-save-contact-form_' . $id);

        if (! current_user_can('cfredux_edit_contact_form', $id)) {
            wp_die(
                __(
                    'You are not allowed to edit this item.', 
                    'contact-form-redux'
                )
            );
        }

        $args = $_REQUEST;
        $args['id'] = $id;

        $args['title'] = isset($_POST['post_title']) 
            ? sanitize_text_field($_POST['post_title']) : null;
        
        $args['locale'] = isset($_POST['cfredux-locale']) 
            ? sanitize_text_field($_POST['cfredux-locale']) : null;
        
        $args['form'] = isset($_POST['cfredux-form']) 
            ? wp_kses_post($_POST['cfredux-form']) : '';

        $args['mail'] = isset($_POST['cfredux-mail']) 
            ? cfredux_sanitize_mail($_POST['cfredux-mail']) : array();

        $args['mail_2'] = isset($_POST['cfredux-mail-2']) 
            ? cfredux_sanitize_mail($_POST['cfredux-mail-2']) : array();
        
        $args['messages'] = isset($_POST['cfredux-messages']) 
            ? sanitize_text_field($_POST['cfredux-messages']) : array();
        
        $args['additional_settings'] = isset($_POST['cfredux-additional-settings']) 
            ? sanitize_textarea_field($_POST['cfredux-additional-settings']) : '';

        $contact_form = cfredux_save_contact_form($args);

        if ($contact_form && cfredux_validate_configuration()) {
            $config_validator = new CFREDUX_ConfigValidator($contact_form);
            $config_validator->validate();
            $config_validator->save();
        }

        $query = array(
        'post' => $contact_form ? $contact_form->id() : 0,
        'active-tab' => isset($_POST['active-tab']) 
            ? filter_input(INPUT_POST, 'active-tab', FILTER_SANITIZE_NUMBER_INT) : 0
        );

        if (! $contact_form) {
            $query['message'] = 'failed';
        } elseif (-1 == $id) {
            $query['message'] = 'created';
        } else {
            $query['message'] = 'saved';
        }

        $redirect_to = add_query_arg($query, menu_page_url('cfredux', false));
        wp_safe_redirect($redirect_to);
        exit();
    }

    if ('copy' == $action) {
        $id = empty($_POST['post_ID']) 
            ? absint($_REQUEST['post']) : absint($_POST['post_ID']);

        check_admin_referer('cfredux-copy-contact-form_' . $id);

        if (! current_user_can('cfredux_edit_contact_form', $id)) {
            wp_die(
                __('You are not allowed to edit this item.', 'contact-form-redux')
            );
        }

        $query = array();

        if ($contact_form = cfredux_contact_form($id)) {
            $new_contact_form = $contact_form->copy();
            $new_contact_form->save();

            $query['post'] = $new_contact_form->id();
            $query['message'] = 'created';
        }

        $redirect_to = add_query_arg($query, menu_page_url('cfredux', false));

        wp_safe_redirect($redirect_to);
        exit();
    }

    if ('delete' == $action) {
        if (! empty($_POST['post_ID'])) {
            check_admin_referer(
                'cfredux-delete-contact-form_' . 
                filter_input(INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT)
            );
        } elseif (! is_array($_REQUEST['post'])) {
            check_admin_referer(
                'cfredux-delete-contact-form_' . absint($_REQUEST['post'])
            );
        } else {
            check_admin_referer('bulk-posts');
        }
        
        $posts = array();  
        if (empty($_POST['post_ID'])) {
            $posts = filter_var_array($_REQUEST['post'], FILTER_SANITIZE_NUMBER_INT);
        } else {
            $posts[] = filter_input(
                INPUT_POST, 
                'post_ID', 
                FILTER_SANITIZE_NUMBER_INT
            );
        }

        $deleted = 0;

        foreach ($posts as $post) {
            $post = CFREDUX_ContactForm::get_instance($post);

            if (empty($post)) {
                continue;
            }

            if (! current_user_can('cfredux_delete_contact_form', $post->id())) {
                wp_die(
                    __(
                        'You are not allowed to delete this item.', 
                        'contact-form-redux'
                    )
                );
            }

            if (! $post->delete()) {
                wp_die(__('Error in deleting.', 'contact-form-redux'));
            }

            $deleted += 1;
        }

        $query = array();

        if (! empty($deleted)) {
            $query['message'] = 'deleted';
        }

        $redirect_to = add_query_arg($query, menu_page_url('cfredux', false));

        wp_safe_redirect($redirect_to);
        exit();
    }

    if ('validate' == $action && cfredux_validate_configuration()) {
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            check_admin_referer('cfredux-bulk-validate');

            if (! current_user_can('cfredux_edit_contact_forms')) {
                wp_die(
                    __(
                        "You are not allowed to validate configuration.", 
                        'contact-form-redux'
                    )
                );
            }

            $contact_forms = CFREDUX_ContactForm::find();

            $result = array(
            'timestamp' => current_time('timestamp'),
            'version' => CFREDUX_VERSION,
            'count_valid' => 0,
            'count_invalid' => 0,
            );

            foreach ($contact_forms as $contact_form) {
                $config_validator = new CFREDUX_ConfigValidator($contact_form);
                $config_validator->validate();
                $config_validator->save();

                if ($config_validator->is_valid()) {
                    $result['count_valid'] += 1;
                } else {
                    $result['count_invalid'] += 1;
                }
            }

            CFREDUX::update_option('bulk_validate', $result);

            $query = array(
            'message' => 'validated',
            );

            $redirect_to = add_query_arg($query, menu_page_url('cfredux', false));
            wp_safe_redirect($redirect_to);
            exit;
        }
    }

    if (!isset($_GET['post'])) {
        $_GET['post'] = '';
    }

    $post = null;

    if ('cfredux-new' == $plugin_page) {
        $post = CFREDUX_ContactForm::get_template(
            array(
            'locale' => isset($_GET['locale']) 
                ? sanitize_text_field($_GET['locale']) : null
            )
        );
    } elseif (! empty($_GET['post'])) {
        $post = CFREDUX_ContactForm::get_instance(
            filter_input(INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT)
        );
    }

    $current_screen = get_current_screen();

    $help_tabs = new CFREDUX_Help_Tabs($current_screen);

    if ($post && current_user_can('cfredux_edit_contact_form', $post->id())) {
        $help_tabs->set_help_tabs('edit');
    } else {
        $help_tabs->set_help_tabs('list');

        if (! class_exists('CFREDUX_Contact_Form_List_Table')) {
            include_once CFREDUX_PLUGIN_DIR . 
                '/admin/includes/class-contact-forms-list-table.php';
        }

        add_filter(
            'manage_' . $current_screen->id . '_columns',
            array('CFREDUX_Contact_Form_List_Table', 'define_columns')
        );

        add_screen_option(
            'per_page', array(
            'default' => 20,
            'option' => 'cfredux_contact_forms_per_page',
            )
        );
    }
}

add_action('admin_enqueue_scripts', 'cfredux_admin_enqueue_scripts');

function cfredux_admin_enqueue_scripts($hook_suffix)
{
    if (false === strpos($hook_suffix, 'cfredux')) {
        return;
    }

    $use_minified = CFREDUX::get_option('use_minified');
    $min = '';
    if ($use_minified == 1) {
        $min = '-min';
    }
    
    wp_enqueue_style(
        'contact-form-redux-admin',
        cfredux_plugin_url('admin/css/styles' . $min . '.css'),
        array(), CFREDUX_VERSION, 'all'
    );

    if (cfredux_is_rtl()) {
        wp_enqueue_style(
            'contact-form-redux-admin-rtl',
            cfredux_plugin_url('admin/css/styles-rtl' . $min . '.css'),
            array(), CFREDUX_VERSION, 'all'
        );
    }
    
    wp_enqueue_script(
        'cfredux-admin',
        cfredux_plugin_url('admin/js/scripts' . $min . '.js'),
        array('jquery'),
        CFREDUX_VERSION, true
    );

    $args = array(
    'apiSettings' => array(
    'root' => esc_url_raw(rest_url('contact-form-redux/v1')),
    'namespace' => 'contact-form-redux/v1',
    'nonce' => (wp_installing() && ! is_multisite())
                ? '' : wp_create_nonce('wp_rest'),
    ),
    'pluginUrl' => cfredux_plugin_url(),
    'saveAlert' => __(
        "The changes you made will be lost if you navigate away from this page.",
        'contact-form-redux'
    ),
    'activeTab' => isset($_GET['active-tab']) 
        ? filter_input(INPUT_GET, 'active-tab', FILTER_SANITIZE_NUMBER_INT) : 0,
    'configValidator' => array(
    'errors' => array(),
    'howToCorrect' => __("How to resolve?", 'contact-form-redux'),
    'oneError' => __('1 configuration error detected', 'contact-form-redux'),
    'manyErrors' => __('%d configuration errors detected', 'contact-form-redux'),
    'oneErrorInTab' => __(
        '1 configuration error detected in this tab panel', 
        'contact-form-redux'
    ),
    'manyErrorsInTab' => __(
        '%d configuration errors detected in this tab panel', 
        'contact-form-redux'
    ),
    'docUrl' => CFREDUX_ConfigValidator::get_doc_link(),
    /* translators: screen reader text */
    'iconAlt' => __('(configuration error)', 'contact-form-redux'),
    ),
    );

    if (($post = cfredux_get_current_contact_form())
        && current_user_can('cfredux_edit_contact_form', $post->id())
        && cfredux_validate_configuration()
    ) {
        $config_validator = new CFREDUX_ConfigValidator($post);
        $config_validator->restore();
        $args['configValidator']['errors'] =
        $config_validator->collect_error_messages();
    }

    wp_localize_script('cfredux-admin', 'cfredux', $args);

    wp_enqueue_script(
        'cfredux-admin-taggenerator',
        cfredux_plugin_url('admin/js/tag-generator' . $min . '.js'),
        array('jquery', 'cfredux-admin'), CFREDUX_VERSION, true
    );
}

function cfredux_admin_management_page()
{
    if ($post = cfredux_get_current_contact_form()) {
        $post_id = $post->initial() ? -1 : $post->id();

        include_once CFREDUX_PLUGIN_DIR . '/admin/includes/editor.php';
        include_once CFREDUX_PLUGIN_DIR . '/admin/edit-contact-form.php';
        return;
    }

    if ('validate' == cfredux_current_action()
        && cfredux_validate_configuration()
        && current_user_can('cfredux_edit_contact_forms')
    ) {
        cfredux_admin_bulk_validate_page();
        return;
    }

    $list_table = new CFREDUX_Contact_Form_List_Table();
    $list_table->prepare_items();

    ?>
<div class="wrap">

<h1 class="wp-heading-inline"><?php
    echo esc_html(__('Contact Forms', 'contact-form-redux'));
?></h1>

    <?php
    if (current_user_can('cfredux_edit_contact_forms')) {
        echo sprintf(
            '<a href="%1$s" class="add-new-h2">%2$s</a>',
            esc_url(menu_page_url('cfredux-new', false)),
            esc_html(__('Add New', 'contact-form-redux'))
        );
    }

    if (! empty($_REQUEST['s'])) {
        echo sprintf(
            '<span class="subtitle">'
            /* translators: %s: search keywords */
            . __('Search results for &#8220;%s&#8221;', 'contact-form-redux')
            . '</span>', esc_html($_REQUEST['s'])
        );
    }
    ?>

<hr class="wp-header-end">

    <?php do_action('cfredux_admin_warnings'); ?>
    <?php cfredux_welcome_panel(); ?>
    <?php do_action('cfredux_admin_notices'); ?>

<form method="get" action="">
    <input 
        type="hidden" 
        name="page" 
        value="<?php echo esc_attr($_REQUEST['page']); ?>"
    >
    <?php 
    $list_table->search_box(
        __(
            'Search Contact Forms', 
            'contact-form-redux'
        ), 
        'cfredux-contact'
    ); 
    $list_table->display(); 
    ?>
</form>

</div>
    <?php
}

function cfredux_admin_bulk_validate_page()
{
    $contact_forms = CFREDUX_ContactForm::find();
    $count = CFREDUX_ContactForm::count();

    $submit_text = sprintf(
    /* translators: %s: number of contact forms */
        _n(
            "Validate %s Contact Form Now",
            "Validate %s Contact Forms Now",
            $count, 'contact-form-redux'
        ),
        number_format_i18n($count)
    );

    ?>
<div class="wrap">

<h1><?php echo esc_html(__('Validate Configuration', 'contact-form-redux')); ?></h1>

<form method="post" action="">
    <input type="hidden" name="action" value="validate">
    <?php wp_nonce_field('cfredux-bulk-validate'); ?>
    <p>
        <input 
            type="submit" 
            class="button" 
            value="<?php echo esc_attr($submit_text); ?>"
        >
    </p>
</form>

    <?php 
        echo cfredux_link(
            __(
                'https://cfr.backwoodsbytes.com/troubleshooting-guide' . 
                    '/configuration-validator/', 
                'contact-form-redux'
            ), 
            __('Configuration Validator Documentation', 'contact-form-redux')
        ); 
    ?>

</div>
    <?php
}

function cfredux_admin_add_new_page()
{
    $post = cfredux_get_current_contact_form();

    if (! $post) {
        $post = CFREDUX_ContactForm::get_template();
    }

    $post_id = -1;

    include_once CFREDUX_PLUGIN_DIR . '/admin/includes/editor.php';
    include_once CFREDUX_PLUGIN_DIR . '/admin/edit-contact-form.php';
}

function cfredux_load_admin_options_page()
{
    $help_tabs = new CFREDUX_Help_Tabs(get_current_screen());
    $help_tabs->set_help_tabs('options');
}

function cfredux_admin_options_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    if (isset($_POST['saved']) 
        && filter_input(INPUT_POST, 'saved', FILTER_SANITIZE_NUMBER_INT) == 1
    ) {
        $vars = array(
            'only_load_scripts', 
            'use_minified', 
            'html_widget_shortcodes', 
            'cfredux_javascript_submission_only', 
            'cfredux_use_spamhaus_rbls', 
            'cfredux_spamhaus_tag_only'
        );
        $saved = true;
        foreach ($vars as $var) {
            switch($var) {
            case 'only_load_scripts':
                $only_load_scripts = str_replace(
                    ' ', 
                    '', 
                    sanitize_text_field($_POST['only_load_scripts'])
                );
                if ($only_load_scripts != '') {
                    $only_load_scripts = array_filter(
                        explode(',', $only_load_scripts), 
                        'ctype_digit'
                    );
                    $only_load_scripts = implode(',', $only_load_scripts);
                }
                break;
            default:
                $$var = filter_input(INPUT_POST, $var, FILTER_SANITIZE_NUMBER_INT);
            }
            CFREDUX::update_option($var, $$var);
        }
    }
   
    
    $only_load_scripts = CFREDUX::get_option('only_load_scripts');
    $vars = array(
        'use_minified', 
        'html_widget_shortcodes', 
        'cfredux_javascript_submission_only', 
        'cfredux_use_spamhaus_rbls', 
        'cfredux_spamhaus_tag_only'
    );
    foreach ($vars as $var) {
        $$var = CFREDUX::get_option($var);
        if ($$var === false) {
            $$var = 0;
        }
    }
    ?>
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="wrap">
                    <?php
                    if (isset($saved)) {
                        if ($saved) {
                            echo '<div class="notice notice-success ' . 
                                'is-dismissible"><p>' . esc_html(
                                    __('Settings saved.', 'contact-form-redux')
                                ) . '</p></div>';    
                        }
                    }
                    ?>
                    <h1>
                        <?php 
                            echo esc_html(
                                __(
                                    'Contact Form Redux Options', 
                                    'contact-form-redux'
                                )
                            ); 
                        ?>
                    </h1>
                    <div class="postbox-container">
                        <form name="cfredux_admin_options_form" method="post">
                            <input type="hidden" name="saved" value="1">
                            <div class="inputgroup">
                                <div class="inputrow">
                                    <label for="only_load_scripts">
                                    <?php 
                                        echo esc_html(
                                            __(
                                                'Only load scripts and styles on ' . 
                                                    'pages: ', 
                                                'contact-form-redux'
                                            )
                                        ); 
                                    ?>
                                    </label> 
                                    <input 
                                        type="text" 
                                        name="only_load_scripts" 
                                        value="<?php 
                                            echo esc_attr($only_load_scripts); 
                                        ?>" 
                                        placeholder="comma-separated page IDs"
                                    >
                                </div>
                                <div class="inputrow">
                                    <label for="use_minified">
                                        <?php 
                                            echo esc_html(
                                                __(
                                                    'Use minified versions of ' . 
                                                        'javascript and CSS:', 
                                                    'contact-form-redux'
                                                )
                                            ); 
                                        ?>
                                    </label> 
                                    <select name="use_minified">
                                        <?php
                                        $no = esc_html(
                                            __('no', 'contact-form-redux')
                                        );
                                        $yes = esc_html(
                                            __('yes', 'contact-form-redux')
                                        );
                                        $vals = array(0=>$no, 1=>$yes);
                                        foreach ($vals as $key=>$val) {
                                            if ($key == $use_minified) {
                                                echo '<option value="' . $key . 
                                                    '" selected="selected">' . 
                                                    $val . '</option>';
                                            } else {
                                                echo '<option value="' . $key . 
                                                    '">' . $val . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="inputrow">
                                    <label for="html_widget_shortcodes">
                                        <?php 
                                            echo esc_html(
                                                __(
                                                    'Allow shortcodes in the ' . 
                                                        'Custom HTML Widget?', 
                                                    'contact-form-redux'
                                                )
                                            ); 
                                        ?>
                                    </label> 
                                    <select name="html_widget_shortcodes">
                                        <?php
                                        // $yes and $no are set above.
                                        $vals = array(0=>$no, 1=>$yes);
                                        foreach ($vals as $key=>$val) {
                                            if ($key == $html_widget_shortcodes) {
                                                echo '<option value="' . $key . 
                                                    '" selected="selected">' . 
                                                    $val . '</option>';
                                            } else {
                                                echo '<option value="' . $key . 
                                                    '">' . $val . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <h2>Anti-Spam Measures</h2>
                            <div class="inputgroup">
                                <div class="inputrow">
                                    <label for="cfredux_javascript_submission_only">
                                        <?php 
                                            echo esc_html(
                                                __(
                                                    'Javascript Submission Only:', 
                                                    'contact-form-redux'
                                                )
                                            ); 
                                        ?>
                                    </label>
                                    <select 
                                        name="cfredux_javascript_submission_only"
                                    >
                                        <?php
                                        // $yes and $no are set above.
                                        $vals = array(0=>$no, 1=>$yes);
                                        foreach ($vals as $key=>$val) {
                                            if ($key == $cfredux_javascript_submission_only) {
                                                echo '<option value="' . $key . 
                                                    '" selected="selected">' . 
                                                    $val . '</option>';
                                            } else {
                                                echo '<option value="' . $key . 
                                                    '">' . $val . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="inputrow">
                                    <label for="cfredux_use_spamhaus_rbls">
                                        <?php 
                                            echo esc_html(
                                                __(
                                                    'Use Spamhaus.org RBLs:', 
                                                    'contact-form-redux'
                                                )
                                            ); 
                                        ?>
                                    </label>
                                    <select name="cfredux_use_spamhaus_rbls">
                                        <?php
                                        // $yes and $no are set above.
                                        $vals = array(0=>$no, 1=>$yes);
                                        foreach ($vals as $key=>$val) {
                                            if ($key == $cfredux_use_spamhaus_rbls) {
                                                echo '<option value="' . $key . 
                                                    '" selected="selected">' . 
                                                    $val . '</option>';
                                            } else {
                                                echo '<option value="' . $key . 
                                                    '">' . $val . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="inputrow">
                                    <label for="cfredux_spamhaus_tag_only">
                                        <?php 
                                            echo esc_html(
                                                __(
                                                    'Tag-Only with Positive ' . 
                                                        'Spamhaus Result:', 
                                                    'contact-form-redux'
                                                )
                                            ); 
                                        ?>
                                    </label>
                                    <select name="cfredux_spamhaus_tag_only">
                                        <?php
                                        // $yes and $no are set above.
                                        $vals = array(0=>$no, 1=>$yes);
                                        foreach ($vals as $key=>$val) {
                                            if ($key == $cfredux_spamhaus_tag_only) {
                                                echo '<option value="' . $key . 
                                                    '" selected="selected">' . 
                                                    $val . '</option>';
                                            } else {
                                                echo '<option value="' . $key . 
                                                    '">' . $val . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <p>
                                <input 
                                    type="submit" 
                                    class="button-primary" 
                                    name="submit" 
                                    value="<?php 
                                        echo esc_attr_e(
                                            'Save', 
                                            'contact-form-redux'
                                        ); 
                                    ?>"
                                >
                            </p>
                        </form>
                    </div>
                    <?php cfredux_add_informationdiv(true); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function cfredux_load_integration_page()
{
    $integration = CFREDUX_Integration::get_instance();

    if (isset($_REQUEST['service'])
        && $integration->service_exists(
            sanitize_text_field($_REQUEST['service'])
        )
    ) {
        $service = $integration->get_service(
            sanitize_text_field($_REQUEST['service'])
        );
        $service->load(cfredux_current_action());
    }

    $help_tabs = new CFREDUX_Help_Tabs(get_current_screen());
    $help_tabs->set_help_tabs('integration');
}

function cfredux_admin_integration_page()
{
    $integration = CFREDUX_Integration::get_instance();

    ?>
<div class="wrap">

<h1>
    <?php 
        echo esc_html(__('Integration with Other Services', 'contact-form-redux')); 
    ?>
</h1>

    <?php do_action('cfredux_admin_warnings'); ?>
    <?php do_action('cfredux_admin_notices'); ?>

    <?php
    if (isset($_REQUEST['service'])
        && $service = $integration->get_service(
            sanitize_text_field($_REQUEST['service'])
        )
    ) {
        $message = isset($_REQUEST['message']) 
            ? sanitize_text_field($_REQUEST['message']) : '';
        $service->admin_notice($message);
        $integration->list_services(
            array(
                'include' => sanitize_text_field($_REQUEST['service'])
            )
        );
    } else {
        $integration->list_services();
    }
    ?>
</div>
    <?php
}

/* Misc */

add_action('cfredux_admin_notices', 'cfredux_admin_updated_message');

function cfredux_admin_updated_message()
{
    $request_message = isset($_REQUEST['message']) ? 
        sanitize_text_field($_REQUEST['message']) : null;
    
    if (empty($request_message)) {
        return;
    }

    switch($request_message) {
    case 'created':
        $updated_message = __("Contact form created.", 'contact-form-redux');
        break;
    case 'saved':
        $updated_message = __("Contact form saved.", 'contact-form-redux');
        break;
    case 'deleted':
        $updated_message = __("Contact form deleted.", 'contact-form-redux');
        break;    
    }

    if (! empty($updated_message)) {
        echo sprintf(
            '<div id="message" class="notice notice-success ' . 
                'is-dismissible"><p>%s</p></div>', 
            esc_html($updated_message)
        );
        return;
    }

    if ('failed' == $request_message) {
        $updated_message = __(
            "There was an error saving the contact form.",
            'contact-form-redux'
        );

        echo sprintf(
            '<div id="message" class="notice notice-error ' . 
                'is-dismissible"><p>%s</p></div>', 
            esc_html($updated_message)
        );
        return;
    }

    if ('validated' == $request_message) {
        $bulk_validate = CFREDUX::get_option('bulk_validate', array());
        $count_invalid = isset($bulk_validate['count_invalid'])
        ? absint($bulk_validate['count_invalid']) : 0;

        if ($count_invalid) {
            $updated_message = sprintf(
            /* translators: %s: number of contact forms */
                _n(
                    "Configuration validation completed. %s invalid contact " . 
                        "form was found.",
                    "Configuration validation completed. %s invalid contact " . 
                        "forms were found.",
                    $count_invalid, 'contact-form-redux'
                ),
                number_format_i18n($count_invalid)
            );

            echo sprintf(
                '<div id="message" class="notice notice-warning ' . 
                    'is-dismissible"><p>%s</p></div>', 
                esc_html($updated_message)
            );
        } else {
            $updated_message = __(
                "Configuration validation completed. No " . 
                    "invalid contact form was found.", 
                'contact-form-redux'
            );

            echo sprintf(
                '<div id="message" class="notice notice-success ' . 
                    'is-dismissible"><p>%s</p></div>', 
                esc_html($updated_message)
            );
        }

        return;
    }
}

add_filter('plugin_action_links', 'cfredux_plugin_action_links', 10, 2);

function cfredux_plugin_action_links($links, $file)
{
    if ($file != CFREDUX_PLUGIN_BASENAME) {
        return $links;
    }

    if (! current_user_can('cfredux_read_contact_forms')) {
        return $links;
    }

    $settings_link = sprintf(
        '<a href="%1$s">%2$s</a>',
        menu_page_url('cfredux', false),
        esc_html(__('Settings', 'contact-form-redux'))
    );

    array_unshift($links, $settings_link);

    return $links;
}

add_action('cfredux_admin_warnings', 'cfredux_old_wp_version_error');

function cfredux_old_wp_version_error()
{
    $wp_version = get_bloginfo('version');

    if (! version_compare($wp_version, CFREDUX_REQUIRED_WP_VERSION, '<')) {
        return;
    }

    ?>
<div class="notice notice-warning">
<p>
    <?php
        /* 
            Translators: 
            1: version of Contact Form Redux, 
            2: version of WordPress, 
            3: URL 
        */
        echo sprintf(
            __(
                '<strong>Contact Form Redux %1$s requires WordPress %2$s ' . 
                    'or higher.</strong> Please <a href="%3$s">update ' . 
                    'WordPress</a> first.', 
                'contact-form-redux'
            ), 
            CFREDUX_VERSION, 
            CFREDUX_REQUIRED_WP_VERSION, 
            admin_url('update-core.php')
        );
    ?>
</p>
</div>
    <?php
}

add_action('cfredux_admin_warnings', 'cfredux_not_allowed_to_edit');

function cfredux_not_allowed_to_edit()
{
    if (! $contact_form = cfredux_get_current_contact_form()) {
        return;
    }

    $post_id = $contact_form->id();

    if (current_user_can('cfredux_edit_contact_form', $post_id)) {
        return;
    }

    $message = __(
        "You are not allowed to edit this contact form.",
        'contact-form-redux'
    );

    echo sprintf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        esc_html($message)
    );
}

add_action('cfredux_admin_warnings', 'cfredux_notice_bulk_validate_config', 5);

function cfredux_notice_bulk_validate_config()
{
    if (! cfredux_validate_configuration()
        || ! current_user_can('cfredux_edit_contact_forms')
    ) {
        return;
    }
    
    $get_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : null;
    $get_action = isset($_GET['action']) 
        ? sanitize_text_field($_GET['action']) : null;
    if (isset($_GET['page']) 
        && 'cfredux' == $get_page 
        && isset($_GET['action']) 
        && 'validate' == $get_action
    ) {
        return;
    }
    
    /*
        Check for major version upgrades to activate the Bulk Validate link.
    */
    $result = CFREDUX::get_option('bulk_validate');
    $last_important_update = '1.1.0';

	if (!empty( $result['version'])
	    && version_compare($last_important_update, $result['version'], '<=')
    ) {
		return;
	}

    $link = add_query_arg(
        array('action' => 'validate'),
        menu_page_url('cfredux', false)
    );

    $link = sprintf(
        '<a href="%1$s">%2$s</a>',
        esc_url($link),
        esc_html(__('Validate Contact Forms', 'contact-form-redux'))
    );

    $message = __(
        "Misconfiguration can cause mail delivery failure. Validate " . 
            "your contact forms any time.", 'contact-form-redux'
    );

    echo sprintf(
        '<div class="notice"><p>%1$s &raquo; %2$s</p></div>',
        esc_html($message),
        $link
    );
}
