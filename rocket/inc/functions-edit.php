<?php

function a4h_admin_edit_enqueue($location) {
	if ( $location != 'post.php' && $location != 'post-new.php' ) return;

    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('admin-edit-js', get_theme_file_uri('assets/js/admin-edit.js'), array(), THEME_VERSION, true);
    wp_enqueue_style('admin-edit-css', get_theme_file_uri('assets/css/admin-edit.css'), array(), THEME_VERSION);
	wp_enqueue_style('admin-checkbox-css', get_theme_file_uri('assets/css/admin-checkbox.css'), array(), THEME_VERSION);
	wp_enqueue_style('jquery-ui-css', get_theme_file_uri('assets/css/jquery-ui.css'), array(), THEME_VERSION);
}
add_action('admin_enqueue_scripts', 'a4h_admin_edit_enqueue');

function a4h_admin_edit_js_vars($location) {
	$js_vars = array();

    $timezone_min = 60 * get_option('gmt_offset');
    $timezone_sign = $timezone_min < 0 ? "-" : "+";
    $timezone_absmin = abs($timezone_min);
    $timezone_str = sprintf("%s%02d:%02d", $timezone_sign, $timezone_absmin/60, $timezone_absmin%60);

	$js_vars['date_input_format'] = 'd M yy';
	$js_vars['timezone_str'] = $timezone_str;
	$js_vars = array_filter($js_vars);
	$js_vars = a4h_filter('edit_js_vars', $js_vars);

	wp_localize_script('jquery', 'edit_js_vars', $js_vars);
}
add_action('admin_enqueue_scripts', 'a4h_admin_edit_js_vars');

function a4h_admin_editor_css() {
    add_editor_style('assets/css/admin-editor.css');
}
add_action('after_setup_theme', 'a4h_admin_editor_css');

function a4h_admin_editor_js($plugin_array) {
	$plugin_array['a4h_editor_tools'] = get_theme_file_uri('assets/js/admin-editor.js');
	return $plugin_array;
}
add_filter('mce_external_plugins', 'a4h_admin_editor_js');

function a4h_admin_editor_buttons($buttons) {
	$buttons[] = 'shortcodes';
	return $buttons;
}
add_filter('mce_buttons', 'a4h_admin_editor_buttons');

function a4h_admin_post_edit_meta_boxes() {
    $post_types = get_post_types(array('exclude_from_search' => 0));
	$screens = a4h_filter('post_edit_meta_boxes_post_types', $post_types);

	foreach ( $screens as $screen ) {
        if ( current_user_can(a4h_filter('capability_to_edit_post_settings', 'edit_posts')) ) {
		    add_meta_box(THEME_VAR.'-settings', THEME_NAME.': إعدادات الصفحة', 'a4h_admin_post_edit_meta_box_settings', $screen, 'normal');
        }
        if ( current_user_can(a4h_filter('capability_to_edit_post_questions', 'edit_posts')) ) {
            add_meta_box(THEME_VAR.'-questions', THEME_NAME.': الأسئلة والأجوبة', 'a4h_admin_post_edit_meta_box_questions', $screen, 'normal');
        }
        if ( current_user_can(a4h_filter('capability_to_edit_post_citations', 'edit_posts')) ) {
            add_meta_box(THEME_VAR.'-citations', THEME_NAME.': المراجع', 'a4h_admin_post_edit_meta_box_citations', $screen, 'normal');
        }
	}
	add_action('save_post', 'a4h_admin_post_edit_save');
}
add_action('load-post.php', 'a4h_admin_post_edit_meta_boxes');
add_action('load-post-new.php', 'a4h_admin_post_edit_meta_boxes');

