<?php
/**
 * A base module for [quiz]
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_quiz');

function cfredux_add_form_tag_quiz()
{
    cfredux_add_form_tag(
        'quiz',
        'cfredux_quiz_form_tag_handler',
        array(
        'name-attr' => true,
        'do-not-store' => true,
        'not-for-mail' => true,
        )
    );
}

function cfredux_quiz_form_tag_handler($tag)
{
    if (empty($tag->name)) {
        return '';
    }

    $validation_error = cfredux_get_validation_error($tag->name);

    $class = cfredux_form_controls_class($tag->type);

    if ($validation_error) {
        $class .= ' cfredux-not-valid';
    }

    $atts = array();

    $atts['size'] = $tag->get_size_option('40');
    $atts['maxlength'] = $tag->get_maxlength_option();
    $atts['minlength'] = $tag->get_minlength_option();

    if ($atts['maxlength'] 
        && $atts['minlength'] 
        && $atts['maxlength'] < $atts['minlength']
    ) {
        unset($atts['maxlength'], $atts['minlength']);
    }

    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);
    $atts['autocomplete'] = 'off';
    $atts['aria-required'] = 'true';
    $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

    $pipes = $tag->pipes;

    if ($pipes instanceof CFREDUX_Pipes && ! $pipes->zero()) {
        $pipe = $pipes->random_pipe();
        $question = $pipe->before;
        $answers = explode('|', $pipe->after);
    } else {
        // default quiz
        $question = '1+1=?';
        $answers = array('2');
    }
    
    $hashedanswers = array();
    foreach ($answers as $val) {
        $hashedanswers[] = wp_hash(cfredux_canonicalize($val), 'cfredux_quiz');
    }
    $answer = implode('|', $hashedanswers);
    
    $atts['type'] = 'text';
    $atts['name'] = $tag->name;

    $atts = cfredux_format_atts($atts);

    $html = sprintf(
        '<span class="cfredux-form-control-wrap %1$s"><label><span class="' . 
            'cfredux-quiz-label">%2$s</span> <input %3$s></label><input type' . 
            '="hidden" name="_cfredux_quiz_answer_%4$s" value="%5$s">%6$s</span>',
        sanitize_html_class($tag->name),
        esc_html($question), $atts, $tag->name,
        $answer, $validation_error
    );

    return $html;
}


/* Validation filter */

add_filter('cfredux_validate_quiz', 'cfredux_quiz_validation_filter', 10, 2);

function cfredux_quiz_validation_filter($result, $tag)
{
    $name = $tag->name;

    if (isset($_POST[$name])) {
        $answer = cfredux_canonicalize(sanitize_text_field($_POST[$name]));
    } else {
        $answer = '';
    }
    
    $answer = wp_unslash($answer);

    $answer_hash = wp_hash($answer, 'cfredux_quiz');
    
    if (isset($_POST['_cfredux_quiz_answer_' . $name])) {
        $expected_hashes = explode(
            '|', 
            sanitize_text_field($_POST['_cfredux_quiz_answer_' . $name])
        );
    } else {
        $expected_hashes = array();
    }
    
    $matched_answer = false;
    foreach ($expected_hashes as $expected_hash) {
        if ($answer_hash == $expected_hash) {
            $matched_answer = true;
            continue;
        }
    }
    if ($matched_answer != true) {
        $result->invalidate($tag, cfredux_get_message('quiz_answer_not_correct'));
    }

    return $result;
}


/* Ajax echo filter */

add_filter('cfredux_ajax_onload', 'cfredux_quiz_ajax_refill');
add_filter('cfredux_ajax_json_echo', 'cfredux_quiz_ajax_refill');

