<?php

add_filter('map_meta_cap', 'cfredux_map_meta_cap', 10, 4);

function cfredux_map_meta_cap($caps, $cap, $user_id, $args)
{
    $meta_caps = array(
        'cfredux_edit_contact_form' => CFREDUX_ADMIN_READ_WRITE_CAPABILITY,
        'cfredux_edit_contact_forms' => CFREDUX_ADMIN_READ_WRITE_CAPABILITY,
        'cfredux_read_contact_forms' => CFREDUX_ADMIN_READ_CAPABILITY,
        'cfredux_delete_contact_form' => CFREDUX_ADMIN_READ_WRITE_CAPABILITY,
        'cfredux_manage_integration' => 'manage_options',
        'cfredux_manage_options' => 'manage_options',
        'cfredux_submit' => 'read',
    );

    $meta_caps = apply_filters('cfredux_map_meta_cap', $meta_caps);

    $caps = array_diff($caps, array_keys($meta_caps));

    if (isset($meta_caps[$cap])) {
        $caps[] = $meta_caps[$cap];
    }

    return $caps;
}
