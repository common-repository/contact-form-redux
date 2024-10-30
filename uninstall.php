<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

function cfredux_delete_plugin()
{
    global $wpdb;

    delete_option('cfredux');

    $posts = get_posts(
        array(
            'numberposts' => -1,
            'post_type' => 'cfredux_contact_form',
            'post_status' => 'any',
        )
    );

    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
}

cfredux_delete_plugin();
