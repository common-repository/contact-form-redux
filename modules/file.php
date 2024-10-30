<?php
/**
 * A base module for [file] and [file*]
 */

/* form_tag handler */

add_action('cfredux_init', 'cfredux_add_form_tag_file');

function cfredux_add_form_tag_file()
{
    cfredux_add_form_tag(
        array('file', 'file*'),
        'cfredux_file_form_tag_handler', array('name-attr' => true)
    );
}

function cfredux_file_form_tag_handler($tag)
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
    $atts['class'] = $tag->get_class_option($class);
    $atts['id'] = $tag->get_id_option();
    $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);

    $atts['accept'] = cfredux_acceptable_filetypes(
        $tag->get_option('filetypes'), 'attr'
    );

    if ($tag->is_required()) {
        $atts['aria-required'] = 'true';
    }

    $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

    $atts['type'] = 'file';
    $atts['name'] = $tag->name;

    $atts = cfredux_format_atts($atts);

    $html = sprintf(
        '<span class="cfredux-form-control-wrap %1$s"><input %2$s>%3$s</span>',
        sanitize_html_class($tag->name), $atts, $validation_error
    );

    return $html;
}


/* Encode type filter */

add_filter('cfredux_form_enctype', 'cfredux_file_form_enctype_filter');

function cfredux_file_form_enctype_filter($enctype)
{
    $multipart = (bool) cfredux_scan_form_tags(
        array('type' => array('file', 'file*'))
    );

    if ($multipart) {
        $enctype = 'multipart/form-data';
    }

    return $enctype;
}


/* Validation + upload handling filter */

add_filter('cfredux_validate_file', 'cfredux_file_validation_filter', 10, 2);
add_filter('cfredux_validate_file*', 'cfredux_file_validation_filter', 10, 2);

function cfredux_file_validation_filter($result, $tag)
{
    $name = $tag->name;
    $id = $tag->get_id_option();

    $file = isset($_FILES[$name]) ? $_FILES[$name] : null;
    
    if ($file['error'] && UPLOAD_ERR_NO_FILE != $file['error']) {
        $result->invalidate($tag, cfredux_get_message('upload_failed_php_error'));
        return $result;
    }
    
    if (empty($file['tmp_name']) && $tag->is_required()) {
        $result->invalidate($tag, cfredux_get_message('invalid_required'));
        return $result;
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return $result;
    }

    /* File type validation */
    $file_type_pattern = cfredux_acceptable_filetypes(
        $tag->get_option('filetypes'), 'regex'
    );

    $file_type_pattern = '/\.(' . $file_type_pattern . ')$/i';

    if (!preg_match($file_type_pattern, sanitize_file_name($file['name']))) {
        $result->invalidate(
            $tag,
            cfredux_get_message('upload_file_type_invalid')
        );
        return $result;
    }

    /* File size validation */

    $allowed_size = 1048576; // default size 1 MB

    if ($file_size_a = $tag->get_option('limit')) {
        $limit_pattern = '/^([1-9][0-9]*)([kKmM]?[bB])?$/';

        foreach ($file_size_a as $file_size) {
            if (preg_match($limit_pattern, $file_size, $matches)) {
                $allowed_size = (int) $matches[1];

                if (! empty($matches[2])) {
                    $kbmb = strtolower($matches[2]);

                    if ('kb' == $kbmb) {
                        $allowed_size *= 1024;
                    } elseif ('mb' == $kbmb) {
                        $allowed_size *= 1024 * 1024;
                    }
                }

                break;
            }
        }
    }

    if ($file['size'] > $allowed_size) {
        $result->invalidate($tag, cfredux_get_message('upload_file_too_large'));
        return $result;
    }

    cfredux_init_uploads(); // Confirm upload dir
    $uploads_dir = cfredux_upload_tmp_dir();
    $uploads_dir = cfredux_maybe_add_random_dir($uploads_dir);

    $filename = $file['name'];
    $filename = cfredux_canonicalize($filename, 'as-is');
    $filename = cfredux_antiscript_file_name($filename);

    $filename = apply_filters(
        'cfredux_upload_file_name', $filename,
        $file['name'], $tag
    );

    $filename = wp_unique_filename($uploads_dir, $filename);
    $new_file = path_join($uploads_dir, $filename);

    if (false === move_uploaded_file($file['tmp_name'], $new_file)) {
        $result->invalidate($tag, cfredux_get_message('upload_failed'));
        return $result;
    }

    // Make sure the uploaded file is only readable for the owner process
    chmod($new_file, 0400);

    if ($submission = CFREDUX_Submission::get_instance()) {
        $submission->add_uploaded_file($name, $new_file);
    }

    return $result;
}


