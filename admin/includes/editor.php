<?php

class CFREDUX_Editor
{

    private $contact_form;
    private $panels = array();

    public function __construct(CFREDUX_ContactForm $contact_form)
    {
        $this->contact_form = $contact_form;
    }

    public function add_panel($id, $title, $callback)
    {
        if (cfredux_is_name($id)) {
            $this->panels[$id] = array(
            'title' => $title,
            'callback' => $callback,
            );
        }
    }

    public function display()
    {
        if (empty($this->panels)) {
            return;
        }

        echo '<div id="contact-form-editor-tabs">';
        
        $tab = 1;
        foreach ($this->panels as $id => $panel) {
            if ($tab === 1) {
                echo sprintf(
                    '<button class="contact-form-editor-tablink cfractivetab" ' . 
                        'id="%1$s-tab">%2$s</button>',
                    esc_attr($id), esc_html($panel['title'])
                );
            } else {
                echo sprintf(
                    '<button class="contact-form-editor-tablink" id="%1$s-tab"' . 
                        '>%2$s</button>',
                    esc_attr($id), esc_html($panel['title'])
                );
            }
            $tab++;
        }

        echo '</div>';
        
        $tab = 1;
        foreach ($this->panels as $id => $panel) {
            if ($tab === 1) {
                echo sprintf(
                    '<div class="contact-form-editor-tabcontent" id="%1$s">',
                    esc_attr($id)
                );
    
                if (is_callable($panel['callback'])) {
                    $this->notice($id, $panel);
                    call_user_func($panel['callback'], $this->contact_form);
                }
    
                echo '</div>';
            } else {
                echo sprintf(
                    '<div class="contact-form-editor-tabcontent" id="%1$s" ' . 
                        'style="display: none">',
                    esc_attr($id)
                );
    
                if (is_callable($panel['callback'])) {
                    $this->notice($id, $panel);
                    call_user_func($panel['callback'], $this->contact_form);
                }
    
                echo '</div>';
            }
            $tab++;
        }
    }

    public function notice($id, $panel)
    {
        echo '<div class="config-error"></div>';
    }
}

function cfredux_editor_panel_form($post)
{
    $desc_link = cfredux_link(
        __('https://cfr.backwoodsbytes.com/forms/#form-tab', 'contact-form-redux'),
        __('Forms: The Form Tab', 'contact-form-redux')
    );
    $description = __(
        "You can edit the form template here. For details, see %s.", 
        'contact-form-redux'
    );
    $description = sprintf(esc_html($description), $desc_link);
    ?>
    <h2><?php echo esc_html(__('Form', 'contact-form-redux')); ?></h2>
    
    <fieldset>
        <legend><?php echo $description; ?></legend>
        
            <?php
            $tag_generator = CFREDUX_TagGenerator::get_instance();
            $tag_generator->print_buttons();
            ?>
        
        <textarea 
            id="cfredux-form" 
            name="cfredux-form" 
            cols="100" 
            rows="24" 
            class="large-text code" 
            data-config-field="form.body"
        ><?php echo esc_textarea($post->prop('form')); ?></textarea>
    </fieldset>
    <?php
}

function cfredux_editor_panel_mail($post)
{
    cfredux_editor_box_mail($post);

    echo '<br class="clear">';

    cfredux_editor_box_mail(
        $post, array(
        'id' => 'cfredux-mail-2',
        'name' => 'mail_2',
        'title' => __('Mail (2)', 'contact-form-redux'),
        'use' => __('Use Mail (2)', 'contact-form-redux'),
        )
    );
}

