<?php

function cfredux_welcome_panel()
{
    $classes = 'cfr-welcome-panel';

    $vers = (array) get_user_meta(
        get_current_user_id(),
        'cfredux_hide_welcome_panel_on', true
    );

    if (cfredux_version_grep(cfredux_version('only_major=1'), $vers)) {
        $classes .= ' hidden';
    }

    ?>
<div id="cfr-welcome-panel" class="<?php echo esc_attr($classes); ?>">
    <?php 
        wp_nonce_field(
            'cfredux-welcome-panel-nonce', 
            'welcomepanelnonce', 
            false
        ); 
    ?>
    <a 
        class="cfr-welcome-panel-close" 
        href="<?php echo esc_url(menu_page_url('cfredux', false)); ?>"
    ><?php echo esc_html(__('Dismiss', 'contact-form-redux')); ?></a>

    <div class="cfr-welcome-panel-content">
        <div class="cfr-welcome-panel-column-container">

            <div>
                <h3>
                    <span 
                        class="dashicons dashicons-shield" 
                        aria-hidden="true"
                    ></span>&nbsp;
                    <?php 
                    echo esc_html(
                        __("Getting contact form spam?", 'contact-form-redux')
                    ); 
                    ?>
                </h3>

                <p>
                    <?php 
                        /* 
                            Translators links labeled: 
                                1: 'Akismet', 
                                2: 'reCAPTCHA', 
                                3: 'comment blacklist' 
                        */ 
                        echo sprintf(
                            esc_html(
                                __(
                                    'Contact Form Redux supports spam-filtering ' . 
                                        'with %1$s. Intelligent %2$s blocks ' . 
                                        'annoying spambots. Plus, using %3$s, ' . 
                                        'you can block messages containing ' . 
                                        'specified keywords or those sent from ' . 
                                        'specified IP addresses.', 
                                    'contact-form-redux'
                                )
                            ), 
                            cfredux_link(
                                __(
                                    'https://cfr.backwoodsbytes.com/advanced-' . 
                                        'techniques/using-akismet/', 
                                    'contact-form-redux'
                                ), 
                                __('Akismet', 'contact-form-redux')
                            ), 
                            cfredux_link(
                                __(
                                    'https://cfr.backwoodsbytes.com/tags/' . 
                                        'recaptcha-tags/', 
                                    'contact-form-redux'
                                ), 
                                __('reCAPTCHA', 'contact-form-redux')
                            ), 
                            cfredux_link(
                                __(
                                    'https://cfr.backwoodsbytes.com/advanced-' . 
                                        'techniques/using-the-comment-blacklist/', 
                                    'contact-form-redux'
                                ), 
                                __('comment blacklist', 'contact-form-redux')
                            )
                        ); 
                    ?>
                </p>
            </div>

        </div>
    </div>
</div>
    <?php
}

add_action(
    'wp_ajax_cfredux-update-welcome-panel', 
    'cfredux_admin_ajax_welcome_panel'
);

function cfredux_admin_ajax_welcome_panel()
{
    check_ajax_referer('cfredux-welcome-panel-nonce', 'welcomepanelnonce');

    $vers = get_user_meta(
        get_current_user_id(),
        'cfredux_hide_welcome_panel_on', true
    );

    if (empty($vers) || ! is_array($vers)) {
        $vers = array();
    }

    if (empty($_POST['visible'])) {
        $vers[] = cfredux_version('only_major=1');
    }

    $vers = array_unique($vers);

    update_user_meta(get_current_user_id(), 'cfredux_hide_welcome_panel_on', $vers);

    wp_die(1);
}