function a4h_admin_post_edit_save($post_id) {
    if ( !current_user_can('edit_post', $post_id) ) return;
	if ( !isset($_POST['a4h_options_nonce']) || !wp_verify_nonce($_POST['a4h_options_nonce'], 'a4h_options_nonce') ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
	
	$fields = a4h_filter('post_edit_custom_fields_names', array('disable_ads', 'hide_featured_image', 'short_title', 'secondary_title', 'video_url'));
	foreach ( $fields as $field ) {
		if ( !empty($_POST[$field]) ) {
			update_post_meta($post_id, $field, strip_tags($_POST[$field]));
		} else {
			delete_post_meta($post_id, $field);
		}
	}

    if ( !empty($_POST['question_title']) ) {
        $questions = array();
        for ( $i = 0; $i <= sizeof($_POST['question_title']); $i++ ) {
            if ( empty($_POST['question_title'][$i]) ) continue;
            $questions[] = array(
                'title' => !empty($_POST['question_title'][$i]) ? wp_kses_post($_POST['question_title'][$i]) : '',
                'answer' => !empty($_POST['question_answer'][$i]) ? wp_kses_post($_POST['question_answer'][$i]) : '',
            );
        }
        if ( $questions ) {
            update_post_meta($post_id, 'questions', $questions);
        } else {
            delete_post_meta($post_id, 'questions');
        }
	}
    
    if ( !empty($_POST['citation_name']) ) {
        $citations = array();
        for ( $i = 0; $i <= sizeof($_POST['citation_name']); $i++ ) {
            if ( empty($_POST['citation_name'][$i]) ) continue;
            $citations[] = array(
                'name' => !empty($_POST['citation_name'][$i]) ? strip_tags($_POST['citation_name'][$i]) : '',
                'title' => !empty($_POST['citation_title'][$i]) ? strip_tags($_POST['citation_title'][$i]) : '',
                'url' => !empty($_POST['citation_url'][$i]) ? strip_tags($_POST['citation_url'][$i]) : '',
                'date' => !empty($_POST['citation_date'][$i]) ? strip_tags($_POST['citation_date'][$i]) : '',
            );
        }
        if ( $citations ) {
            update_post_meta($post_id, 'citations', $citations);
        } else {
            delete_post_meta($post_id, 'citations');
        }
	}
}

function a4h_admin_post_edit_meta_box_settings($post) {
	?>
	<?php wp_nonce_field('a4h_options_nonce', 'a4h_options_nonce'); ?>

    <p>
        <span class="settings-checkbox">
            <input type="checkbox" name="disable_ads" value="1" <?php checked(1, get_post_meta($post->ID, 'disable_ads', true)); ?> />
        </span>
        <label>إخفاء الإعلانات من هذه الصفحة</label>
    </p>
    <p>
        <span class="settings-checkbox">
            <input type="checkbox" name="hide_featured_image" value="1" <?php checked(1, get_post_meta($post->ID, 'hide_featured_image', true)); ?> />
        </span>
        <label>إخفاء الصورة البارزة من هذه الصفحة</label>
    </p>
    <p>
		<label>العنوان المختصر</label>
		<input type="text" name="short_title" class="widefat" value="<?php echo get_post_meta($post->ID, 'short_title', true); ?>" />
        <label>يظهر بدلا من العنوان الرئيسي في قائمة المقالات</label>
	</p>
	<p>
		<label>العنوان الثانوي</label>
		<input type="text" name="secondary_title" class="widefat" value="<?php echo get_post_meta($post->ID, 'secondary_title', true); ?>" />
        <label>يظهر أسفل العنوان الرئيسي في صفحة المقال</label>
	</p>
    <p>
		<label>رابط الفيديو</label>
		<input type="url" name="video_url" class="widefat" value="<?php echo get_post_meta($post->ID, 'video_url', true); ?>" />
        <label>يظهر بدلا من الصورة البارزة في الصفحة</label>
	</p>
    <?php a4h_hook('post_edit_custom_fields_extra', $post->ID); ?>
	<?php
}

function a4h_admin_post_edit_meta_box_questions($post) {
    $actions = '<div class="actions">
        <button type="button" class="button" data-action="remove-row" title="حذف"><span class="dashicons dashicons-remove"></span></button>
    </div>';
	?>
	<?php wp_nonce_field('a4h_options_nonce', 'a4h_options_nonce'); ?>
    <?php
        $questions = get_post_meta($post->ID, 'questions', true);  
    ?>
    <table class="questions-table table-repeatable">
        <thead class="<?php echo $questions ? '' : 'empty'; ?>">
            <tr>
                <th class="action">#</th>
                <th>السؤال</th>
                <th>الإجابة</th>
                <th class="action"></th>
            </tr>
        </thead>
        <tbody>
            <tr class="sample">
                <td class="action row-id" title="تحريك"></td>
                <td><input type="text" name="question_title[]" class="widefat" value="" /></td>
                <td><textarea rows="3" name="question_answer[]" class="widefat"></textarea></td>
                <td class="action">
                    <?php echo $actions; ?>
                </td>
            </tr>
            <?php if ( $questions ) { ?>
                <?php foreach ( $questions as $key => $value ) { ?>
                    <tr>
                        <td class="action row-id" title="تحريك"><?php echo $key+1; ?></td>
                        <td><input type="text" name="question_title[]" class="widefat" value="<?php echo !empty($value['title']) ? $value['title'] : ''; ?>" /></td>
                        <td><textarea rows="3" name="question_answer[]" class="widefat"><?php echo !empty($value['answer']) ? $value['answer'] : ''; ?></textarea></td>
                        <td class="action">
                            <?php echo $actions; ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
	<p>
        <input type="button" class="button button-primary button-large" data-action="add-row" value="أضف سؤال">
	</p>
	<?php
}

function a4h_admin_post_edit_meta_box_citations($post) {
    $actions = '<div class="actions">
        <button type="button" class="button" data-action="remove-row" title="حذف"><span class="dashicons dashicons-remove"></span></button>
    </div>';
	?>
	<?php wp_nonce_field('a4h_options_nonce', 'a4h_options_nonce'); ?>
    <?php
        $citations = get_post_meta($post->ID, 'citations', true);  
    ?>
    <table class="citations-table table-repeatable">
        <thead class="<?php echo $citations ? '' : 'empty'; ?>">
            <tr>
                <th class="action">#</th>
                <th>اسم المرجع <span class="asterisk">*</span></th>
                <th>عنوان صفحة المرجع</th>
                <th>رابط صفحة المرجع</th>
                <th>التاريخ</th>
                <th class="action"></th>
            </tr>
        </thead>
        <tbody>
            <tr class="sample">
                <td class="action row-id" title="تحريك"></td>
                <td><input type="text" name="citation_name[]" class="widefat" value="" /></td>
                <td><input type="text" name="citation_title[]" class="widefat" value="" /></td>
                <td><input type="text" name="citation_url[]" class="widefat" value="" /></td>
                <td><input type="text" name="citation_date[]" class="widefat datepicker2" autocomplete="off" value="" /></td>
                <td class="action">
                    <?php echo $actions; ?>
                </td>
            </tr>
            <?php if ( $citations ) { ?>
                <?php foreach ( $citations as $key => $value ) { ?>
                    <tr>
                        <td class="action row-id" title="تحريك"><?php echo $key+1; ?></td>
                        <td><input type="text" name="citation_name[]" class="widefat" value="<?php echo !empty($value['name']) ? $value['name'] : ''; ?>" /></td>
                        <td><input type="text" name="citation_title[]" class="widefat" value="<?php echo !empty($value['title']) ? $value['title'] : ''; ?>" /></td>
                        <td><input type="text" name="citation_url[]" class="widefat" value="<?php echo !empty($value['url']) ? $value['url'] : ''; ?>" /></td>
                        <td><input type="text" name="citation_date[]" class="widefat datepicker2" autocomplete="off" value="<?php echo !empty($value['date']) ? $value['date'] : ''; ?>" /></td>
                        <td class="action">
                            <?php echo $actions; ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
	<p>
        <input type="button" class="button button-primary button-large" data-action="add-row" value="أضف مرجع">
	</p>
	<?php
}