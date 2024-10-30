(function ($) {

    'use strict';

    if (typeof cfredux === 'undefined' || cfredux === null) {
        return;
    }

    cfredux.taggen = {};

    $(
        function () {
            $('form.tag-generator-panel').each(
                function () {
                    cfredux.taggen.update($(this));
                }
            );
        }
    );

    $('form.tag-generator-panel').submit(
        function () {
            return false;
        }
    );

    $('form.tag-generator-panel .control-box input, form.tag-generator-panel .control-box select, form.tag-generator-panel .control-box textarea').change(
        function () {
            var $form = $(this).closest('form.tag-generator-panel');
            cfredux.taggen.normalize($(this));
            cfredux.taggen.update($form);
        }
    );

    $('input.insert-tag').click(
        function () {
            var $form = $(this).closest('form.tag-generator-panel'), 
            tag = $form.find('input.tag').val();
            cfredux.taggen.insert(tag);
            $(this).closest('.cfrmodaldiv').css('display', 'none');
            return false;
        }
    );
    
    /*
       Select the tag on the modal dialogs when the user clicks the tag.
       You may want to add a copy function to this event later...
    */
    $('.insert-box input[type="text"].tag').click(
        function () {
            $(this).select();
        }
    );

    cfredux.taggen.update = function ($form) {
        var id = $form.attr('data-id');
        var name = '';
        var name_fields = $form.find('input[name="name"]');

        if (name_fields.length) {
            name = name_fields.val();

            if ('' === name) {
                name = id + '-' + Math.floor(Math.random() * 1000);
                name_fields.val(name);
            }
        }

        if ($.isFunction(cfredux.taggen.update[id])) {
            return cfredux.taggen.update[id].call(this, $form);
        }

        $form.find('input.tag').each(
            function () {
                var tag_type = $(this).attr('name');

                /* This is for the number to set range or slider tag type. */
                if ($form.find('select[name="tagtype"]').length) {
                    tag_type = $form.find('select[name="tagtype"]').val();
                }

                if ($form.find('input[name="required"]').prop('checked') === true) {
                    tag_type += '*';
                }

                var components = cfredux.taggen.compose(tag_type, $form);
                $(this).val(components);
            }
        );

        $form.find('span.mail-tag').text('[' + name + ']');

        $form.find('input.mail-tag').each(
            function () {
                $(this).val('[' + name + ']');
            }
        );
    };

    cfredux.taggen.update.captcha = function ($form) {
        var captchac = cfredux.taggen.compose('captchac', $form);
        var captchar = cfredux.taggen.compose('captchar', $form);

        $form.find('input.tag').val(captchac + ' ' + captchar);
    };

    cfredux.taggen.compose = function (tagType, $form) {
        var name = $form.find('input[name="name"]').val(), 
            scope = $form.find('.scope.' + tagType);

        if (!scope.length) {
            scope = $form;
        }

        var options = [];

        scope.find('input.option').not('[type="checkbox"], [type="radio"]').each(
            function (i) {
                var val = $(this).val();

                if (!val) {
                    return;
                }

                if ($(this).hasClass('filetype')) {
                    val = val.split(/[,|\s]+/).join('|');
                }

                if ($(this).hasClass('color')) {
                      val = '#' + val;
                }

                if ('class' == $(this).attr('name')) {
                    $.each(
                        val.split(' '), function (i, n) {
                            options.push('class:' + n);
                        }
                    );
                } else {
                    options.push($(this).attr('name') + ':' + val);
                }
            }
        );
        
        /*
            Handle the select for reCAPTCHA tags.
            
            If you add additional select elements in the future, you can modify 
            this code block to handle them.
        */
        scope.find('select.option').each(
            function (i) {
                var selectname = $(this).attr('name'), 
                    selectval = $(this).val();
                if (selectval == 'compact' || selectval == 'dark') {
                    options.push(selectname + ':' + selectval);
                }
            }
        );
        
        scope.find('input[type="checkbox"].option').each(
            function (i) {
                if ($(this).prop('checked') === true) {
                    options.push($(this).attr('name'));
                }
            }
        );

        scope.find('input[type="radio"].option').each(
            function (i) {
                if ($(this).prop('checked') === true && !$(this).hasClass('default')) {
                    options.push($(this).attr('name') + ':' + $(this).val());
                }
            }
        );

        if ('radio' == tagType) {
            options.push('default:1');
        }

        options = (options.length > 0) ? options.join(' ') : '';

        var value = '';
        
        /* 
            This is for the Default value (text) and the textareas used for 
            checkboxes, radios, and selects. 
        */
        var e = scope.find('input[name="values"], textarea[name="values"]').val();
        
        if (e) {
            $.each(
                e.split("\n"),
                function (i, n) {
                    value += ' "' + n.replace(/["]/g, '&quot;') + '"';
                }
            );
        }
        
        var components = [];

        $.each(
            [tagType, name, options, value], function (i, v) {
                v = $.trim(v);

                if ('' != v) {
                    components.push(v);
                }
            }
        );

        components = $.trim(components.join(' '));
        components = '[' + components + ']';
        
        /* -- This is for the Condition of the Acceptance tag. -- */
        var content = scope.find('input[name="content"]').val();
        content = $.trim(content);

        if (content) {
            components += ' ' + content + ' [/' + tagType + ']';
        }

        return components;
    };

    cfredux.taggen.normalize = function ($input) {
        var val = $input.val();

        if ($input.is('input[name="name"]')) {
            val = val.replace(/[^0-9a-zA-Z:._-]/g, '').replace(/^[^a-zA-Z]+/, '');
        }

        if ($input.is('.numeric')) {
            val = val.replace(/[^0-9.-]/g, '');
        }

        if ($input.is('.idvalue')) {
            val = val.replace(/[^-0-9a-zA-Z_]/g, '');
        }

        if ($input.is('.classvalue')) {
            val = $.map(
                val.split(' '), function (n) {
                    return n.replace(/[^-0-9a-zA-Z_]/g, '');
                }
            ).join(' ');

            val = $.trim(val.replace(/\s+/g, ' '));
        }

        if ($input.is('.color')) {
            val = val.replace(/[^0-9a-fA-F]/g, '');
        }

        if ($input.is('.filesize')) {
            val = val.replace(/[^0-9kKmMbB]/g, '');
        }

        if ($input.is('.filetype')) {
            val = val.replace(/[^0-9a-zA-Z.,|\s]/g, '');
        }

        if ($input.is('.date')) {
            // 'yyyy-mm-dd' ISO 8601 format
            if (!val.match(/^\d{4}-\d{2}-\d{2}$/)) {
                val = '';
            }
        }
        
        /* 
            This is for the Default value (text) and the textareas used for 
            checkboxes, radios, and selects. 
        */
        if ($input.is('input[name="values"], textarea[name="values"]')) {
            val = $.trim(val);
        }

        $input.val(val);

        if ($input.is('[type="checkbox"].exclusive')) {
            cfredux.taggen.exclusiveCheckbox($input);
        }
    };

    cfredux.taggen.exclusiveCheckbox = function ($cb) {
        if ($cb.prop('checked') === true) {
            $cb.siblings('[type="checkbox"].exclusive').prop('checked', false);
        }
    };

    cfredux.taggen.insert = function (content) {
        $('textarea#cfredux-form').each(
            function () {
                this.focus();

                if (document.selection) { // IE
                    var selection = document.selection.createRange();
                    selection.text = content;
                } else if (this.selectionEnd || 0 === this.selectionEnd) {
                    var val = $(this).val();
                    var end = this.selectionEnd;
                    $(this).val(
                        val.substring(0, end) +
                        content + val.substring(end, val.length)
                    );
                    this.selectionStart = end + content.length;
                    this.selectionEnd = end + content.length;
                } else {
                    $(this).val($(this).val() + content);
                }

                this.focus();
            }
        );
    };

})(jQuery);
