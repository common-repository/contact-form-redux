<?php

class CFREDUX_Help_Tabs
{

    private $screen;

    public function __construct(WP_Screen $screen)
    {
        $this->screen = $screen;
    }

    public function set_help_tabs($type)
    {
        switch ($type) {
        case 'list':
            $this->screen->add_help_tab(
                array(
                'id' => 'list_overview',
                'title' => __('Overview', 'contact-form-redux'),
                'content' => $this->content('list_overview'))
            );

            $this->screen->add_help_tab(
                array(
                'id' => 'list_available_actions',
                'title' => __('Available Actions', 'contact-form-redux'),
                'content' => $this->content('list_available_actions'))
            );

            $this->sidebar();

            return;
        case 'edit':
            $this->screen->add_help_tab(
                array(
                'id' => 'edit_overview',
                'title' => __('Overview', 'contact-form-redux'),
                'content' => $this->content('edit_overview'))
            );

            $this->screen->add_help_tab(
                array(
                'id' => 'edit_form_tags',
                'title' => __('Form Tags', 'contact-form-redux'),
                'content' => $this->content('edit_form_tags'))
            );

            $this->screen->add_help_tab(
                array(
                'id' => 'edit_mail_tags',
                'title' => __('Mail Tags', 'contact-form-redux'),
                'content' => $this->content('edit_mail_tags'))
            );

            $this->sidebar();

            return;
        case 'integration':
            $this->screen->add_help_tab(
                array(
                'id' => 'integration_overview',
                'title' => __('Overview', 'contact-form-redux'),
                'content' => $this->content('integration_overview'))
            );

            $this->sidebar();

            return;
        case 'options':
            $this->screen->add_help_tab(
                array(
                'id'=>'options_overview', 
                'title'=>__('Overview', 'contact-form-redux'), 
                'content'=>$this->content('options_overview'))
            );
            
            return;
        }
    }

