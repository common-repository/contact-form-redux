<?php

function cfredux_current_action()
{
    if (isset($_REQUEST['action']) 
        && -1 != sanitize_text_field($_REQUEST['action'])
    ) {
        return sanitize_text_field($_REQUEST['action']);
    }

    if (isset($_REQUEST['action2']) 
        && -1 != sanitize_text_field($_REQUEST['action2'])
    ) {
        return sanitize_text_field($_REQUEST['action2']);
    }

    return false;
}

function cfredux_admin_has_edit_cap()
{
    return current_user_can('cfredux_edit_contact_forms');
}

function cfredux_add_tag_generator(
    $name, 
    $title, 
    $elm_id, 
    $callback, 
    $options = array()
) {
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    return $tag_generator->add($name, $title, $callback, $options);
}

function cfredux_add_informationdiv($include_postbox)
{
    if ($include_postbox) {
        echo '<div id="postbox-container-1" class="postbox-container">';
    }
    ?>
    <div id="informationdiv" class="postbox">
        <h3>
            <?php 
                echo esc_html(__("Need more information?", 'contact-form-redux')); 
            ?>
        </h3>
        <div class="inside">
            <ul>
                <li>
                    <?php 
                    /* translators: 1: Documentation */
                    echo sprintf(
                        __(
                            '%1$s', 
                            'contact-form-redux'
                        ),
                        cfredux_link(
                            __(
                                'https://cfr.backwoodsbytes.com/', 
                                'contact-form-redux'
                            ),
                            __('Documentation', 'contact-form-redux')
                        )
                    ); 
                    ?>
                </li>
                <li>
                    <?php 
                    echo cfredux_link(
                        __(
                            'https://wordpress.org/support/plugin/contact' . 
                                '-form-redux/', 
                            'contact-form-redux'
                        ),
                        __('Support Forum', 'contact-form-redux')
                    ); 
                    ?>
                </li>
            </ul>
        </div>
    </div><!-- #informationdiv -->
    <?php
    if ($include_postbox) {
        echo '</div>';
    }
}
?>