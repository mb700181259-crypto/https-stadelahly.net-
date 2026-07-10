$ = jQuery;

a4h_edit = {

    repeatable: function() {
        let check_rows = function(target_table) {
            let rows = target_table.find('tbody tr:not(.sample)');
            if ( rows.length < 1 ) {
                target_table.find('thead').addClass('empty');
            } else {
                target_table.find('thead').removeClass('empty');
            }
            rows.each(function(key) {
                $(this).find('td.row-id').html(key+1);
            });
        }
        $('body').on('click', '[data-action="add-row"]', function() {
            let target_table = $(this).parent().parent().find('.table-repeatable');
            let cloned = target_table.find('tr.sample').clone().removeClass('sample');
            target_table.find('tbody').append(cloned);
            check_rows(target_table);
        });
        $('body').on('click', '[data-action="remove-row"]', function() {
            let target_table = $(this).closest('.table-repeatable');
            if ( confirm('هل أنت متأكد من الحذف؟') ) {
                $(this).closest('tr').remove();
                check_rows(target_table);
            }
        });
        $('.table-repeatable tbody').sortable({
            handle: 'td.action:first-child',
            update: function(event, ui) {
                let target_table = $(this).closest('.table-repeatable');
                check_rows(target_table);
            },
        });
    },

    datePicker: function() {
        $('body').on('focus', 'input.datepicker2', function() {
            $(this).datepicker({
                changeYear: true,
                changeMonth: true,
                dateFormat : edit_js_vars.date_input_format,
                showButtonPanel: true,
            });
        });
    },

    quickSearch: function() {
        $('.postbox .inside .categorydiv').prepend('<input type="text" class="quick-search" placeholder="بحث سريع...">');
        $('.quick-search').on('keyup', function () {
            var searchTerm = $(this).val().toLowerCase();
            $(this).closest('.inside').find('ul.categorychecklist li').each(function () {
                var categoryLabel = $(this).text().toLowerCase();
                if ( categoryLabel.includes(searchTerm) ) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    },

}

addEventListener('DOMContentLoaded', function() {

    Object.keys(a4h_edit).forEach(function(key) {
        a4h_edit[key]();
    });

});