    private function content($name)
    {
        $content = array();

        $content['list_overview'] = '<p>' . 
        __(
            "On this page, you can manage multiple contact forms created " . 
                "with Contact Form Redux. Each contact form has a unique ID " . 
                "and Contact Form Redux shortcode ([contact-form-redux ...])." . 
                " To insert a contact form into a post or a text widget, " . 
                "paste the shortcode into the target using a Shortcode Block " . 
                "from the Widget Section of the WordPress editor for the " . 
                "former, or a Text widget for the latter.", 
            'contact-form-redux'
        ) . '</p>';

        $content['list_available_actions'] = '<p>' . 
        __(
            "Hovering over a row in the contact forms list will display " . 
                "action links that allow you to manage your contact form. " . 
                "You can perform the following actions:", 
            'contact-form-redux'
        ) . '</p>';
        $content['list_available_actions'] .= '<p>' . 
        __(
            "<strong>Edit</strong> - Opens the editing screen for the " . 
                "selected contact form. You can also reach that screen by " . 
                "clicking on the contact form title.", 
            'contact-form-redux'
        ) . '</p>';
        $content['list_available_actions'] .= '<p>' . 
        __(
            "<strong>Duplicate</strong> - Clones that contact form. A " . 
                "cloned contact form inherits all content from the " . 
                "original, but has a different ID.", 
            'contact-form-redux'
        ) . '</p>';

        $content['edit_overview'] = '<p>' . 
        __(
            "On this page, you can edit (or add, if you&#8217;re on the " . 
                "Add New Contact Form page) a contact form. A contact form " . 
                "is comprised of the following components:", 
            'contact-form-redux'
        ) . '</p>';
        $content['edit_overview'] .= '<p>' . 
        __(
            "<strong>Title</strong> is the title of a contact form. " . 
                "This title is only used for labeling a contact form, " . 
                "and can be edited.", 
            'contact-form-redux'
        ) . '</p>';
        $content['edit_overview'] .= '<p>' . 
        __(
            "<strong>Form Tab</strong> contains the  content of contact " . 
                "form displayed on the front end of the website. You can " . 
                "use HTML as well as Contact Form Redux&#8217;s form " . 
                "tags here.", 
            'contact-form-redux'
        ) . '</p>';
        $content['edit_overview'] .= '<p>' . 
        __(
            "<strong>Mail Tab</strong> allows you to manage a mail " . 
                "template (headers and message body) that the contact " . 
                "form will send when users submit it. You can use " . 
                "Contact Form Redux&#8217;s mail tags here.", 
            'contact-form-redux'
        ) . '</p>';
        $content['edit_overview'] .= '<p>' . 
        __(
            "<strong>Mail (2)</strong> is an additional mail template " . 
                "that works similar to Mail. Mail (2) is different in " . 
                "that it is sent only when Mail has been sent successfully.", 
            'contact-form-redux'
        ) . '</p>';
        $content['edit_overview'] .= '<p>' . 
        __(
            "<strong>Messages Tab</strong> allows editing of the " . 
                "messages used for the selected contact form. The " . 
                "messages are relatively short, such as the validation " . 
                "error message you see when you leave a required field blank.", 
            'contact-form-redux'
        ) . '</p>';
        $content['edit_overview'] .= '<p>' . 
        __(
            "<strong>Additional Settings</strong> provides a place to " . 
                "customize the behavior of the contact form by adding " . 
                "code snippets.", 
            'contact-form-redux'
        ) . '</p>';

        $content['edit_form_tags'] = '<p>' . 
        __(
            "A form tag is a short code enclosed in square brackets " . 
                "used in form content. A form tag generally represents " . 
                "an input field, and its components can be separated " . 
                "into four parts: type, name, options, and values. " . 
                "Contact Form Redux supports several types of form tags " . 
                "including text fields, number fields, date fields, " . 
                "checkboxes, radio buttons, menus, file-uploading " . 
                "fields, Google&#8217;s reCAPTCHA v2, and quiz fields.", 
            'contact-form-redux'
        ) . '</p>';
        $content['edit_form_tags'] .= '<p>' . 
        __(
            "While form tags have a comparatively complex syntax, you " . 
                "don&#8217;t need to know the syntax to add form tags; " . 
                "you can use the corresponding toolbar buttons to " . 
                "generate the tags; e.g., the <strong>text</strong> " . 
                "button for a text field, the <strong>file</strong> " . 
                "button for a file-upload field, etc.", 
            'contact-form-redux'
        ) . '</p>';

        $content['edit_mail_tags'] = '<p>' . 
        __(
            "A mail tag is a short code enclosed in square brackets " . 
                "that can be used in most Mail and Mail (2) fields. " . 
                "A mail tag represents a user input value through an " . 
                "input field of a corresponding form tag.", 
            'contact-form-redux'
        ) . '</p>';
        $content['edit_mail_tags'] .= '<p>' . 
        __(
            "There are also special mail tags that have specific " . 
                "names, but don&#8217;t have corresponding form tags. " . 
                "They are used to represent meta information of form " . 
                "submissions like the submitter&#8217;s IP address or " . 
                "the URL of the page.", 
            'contact-form-redux'
        ) . '</p>';

        $content['integration_overview'] = '<p>' . 
        __(
            "On this page, you can manage services that are available " . 
                "through Contact Form Redux.", 
            'contact-form-redux'
        ) . '</p>';
        $content['integration_overview'] .= '<p>' . 
        __(
            "You may need to first sign up for an account with the service " . 
                "that you plan to use. When you do so, you need to authorize " . 
                "Contact Form Redux to access the service with your account.", 
            'contact-form-redux'
        ) . '</p>';
        $content['integration_overview'] .= '<p>' . 
        __(
            "Any information you provide will not be shared with service " . 
                "providers without your authorization.", 
            'contact-form-redux'
        ) . '</p>';
        
        $content['options_overview'] = '<p>' . 
        __(
            'On this page, you can set options that affect the functioning " . 
                "of Contact Form Redux globally. Changes you make here " . 
                "affect all forms.', 
            'contact-form-redux'
        ) . '</p>';
        $content['options_overview'] .= '<p>' . 
        __(
            '<strong>Only load scripts and styles on pages:</strong> This ' . 
                'option allows you to specify the pages on which you wish ' . 
                'to allow the javascript and CSS files for Contact Form ' . 
                'Redux to be loaded on the front-end of your site. If you ' . 
                'enter a comma-separated string of post IDs, the javascript ' . 
                'and CSS files for Contact Form Redux will only be loaded ' . 
                'on those pages.', 
            'contact-form-redux'
        ) . '</p>';
        $content['options_overview'] .= '<p>' . 
        __(
            'You can obtain the post IDs from the Posts or Pages All Posts ' . 
                'lists by hovering your mouse over the post or page title ' . 
                'and finding the number following the &#34;post=&#34; in ' . 
                'the status bar of your browser, or from the post or page ' . 
                'URL if you don&#8217;t use pretty permalinks.', 
            'contact-form-redux'
        ) . '</p>';
        $content['options_overview'] .= '<p>' . 
        __(
            'Leave this option blank to load the javascript and CSS on all ' . 
                'pages of the front end of your site.', 
            'contact-form-redux'
        ) . '</p>';
        $content['options_overview'] .= '<p>' . 
        __(
            'If you put a Contact Form Redux shortcode in a widget, be sure ' . 
                'to include the post ID of all pages on which the contact form ' . 
                'is to appear; otherwise, the contact form in the widget will ' . 
                'not function correctly!', 
            'contact-form-redux'
        ) . '</p>';
        $content['options_overview'] .= '<p>' . 
        __(
            '<strong>Use minified versions of javascript and CSS:</strong> ' . 
                'This option will load minified versions of javascript and CSS ' . 
                'files for Contact Form Redux both in the admin and on the ' . 
                'front-end of your site, reducing the file size of the web ' . 
                'pages on which the files are loaded, and improving your page ' . 
                'load times.', 
            'contact-form-redux'
        ) . '</p>';
        $content['options_overview'] .= '<p>' . 
        __(
            '<strong>Allow shortcodes in the Custom HTML widget:</strong> This ' . 
                'option makes Contact Form Redux shortcodes work in the ' . 
                'Custom HTML widget as well as the Text widget. Normally, you ' . 
                'should use the Text widget to display Contact Form Redux ' . 
                'contact forms; however, there may occasionally be a need for ' . 
                'using the Custom HTML widget. This option allows you to do so.', 
            'contact-form-redux'
        ) . '</p>';
        $content['options_overview'] .= '<h2>' . 
        __(
            'Anti-Spam Measures', 
            'contact-form-redux'
        ) . '</h2>';
        $content['options_overview'] .= '<p>' . 
        __(
            '<strong>Javascript Submission Only:</strong> Selecting ' . 
                '&quot;yes&quot; allows submission of contact forms only ' . 
                'when javascript is active. This will elminate bots from ' . 
                'submitting the contact form if they do not support javascript, ' . 
                'but can also block users who have disabled javascript from ' . 
                'submitting the form.', 
            'contact-form-redux'
        ) . '</p>';
        $content['options_overview'] .= '<p>' . 
        __(
            '<strong>Use Spamhaus.org RBLs:</strong> Select &quot:yes&quot; to ' . 
                'check the IP address of the user submitting a contact form ' . 
                'against Spamhaus.org&#39;s SBL and XBL blacklists.', 
            'contact-form-redux'
        ) . '</p>';
        $content['options_overview'] .= '<p>' . 
        __(
            '<strong>Tag-Only with Positive Spamhaus Result:</strong> If ' . 
                'Spamhaus RBLs are enabled, and a user&#39;s IP address is ' . 
                'found in the blacklists, selecting &quot;yes&quot; will ' . 
                'allow the contact form submission but will add a &quot;FAILED ' . 
                'SPAMHAUS CHECK&quot; tag to the subject of the email.', 
            'contact-form-redux'
        ) . '</p>';

        if (! empty($content[$name])) {
            return $content[$name];
        }
    }

    public function sidebar()
    {
        $content = '<p><strong>' . __('More Information:', 'contact-form-redux') . 
            '</strong></p>';
        $content .= '<p>' . cfredux_link(
            __('https://cfr.backwoodsbytes.com/', 'contact-form-redux'), 
            __('Documentation', 'contact-form-redux')
        ) . '</p>';
        $content .= '<p>' . cfredux_link(
            __(
                'https://wordpress.org/support/plugin/contact-form-redux/', 
                'contact-form-redux'
            ), 
            __('Support Forum', 'contact-form-redux')
        ) . '</p>';

        $this->screen->set_help_sidebar($content);
    }
}
