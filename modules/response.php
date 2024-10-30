<?php
/**
 * A base module for [response]
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_response');

function cfredux_add_form_tag_response()
{
    cfredux_add_form_tag(
        'response', 'cfredux_response_form_tag_handler',
        array('display-block' => true)
    );
}

function cfredux_response_form_tag_handler($tag)
{
    if ($contact_form = cfredux_get_current_contact_form()) {
        return $contact_form->form_response_output();
    }
}
