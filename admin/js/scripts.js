(function ($) {

    'use strict';

    if (typeof cfredux === 'undefined' || cfredux === null) {
        return;
    }

    $(
        function () {
            var welcomePanel = $('#cfr-welcome-panel');
            var updateWelcomePanel;

            updateWelcomePanel = function (visible) {
                $.post(
                    ajaxurl, {
                        action: 'cfredux-update-welcome-panel',
                        visible: visible,
                        welcomepanelnonce: $('#welcomepanelnonce').val()
                    } 
                );
            };

            $('a.cfr-welcome-panel-close', welcomePanel).click(
                function (event) {
                    event.preventDefault();
                    welcomePanel.addClass('hidden');
                    updateWelcomePanel(0);
                } 
            );
            
            /* ------------------- Form Editor Tabs --------------------- */
            $('#contact-form-editor-tabs').focusin(
                function (event) {
                    $('#contact-form-editor .keyboard-interaction').css(
                        'visibility', 'visible' 
                    );
                    document.addEventListener('keyup', cfrEditorFormArrowKeys, false);
                } 
            ).focusout(
                function (event) {
                    $('#contact-form-editor .keyboard-interaction').css(
                        'visibility', 'hidden' 
                    );
                    document.removeEventListener('keyup', cfrEditorFormArrowKeys, false);
                } 
            );
            
            function cfrEditorFormArrowKeys(event) 
            {
                var key = event.key || event.keyCode, 
                    currenttab = $('.cfractivetab').index(), 
                    thedirection = 0;
                switch(key) {
                case 37:    // left key
                case 'ArrowLeft':
                case '<':
                    thedirection = -1;
                    break;
                case 39:    // right key
                case 'ArrowRight':
                case '>':
                    thedirection = 1;
                    break;
                }
                if (thedirection === -1) {
                    if (currenttab !== 0) {
                        $('.contact-form-editor-tablink').eq(currenttab - 1).click();
                    }
                } else if (thedirection === 1) {
                    if (currenttab !== $('.contact-form-editor-tablink').last().index()) {
                        $('.contact-form-editor-tablink').eq(currenttab + 1).click();
                    }
                }
            }
            
            $('.contact-form-editor-tablink').click(
                function (event) {
                    event.preventDefault();
                    var tabcontent = $(this).attr('id').replace('-tab', '');
                    $('.contact-form-editor-tabcontent').each(
                        function () {
                            $(this).hide();
                        }
                    );
                    $('.contact-form-editor-tablink').each(
                        function () {
                            $(this).removeClass('cfractivetab');
                        }
                    );
                    $('#' + tabcontent).show();
                    $(this).addClass('cfractivetab');
                }
            );
            
            /* --- Handle the close click and escape on modal dialogs. -- */
            $('.cfrclose').click(
                function () {
                    $(this).closest('.cfrmodaldiv').css('display', 'none');
                }
            );
            $(document).keyup(
                function (e) {
                    if (e.keyCode === 27) {
                        $('.cfrmodaldiv').each(
                            function () {
                                $(this).css('display', 'none');
                            }
                        );
                    }
                }
            );
            
            /* ---- Display the Tag Generator Form for a Given Tag ----- */
            $('.cfrtagbutton').click(
                function () {
                    $('#' + $(this).data('tag')).closest('.cfrmodaldiv').css('display', 'block');
                }
            );
            
            /* ----------------- End Form Editor Tabs ------------------ */
            
            /* Display Information Pop-ups on Tag Button modal dialogs.  */
            $('.cfrinfo').click(
                function () {
                    //$(this).siblings('.cfrinfopopup').toggle();
                    $(this).closest('p').siblings('.cfrinfopopup').toggle();
                }
            );
            
            cfredux.toggleMail2('input[type="checkbox"].toggle-form-table');
            
            /* --- Save the contact form from the Contact Form Editor -- */
            $('#cfredux-save-cf').click(
                function () {
                    this.form._wpnonce.value = $(this).data('nonce');
                    this.form.action.value = 'save';
                    return true;
                }
            );
            
            /* - Duplicate the contact from from the Contact Form Editor */
            $('#cfredux-copy-cf').click(
                function () {
                    this.form._wpnonce.value = $(this).data('nonce'); 
                    this.form.action.value = 'copy'; 
                    return true;
                }
            );
            
            /* -- Delete a contact form from the Contact Form Editor -- */
            $('#cfredux-delete-cf').click(
                function () {
                    if (confirm($(this).data('confirm'))) {
                        this.form._wpnonce.value = $(this).data('nonce'); 
                        this.form.action.value = 'delete'; 
                        return true;
                    } else { 
                        return false;
                    }
                }
            );
            
            /* - Select the shortcode on the Contact Forms table page. - */
            $('input[type="text"].code').click(
                function () {
                    $(this).select();
                }
            );
            
            /* -- Select the shortcode on the Edit Contact Form page. -- */
            $('#cfredux-shortcode').click(
                function () {
                    $(this).select();
                }
            );

            $('input[type="checkbox"].toggle-form-table').click(
                function (event) {
                    cfredux.toggleMail2(this);
                } 
            );

            if ('' === $('#title').val()) {
                  $('#title').focus();
            }

            cfredux.titleHint();

            $('.contact-form-editor-box-mail span.mailtag').click(
                function (event) {
                    var range = document.createRange();
                    range.selectNodeContents(this);
                    window.getSelection().addRange(range);
                } 
            );

            cfredux.updateConfigErrors();

            $('[data-config-field]').change(
                function () {
                    var postId = $('#post_ID').val();

                    if (! postId || -1 == postId) {
                        return;
                    }

                    var data = [];

                    $(this).closest('form').find('[data-config-field]').each(
                        function () {
                            data.push(
                                {
                                    'name': $(this).attr('name').replace(/^cfredux-/, '').replace(/-/g, '_'),
                                    'value': $(this).val()
                                } 
                            );
                        } 
                    );

                    data.push({ 'name': 'context', 'value': 'dry-run' });

                    $.ajax(
                        {
                            method: 'POST',
                            url: cfredux.apiSettings.getRoute('/contact-forms/' + postId),
                            beforeSend: function (xhr) {
                                xhr.setRequestHeader('X-WP-Nonce', cfredux.apiSettings.nonce);
                            },
                            data: data
                        } 
                    ).done(
                        function (response) {
                            cfredux.configValidator.errors = response.config_errors;
                            cfredux.updateConfigErrors();
                        } 
                    );
                } 
            );

            $(window).on(
                'beforeunload', function (event) {
                    if (document.getElementById('cfredux-admin-form-element')) {
                        var e = document.getElementById('cfredux-admin-form-element').elements, 
                            i,
                            changed = false;
                        for (i = 0; i < e.length; i++) {
                            switch(e[i].nodeName.toLowerCase()) {
                            case 'input':
                                if (e[i].type != 'hidden') {
                                    switch(e[i].type) {
                                    case 'checkbox':
                                    case 'radio':
                                        if (e[i].defaultChecked != e[i].checked) {
                                            changed = true;
                                        }
                                        break;
                                    default:
                                        if (e[i].defaultValue != e[i].value) {
                                            changed = true;
                                        }
                                    }
                                }
                                break;
                            case 'textarea':
                                if (e[i].defaultValue != e[i].value) {
                                    changed = true;
                                }
                                break;
                            case 'select':
                                if (e[i].options[e[i].selectedIndex].defaultSelected === false) {   
                                    changed = true;
                                }
                                break;
                            /*
                                Currently, you're only using text, checkbox, and 
                                textarea, but you left the select and this default 
                                in here in case you add more in the future. 
                            */
                            default:
                                if (e[i].defaultValue != e[i].value && !e[i].classList.contains('contact-form-editor-tablink')) {
                                    changed = true;
                                }
                            }
                        }
                    }

                    if (changed) {
                        event.returnValue = cfredux.saveAlert;
                        return cfredux.saveAlert;
                    }
                } 
            );

            $('#cfredux-admin-form-element').submit(
                function () {
                    if ('copy' != this.action.value) {
                        $(window).off('beforeunload');
                    }

                    if ('save' == this.action.value) {
                        $('#publishing-action .spinner').addClass('is-active');
                    }
                } 
            );
        } 
    );

    cfredux.toggleMail2 = function (checkbox) {
        var $checkbox = $(checkbox);
        var $fieldset = $(
            'fieldset',
            $checkbox.closest('.contact-form-editor-box-mail') 
        );

        if ($checkbox.prop('checked') === true) {
            $fieldset.removeClass('hidden');
        } else {
            $fieldset.addClass('hidden');
        }
    };

    cfredux.updateConfigErrors = function () {
        var errors = cfredux.configValidator.errors;
        var errorCount = { total: 0 };

        $('[data-config-field]').each(
            function () {
                $(this).removeAttr('aria-invalid');
                $(this).next('ul.config-error').remove();

                var section = $(this).attr('data-config-field');

                if (errors[ section ]) {
                    var $list = $('<ul></ul>').attr(
                        {
                            'role': 'alert',
                            'class': 'config-error'
                        } 
                    );

                    $.each(
                        errors[ section ], function (i, val) {
                            var $li = $('<li></li>').append(
                                $('<span class="dashicons dashicons-warning" aria-hidden="true"></span>')
                            ).append(
                                $('<span class="screen-reader-text"></span>').text(cfredux.configValidator.iconAlt)
                            ).append(' ');

                            if (val.link) {
                                $li.append(
                                    $('<a></a>').attr('href', val.link).text(val.message)
                                );
                            } else {
                                $li.text(val.message);
                            }

                            $li.appendTo($list);

                            var tab = section
                            .replace(/^mail_\d+\./, 'mail.').replace(/\..*$/, '');

                            if (! errorCount[ tab ]) {
                                      errorCount[ tab ] = 0;
                            }

                            errorCount[ tab ] += 1;

                            errorCount.total += 1;
                        } 
                    );

                    $(this).after($list).attr({ 'aria-invalid': 'true' });
                }
            } 
        );

        $('.contact-form-editor-tablink').each(
            function () {
                var el = $(this), 
                    tab = el.attr('id').replace(/-panel-tab$/, ''), 
                    tabPanelError = $('#' + tab + '-panel > div.config-error').first(), 
                    manyErrorsInTab;
                
                el.find('span.dashicons').remove();
                
                $.each(
                    errors, function (key, val) {
                        key = key.replace(/^mail_\d+\./, 'mail.');

                        if (key.replace(/\..*$/, '') == tab.replace('-', '_')) {
                            el.append('<span class="dashicons dashicons-warning" aria-hidden="true"></span>');
                            return false;
                        }
                    } 
                );

                tabPanelError.empty();

                if (errorCount[ tab.replace('-', '_') ]) {
                     tabPanelError
                      .append('<span class="dashicons dashicons-warning" aria-hidden="true"></span> ');

                    if (1 < errorCount[ tab.replace('-', '_') ]) {
                        manyErrorsInTab = cfredux.configValidator.manyErrorsInTab
                        .replace('%d', errorCount[ tab.replace('-', '_') ]);
                        tabPanelError.append(manyErrorsInTab);
                    } else {
                        tabPanelError.append(cfredux.configValidator.oneErrorInTab);
                    }
                }
            } 
        );

        $('#misc-publishing-actions .misc-pub-section.config-error').remove();

        if (errorCount.total) {
            var $warning = $('<div></div>')
            .addClass('misc-pub-section config-error')
            .append('<span class="dashicons dashicons-warning" aria-hidden="true"></span> ');

            if (1 < errorCount.total) {
                $warning.append(
                    cfredux.configValidator.manyErrors.replace('%d', errorCount.total)
                );
            } else {
                $warning.append(cfredux.configValidator.oneError);
            }

            $warning.append('<br />').append(
                $('<a></a>')
                .attr('href', cfredux.configValidator.docUrl)
                .text(cfredux.configValidator.howToCorrect)
            );

            $('#misc-publishing-actions').append($warning);
        }
    };

    /**
     * Copied from wptitlehint() in wp-admin/js/post.js
     */
    cfredux.titleHint = function () {
        var $title = $('#title');
        var $titleprompt = $('#title-prompt-text');

        if ('' === $title.val()) {
            $titleprompt.removeClass('screen-reader-text');
        }

        $titleprompt.click(
            function () {
                $(this).addClass('screen-reader-text');
                $title.focus();
            } 
        );

        $title.blur(
            function () {
                if ('' === $(this).val()) {
                    $titleprompt.removeClass('screen-reader-text');
                }
            } 
        ).focus(
            function () {
                $titleprompt.addClass('screen-reader-text');
            } 
        ).keydown(
            function (e) {
                $titleprompt.addClass('screen-reader-text');
                $(this).unbind(e);
            } 
        );
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