function cfredux_editor_box_mail($post, $args = '')
{
    $args = wp_parse_args(
        $args, array(
        'id' => 'cfredux-mail',
        'name' => 'mail',
        'title' => __('Mail', 'contact-form-redux'),
        'use' => null,
        )
    );

    $id = esc_attr($args['id']);

    $mail = wp_parse_args(
        $post->prop($args['name']), array(
        'active' => false,
        'recipient' => '',
        'sender' => '',
        'subject' => '',
        'body' => '',
        'additional_headers' => '',
        'attachments' => '',
        'use_html' => false,
        'exclude_blank' => false,
        )
    );

    ?>
<div class="contact-form-editor-box-mail" id="<?php echo $id; ?>">
<h2><?php echo esc_html($args['title']); ?></h2>

    <?php
    if (!empty($args['use'])) {
        ?>
        <p class="description">
            <?php 
                echo esc_html(
                    __(
                        "Mail (2) is an additional mail template often used " . 
                            "as an autoresponder.", 
                        'contact-form-redux'
                    )
                ); 
            ?>
        </p>
        <div class="inputgroup">
            <div class="inputrow">
                <label for="<?php echo $id; ?>[active]">
                    <?php 
                        echo esc_html(
                            __(
                                esc_html($args['use']) . ':', 
                                'contact-form-redux'
                            )
                        ); 
                    ?>
                </label>
                <label class="switch">
                    <input 
                        type="checkbox" 
                        id="<?php echo $id; ?>-active" 
                        name="<?php echo $id; ?>[active]" 
                        class="toggle-form-table" 
                        value="1"
                        <?php echo ($mail['active']) ? ' checked' : ''; ?>
                    >
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
        <?php
    }
    /*
        Make sure you leave the fieldset below, or it will break the javascipt 
        that hides/shows Mail 2.
    */
    ?>
    <fieldset>
        <p>
            <?php
            $desc_link = cfredux_link(
                __(
                    'https://cfr.backwoodsbytes.com/forms/#mail-tab', 
                    'contact-form-redux'
                ),
                __('Forms: The Mail Tab', 'contact-form-redux')
            );
            $description = __(
                "You can edit the mail template here. For details, see %s.", 
                'contact-form-redux'
            );
            $description = sprintf(esc_html($description), $desc_link);
            echo $description;
            echo '</p>';
            echo esc_html(
                __(
                    "The To field accepts a comma-separated list of email " . 
                    "addresses. In the From, Subject, and Message Body fields, " . 
                    "you can use these mail tags:",
                    'contact-form-redux'
                )
            );
            echo '<p>';
            $post->suggest_mail_tags($args['name']);
            ?>
        </p>
        <div class="inputgroup">
            <div class="inputrow">
                <label for="<?php echo $id; ?>-recipient">
                    <?php echo esc_html(__('To:', 'contact-form-redux')); ?>
                </label>
                <input 
                    type="text" 
                    id="<?php echo $id; ?>-recipient" 
                    name="<?php echo $id; ?>[recipient]" 
                    class="large-text code" 
                    size="70" 
                    value="<?php echo esc_attr($mail['recipient']); ?>" 
                    data-config-field="<?php 
                        echo sprintf('%s.recipient', esc_attr($args['name'])); 
                    ?>"
                >
            </div>
            <div class="inputrow">
                <label for="<?php echo $id; ?>-sender">
                    <?php echo esc_html(__('From:', 'contact-form-redux')); ?>
                </label>
                <input 
                    type="text" 
                    id="<?php echo $id; ?>-sender" 
                    name="<?php echo $id; ?>[sender]" 
                    class="large-text code" 
                    size="70" 
                    value="<?php echo esc_attr($mail['sender']); ?>" 
                    data-config-field="<?php 
                        echo sprintf('%s.sender', esc_attr($args['name'])); 
                    ?>"
                >
            </div>
            <div class="inputrow">
                <label for="<?php echo $id; ?>-subject">
                    <?php echo esc_html(__('Subject:', 'contact-form-redux')); ?>
                </label>
                <input 
                    type="text" 
                    id="<?php echo $id; ?>-subject" 
                    name="<?php echo $id; ?>[subject]" 
                    class="large-text code" 
                    size="70" 
                    value="<?php echo esc_attr($mail['subject']); ?>" 
                    data-config-field="<?php 
                        echo sprintf('%s.subject', esc_attr($args['name'])); 
                    ?>"
                >
            </div>
        </div>
        <p>
            <?php echo esc_html(__('Additional Headers:', 'contact-form-redux')); ?>
        </p>
        <textarea 
            id="<?php echo $id; ?>-additional-headers" 
            name="<?php echo $id; ?>[additional_headers]" 
            cols="100" 
            rows="4" 
            class="large-text code" 
            data-config-field="<?php 
                echo sprintf('%s.additional_headers', esc_attr($args['name'])); 
            ?>"
        ><?php echo esc_textarea($mail['additional_headers']); ?></textarea>
        <br><br>
        <p>
            <?php echo esc_html(__('Message Body:', 'contact-form-redux')); ?>
        </p>
        <textarea 
            id="<?php echo $id; ?>-body" 
            name="<?php echo $id; ?>[body]" 
            cols="100" 
            rows="18" 
            class="large-text code" 
            data-config-field="<?php 
                echo sprintf('%s.body', esc_attr($args['name'])); 
            ?>"
        ><?php echo esc_textarea($mail['body']); ?></textarea>
        <br><br>
        <div class="inputgroup">
            <div class="inputrow">
                 <label for="<?php echo $id; ?>[exclude_blank]">
                    <?php 
                        echo esc_html(
                            __(
                                'Exclude Blank Mail Tags:', 
                                'contact-form-redux'
                            )
                        ); 
                    ?>
                </label>
                <label class="switch">
                    <input 
                        type="checkbox" 
                        id="<?php echo $id; ?>-exclude-blank" 
                        name="<?php echo $id; ?>[exclude_blank]" 
                        value="1"
                        <?php 
                            echo (!empty($mail['exclude_blank'])) ? ' checked' : ''; 
                        ?>
                    >
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="inputrow">
                <label for="<?php echo $id; ?>[use_html]">
                    <?php 
                        echo esc_html(
                            __(
                                'Use HTML Content Type:', 
                                'contact-form-redux'
                            )
                        ); 
                    ?>
                </label>
                <label class="switch">
                    <input 
                        type="checkbox" 
                        id="<?php echo $id; ?>-use-html" 
                        name="<?php echo $id; ?>[use_html]" 
                        value="1"
                        <?php echo ($mail['use_html']) ? ' checked' : ''; ?>
                    >
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
        <p>
            <?php echo esc_html(__('File Attachments:', 'contact-form-redux')); ?>
        </p>
        <textarea 
            id="<?php echo $id; ?>-attachments" 
            name="<?php echo $id; ?>[attachments]" 
            cols="100" 
            rows="4" 
            class="large-text code" 
            data-config-field="<?php 
                echo sprintf('%s.attachments', esc_attr($args['name'])); 
            ?>"
        ><?php echo esc_textarea($mail['attachments']); ?></textarea>
    </fieldset>
</div>
<?php
}