function cfredux_quiz_ajax_refill($items)
{
    if (! is_array($items)) {
        return $items;
    }

    $fes = cfredux_scan_form_tags(array('type' => 'quiz'));

    if (empty($fes)) {
        return $items;
    }

    $refill = array();

    foreach ($fes as $fe) {
        $name = $fe['name'];
        $pipes = $fe['pipes'];

        if (empty($name)) {
            continue;
        }
        
        if ($pipes instanceof CFREDUX_Pipes && ! $pipes->zero()) {
            $pipe = $pipes->random_pipe();
            $question = $pipe->before;
            $answers = explode('|', $pipe->after);
        } else {
            // default quiz
            $question = '1+1=?';
            $answers = array('2');
        }
        
        $hashedanswers = array();
        foreach ($answers as $val) {
            $hashedanswers[] = wp_hash(cfredux_canonicalize($val), 'cfredux_quiz');
        }
        $answer = implode('|', $hashedanswers);
        
        $refill[$name] = array($question, $answer);
    }

    if (! empty($refill)) {
        $items['quiz'] = $refill;
    }

    return $items;
}


/* Messages */

add_filter('cfredux_messages', 'cfredux_quiz_messages');

function cfredux_quiz_messages($messages)
{
    $messages = array_merge(
        $messages, array(
        'quiz_answer_not_correct' => array(
            'description' => __(
                "Sender doesn't enter the correct answer to the quiz", 
                'contact-form-redux'
            ),
            'default' => __(
                "The answer to the quiz is incorrect.", 
                'contact-form-redux'
            ),
        ),
        )
    );

    return $messages;
}


/* Tag generator */

add_action('cfredux_admin_init', 'cfredux_add_tag_generator_quiz', 40);

function cfredux_add_tag_generator_quiz()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'quiz', __('quiz', 'contact-form-redux'),
        'cfredux_tag_generator_quiz'
    );
}

function cfredux_tag_generator_quiz($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());
    $type = 'quiz';

    $description = __(
        "Generate a form tag for one or more question-and-answer pairs. For " . 
            "more details, see %s.", 'contact-form-redux'
    );

    $desc_link = cfredux_link(
        __('https://cfr.backwoodsbytes.com/tags/quiz-tags/', 'contact-form-redux'), 
        __('Quiz Tags', 'contact-form-redux')
    );

    ?>
<div class="control-box">
    <p class="cfrtaginfo">
        <?php echo sprintf(esc_html($description), $desc_link); ?>
    </p>
    
    <div class="inputgroup">
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-name'); ?>">
                <?php echo esc_html(__('Name:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="text" 
                name="name" 
                class="tg-name oneline" 
                id="<?php echo esc_attr($args['content'] . '-name'); ?>"
            >
        </div>
    </div>
    <p>
        <?php 
            echo esc_html(__('Questions and Answers:', 'contact-form-redux')); 
            $str = __(
                'One pipe-separated question and answer set per line.&#10;For ' . 
                'example, &quot;The capital of Brazil?|Rio|Rio de ' . 
                'Janeiro&quot; would ask the user for the capital of Brazil, ' . 
                'and would accept either &quot;Rio&quot; or &quot;Rio de ' . 
                'Janeiro&quot; as the correct answer.', 
                'contact-form-redux'
            );
        ?>
        <span 
            class="cfrinfo" 
            title="<?php echo esc_attr($str); ?>"
        >&#8505;</span>
    </p>
    <p class="cfrinfopopup"><?php echo esc_html($str); ?></p>
    <textarea 
        name="values" 
        class="values" 
        id="<?php echo esc_attr($args['content'] . '-values'); ?>"
    ></textarea>
    <br><br>
    <div class="inputgroup">
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-id'); ?>">
                <?php echo esc_html(__('ID Attribute:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="text" 
                name="id" 
                class="idvalue oneline option" 
                id="<?php echo esc_attr($args['content'] . '-id'); ?>"
            >
        </div>
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-class'); ?>">
                <?php echo esc_html(__('Class Attribute:', 'contact-form-redux')); ?>
            </label>
            <input 
                type="text" 
                name="class" 
                class="classvalue oneline option" 
                id="<?php echo esc_attr($args['content'] . '-class'); ?>"
            >
        </div>
    </div>
</div>

<div class="insert-box">
    <input 
        type="text" 
        name="<?php echo $type; ?>" 
        class="tag code" 
        readonly
    >

    <div class="submitbox">
        <input 
            type="button" 
            class="button button-primary insert-tag" 
            value="<?php echo esc_attr(__('Insert Tag', 'contact-form-redux')); ?>"
        >
    </div>
    <br class="clear">
</div>
    <?php
}
