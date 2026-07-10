(function() {
    tinymce.PluginManager.add('a4h_editor_tools', function(editor) {

        editor.addButton('shortcodes', {
            type: 'listbox',
            text: 'أكواد مختصرة (shortcodes)',
            values: [
                { text: 'فاصل المحتوى', value: '<!--content_sep-->' },
                { text: 'صفحة جديدة', value: '<!--nextpage-->' },
                { text: 'رقم مرجع', value: 'citation' },
                { text: 'عداد زمني', value: 'timer' },
                { text: 'سؤال وإجابة', value: 'question_answer' },
            ],
            onselect: function(e) {
                if ( this.value() == 'citation' ) {
                    editor.windowManager.open({
                        body: [
                            {
                                type: 'textbox',
                                name: 'id',
                                label: 'رقم المرجع',
                            },
                        ],
                        onsubmit: function(e) {
                            if ( !/^\d+$/.test(e.data.id) ) {
                                alert('أرقام فقط');
                                return false;
                            }
                            let content = '[citation id=' + e.data.id + ']';
                            editor.insertContent(content);
                        }
                    });
                } else if ( this.value() == 'timer' ) {
                    editor.windowManager.open({
                        title: 'عداد زمني يحسب الوقت المتبقي على حدث',
                        body: [
                            {
                                type: 'textbox',
                                name: 'time',
                                label: 'موعد الحدث',
                                classes: 'timeinputField',
                            },
                            {
                                type: 'textbox',
                                name: 'title',
                                label: 'عنوان الحدث',
                            },
                          ],
                        onsubmit: function(e) {
                            let content = '[timer time="' + e.data.time + edit_js_vars.timezone_str + '" title="' + e.data.title + '"]';
                            editor.insertContent(content);
                        },
                    });
                } else if ( this.value() == 'question_answer' ) {
                    editor.windowManager.open({
                        title: 'سؤال وإجابة داخل المحتوى',
                        width: 500,
                        height: 200,
                        body: [
                            {
                                type: 'textbox',
                                name: 'question',
                                label: 'السؤال',
                            },
                            {
                                type: 'textbox',
                                name: 'answer',
                                label: 'الإجابة',
                                multiline: true,
                                minHeight: 100,
                            },
                          ],
                        onsubmit: function(e) {
                            let question = e.data.question;
                            let answer = e.data.answer.replace(/\n/g, '<br>');
                            let content = '[question question="' + question + '"]' + answer + '[/question]';
                            editor.insertContent(content);
                        },
                    });
                } else {
                    editor.insertContent(this.value());
                }
                
                jQuery(function() {
                    let now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    now = now.toISOString().slice(0,16);
                    jQuery('.mce-timeinputField').attr('type', 'datetime-local').val(now);
                });
            },
        });

        editor.on('BeforeSetContent', function (event) {
            event.content = event.content.replace(/<!--content_sep-->/g, '<img class="content-break" title="" alt="" data-mce-placeholder="1">');
            event.content = event.content.replace(/\[citation\s*id=(\d+)\]/g, '<a class="citation-id">[مرجع $1]</a>');
        });
        
        editor.on('PostProcess', function (event) {
            if ( event.get ) {
                event.content = event.content.replace(/<img class="content-break"(?:[^>]*)title=""(?:[^>]*)alt=""(?:[^>]*)data-mce-placeholder="1"(?:[^>]*)>/g, '<!--content_sep-->');
                event.content = event.content.replace(/<a class="citation-id">\[مرجع (\d+)]<\/a>/g, '[citation id=$1]');
            }
        });             

    });
})();