function cfredux_editor_panel_messages($post)
{
    $desc_link = cfredux_link(
        __(
            'https://cfr.backwoodsbytes.com/forms/#messages-tab', 
            'contact-form-redux'
        ),
        __('Forms: The Messages Tab', 'contact-form-redux')
    );
    $description = __(
        "You can edit messages used in various situations here. For details, " . 
            "see %s.", 
        'contact-form-redux'
    );
    $description = sprintf(esc_html($description), $desc_link);

    $messages = cfredux_messages();

    if (isset($messages['captcha_not_match'])
        && !cfredux_use_really_simple_captcha()
    ) {
        unset($messages['captcha_not_match']);
    }

    ?>
<h2><?php echo esc_html(__('Messages', 'contact-form-redux')); ?></h2>
<fieldset>
<legend><?php echo $description; ?></legend>
    <?php

    foreach ($messages as $key => $arr) {
        $field_id = sprintf('cfredux-message-%s', strtr($key, '_', '-'));
        $field_name = sprintf('cfredux-messages[%s]', $key);

        ?>
<p class="description">
<label for="<?php echo $field_id; ?>"><?php echo esc_html($arr['description']); ?>
    <br>
    <input 
        type="text" 
        id="<?php echo $field_id; ?>" 
        name="<?php echo $field_name; ?>" 
        class="large-text" 
        size="70" 
        value="<?php echo esc_attr($post->message($key, false)); ?>" 
        data-config-field="<?php echo sprintf('messages.%s', esc_attr($key)); ?>"
    >
</label>
</p>
        <?php
    }
    ?>
</fieldset>
    <?php
}

function cfredux_editor_panel_additional_settings($post)
{
    $desc_link = cfredux_link(
        __(
            'https://cfr.backwoodsbytes.com/forms/#additional-settings-tab', 
            'contact-form-redux'
        ),
        __('Additional Settings', 'contact-form-redux')
    );
    $description = __(
        "You can add customization code snippets here. For details, see %s.", 
        'contact-form-redux'
    );
    $description = sprintf(esc_html($description), $desc_link);

    ?>
<h2><?php echo esc_html(__('Additional Settings', 'contact-form-redux')); ?></h2>
<fieldset>
<legend><?php echo $description; ?></legend>
<textarea 
    id="cfredux-additional-settings" 
    name="cfredux-additional-settings" 
    cols="100" 
    rows="8" 
    class="large-text" 
    data-config-field="additional_settings.body"
><?php echo esc_textarea($post->prop('additional_settings')); ?></textarea>
</fieldset>
    <?php
}
