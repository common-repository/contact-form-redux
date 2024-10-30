<?php

// don't load directly
if (! defined('ABSPATH')) {
    die('-1');
}

function cfredux_admin_save_button($post_id) 
{
    static $button = '';

    if (! empty($button)) {
        echo $button;
        return;
    }

    $nonce = wp_create_nonce('cfredux-save-contact-form_' . $post_id);
    
    $button = sprintf(
        '<input type="submit" class="button-primary" name="cfredux-save" ' . 
            'id="cfredux-save-cf" value="%1$s" data-nonce="' . $nonce . '">', 
        esc_attr(__('Save', 'contact-form-redux'))
    );
            

    echo $button;
}

?>
<div class="wrap">

<h1 class="wp-heading-inline">
    <?php
    if ($post->initial()) {
        echo esc_html(__('Add New Contact Form', 'contact-form-redux'));
    } else {
        echo esc_html(__('Edit Contact Form', 'contact-form-redux'));
    }
    ?>
</h1>

<?php
if (! $post->initial() && current_user_can('cfredux_edit_contact_forms')) {
    echo sprintf(
        '<a href="%1$s" class="add-new-h2">%2$s</a>',
        esc_url(menu_page_url('cfredux-new', false)),
        esc_html(__('Add New', 'contact-form-redux'))
    );
}
?>

<hr class="wp-header-end">

<?php do_action('cfredux_admin_warnings'); ?>
<?php do_action('cfredux_admin_notices'); ?>