/* Messages */

add_filter('cfredux_messages', 'cfredux_file_messages');

function cfredux_file_messages($messages)
{
    return array_merge(
        $messages, array(
        'upload_failed' => array(
            'description' => __(
                "Uploading a file fails for any reason", 
                'contact-form-redux'
            ),
            'default' => __(
                "There was an unknown error uploading the file.", 
                'contact-form-redux'
            )
        ),

        'upload_file_type_invalid' => array(
            'description' => __(
                "Uploaded file is not allowed for file type", 
                'contact-form-redux'
            ),
            'default' => __(
                "You are not allowed to upload files of this type.", 
                'contact-form-redux'
            )
        ),

        'upload_file_too_large' => array(
            'description' => __("Uploaded file is too large", 'contact-form-redux'),
            'default' => __("The file is too big.", 'contact-form-redux')
        ),

        'upload_failed_php_error' => array(
            'description' => __(
                "Uploading a file fails for PHP error", 
                'contact-form-redux'
            ),
            'default' => __(
                "There was an error uploading the file.", 
                'contact-form-redux'
            )
        )
        )
    );
}


/* Tag generator */

add_action('cfredux_admin_init', 'cfredux_add_tag_generator_file', 50);

function cfredux_add_tag_generator_file()
{
    $tag_generator = CFREDUX_TagGenerator::get_instance();
    $tag_generator->add(
        'file', __('file', 'contact-form-redux'),
        'cfredux_tag_generator_file'
    );
}

function cfredux_tag_generator_file($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());
    $type = 'file';

    $description = __(
        "Generate a form tag for a file upload field. For more details, " . 
            "see %s.", 'contact-form-redux'
    );

    $desc_link = cfredux_link(
        __('https://cfr.backwoodsbytes.com/tags/file-tags/', 'contact-form-redux'), 
        __('File Tags', 'contact-form-redux')
    );

    ?>
<div class="control-box">
    <p class="cfrtaginfo">
        <?php echo sprintf(esc_html($description), $desc_link); ?>
    </p>
    
    <div class="inputgroup">
        <div class="inputrow xmedia">
            <label for="required">
                <?php 
                    echo esc_html(
                        __(
                            'Required:', 
                            'contact-form-redux'
                        )
                    ); 
                ?>
            </label>
            <label class="switch">
                <input type="checkbox" name="required">
                <span class="slider round"></span>
            </label>
        </div>
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
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-limit'); ?>">
                <?php 
                    echo esc_html(
                        __("File Size Limit (bytes):", 'contact-form-redux')
                    ); 
                ?>
            </label>
            <input 
                type="text" 
                name="limit" 
                class="filesize oneline option" 
                id="<?php echo esc_attr($args['content'] . '-limit'); ?>"
            >
        </div>
        <div class="inputrow">
            <label for="<?php echo esc_attr($args['content'] . '-filetypes'); ?>">
                <?php 
                    echo esc_html(
                        __('Acceptable File Types:', 'contact-form-redux')
                    ); 
                ?>
            </label>
            <input 
                type="text" 
                name="filetypes" 
                class="filetype oneline option" 
                id="<?php echo esc_attr($args['content'] . '-filetypes'); ?>"
            >
        </div>
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

    <p class="description mail-tag">
        <label for="<?php echo esc_attr($args['content'] . '-mailtag'); ?>">
            <?php 
                echo sprintf(
                    esc_html(
                        __(
                            "To attach the file uploaded through this field " . 
                                "to mail, you need to insert the corresponding " . 
                                "mail tag (%s) into the File Attachments field " . 
                                "on the Mail tab.", 
                            'contact-form-redux'
                        )
                    ), 
                    '<strong><span class="mail-tag"></span></strong>'
                ); 
            ?>
            <input 
                type="text" 
                class="mail-tag code hidden" 
                readonly 
                id="<?php echo esc_attr($args['content'] . '-mailtag'); ?>"
            >
        </label>
    </p>
</div>
    <?php
}


/* Warning message */

