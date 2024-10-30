<?php

class CFREDUX_TagGenerator
{

    private static $instance;

    private $panels = array();

    private function __construct()
    {
    }

    public static function get_instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function add($id, $title, $callback, $options = array())
    {
        $id = trim($id);

        if ('' === $id || ! cfredux_is_name($id)) {
            return false;
        }

        $this->panels[$id] = array(
        'title' => $title,
        'content' => 'tag-generator-panel-' . $id,
        'options' => $options,
        'callback' => $callback,
        );

        return true;
    }

    public function print_buttons()
    {
        echo '<p id="tag-generator-list">';

        foreach ((array) $this->panels as $panel) {
            /* translators: %s: title of form tag like 'email' or 'checkboxes' */
            echo sprintf(
                '<a href="#" data-tag="%1$s" class="cfrtagbutton button" ' . 
                    'title="%2$s">%3$s</a>',
                esc_attr($panel['content']),
                esc_attr(
                    sprintf(
                        __('Form Tag Generator: %s', 'contact-form-redux'),
                        $panel['title']
                    )
                ),
                esc_html($panel['title'])
            );
        }

        echo '</p>';
    }

    public function print_panels(CFREDUX_ContactForm $contact_form)
    {
        foreach ((array) $this->panels as $id => $panel) {
            $callback = $panel['callback'];

            $options = wp_parse_args($panel['options'], array());
            $options = array_merge(
                $options, array(
                'id' => $id,
                'title' => $panel['title'],
                'content' => $panel['content'],
                )
            );

            if (is_callable($callback)) {
                echo sprintf(
                    '<div class="cfrmodaldiv"><div class="cfrmodalcontainer">' . 
                        '<span class="cfrclose">&times;</span><div ' . 
                        'class="cfrmodalcontent"><div id="%1$s" ' . 
                        'class="tag-gen-div"><h2>%2$s</h2>',
                    esc_attr($options['content']), 
                    esc_html(ucwords($options['title']))
                );
                echo sprintf(
                    '<form action="" class="tag-generator-panel" data-id="%s">',
                    $options['id']
                );

                   call_user_func($callback, $contact_form, $options);

                   echo '</form></div></div></div></div>';
            }
        }
    }

}