<?php
if ($post) {

    if (current_user_can('cfredux_edit_contact_form', $post_id)) {
        $disabled = '';
    } else {
        $disabled = ' disabled="disabled"';
    }
    ?>

    <form 
        method="post" 
        action="<?php 
            echo esc_url(
                add_query_arg(
                    array('post' => intval($post_id)), menu_page_url('cfredux', false)
                )
            ); 
        ?>" 
        id="cfredux-admin-form-element"
        <?php do_action('cfredux_post_edit_form_tag'); ?>
    >
    <?php
    if (current_user_can('cfredux_edit_contact_form', $post_id)) {
        wp_nonce_field('cfredux-save-contact-form_' . $post_id);
    }
    ?>
    <input 
        type="hidden" 
        id="post_ID" 
        name="post_ID" 
        value="<?php echo intval($post_id); ?>"
    >
    <input 
        type="hidden" 
        id="cfredux-locale" 
        name="cfredux-locale" 
        value="<?php echo esc_attr($post->locale()); ?>"
    >
    <input 
        type="hidden" 
        id="hiddenaction" 
        name="action" 
        value="save"
    >
    <input 
        type="hidden" 
        id="active-tab" 
        name="active-tab" 
        value="<?php 
            echo isset($_GET['active-tab']) 
                ? filter_input(INPUT_GET, 'active-tab', FILTER_SANITIZE_NUMBER_INT) 
                : '0'; 
        ?>"
    >
    
    <div id="poststuff">
    <div id="post-body" class="metabox-holder columns-2">
    <div id="post-body-content">
    <div id="titlediv">
    <div id="titlewrap">
        <label class="screen-reader-text" id="title-prompt-text" for="title">
            <?php echo esc_html(__('Enter title here', 'contact-form-redux')); ?>
        </label>
    <?php
        $posttitle_atts = array(
            'type' => 'text',
            'name' => 'post_title',
            'size' => 30,
            'value' => $post->initial() ? '' : esc_attr($post->title()),
            'id' => 'title',
            'spellcheck' => 'true',
            'autocomplete' => 'off',
            'disabled' => current_user_can('cfredux_edit_contact_form', $post_id) 
                ? '' : 'disabled',
        );
    
        echo sprintf('<input %s>', cfredux_format_atts($posttitle_atts));
        ?>
    </div><!-- #titlewrap -->
    
    <div class="inside">
    <?php
    if (! $post->initial()) {
        ?>
        <p class="description">
            <label for="cfredux-shortcode">
                <?php 
                    echo esc_html(
                        __(
                            "Copy this shortcode and paste it into your post, " . 
                                "page, or text widget content:", 
                            'contact-form-redux'
                        )
                    ); 
                ?>
            </label>
            <span class="shortcode wp-ui-highlight">
                <input 
                    type="text" 
                    id="cfredux-shortcode" 
                    readonly 
                    class="large-text code" 
                    value="<?php echo esc_attr($post->shortcode()); ?>"
                >
            </span>
        </p>
        <?php
    }
    ?>
    </div>
    </div><!-- #titlediv -->
    </div><!-- #post-body-content -->
    
    <div id="postbox-container-1" class="postbox-container">
    <?php 
    if (current_user_can('cfredux_edit_contact_form', $post_id)) { 
        ?>
        <div id="submitdiv" class="postbox">
        <h3><?php echo esc_html(__('Status', 'contact-form-redux')); ?></h3>
        <div class="inside">
        <div class="submitbox" id="submitpost">
        
        <div id="minor-publishing-actions">
        
        <div class="hidden">
            <input 
                type="submit" 
                class="button-primary" 
                name="cfredux-save" 
                value="<?php echo esc_attr(__('Save', 'contact-form-redux')); ?>"
            >
        </div>
        
            <?php
            if (! $post->initial()) {
                $copy_nonce = wp_create_nonce(
                    'cfredux-copy-contact-form_' . $post_id
                );
                ?>
                <input
                    id="cfredux-copy-cf" 
                    type="submit" 
                    name="cfredux-copy" 
                    class="copy button" 
                    value="<?php 
                        echo esc_attr(__('Duplicate', 'contact-form-redux'));
                    ?>" 
                    data-nonce="<?php echo $copy_nonce; ?>"
                >
                <?php
            }
            ?>
        </div><!-- #minor-publishing-actions -->
        
        <div id="misc-publishing-actions">
            <?php do_action('cfredux_admin_misc_pub_section', $post_id); ?>
        </div><!-- #misc-publishing-actions -->
        
        <div id="major-publishing-actions">
        
            <?php
            if (! $post->initial()) {
                $delete_nonce = wp_create_nonce(
                    'cfredux-delete-contact-form_' . $post_id
                );
                $confirm = esc_js(
                    __(
                        "You are about to delete this contact form.&#10;" . 
                            "Click OK to delete the contact form, or Cancel " . 
                            "to cancel the deletion.", 
                        'contact-form-redux'
                    )
                )
                ?>
                <div id="delete-action">
                    <input 
                        id="cfredux-delete-cf" 
                        type="submit" 
                        name="cfredux-delete" 
                        class="delete submitdelete" 
                        value="<?php 
                            echo esc_attr(__('Delete', 'contact-form-redux')); 
                        ?>" 
                        data-nonce="<?php echo $delete_nonce; ?>" 
                        data-confirm="<?php echo $confirm; ?>"
                    >
                </div><!-- #delete-action -->
                <?php 
            }
            ?>
        
        <div id="publishing-action">
            <span class="spinner"></span>
            <?php cfredux_admin_save_button($post_id); ?>
        </div>
        <div class="clear"></div>
        </div><!-- #major-publishing-actions -->
        </div><!-- #submitpost -->
        </div>
        </div><!-- #submitdiv -->
        <?php 
    } 
    
    cfredux_add_informationdiv(false); 
    ?>
    
    </div><!-- #postbox-container-1 -->
    
    <div id="postbox-container-2" class="postbox-container">
    <div id="contact-form-editor">
    <div class="keyboard-interaction">
        <?php
        /* 
            translators: 
                1: ◀ ▶ dashicon, 
                2: screen reader text for the dashicon 
        */
        echo sprintf(
            esc_html(__('%1$s %2$s keys switch panels', 'contact-form-redux')),
            '<span class="dashicons dashicons-leftright" aria-hidden="true"></span>',
            sprintf(
                '<span class="screen-reader-text">%s</span>',
                esc_html(__('(left and right arrow)', 'contact-form-redux'))
            )
        );
        ?>
    </div>
    
    <?php
    
        $editor = new CFREDUX_Editor($post);
        $panels = array();
    
    if (current_user_can('cfredux_edit_contact_form', $post_id)) {
        $panels = array(
            'form-panel' => array(
                'title' => __('Form', 'contact-form-redux'),
                'callback' => 'cfredux_editor_panel_form',
            ),
            'mail-panel' => array(
                'title' => __('Mail', 'contact-form-redux'),
                'callback' => 'cfredux_editor_panel_mail',
            ),
            'messages-panel' => array(
                'title' => __('Messages', 'contact-form-redux'),
                'callback' => 'cfredux_editor_panel_messages',
            ),
        );
        
        $additional_settings = $post->prop('additional_settings');
        if (!empty($additional_settings)) {
            $additional_settings = explode("\n", trim($additional_settings));
            $additional_settings = array_filter($additional_settings);
            $additional_settings = count($additional_settings);
        }
    
        $panels['additional-settings-panel'] = array(
            'title' => $additional_settings
                /* translators: %d: number of additional settings */
                ? sprintf(
                    __('Additional Settings (%d)', 'contact-form-redux'),
                    $additional_settings
                )
                : __('Additional Settings', 'contact-form-redux'),
            'callback' => 'cfredux_editor_panel_additional_settings',
        );
    }
    
        $panels = apply_filters('cfredux_editor_panels', $panels);
    
    foreach ($panels as $id => $panel) {
        $editor->add_panel($id, $panel['title'], $panel['callback']);
    }
    
        $editor->display();
    ?>
    </div><!-- #contact-form-editor -->
    
        <?php 
        if (current_user_can('cfredux_edit_contact_form', $post_id)) { 
            ?>
            <p class="submit"><?php cfredux_admin_save_button($post_id); ?></p>
            <?php 
        } 
        ?>
    
    </div><!-- #postbox-container-2 -->
    
    </div><!-- #post-body -->
    <br class="clear">
    </div><!-- #poststuff -->
    </form>

    <?php 
} 
?>

</div><!-- .wrap -->

<?php

    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->print_panels($post);

    do_action('cfredux_admin_footer', $post);
