(function ($) {

    'use strict';

    if (typeof cfredux === 'undefined' || cfredux === null) {
        return;
    }

    cfredux = $.extend(
        {
            cached: 0,
            inputs: []
        }, cfredux 
    );

    $(
        function () {
            $('div.cfredux > form').each(
                function () {
                    var $form = $(this);
                    cfredux.initForm($form);

                    if (cfredux.cached) {
                         cfredux.refill($form);
                    }
                } 
            );
        } 
    );
    cfredux.getId = function (form) {
        return parseInt($('input[name="_cfredux"]', form).val(), 10);
    };

    cfredux.initForm = function (form) {
        var $form = $(form);

        $form.submit(
            function (event) {
                if (typeof window.FormData === 'function') {
                    cfredux.submit($form);
                    event.preventDefault();
                }
            } 
        );

        $('.cfredux-submit', $form).after('<span class="ajax-loader"></span>');

        cfredux.toggleSubmit($form);

        $form.on(
            'click', '.cfredux-acceptance', function () {
                cfredux.toggleSubmit($form);
            } 
        );

        // Exclusive Checkbox
        $('.cfredux-exclusive-checkbox', $form).on(
            'click', 'input[type="checkbox"]', function () {
                var name = $(this).attr('name');
                $form.find('input[type="checkbox"][name="' + name + '"]').not(this).prop('checked', false);
            } 
        );

        // Free Text Option for Checkboxes and Radio Buttons
        $('.cfredux-list-item.has-free-text', $form).each(
            function () {
                var $freetext = $('input[type="text"].cfredux-free-text', this), 
                    $wrap = $(this).closest('.cfredux-form-control');

                if ($('[type="checkbox"], [type="radio"]', this).prop('checked') === true) {
                    $freetext.prop('disabled', false);
                } else {
                    $freetext.prop('disabled', true);
                }

                $wrap.on(
                    'change', '[type="checkbox"], [type="radio"]', function () {
                        var $cb = $(
                            '.has-free-text', 
                            $wrap
                        ).find('[type="checkbox"], [type="radio"]');

                        if ($cb.prop('checked') === true) {
                            $freetext.prop('disabled', false).focus();
                        } else {
                            $freetext.prop('disabled', true);
                        }
                    } 
                );
            } 
        );

        // Character Count
        $('.cfredux-character-count', $form).each(
            function () {
                var $count = $(this), 
                    name = $count.attr('data-target-name'), 
                    down = $count.hasClass('down'), 
                    starting = parseInt($count.attr('data-starting-value'), 10), 
                    maximum = parseInt($count.attr('data-maximum-value'), 10), 
                    minimum = parseInt($count.attr('data-minimum-value'), 10), 
                    updateCount = function (target) {
                          var $target = $(target), 
                              length = $target.val().length, 
                              count = down ? starting - length : length;
                          $count.attr('data-current-value', count);
                          $count.text(count);
      
                        if (maximum && maximum < length) {
                            $count.addClass('too-long');
                        } else {
                            $count.removeClass('too-long');
                        }
      
                        if (minimum && length < minimum) {
                            $count.addClass('too-short');
                        } else {
                            $count.removeClass('too-short');
                        }
                    };
                /*
                    This updates the count in count fields, which currently only 
                    applies to input and textarea fields.
                */
                $('input[name="' + name + '"], textarea[name="' + name + '"]', $form).each(
                    function () {
                        updateCount(this);

                        $(this).keyup(
                            function () {
                                updateCount(this);
                            } 
                        );
                    } 
                );
            } 
        );

        // URL Input Correction
        $form.on(
            'change', '.cfredux-validates-as-url', function () {
                var val = $.trim($(this).val());

                if (val
                    && !val.match(/^[a-z][a-z0-9.+-]*:/i)
                    && -1 !== val.indexOf('.') 
                ) {
                    val = val.replace(/^\/+/, '');
                    val = 'http://' + val;
                }

                $(this).val(val);
            } 
        );
    };

    cfredux.submit = function (form) {
        if (typeof window.FormData !== 'function') {
            return;
        }

        var $form = $(form), 
            formData, 
            detail, 
            ajaxSuccess, 
            cfredux_txn_id;

        $('.ajax-loader', $form).addClass('is-active');

        cfredux.clearResponse($form);

        formData = new FormData($form.get(0));

        detail = {
            id: $form.closest('div.cfredux').attr('id'),
            status: 'init',
            inputs: [],
            formData: formData
        };

        $.each(
            $form.serializeArray(), function (i, field) {
                if ('_cfredux' == field.name) {
                    detail.contactFormId = field.value;
                } else if ('_cfredux_version' == field.name) {
                    detail.pluginVersion = field.value;
                } else if ('_cfredux_locale' == field.name) {
                    detail.contactFormLocale = field.value;
                } else if ('_cfredux_unit_tag' == field.name) {
                    detail.unitTag = field.value;
                } else if ('_cfredux_container_post' == field.name) {
                    detail.containerPostId = field.value;
                } else if (field.name.match(/^_cfredux_\w+_free_text_/)) {
                    var owner = field.name.replace(/^_cfredux_\w+_free_text_/, '');
                    detail.inputs.push(
                        {
                            name: owner + '-free-text',
                            value: field.value
                        } 
                    );
                } else if (field.name.match(/^_/)) {
                    // do nothing
                } else {
                    detail.inputs.push(field);
                }
            } 
        );
    
        cfredux_txn_id = Math.random().toString(20).substr(2, 12)
        formData.set('_cfredux_txn_id', cfredux_txn_id);

        cfredux.triggerEvent($form.closest('div.cfredux'), 'beforesubmit', detail);

        ajaxSuccess = function (data, status, xhr, $form) {
            detail.id = $(data.into).attr('id');
            detail.status = data.status;
            detail.apiResponse = data;

            var $message = $('.cfredux-response-output', $form), 
                customStatusClass;

            switch (data.status) {
            case 'validation_failed':
                $.each(
                    data.invalidFields, function (i, n) {
                        $(n.into, $form).each(
                            function () {
                                cfredux.notValidTip(this, n.message);
                                $('.cfredux-form-control', this).addClass('cfredux-not-valid');
                                $('[aria-invalid]', this).attr('aria-invalid', 'true');
                            } 
                        );
                    } 
                );

                $message.addClass('cfredux-validation-errors');
                $form.addClass('invalid');

                cfredux.triggerEvent(data.into, 'invalid', detail);
                break;
            case 'acceptance_missing':
                $message.addClass('cfredux-acceptance-missing');
                $form.addClass('unaccepted');

                cfredux.triggerEvent(data.into, 'unaccepted', detail);
                break;
            case 'spam':
                $message.addClass('cfredux-spam-blocked');
                $form.addClass('spam');

                $('[name="g-recaptcha-response"]', $form).each(
                    function () {
                        if ('' === $(this).val()) {
                              var $recaptcha = $(this).closest('.cfredux-form-control-wrap');
                              cfredux.notValidTip($recaptcha, cfredux.recaptcha.messages.empty);
                        }
                    } 
                );

                cfredux.triggerEvent(data.into, 'spam', detail);
                break;
            case 'aborted':
                $message.addClass('cfredux-aborted');
                $form.addClass('aborted');

                cfredux.triggerEvent(data.into, 'aborted', detail);
                break;
            case 'mail_sent':
                $message.addClass('cfredux-mail-sent-ok');
                $form.addClass('sent');

                cfredux.triggerEvent(data.into, 'mailsent', detail);
                break;
            case 'mail_failed':
                $message.addClass('cfredux-mail-sent-ng');
                $form.addClass('failed');

                cfredux.triggerEvent(data.into, 'mailfailed', detail);
                break;
            default:
                customStatusClass = 'custom-'
                + data.status.replace(/[^0-9a-z]+/i, '-');
                $message.addClass('cfredux-' + customStatusClass);
                $form.addClass(customStatusClass);
            }

            cfredux.refill($form, data);

            cfredux.triggerEvent(data.into, 'submit', detail);

            if ('mail_sent' == data.status) {
                $form.each(
                    function () {
                        this.reset();
                    } 
                );

                cfredux.toggleSubmit($form);
            }

            $message.html('').append(data.message).slideDown('fast');
            $message.attr('role', 'alert');

            $('.screen-reader-response', $form.closest('.cfredux')).each(
                function () {
                    var $response = $(this), 
                        $invalids = $('<ul></ul>');
                    $response.html('').attr('role', '').append(data.message);

                    if (data.invalidFields) {
          
                        $.each(
                            data.invalidFields, function (i, n) {
                                var $li;
                                if (n.idref) {
                                    $li = $('<li></li>').append($('<a></a>').attr('href', '#' + n.idref).append(n.message));
                                } else {
                                    $li = $('<li></li>').append(n.message);
                                }

                                $invalids.append($li);
                            } 
                        );

                          $response.append($invalids);
                    }

                    $response.attr('role', 'alert').focus();
                } 
            );
        };

        $.ajax(
            {
                type: 'POST',
                url: cfredux.apiSettings.getRoute(
                    '/contact-forms/' + cfredux.getId($form) + '/feedback' 
                ),
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false
            } 
        ).done(
            function (data, status, xhr) {
                ajaxSuccess(data, status, xhr, $form);
                $('.ajax-loader', $form).removeClass('is-active');
            } 
        ).fail(
            function (xhr, status, error) {
                var $e = $('<div class="ajax-error"></div>').text(error.message);
                $form.after($e);
            } 
        );
    };

    cfredux.triggerEvent = function (target, name, detail) {
        var $target = $(target), 
            event = new CustomEvent(
                'cfredux' + name, {
                    bubbles: true,
                    detail: detail
                } 
            );

        $target.get(0).dispatchEvent(event);

        /* jQuery event */
        $target.trigger('cfredux:' + name, detail);
        $target.trigger(name + '.cfredux', detail); // deprecated
    };

    cfredux.toggleSubmit = function (form, state) {
        var $form = $(form), 
            $submit = $('input[type="submit"]', $form);

        if (typeof state !== 'undefined') {
            $submit.prop('disabled', !state);
            return;
        }

        if ($form.hasClass('cfredux-acceptance-as-validation')) {
            return;
        }

        $submit.prop('disabled', false);

        $('.cfredux-acceptance', $form).each(
            function () {
                var $span = $(this), 
                    $input = $('input[type="checkbox"]', $span);

                if (!$span.hasClass('optional')) {
                    if ($span.hasClass('invert') && $input.prop('checked') === true 
                        || !$span.hasClass('invert') && $input.prop('checked') === false 
                    ) {
                        $submit.prop('disabled', true);
                        return false;
                    }
                }
            } 
        );
    };

    cfredux.notValidTip = function (target, message) {
        var $target = $(target), 
            fadeOut;
        $('.cfredux-not-valid-tip', $target).remove();
        $('<span role="alert" class="cfredux-not-valid-tip"></span>')
        .text(message).appendTo($target);
        
        /*
            It doesn't look like you're using this anymore, as of 04/02/2021.
        */
        /*
        if ($target.is('.use-floating-validation-tip *')) {
            fadeOut = function (target) {
                $(target).not('[type="hidden"]').animate(
                    {
                        opacity: 0
                    }, 'fast', function () {
                        $(this).css({ 'z-index': -100 });
                    } 
                );
            };

            $target.on(
                'mouseover', '.cfredux-not-valid-tip', function () {
                    fadeOut(this);
                } 
            );

            $target.on(
                'focus', ':input', function () {
                    fadeOut($('.cfredux-not-valid-tip', $target));
                } 
            );
        }
        */
    };

    cfredux.refill = function (form, data) {
        var $form = $(form), 
            refillQuiz;
        
        /*
            This is for the Quiz tag inputs.
        */
        refillQuiz = function ($form, items) {
            $.each(
                items, function (i, n) {
                    $form.find('input[name="' + i + '"]').val('');
                    $form.find('input[name="' + i + '"]').siblings('span.cfredux-quiz-label').text(n[ 0 ]);
                    $form.find('input[type="hidden"][name="_cfredux_quiz_answer_' + i + '"]').attr('value', n[ 1 ]);
                } 
            );
        };

        if (typeof data === 'undefined') {
            $.ajax(
                {
                    type: 'GET',
                    url: cfredux.apiSettings.getRoute(
                        '/contact-forms/' + cfredux.getId($form) + '/refill' 
                    ),
                beforeSend: function (xhr) {
                    /*
                        You changed the following from :input to input in version 
                        1.2.2, expecting that this would be using an input of type 
                        hidden.
                    */
                    var nonce = $form.find('input[name="_wpnonce"]').val();
                    
                    if (nonce) {
                        xhr.setRequestHeader('X-WP-Nonce', nonce);
                    }
                },
                    dataType: 'json'
                } 
            ).done(
                function (data, status, xhr) {
                    if (data.quiz) {
                        refillQuiz($form, data.quiz);
                    }
                } 
            );

        } else {
            if (data.quiz) {
                refillQuiz($form, data.quiz);
            }
        }
    };

    cfredux.clearResponse = function (form) {
        var $form = $(form);
        $form.removeClass('invalid spam sent failed');
        $form.siblings('.screen-reader-response').html('').attr('role', '');

        $('.cfredux-not-valid-tip', $form).remove();
        $('[aria-invalid]', $form).attr('aria-invalid', 'false');
        $('.cfredux-form-control', $form).removeClass('cfredux-not-valid');

        $('.cfredux-response-output', $form)
        .hide().empty().removeAttr('role')
        .removeClass('cfredux-mail-sent-ok cfredux-mail-sent-ng cfredux-validation-errors cfredux-spam-blocked');
    };

    cfredux.apiSettings.getRoute = function (path) {
        var url = cfredux.apiSettings.root;

        url = url.replace(
            cfredux.apiSettings.namespace,
            cfredux.apiSettings.namespace + path 
        );

        return url;
    };

})(jQuery);

/*
 * Polyfill for Internet Explorer
 * See https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent
 */
(function () {
    if (typeof window.CustomEvent === "function") { return false;
    }

    function CustomEvent(event, params)
    {
        params = params || { bubbles: false, cancelable: false, detail: undefined };
        var evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(
            event,
            params.bubbles, params.cancelable, params.detail 
        );
        return evt;
    }

    CustomEvent.prototype = window.Event.prototype;

    window.CustomEvent = CustomEvent;
})();