add_action('cfredux_admin_warnings', 'cfredux_file_display_warning_message');

function cfredux_file_display_warning_message()
{
    if (! $contact_form = cfredux_get_current_contact_form()) {
        return;
    }

    $has_tags = (bool) $contact_form->scan_form_tags(
        array('type' => array('file', 'file*'))
    );

    if (! $has_tags) {
        return;
    }

    $uploads_dir = cfredux_upload_tmp_dir();
    cfredux_init_uploads();

    if (! is_dir($uploads_dir) || ! wp_is_writable($uploads_dir)) {
        $message = sprintf(
            __(
                'This contact form contains file uploading fields, but the ' . 
                    'temporary folder for the files (%s) does not exist or ' . 
                    'is not writable. You can create the folder or change its ' . 
                    'permission manually.', 
                'contact-form-redux'
            ), 
            $uploads_dir
        );

        echo sprintf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html($message)
        );
    }
}


/* File uploading functions */

function cfredux_acceptable_filetypes($types = 'default', $format = 'regex')
{
    if ('default' === $types || empty($types)) {
        $types = array(
        'jpg',
        'jpeg',
        'png',
        'gif',
        'pdf',
        'doc',
        'docx',
        'ppt',
        'pptx',
        'odt',
        'avi',
        'ogg',
        'm4a',
        'mov',
        'mp3',
        'mp4',
        'mpg',
        'wav',
        'webp',
        'wmv',
        );
    } else {
        $types_tmp = (array) $types;
        $types = array();

        foreach ($types_tmp as $val) {
            if (is_string($val)) {
                $val = preg_split('/[\s|,]+/', $val);
            }

            $types = array_merge($types, (array) $val);
        }
    }

    $types = array_unique(array_filter($types));

    $output = '';

    foreach ($types as $type) {
        $type = trim($type, ' ,.|');
        $type = str_replace(
            array('.', '+', '*', '?'),
            array('\.', '\+', '\*', '\?'),
            $type
        );

        if ('' === $type) {
            continue;
        }

        if ('attr' === $format || 'attribute' === $format) {
            $output .= sprintf('.%s', $type);
            $output .= ',';
        } else {
            $output .= $type;
            $output .= '|';
        }
    }

    return trim($output, ' ,|');
}

function cfredux_init_uploads()
{
    $dir = cfredux_upload_tmp_dir();
    wp_mkdir_p($dir);

    $htaccess_file = path_join($dir, '.htaccess');

    if (file_exists($htaccess_file)) {
        return;
    }

    if ($handle = fopen($htaccess_file, 'w')) {
        fwrite($handle, "Deny from all\n");
        fclose($handle);
    }
}

function cfredux_maybe_add_random_dir($dir)
{
    do {
        $rand_max = mt_getrandmax();
        $rand = zeroise(mt_rand(0, $rand_max), strlen($rand_max));
        $dir_new = path_join($dir, $rand);
    } while (file_exists($dir_new));

    if (wp_mkdir_p($dir_new)) {
        return $dir_new;
    }

    return $dir;
}

function cfredux_upload_tmp_dir()
{
    if (defined('CFREDUX_UPLOADS_TMP_DIR')) {
        return CFREDUX_UPLOADS_TMP_DIR;
    } else {
        return path_join(cfredux_upload_dir('dir'), 'cfredux_uploads');
    }
}

add_action('template_redirect', 'cfredux_cleanup_upload_files', 20, 0);

function cfredux_cleanup_upload_files($seconds = 60, $max = 100)
{
    if (is_admin() || 'GET' != $_SERVER['REQUEST_METHOD']
        || is_robots() || is_feed() || is_trackback()
    ) {
        return;
    }

    $dir = trailingslashit(cfredux_upload_tmp_dir());

    if (! is_dir($dir) || ! is_readable($dir) || ! wp_is_writable($dir)) {
        return;
    }

    $seconds = absint($seconds);
    $max = absint($max);
    $count = 0;

    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if ('.' == $file || '..' == $file || '.htaccess' == $file) {
                continue;
            }

            $mtime = filemtime(path_join($dir, $file));
            
            // less than $seconds old
            if ($mtime && time() < $mtime + $seconds) {
                continue;
            }

            cfredux_rmdir_p(path_join($dir, $file));
            $count += 1;

            if ($max <= $count) {
                break;
            }
        }

        closedir($handle);
    }
}
