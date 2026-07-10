$ = jQuery;

a4h_widgets = {

    sideBarsReorder: function() {
        $('#widgets-right').show();
        $('.sidebars-container > .widgets-holder-wrap:nth-child(1) .sidebar-name').trigger('click');
        
        $('.sidebars-column-2 >').appendTo('.sidebars-column-1');
        $('.sidebars-column-2').remove();
        $('.sidebars-column-1').addClass('sidebars-container').removeClass('sidebars-column-1');
        $('.sidebars-container > .widgets-holder-wrap:nth-child(1) .sidebar-name').trigger('click');

        $('.sidebars-container > .widgets-holder-wrap:nth-child(-n+2)').wrapAll('<div class="sidebars-wrapper sidebars-global"></div>');
        $('.sidebars-container > .widgets-holder-wrap:nth-child(-n+2)').wrapAll('<div class="sidebars-wrapper sidebars-archive"></div>');
        $('.sidebars-container > .widgets-holder-wrap:nth-child(-n+6)').wrapAll('<div class="sidebars-wrapper sidebars-post"></div>');
        $('.sidebars-container > .widgets-holder-wrap:nth-child(-n+11)').wrapAll('<div class="sidebars-wrapper sidebars-home"></div>');
        $('.sidebars-container > .widgets-holder-wrap:nth-child(-n+12)').wrapAll('<div class="sidebars-wrapper sidebars-widgets_lists"></div>');
        $('.sidebars-global').prepend('<div class="sidebars-title">تظهر في كل الصفحات</div>');
        $('.sidebars-archive').prepend('<div class="sidebars-title">تظهر في الأقسام والتصنيفات فقط</div>');
        $('.sidebars-post').prepend('<div class="sidebars-title">تظهر في المقالات فقط</div>');
        $('.sidebars-home').prepend('<div class="sidebars-title">تظهر في الصفحة الرئيسية فقط</div>');
        $('.sidebars-widgets_lists').prepend('<div class="sidebars-title">تظهر في أي مكان</div>');

        $('.sidebars-title').append('<span class="dashicons dashicons-arrow-down-alt2"></span>');
        
        $('.sidebars-title').on('click', function() {
            $(this).closest('.sidebars-wrapper').toggleClass('inactive');
        });
    },

    sidebarsHints: function() {
        $('#widgets-right .widgets-sortables').each(function() {
            if ( $(this).find('.widget').length ) {
                $(this).addClass('has-widgets');
            } else {
                $(this).removeClass('has-widgets');
            }
        });
    },

    postsWidget: function() {
        $('.widget-loading-placeholder').remove();

        let widget_selector = '.widget[id*="_posts-list"]';
        
        let post_type_input = widget_selector + ' [data-field="post_type"] input';
        $('body').off('click', post_type_input).on('click', post_type_input, function(event) {
            if ( confirm('سيتم حفظ الودجت إذا قمت بتغيير النوع') ) {
                $(this).closest('.widget').find('[data-field="taxonomies"] input').prop('checked', false);
                $(this).closest('.widget').find('[data-field="terms"] input').prop('checked', false);
                $(this).closest('.widget').find('[data-field="extra_terms"] input').val('');
                $(this).closest('.widget').find('input[type="submit"]').click();
                $(this).closest('.widget').prepend('<div class="widget-loading-placeholder"></div>');
            }
        });

        let taxonomies_input = widget_selector + ' [data-field="taxonomies"] input';
        $('body').off('click', taxonomies_input).on('click', taxonomies_input, function() {
            let has_terms = false;
            let checked = [];
            $(this).closest('.widget').find('[data-field="taxonomies"] input:checked').each(function(index, elem) {
                checked.push($(elem).val());
            });
            $(this).closest('.widget').find('[data-field="terms"] .label-for-choices').each((index, elem) => {
                if ( checked.includes($(elem).attr('data-type')) ) {
                    has_terms = true;
                    return false;
                }
            });
            $(this).closest('.widget').find('[data-field="terms"] .label-for-choices').each((index, elem) => {
                if ( checked.includes($(elem).attr('data-type')) ) {
                    //$(elem).show();
                } else {
                    //$(elem).hide();
                }
            });
            if ( has_terms ) {
                //$(this).closest('.widget').find('[data-field="term_type"]').show();
            } else {
                //$(this).closest('.widget').find('[data-field="term_type"]').hide();
                //$(this).closest('.widget').find('[data-field="term_type"] input').eq(0).prop('checked', true);
                //$(this).closest('.widget').find('[data-field="term_type"] input').eq(0).change();
            }
        });

        let term_type_input = widget_selector + ' [data-field="term_type"] input';
        $('body').off('click', term_type_input).on('click', term_type_input, function() {
            if ( $(this).val() == 'selected' ) {
                $(this).closest('.widget').find('[data-field="terms"]').show();
                $(this).closest('.widget').find('[data-field="extra_terms"]').show();
            } else {
                $(this).closest('.widget').find('[data-field="terms"]').hide();
                $(this).closest('.widget').find('[data-field="extra_terms"]').hide();
            }
            if ( $(this).val() == 'current' ) {
                $(this).closest('.widget').find('[data-field="taxonomies"]').show();
            } else {
                $(this).closest('.widget').find('[data-field="taxonomies"]').hide();
            }
            if ( $(this).val() == 'selected' || $(this).val() == 'current' ) {
                $(this).closest('.widget').find('[data-field="taxonomy_relation"]').show();
            } else {
                $(this).closest('.widget').find('[data-field="taxonomy_relation"]').hide();
            }
        });
        $(term_type_input + ':checked').click();

        let special_posts_input = widget_selector + ' [data-field="special_posts"] input';
        $('body').off('click', special_posts_input).on('click', special_posts_input, function() {
            if ( $(this).val() == 'selected' ) {
                $(this).closest('.widget').find('[data-field="selected_posts_ids"]').show();
            } else {
                $(this).closest('.widget').find('[data-field="selected_posts_ids"]').hide();
            }
        });
        $(special_posts_input + ':checked').click();

        let author_type_input = widget_selector + ' [data-field="author_type"] input';
        $('body').off('click', author_type_input).on('click', author_type_input, function() {
            if ( $(this).val() == 'selected' ) {
                $(this).closest('.widget').find('[data-field="authors"]').show();
            } else {
                $(this).closest('.widget').find('[data-field="authors"]').hide();
            }
        });
        $(author_type_input + ':checked').click();

        let order_by_input = widget_selector + ' [data-field="order_by"] input';
        $('body').off('click', order_by_input).on('click', order_by_input, function() {
            if ( $(this).val() == 'views' ) {
                $(this).closest('.widget').find('[data-field="views_interval"]').show();
            } else {
                $(this).closest('.widget').find('[data-field="views_interval"]').hide();
            }
        });
        $(order_by_input + ':checked').click();
    },

    load: function() {
        $(document).on('widget-added', (event, widget) => {
            this.postsWidget();
        });
        $(document).on('widget-updated', (event, widget) => {
            this.postsWidget();
        });
    },

}

addEventListener('DOMContentLoaded', function() {
    Object.keys(a4h_widgets).forEach(function(key) {
        a4h_widgets[key]();
    });
});