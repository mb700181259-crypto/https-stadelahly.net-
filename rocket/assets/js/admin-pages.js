$ = jQuery;

a4h_pages = {

    tabs: function() {
        if ( typeof $.fn.tabs === 'undefined' ) return;
        $('.a4h-admin-page').tabs({
            activateX: function(event, ui) {
                window.location.hash = ui.newPanel.attr('id');
            }
        });
    },

    repeaters: function() {
        $('body').on('click', '.a4h-admin-page-repeater-item-toggle', function() {
            $(this).closest('.a4h-admin-page-repeater-item').toggleClass('toggled');
            return false;
        });
        $('body').on('click', '.a4h-admin-page-repeater-item-move', function() {
            return false;
        });
        $('body').on('click', '.a4h-admin-page-repeater-item-delete', function() {
            $(this).closest('.a4h-admin-page-repeater-item').fadeOut('fast', function() { $(this).remove(); $(document).triggerHandler('RepeaterUpdated'); } );
            return false;
        });
        $('body').on('click', '.a4h-admin-page-repeater-item-add', function() {
            let target = $(this).siblings('.a4h-admin-page-repeater-items');
            let cloned_sample = target.find('.a4h-admin-page-repeater-item.sample').clone();
            let rand = (Math.random() + 1).toString(36).substring(7);
            cloned_sample.find('[name]').each(function() {
                $(this).attr('name', $(this).attr('name').replace('sample', rand));
            });
            let container_name = $(this).siblings('.a4h-admin-page-repeater-items').attr('data-name');
            cloned_sample.attr('data-container', container_name);
            target.append(cloned_sample.removeClass('sample'));
            cloned_sample.find('.a4h-admin-page-repeater-item-overlay').each(function() {
                $(this).show();
                $(this).fadeOut('slow');
            });
            cloned_sample.find('.ad-status-checkbox input[type="checkbox"]').attr('checked', 1);
            $(document).triggerHandler('RepeaterUpdated');
            return false;
        });
        $('.a4h-admin-page-repeater-items').sortable({
            handle: '.a4h-admin-page-repeater-item-move',
            connectWith: $('.a4h-admin-page-repeater-items'),
            start: function(event, ui) {
                $('.a4h-admin-page-repeater-items').addClass('active');
                ui.item.find('.a4h-admin-page-repeater-item-overlay').show();
            },
            stop: function(event, ui) {
                $('.a4h-admin-page-repeater-items').removeClass('active');
                ui.item.find('.a4h-admin-page-repeater-item-overlay').fadeOut('slow');
            },
            update: function() {
                $(document).triggerHandler('RepeaterUpdated');
            },
        });
        $('.a4h-admin-page-repeater-items').on('sortbeforestop', function(event, ui) {
            let current_container_name = ui.item.attr('data-container');
            let new_container_name = ui.item.parents('.a4h-admin-page-repeater-items').attr('data-name');
            ui.item.removeClass('active');
            ui.item.find('[name]').each(function() {
                $(this).attr('name', $(this).attr('name').replace(current_container_name, new_container_name));
            });
        });
        $(document).on('RepeaterUpdated', function() {
            $('.a4h-admin-page-repeater-items').each(function() {
                let id_div = $(this).find('.a4h-admin-page-repeater-item:not(.sample) .a4h-admin-page-repeater-item-id');
                id_div.each(function(index) {
                    $(this).text(index + 1);
                });
            });
        });
        $(document).triggerHandler('RepeaterUpdated');
    },

    upload: function() {
        $('body').on('click', '.a4h-admin-page-field .upload-image', function() {
            upload_button = $(this);
            let upload_window = wp.media({library : {type : 'image'}, multiple: false})
            upload_window.open();
            upload_window.on('select', function() {
                var attachment = upload_window.state().get('selection').first().toJSON();
                upload_button.closest('.a4h-admin-page-field').find('input[type="hidden"]').val(attachment.id);
                upload_button.closest('.a4h-admin-page-field').find('.image-preview').removeClass('hidden');
                upload_button.closest('.a4h-admin-page-field').find('.image-preview img').attr('src', attachment.url);
            });
            return false;
        });
        $('body').on('click', '.a4h-admin-page-field .remove-image', function() {
            upload_button = $(this);
            upload_button.closest('.a4h-admin-page-field').find('input[type="hidden"]').val('');
            upload_button.closest('.a4h-admin-page-field').find('.image-preview').addClass('hidden');
            upload_button.closest('.a4h-admin-page-field').find('.image-preview img').attr('src', '');
            return false;
        });
    },

    colorInput: function() {
        if ( typeof $.fn.wpColorPicker === 'undefined' ) return;
        $('.settings-color').wpColorPicker({
            palettes: ['#d62f2f', '#c43a8f', '#a854f5', '#5477f5', '#1168c4', '#0fa970', '#b78721', '#606060'],
        });
    },

    textAreaIdent: function() {
        $('.css-textarea, .js-textarea').on('keydown', function(e) {
            if ( e.key === 'Tab' ) {
                e.preventDefault();
                
                document.execCommand('insertText', false, '\t');
            }
        });
    },

    layoutInsertions: function() {
        let data_insertions;
        let target_column_items;
        let target_column_textarea;
        let insertions_overlay = $('.a4h-admin-page-layout-builder-overlay');
        $('body').on('click', '.a4h-admin-page-layout-builder-column-insert', function() {
            data_insertions = $(this).closest('.a4h-admin-page-layout-builder-row').attr('data-insertions');
            insertions_overlay.addClass('active');
            insertions_overlay.attr('data-insertions', data_insertions);
            target_column_items = $(this).closest('.a4h-admin-page-layout-builder-column').find('.a4h-admin-page-layout-builder-column-items');
            target_column_textarea = $(this).closest('.a4h-admin-page-ad-content').find('.code-input');
            return false;
        });
        $('body').on('click', '.a4h-admin-page-layout-builder-overlay-cancel', function() {
            insertions_overlay.removeClass('active');
            return false;
        });
        $('body').on('click', '.a4h-admin-page-layout-builder-insertion', function() {
            insertions_overlay.removeClass('active');
            target_column_items.append('<div class="a4h-admin-page-layout-builder-column-item" data-name="' + $(this).attr('data-name') + '"><span class="a4h-admin-page-layout-builder-column-item-remove"><span class="dashicons dashicons-trash" title="حذف"></span></span>' + $(this).text() + '</div>');
            target_column_textarea.val($(this).attr('data-name'));
            $(document).triggerHandler('LayoutUpdated');
            return false;
        });
        $('body').on('click', '.a4h-admin-page-layout-builder-column-item-remove', function() {
            $(this).closest('.a4h-admin-page-layout-builder-column-item').fadeOut('fast', function() { $(this).remove(); $(document).triggerHandler('LayoutUpdated'); } );
            return false;
        });
        let update = function() {
            $('.a4h-admin-page-layout-builder-column-items').sortable({
                connectWith: '.a4h-admin-page-layout-builder-column-items',
                update: function() {
                    $(document).triggerHandler('LayoutUpdated');
                },
                start: function(event, ui) {
                    $('.a4h-admin-page-layout-builder-row-content').addClass('active');
                },
                stop: function(event, ui) {
                    $('.a4h-admin-page-layout-builder-row-content').removeClass('active');
                },
            });
            let layout_columns = $('.a4h-admin-page-layout-builder-column');
            layout_columns.each(function() {
                let layout_column_value = [];
                let layout_column_items = $(this).find('.a4h-admin-page-layout-builder-column-item');
                let layout_column_input = $(this).find('.a4h-admin-page-layout-builder-column-value');
                layout_column_items.each(function(index, elem) {
                    layout_column_value.push($(elem).attr('data-name'))
                });
                layout_column_value.join(',');
                layout_column_input.val(layout_column_value);
            });
        }
        $(document).on('RepeaterUpdated', function() {
            update();
        });
        $(document).on('LayoutUpdated', function() {
            update();
        });
        update();
    },

    adsShortcodes: function() {
        $('body').on('blur', '.shortcode-input-field', function() {
            let val = $(this).val();
            if ( val != '' && ( !val.match("^\\[ad_") || !val.match("]$") ) ) {
                $(this).val('[ad_' + val + ']');
            }
        });
    },

    codeHighlighter: function() {
        return;
        var cssSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
        cssSettings.codemirror = _.extend({}, cssSettings.codemirror, { mode: 'css' });
        wp.codeEditor.initialize($('.css-textarea'), cssSettings);
        var jsSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
        jsSettings.codemirror = _.extend({}, jsSettings.codemirror, { mode: 'css' });
        wp.codeEditor.initialize($('.js-textarea'), jsSettings);
    },

    formSerialize: function() {
        $('.a4h-admin-form').data('serialize', $('.a4h-admin-form').serialize());
    },

    beforeUnload: function() {
        $(window).bind('beforeunload', function(e) {
            if ( $('.a4h-admin-form').length < 1 ) return;
            if ( $('.a4h-admin-form').serialize() != $('.a4h-admin-form').data('serialize') ) {
                return true;
            } else {
                e = null;
            }
        });
    },

    submitForm: function() {
        if ( typeof $.fn.ajaxSubmit === 'undefined' ) return;
        let placeholder = $('.a4h-admin-page-placeholder');
        let loader = $('.a4h-loader');
        let loader_text = $('.a4h-loader-text');
        let page_type = $('.a4h-admin-page').attr('data-type');
        $('.a4h-admin-form').submit(function() {
            placeholder.fadeIn();
            loader.removeClass('stop');
            loader_text.find('.saving').show();
            loader_text.find('.saved').hide();
            $(this).ajaxSubmit({
                success: function() {
                    $('.a4h-admin-form').data('serialize', $('.a4h-admin-form').serialize());
                    loader.addClass('stop');
                    loader_text.find('.saved').show();
                    loader_text.find('.saving').hide();
                    if ( page_type == 'tools' ) {
                        location.reload();
                    }
                },
                timeout: 5000,
            });	  
            return false; 
        });
        $('body').on('click', function() {
            placeholder.fadeOut();
        });
    },

}

addEventListener('DOMContentLoaded', function() {

    Object.keys(a4h_pages).forEach(function(key) {
        a4h_pages[key]();
    });

});