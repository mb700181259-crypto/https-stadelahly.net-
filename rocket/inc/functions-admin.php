<?php

function a4h_admin_css() {
	?>
	<style>
	#adminmenu li.menu-top[class*=toplevel_page_rocket] img { max-width: 25px; margin-top: -4px; opacity: 1 !important; }
	.a4h-admin-notes-to-members { max-width: 700px; margin: 4em auto; padding: 1em; background: #fbf8f1; border: 2px solid rgba(0,0,0,0.2); border-radius: 0.3em; }
	.a4h-admin-notes-to-members pre { margin: 0px; padding: 0px; font-size: 16px; font-weight: bold; font-family: arial; line-height: 1.5; overflow-wrap: break-word; word-wrap: break-word; white-space: break-spaces; }
	.a4h-admin-section { max-width: 900px; margin-bottom: 2em; padding: 2em; background: #FFFFFF; border: 3px solid #be2ebc; }
	.a4h-admin-section .form-table { margin: 0px !important; }
    .term-image-wrap .image-preview img { width: auto; height: auto; max-height: 100px; padding: 5px; border: 2px solid #DDDDDD; background: repeating-linear-gradient(45deg, #FFFFFF, #FFFFFF 5px, #CCCCCC 5px, #CCCCCC 10px); }
	</style>
	<?php
}
add_action('admin_head', 'a4h_admin_css');

function a4h_admin_bar_links() {
    if ( !current_user_can('edit_theme_options') ) return;
    global $wp_admin_bar;
	$wp_admin_bar->add_menu(array('id' => THEME_VAR_OPTIONS, 'title' => '<span style="padding: 7px 15px; background: #6A1768; color: #FFFFFF;">'.THEME_NAME.'</span>', 'href' => admin_url('admin.php?page='.THEME_VAR_OPTIONS)));
	$wp_admin_bar->add_menu(array('parent' => THEME_VAR_OPTIONS, 'id' => 'link30', 'title' => 'الدعم الفني', 'href' => SUPPORT_LINK, 'meta' => array('target' => '_blank')));
}
add_action('admin_bar_menu', 'a4h_admin_bar_links', 99999);

function a4h_admin_page_scripts() {
	wp_enqueue_media();
	wp_enqueue_style('wp-color-picker');
	wp_enqueue_style('admin-loader-css', get_theme_file_uri('assets/css/admin-loader.css'), '', THEME_VERSION);
	wp_enqueue_style('admin-checkbox-css', get_theme_file_uri('assets/css/admin-checkbox.css'), '', THEME_VERSION);
	wp_enqueue_style('admin-pages-css', get_theme_file_uri('assets/css/admin-pages.css'), '', THEME_VERSION);
	wp_enqueue_script('wp-color-picker');
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script('jquery-form');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('media-upload');
	wp_enqueue_script('admin-pages-js', get_theme_file_uri('assets/js/admin-pages.js'), array('jquery'), THEME_VERSION, true);
	wp_localize_script('jquery', 'a4h_ce_settings', array('css' => wp_enqueue_code_editor(array('type' => 'text/css')), 'js' => wp_enqueue_code_editor(array('type' => 'application/javascript'))));
}

function a4h_admin_page_scripts_extra() {
    $current_screen = get_current_screen();
    $pages_slugs = array(THEME_VAR_OPTIONS, THEME_VAR_ADS, THEME_VAR_TOOLS);

    foreach ( $pages_slugs as $slug ) {
        if ( strpos($current_screen->id, $slug) !== false ) {
            wp_dequeue_style('jquery-ui-style');
            wp_deregister_style('jquery-ui-style');
            break;
        }
    }  
}
add_action('admin_enqueue_scripts', 'a4h_admin_page_scripts_extra', 100);

function a4h_admin_options_sanitize_callback($options) {
	if ( empty($options) ) return;
	foreach ( $options as &$value ) {
		if ( is_array($value) ) {
			  $value = a4h_admin_options_sanitize_callback($value); 
		}
	}
	return array_filter($options); 
}

function a4h_admin_page_fields($field_type, $field_name, $field_title, $args = array()) {

	$option_group = $_GET['page'];

	if ( $option_group == THEME_VAR_OPTIONS ) {
		$function_name = 'a4h_options';
	}
	if ( $option_group == THEME_VAR_ADS ) {
		$function_name = 'a4h_ads';
	}
	if ( $option_group == THEME_VAR_TOOLS ) {
		$function_name = 'a4h_tools';
	}

	$name = sprintf('%s[%s]', $option_group, str_replace('>', '][', $field_name));
	$value = call_user_func($function_name, $field_name);

	$rules_name = $option_group.'['.str_replace('>', '][', $field_name).'_rules]';
	$rules_value = call_user_func($function_name, $field_name.'_rules');

	$container_class = !empty($args['container_class']) ? $args['container_class'] : '';
	$class = !empty($args['class']) ? $args['class'] : '';
	$placeholder = !empty($args['placeholder']) ? $args['placeholder'] : '';
	$min = !empty($args['min']) ? $args['min'] : 0;
	$max = !empty($args['max']) ? $args['max'] : '';
	$step = !empty($args['step']) ? $args['step'] : '';
	$rows = !empty($args['rows']) ? $args['rows'] : 10;
	$prepend = !empty($args['prepend']) ? '<span class="a4h-admin-page-field-prepend">'.$args['prepend'].'</span>' : '';
	$append = !empty($args['append']) ? '<span class="a4h-admin-page-field-append">'.$args['append'].'</span>' : '';
	$notes = !empty($args['notes']) ? '<div class="a4h-admin-page-field-notes">'.$args['notes'].'</div>' : '';
	$options = !empty($args['options']) ? $args['options'] : array();
	$columns = !empty($args['columns']) ? $args['columns'] : array();
	$insertions = !empty($args['insertions']) ? $args['insertions'] : '';
	$add_row_label = !empty($args['add_row_label']) ? $args['add_row_label'] : 'إضافة';

	?>
	<div class="a4h-admin-page-field" data-field_type="<?php echo $field_type; ?>">
		<?php if ( $field_title && $field_type != 'checkbox' && $field_type != 'checkbox_with_rules' ) { ?>
			<div class="a4h-admin-page-field-title"><?php echo $field_title; ?></div>
		<?php } ?>
			<div class="a4h-admin-page-field-content">
				<?php echo $prepend; ?>
				<?php #### fields start #### ?>

				<?php if ( $field_type == 'text' ) { ?>
					<input type="text" class="widefat <?php echo $class; ?>" name="<?php echo $name; ?>" value="<?php echo $value; ?>" placeholder="<?php echo $placeholder; ?>">
				<?php } ?>

				<?php if ( $field_type == 'checkbox' ) { ?>
					<div class="a4h-admin-page-field-group">
						<span class="settings-checkbox">
							<input <?php checked(1, $value); ?> type="checkbox" name="<?php echo $name; ?>" value="1">
						</span>
						<div class="settings-checkbox-title">
							<?php if ( $field_title ) { ?>
								<?php echo $field_title; ?>
							<?php } ?>
						</div>
					</div>
				<?php } ?>

				<?php if ( $field_type == 'number' ) { ?>
					<input type="number" class="<?php echo $class; ?>" name="<?php echo $name; ?>" value="<?php echo $value; ?>" step="<?php echo $step; ?>" min="<?php echo $min; ?>" max="<?php echo $max; ?>">
				<?php } ?>

				<?php if ( $field_type == 'checkbox_with_rules' ) { ?>
					<div class="a4h-admin-page-field-group">
						<span class="settings-checkbox">
							<input <?php checked(1, $value); ?> type="checkbox" name="<?php echo $name; ?>" value="1">
						</span>
						<div class="settings-checkbox-title">
							<?php if ( $field_title ) { ?>
								<?php echo $field_title; ?>
							<?php } ?>
						</div>
						<span class="settings-with-label">
						كود شرط الظهور <input class="widefat ltr" type="text" type="checkbox" name="<?php echo $rules_name; ?>" value="<?php echo $rules_value; ?>">
						</span>
					</div>
				<?php } ?>

				<?php if ( $field_type == 'radio' ) { ?>
					<span class="settings-radio">
					<?php foreach ( $options as $option_value => $option_label ) { ?>
						<label class="label-for-radio label-for-radio_<?php echo $option_value; ?>"><input <?php checked($option_value, $value); ?> type="radio" name="<?php echo $name; ?>" value="<?php echo $option_value; ?>"><span><?php echo $option_label; ?></span></label>
						<?php } ?>
					</span>
				<?php } ?>

				<?php if ( $field_type == 'color' ) { ?>
					<input type="text" class="widefat <?php echo $class; ?> settings-color" name="<?php echo $name; ?>" value="<?php echo $value; ?>" placeholder="<?php echo $placeholder; ?>">
				<?php } ?>

				<?php if ( $field_type == 'textarea' ) { ?>
					<textarea class="widefat <?php echo $class; ?>" name="<?php echo $name; ?>" rows="<?php echo $rows; ?>" placeholder="<?php echo $placeholder; ?>"><?php echo $value; ?></textarea>
				<?php } ?>

				<?php if ( $field_type == 'select' ) { ?>
					<select class="widefat <?php echo $class; ?>" name="<?php echo $name; ?>">
						<?php foreach ( $options as $option_value => $option_label ) { ?>
							<option <?php selected($option_value, $value); ?> value="<?php echo $option_value; ?>"><?php echo $option_label; ?></option>
						<?php } ?>
					</select>
				<?php } ?>

				<?php if ( $field_type == 'image' ) { ?>
					<div class="image-preview<?php if ( !$value ) { echo ' hidden'; } ?>">
						<img src="<?php if ( $value ) { echo wp_get_attachment_image_url($value, 'full'); } ?>">
						<p><a href="#" class="button button-link-delete remove-image">حذف الصورة</a></p>
					</div>
					<p><a href="#" class="button upload-image">اختيار صورة</a></p>
					<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
				<?php } ?>

				<?php if ( $field_type == 'site_icon' ) { ?>
					<?php $site_icon = get_option('site_icon'); ?>
					<div class="image-preview<?php if ( !$site_icon ) { echo ' hidden'; } ?>">
						<img src="<?php if ( $site_icon ) { echo wp_get_attachment_image_url($site_icon, 'full'); } ?>">
					</div>
					<p><a target="_blank" href="<?php echo admin_url('options-general.php'); ?>" class="button">اختيار صورة</a></p>
				<?php } ?>

				<?php if ( $field_type == 'repeater' ) { ?>
					<div class="a4h-admin-page-repeater-items" data-name="<?php echo $field_name; ?>">
						<div class="a4h-admin-page-repeater-item sample">
							<div class="a4h-admin-page-repeater-item-overlay"></div>
							<div class="a4h-admin-page-repeater-item-content">
								<?php a4h_admin_page_fields_repeater($field_name, 'sample', $args); ?>
							</div>
							<div class="a4h-admin-page-repeater-item-meta">
								<div class="a4h-admin-page-repeater-item-tools">
									<span class="a4h-admin-page-repeater-item-id"></span>
									<a class="a4h-admin-page-repeater-item-toggle" href="#" title="فتح/إغلاق"><span class="dashicons dashicons-arrow-down-alt2"></span></a>
									<a class="a4h-admin-page-repeater-item-move" href="#" title="نقل"><span class="dashicons dashicons-move"></span></a>
									<a class="a4h-admin-page-repeater-item-delete" href="#" title="حذف"><span class="dashicons dashicons-trash"></span></a>
								</div>
							</div>
						</div>
						<?php if ( $value ) { ?>
							<?php foreach ( $value as $item_key => $item_value ) { ?>
								<?php if ( $item_key == 'sample' ) continue; ?>
								<div class="a4h-admin-page-repeater-item" data-container="<?php echo $field_name; ?>">
									<div class="a4h-admin-page-repeater-item-overlay"></div>
									<div class="a4h-admin-page-repeater-item-content">
										<?php a4h_admin_page_fields_repeater($field_name, $item_key, $args); ?>
									</div>
									<div class="a4h-admin-page-repeater-item-meta">
										<div class="a4h-admin-page-repeater-item-tools">
											<span class="a4h-admin-page-repeater-item-id"></span>
											<a class="a4h-admin-page-repeater-item-toggle" href="#" title="فتح/إغلاق"><span class="dashicons dashicons-arrow-down-alt2"></span></a>
											<a class="a4h-admin-page-repeater-item-move" href="#" title="نقل"><span class="dashicons dashicons-move"></span></a>
											<a class="a4h-admin-page-repeater-item-delete" href="#" title="حذف"><span class="dashicons dashicons-trash"></span></a>
										</div>
									</div>
								</div>
							<?php } ?>
						<?php } ?>
					</div>
					<a href="#" class="a4h-admin-page-repeater-item-add button-secondary"><?php echo $add_row_label; ?></a>
				<?php } ?>

				<?php if ( $field_type == 'layout_builder' ) { ?>
					<div class="a4h-admin-page-layout-builder-row" data-insertions="<?php echo $insertions; ?>">
						<div class="a4h-admin-page-layout-builder-row-options">
							<?php if ( !empty($args['allow_theme']) && $args['allow_theme'] == 1 ) { ?>
								<div class="a4h-admin-page-layout-builder-row-theme">
									<?php
									$theme_name = $name.'[theme]';
									$theme_value = !empty($value['theme']) ? $value['theme'] : '';
									?>
									الثيم <select class="sm-input" name="<?php echo $theme_name; ?>"><option value="">------</option><option value="light" <?php selected('light', $theme_value); ?>>فاتح</option><option value="dark" <?php selected('dark', $theme_value); ?>>غامق</option></select>
								</div>
							<?php } ?>
							<div class="a4h-admin-page-layout-builder-row-css">
								<?php
									$css_name = $name.'[css]';
									$css_value = !empty($value['css']) ? $value['css'] : '';
								?>
								CSS class <input class="sm-input ltr" type="text" name="<?php echo $css_name; ?>" value="<?php echo $css_value; ?>">
							</div>
						</div>
						<div class="a4h-admin-page-layout-builder-row-title">عناصر الصف</div>
						<div class="a4h-admin-page-layout-builder-row-content">
							<?php
								$insertions = a4h_admin_layout_builder_insertions();
								$insertions = array_merge(...array_values($insertions));
								$columns_icons = array('start' => 'right', 'middle' => 'center', 'end' => 'left');
								array_walk($columns_icons, function(&$value, $key) {
									$value = sprintf('<div class="a4h-admin-page-layout-builder-column-position position-%s"><span class="dashicons dashicons-editor-align%s"></span></div>', $value, $value);
								});
							?>
							<?php foreach ( array('start', 'middle', 'end') as $column ) { ?>
								<?php
									$column_name = $name.sprintf('[%s]', $column);;
									$column_value = !empty($value[$column]) ? $value[$column] : '';
								?>
								<div class="a4h-admin-page-layout-builder-column">
									<?php if ( in_array($column, $columns) ) { ?>
										<?php echo $columns_icons[$column]; ?>
										<div class="a4h-admin-page-layout-builder-column-items">
											<?php
												if ( $column_value ) {
													$column_values = explode(',', $column_value);
													foreach ( $column_values as $single_value ) {
														$item_name = !empty($insertions[$single_value]) ? $insertions[$single_value] : '[غير موجود]';
														printf('<div class="a4h-admin-page-layout-builder-column-item" data-name="%s"><span class="a4h-admin-page-layout-builder-column-item-remove"><span class="dashicons dashicons-trash" title="حذف"></span></span>%s</div>', $single_value, $item_name);
													}
												}
											?>
										</div>
										<input class="a4h-admin-page-layout-builder-column-value" type="hidden" name="<?php echo $column_name; ?>" value="<?php echo $column_value; ?>">
										<a href="#" class="a4h-admin-page-layout-builder-column-insert" title="إضافة عنصر"><span class="dashicons dashicons-plus"></span></a>
									<?php } ?>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>

				<?php if ( $field_type == 'ad' ) { ?>
					<div class="a4h-admin-page-ad-item">
						<div class="a4h-admin-page-ad-options">
							<div>
								<?php
									$label_name = $name.'[label]';
									$label_value = !empty($value['label']) ? $value['label'] : '';
								?>
								وصف الإعلان <input type="text" class="widefat" name="<?php echo $label_name; ?>" value="<?php echo $label_value; ?>">
							</div>
							<div>
								<?php
									$hide_name = $name.'[hide]';
									$hide_value = !empty($value['hide']) ? $value['hide'] : '';
								?>
								إخفاء على <select class="sm-input" name="<?php echo $hide_name; ?>"><option value="">------</option><option value="mobile" <?php selected('mobile', $hide_value); ?>>الموبايل</option><option value="desktop" <?php selected('desktop', $hide_value); ?>>الديسكتوب</option></select>
							</div>
							<div>
								<?php
									$rules_name = $name.'[rules]';
									$rules_value = !empty($value['rules']) ? $value['rules'] : '';
								?>
								كود شرط الظهور <input class="sm-input ltr" type="text" name="<?php echo $rules_name; ?>" value="<?php echo $rules_value; ?>">
							</div>
							<div>
								<?php
									$css_name = $name.'[css]';
									$css_value = !empty($value['css']) ? $value['css'] : '';
								?>
								CSS class <input class="sm-input ltr" type="text" name="<?php echo $css_name; ?>" value="<?php echo $css_value; ?>">
							</div>
							<div>
								<?php
									$force_name = $name.'[force]';
									$force_value = !empty($value['force']) ? $value['force'] : '';
								?>
								<span class="settings-checkbox">
									<input <?php checked(1, $force_value); ?> type="checkbox" name="<?php echo $force_name; ?>" value="1"> إجبار على الظهور
								</span>
							</div>
						</div>
						<div class="a4h-admin-page-ad-content">
							<div>
								<?php
									$status_name = $name.'[status]';
									$status_value = !empty($value['status']) ? $value['status'] : '';
								?>
								<span class="settings-checkbox ad-status-checkbox">
									<input <?php checked(1, $status_value); ?> type="checkbox" name="<?php echo $status_name; ?>" value="1"> تفعيل
								</span>
							</div>
							<?php
								$ads_location_name = explode('>', $field_name);
								$ads_location_type = reset($ads_location_name);
							?>
							<?php if ( $ads_location_type == 'shortcode_ads' ) { ?>
								<p>الكود المختصر</p>
								<div>
									<?php
										$shortcode_name = $name.'[shortcode]';
										$shortcode_value = !empty($value['shortcode']) ? $value['shortcode'] : '';
									?>
									<input type="text" class="widefat sm-input ltr shortcode-input-field" name="<?php echo $shortcode_name; ?>" value="<?php echo $shortcode_value; ?>">
								</div>
							<?php } else { ?>
								<p>مكان الإعلان</p>
								<div>
									<?php
										$location_name = $name.'[location]';
										$location_value = !empty($value['location']) ? $value['location'] : '';
									?>
								</div>
								<select class="sm-input" name="<?php echo $location_name; ?>">
									<?php
										$ads_locations = a4h_admin_page_ads_locations(str_replace('locations_ads_', '', $ads_location_type));
										foreach ( $ads_locations as $ad_location_name => $ad_location_title ) {
                                            if ( strpos($ad_location_name, '_group_start_') !== false ) {
                                                echo '<optgroup label="'.$ad_location_title.'">';
                                            } else if ( strpos($ad_location_name, '_group_end_') !== false ) {
                                                echo '</optgroup">';
                                            } else {
											    echo '<option value="'.$ad_location_name.'" '.selected($ad_location_name, $location_value, false).'>'.$ad_location_title.'</options>';
                                            }
										}
									?>
								</select>
							<?php } ?>
							<p>كود الإعلان</p>
							<?php
								$code_name = $name.'[code]';
								$code_value = !empty($value['code']) ? $value['code'] : '';
							?>
							<textarea class="widefat code-input" name="<?php echo $code_name; ?>" rows="5" placeholder=""><?php echo $code_value; ?></textarea>
                            <p>
                                <a href="#" class="a4h-admin-page-layout-builder-column-insert button-secondary">إدخال كود جاهز</a>
                            </p>
						</div>
					</div>
				<?php } ?>

				<?php if ( $field_type == 'import_settings' ) { ?>
					<?php wp_nonce_field('import_nonce', 'import_nonce'); ?>
					<input type="file" name="import_settings">
				<?php } ?>

				<?php if ( $field_type == 'export_settings' ) { ?>
					<p>
						<a href="<?php echo wp_nonce_url(admin_url('admin.php?page='.THEME_VAR_TOOLS.'&action=export_options'), 'export_settings', 'nonce'); ?>" class="button">تصدير إعدادات القالب</a>
					</p>
					<p>
						<a href="<?php echo wp_nonce_url(admin_url('admin.php?page='.THEME_VAR_TOOLS.'&action=export_ads'), 'export_settings', 'nonce'); ?>" class="button">تصدير إعدادات الإعلانات</a>
					</p>
					<p>
						<a href="<?php echo wp_nonce_url(admin_url('admin.php?page='.THEME_VAR_TOOLS.'&action=export_widgets'), 'export_settings', 'nonce'); ?>" class="button">تصدير إعدادات الودجات</a>
					</p>
				<?php } ?>

				<?php if ( $field_type == 'reset_settings' ) { ?>
					<?php wp_nonce_field('reset_nonce', 'reset_nonce'); ?>
					<p>
						<span class="settings-checkbox">
							<input type="checkbox" name="reset_options"> <span class="a4h-admin-page-field-append">استعادة إعدادات القالب</span>
						</span>
					</p>
					<p>
						<span class="settings-checkbox">
							<input type="checkbox" name="reset_ads"> <span class="a4h-admin-page-field-append">استعادة إعدادات الإعلانات</span>
						</span>
					</p>
					<p>
						<span class="settings-checkbox">
							<input type="checkbox" name="reset_widgets"> <span class="a4h-admin-page-field-append">استعادة إعدادات الودجات</span>
						</span>
					</p>
				<?php } ?>

				<?php #### fields end #### ?>
				<?php echo $append; ?>
				<?php echo $notes; ?>
			</div>
		</div>
	<?php
}

function a4h_admin_page_fields_repeater($repeater_name, $field_name, $args) {
	foreach ( $args['fields'] as $field ) {
		a4h_admin_page_fields(
			$field[0],
			!empty($field[1]) ? sprintf('%s>%s>%s', $repeater_name, $field_name, $field[1]) : sprintf('%s>%s', $repeater_name, $field_name),
			!empty($field[2]) ? $field[2] : '',
			!empty($field[3]) ? $field[3] : '',
		);
	}
}

function a4h_admin_page_fields_insertions_output() {
	?>
	<div class="a4h-admin-page-layout-builder-overlay">
		<div class="a4h-admin-page-layout-builder-overlay-inner">
			<div class="a4h-admin-page-layout-builder-overlay-title">اختر عنصر للإضافة للمكان المختار</div>
			<div class="a4h-admin-page-layout-builder-insertions">
				<?php
					$insertions = a4h_admin_layout_builder_insertions();
					$new_insertions = array();
					foreach ( $insertions as $insertion_group => $insertion_items ) {
						echo '<div class="a4h-admin-page-layout-builder-insertion-group" data-name="'.$insertion_group.'">';
						if ( $insertion_group == 'misc' ) {
							echo '<strong>عناصر متنوعة</strong>';
						}
						if ( $insertion_group == 'menus' ) {
							echo '<strong>قوائم تصفح</strong>';
						}
                        if ( $insertion_group == 'adsense' ) {
							echo '<strong>شفرات أدسنس جاهزة</strong>';
						}
                        if ( $insertion_group == 'widgets_list' ) {
							echo '<strong>قوائم ودجات (داخل المحتوى أو الفقرات)</strong>';
						}
						if ( $insertion_group == 'options_blocks' || $insertion_group == 'ads_blocks' ) {
							echo '<strong>بلوكات</strong>';
						}
						foreach ( $insertion_items as $insertion_name => $insertion_label ) {
							echo '<div class="a4h-admin-page-layout-builder-insertion" data-name="'.$insertion_name.'">'.$insertion_label.'</div>';
							$new_insertions[$insertion_name] = $insertion_label;
						}
						echo '</div>';
					}
				?>
				<a href="#" class="a4h-admin-page-layout-builder-overlay-cancel"><span class="dashicons dashicons-no"></span></a>
			</div>
		</div>
	</div>
	<?php
}

function a4h_admin_google_analytics_profile_fields_add($user) {
	if ( !a4h_options('enable_google_analytics_for_members') ) return;
	?>
	<div id="a4h-admin-section_ganalytics" class="a4h-admin-section">
		<h3>Google Analytics</h3>
		<table class="form-table">
			<tr>
				<th><label for="ganalytics">Google Analytics Tracking ID</label></th>
				<td>
					<input type="text" name="ganalytics" id="ganalytics" value="<?php echo esc_attr(get_the_author_meta('ganalytics', $user->ID)); ?>" class="regular-text" style="text-align: left; direction: ltr;" /><br />
					<span class="description">ادخل الرقم التعريفي للتتبع الخاص بحسابك على جوجل انلايتيكس لكي تتمكن من معرفة نشاط الزوار في مواضيعك.</span>
				</td>
			</tr>
		</table>
	</div>
	<?php
}
add_action('show_user_profile', 'a4h_admin_google_analytics_profile_fields_add');
add_action('edit_user_profile', 'a4h_admin_google_analytics_profile_fields_add');

function a4h_admin_google_analytics_profile_fields_update($user_id) {
	if ( !a4h_options('enable_google_analytics_for_members') ) return;
	if ( !current_user_can('edit_user', $user_id) ) return;

    update_user_meta($user_id, 'ganalytics', trim(preg_replace("/[^\w-]/", "", $_POST['ganalytics'])));

}
add_action('personal_options_update', 'a4h_admin_google_analytics_profile_fields_update');
add_action('edit_user_profile_update', 'a4h_admin_google_analytics_profile_fields_update');

function a4h_admin_users_custom_columns_title($column) {
	$post_types = get_post_types(array('exclude_from_search' => 0));
	unset($post_types['attachment']);
	unset($post_types['post']);

	foreach ( $post_types as $post_type ) {
		$post_type_object = get_post_type_object($post_type);
		$post_type_label = !empty($post_type_object->labels->name) ? $post_type_object->labels->name : $post_type;
		$column['cpt_'.$post_type] = $post_type_label;
	}

	if ( a4h_ads('enable_rs') ) {
    	$column['rs_ads'] = 'مشاركة الأرباح';
	}
    return $column;
}
add_filter('manage_users_columns', 'a4h_admin_users_custom_columns_title');

function a4h_admin_users_custom_columns_content($value, $column_name, $user_id) {
	$post_types = get_post_types(array('exclude_from_search' => 0));
	unset($post_types['attachment']);
	unset($post_types['post']);
	
	foreach ( $post_types as $post_type ) {
		if ( $column_name == 'cpt_'.$post_type ) {
			$post_counts = count_many_users_posts(array($user_id), $post_type);
			$user_post_counts = $post_counts[$user_id];
			if ( $user_post_counts > 0 ) {
				$value = sprintf('<a href="%s" class="edit"><span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>', "edit.php?post_type={$post_type}&author={$user_id}", $user_post_counts, sprintf(_n('%s post by this author', '%s posts by this author', $user_post_counts), number_format_i18n($user_post_counts)));
			} else {
				$value = 0;
			}
		}
	}

	if ( $column_name == 'rs_ads' ) {
		$author_adsense = get_the_author_meta('adsense', $user_id);
		$author_client = !empty($author_adsense['client']) ? esc_attr($author_adsense['client']) : '';
		$author_channel = !empty($author_adsense['channel']) ? esc_attr($author_adsense['channel']) : '';
		$author_slot = !empty($author_adsense['slot']) ? esc_attr($author_adsense['slot']) : '';
		$author_url = !empty($author_adsense['url']) ? esc_attr($author_adsense['url']) : '';
		
		$value = '';
		$value .= '<div style="direction: ltr; text-align: left;">';
		$value .= $author_client ? '<p>pub: <input type="text" class="widefat" style="width: 200px; text-align: left; direction: ltr; font-size: 90%" readonly value="'.$author_client.'" /></p>' : '';
		$value .= $author_channel ? '<p>channel: <input type="text" class="widefat" style="width: 200px; text-align: left; direction: ltr; font-size: 90%" readonly value="'.$author_channel.'" /></p>' : '';
		$value .= $author_slot ? '<p>slot: <input type="text" class="widefat" style="width: 200px; text-align: left; direction: ltr; font-size: 90%" readonly value="'.$author_slot.'" /></p>' : '';
		$value .= $author_url ? '<p>url: <input type="text" class="widefat" style="width: 200px; text-align: left; direction: ltr; font-size: 90%" readonly value="'.$author_url.'" /></p>' : '';
		$value .= '</div>';
	}
    return $value;
}
add_filter('manage_users_custom_column', 'a4h_admin_users_custom_columns_content', 10, 3);

function a4h_admin_tools_import() {
	if ( empty($_POST['option_page']) || $_POST['option_page'] != THEME_VAR_TOOLS ) return;
	if ( !isset($_POST['import_nonce']) || !wp_verify_nonce($_POST['import_nonce'], 'import_nonce') ) return;

	if ( empty($_FILES['import_settings']) ) return;

	$file_name = $_FILES['import_settings']['name'];
	$file_type = $_FILES['import_settings']['type'];
	$file_contents = $_FILES['import_settings']['tmp_name'];
	$file_name_array = explode('.', $file_name);
	$file_extension = strtolower(end($file_name_array));
	if ( $_FILES['import_settings']['error'] < 1 && $file_extension == 'txt' ) {
		$options = unserialize(file_get_contents($file_contents));
		foreach ( $options as $option_name => $option_value ) {
			update_option($option_name, $option_value);
		}
	}
}
add_action('admin_init', 'a4h_admin_tools_import');

function a4h_admin_tools_export() {
	if ( empty($_GET['page']) || $_GET['page'] != THEME_VAR_TOOLS ) return;
	if ( empty($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'export_settings') ) return;

	$site_url = get_option('siteurl');
	$site_url = parse_url($site_url);
	$site_url = $site_url['host'];

	if ( $_GET['action'] == 'export_options' ) {
		$option_label = 'options';
		$options = get_option(THEME_VAR_OPTIONS);
		$new_options = array();
		$new_options[THEME_VAR_OPTIONS] = $options;
	}
	if ( $_GET['action'] == 'export_ads' ) {
		$option_label = 'ads';
		$options = get_option(THEME_VAR_ADS);
		$new_options = array();
		$new_options[THEME_VAR_ADS] = $options;
	}
	if ( $_GET['action'] == 'export_widgets' ) {
		$option_label = 'widgets';
		$sidebars_widgets = (array)get_option('sidebars_widgets');
		unset($sidebars_widgets['wp_inactive_widgets']);
		$new_options = array();
		$new_options['sidebars_widgets'] = $sidebars_widgets;
		$widget_list = a4h_widgets_get_registered_widgets_list();
		foreach ( $widget_list as $widget ) {
			$new_options['widget_'.$widget] = get_option('widget_'.$widget);
		}
	}
	
	$file_name = sprintf('%s - %s - (%s) - (%s)', THEME_NAME, $option_label, $site_url, date('d-M-y'));

	header("Cache-Control: public, must-revalidate");
	header("Pragma: hack");
	header("Content-Type: text/plain");
	header("Content-Disposition: attachment; filename={$file_name}.txt");
	echo serialize($new_options);
	die();
}
add_action('admin_init', 'a4h_admin_tools_export');

function a4h_admin_tools_reset() {
	if ( empty($_POST['reset_nonce']) || !wp_verify_nonce($_POST['reset_nonce'], 'reset_nonce') ) return;

	if ( !empty($_POST['reset_options']) ) {
		update_option(THEME_VAR_OPTIONS, a4h_default_options());
	}

	if ( !empty($_POST['reset_ads']) ) {
		update_option(THEME_VAR_ADS, a4h_default_ads());
	}
	if ( !empty($_POST['reset_widgets']) ) {
		a4h_default_widgets_install();
	}
}
add_action('admin_init', 'a4h_admin_tools_reset');

function a4h_widgets_get_registered_widgets_list() {
	global $wp_registered_widget_controls;

	$registered_widgets = $wp_registered_widget_controls;
	$new_list = array();
	foreach ( $registered_widgets as $widget_id => $widget_content) {
		$new_list[] = $widget_content['id_base'];
	}

	return $new_list;
}

function a4h_admin_widget_fields($_this, $field_instance, $field_name, $field_title, $field_type, $args = array()) {

	$id = $_this->get_field_id($field_name);
	$name = $_this->get_field_name($field_name);
	$value = !empty($field_instance[$field_name]) ? $field_instance[$field_name] : '';

	$placeholder = !empty($args['placeholder']) ? $args['placeholder'] : '';
	$step = !empty($args['step']) ? $args['step'] : '';
	$min = !empty($args['min']) ? $args['min'] : 0;
	$max = !empty($args['max']) ? $args['max'] : '';
	$rows = !empty($args['rows']) ? $args['rows'] : 10;
	$class = !empty($args['class']) ? $args['class'] : '';
	$prepend = !empty($args['prepend']) ? '<span class="widget-field-prepend">'.$args['prepend'].'</span>' : '';
	$append = !empty($args['append']) ? '<span class="widget-field-append">'.$args['append'].'</span>' : '';
	$notes = !empty($args['notes']) ? '<div class="widget-field-notes">'.$args['notes'].'</div>' : '';
	$options = !empty($args['options']) ? $args['options'] : array();
	?>
	<div class="widget-field" data-field="<?php echo $field_name; ?>">
		<?php if ( $field_title && $field_type != 'checkbox' ) { ?>
			<div class="field-title">
			<?php echo $field_title; ?></div>
		<?php } ?>
			<div class="page-field-content">
				<?php echo $prepend; ?>
				<?php #### fields start #### ?>

				<?php if ( $field_type == 'text' ) { ?>
					<input type="text" class="widefat <?php echo $class; ?>" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $value; ?>" placeholder="<?php echo $placeholder; ?>">
				<?php } ?>

				<?php if ( $field_type == 'number' ) { ?>
					<input type="number" class="<?php echo $class; ?>" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $value; ?>" step="<?php echo $step; ?>" min="<?php echo $min; ?>" max="<?php echo $max; ?>">
				<?php } ?>

				<?php if ( $field_type == 'textarea' ) { ?>
					<textarea class="widefat <?php echo $class; ?>" id="<?php echo $id; ?>" name="<?php echo $name; ?>" rows="<?php echo $rows; ?>" placeholder="<?php echo $placeholder; ?>"><?php echo $value; ?></textarea>
				<?php } ?>

				<?php if ( $field_type == 'checkbox' ) { ?>
					<div class="settings-field-group">
						<span class="settings-checkbox">
							<input <?php checked(1, $value); ?> type="checkbox" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="1">
						</span>
						<div class="settings-checkbox-title">
							<?php if ( $field_title ) { ?>
								<?php echo $field_title; ?>
							<?php } ?>
						</div>
					</div>
				<?php } ?>

				<?php if ( $field_type == 'select' ) { ?>
					<select class="widefat <?php echo $class; ?>" id="<?php echo $id; ?>" name="<?php echo $name; ?>">
						<?php foreach ( $options as $option ) { ?>
							<option data-type="<?php echo !empty($option['type']) ? $option['type'] : ''; ?>" <?php selected($option['name'], $value); ?> value="<?php echo $option['name']; ?>"><?php echo $option['title']; ?></option>
						<?php } ?>
					</select>
				<?php } ?>

				<?php if ( $field_type == 'multi_select' ) { ?>
					<span class="settings-choices">
					<?php foreach ( $options as $option ) { ?>
						<label data-type="<?php echo !empty($option['type']) ? $option['type'] : ''; ?>" class="label-for-choices label-for-choice_<?php echo $option['name']; ?>"><input <?php checked(1, in_array($option['name'], (array)$value)); ?> type="checkbox" id="<?php echo $id; ?>" name="<?php echo $name; ?>[]" value="<?php echo $option['name']; ?>"><span><?php echo $option['title']; ?></span></label>
						<?php } ?>
					</span>
				<?php } ?>

				<?php if ( $field_type == 'radio' ) { ?>
					<span class="settings-choices">
					<?php foreach ( $options as $option ) { ?>
						<label data-type="<?php echo !empty($option['type']) ? $option['type'] : ''; ?>" class="label-for-choices label-for-choice_<?php echo $option['name']; ?>"><input <?php checked($option['name'], $value); ?> type="radio" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $option['name']; ?>"><span><?php echo $option['title']; ?></span></label>
						<?php } ?>
					</span>
				<?php } ?>

				<?php #### fields end #### ?>
				<?php echo $append; ?>
				<?php echo $notes; ?>
			</div>
		</div>
	<?php
}

function a4h_admin_clear_cache($option_name) {
	if ( !in_array($option_name, array(THEME_VAR_OPTIONS, THEME_VAR_ADS, THEME_VAR_TOOLS)) ) return;
	wp_cache_flush();
}
add_action('update_option', 'a4h_admin_clear_cache');

function a4h_admin_ads_ads_txt_file_update($option_name) {
	if ( $option_name != THEME_VAR_ADS ) return;
	delete_transient(THEME_VAR.'_ads_txt_file');
}
add_action('update_option', 'a4h_admin_ads_ads_txt_file_update');

function a4h_widgets_get_post_types($post_type = '', $show_all = false) {
	$post_types = array();
	$get_post_types = $post_type ? array($post_type) : get_post_types(array('exclude_from_search' => 0));
	unset($get_post_types['attachment']);
	if ( $show_all ) {
		$post_types[] = array('name' => 'all', 'title' => sprintf('- %s -', __('All', THEME_TEXT_DOMAIN)));
	}
	foreach ( $get_post_types as $post_type ) {
		$post_type_object = get_post_type_object($post_type);
		$post_type_label = !empty($post_type_object->labels->name) ? $post_type_object->labels->name : $post_type;
		$post_types[] = array('name' => $post_type, 'title' => $post_type_label);
	}
	return $post_types;
}

function a4h_widgets_get_taxonomies($post_type = '') {
	$taxonomies = array();
	$post_types = a4h_widgets_get_post_types($post_type);
	foreach ( $post_types as $post_type ) {
		$post_types_taxonomies = get_object_taxonomies($post_type['name'], 'objects');
		if ( !$post_types_taxonomies ) continue;
		foreach ( $post_types_taxonomies as $taxonomy ) {
			if ( $taxonomy->name == 'post_format' ) continue;
			if ( $taxonomy->public == false ) continue;
			$keys[] = $taxonomy->name;
			$taxonomies[] = array('name' => $taxonomy->name, 'title' => $taxonomy->label, 'type' => $post_type['name']);
		}
	}
	return $taxonomies;
}

function a4h_widgets_get_terms($post_type) {
	$terms = array();
	$taxonomies = a4h_widgets_get_taxonomies($post_type);

	foreach ( $taxonomies as $taxonomy ) {
		if ( $taxonomy['name'] == 'post_tag' ) continue;
		$args['hide_empty'] = '';
		$args['taxonomy'] = $taxonomy['name'];
		$args['number'] = 100;

		$get_terms = get_terms($args);
		if ( empty($get_terms) || is_wp_error($get_terms) ) continue;

		foreach ( $get_terms as $term ) {
			$term->order = get_term_meta($term->term_id, 'order', true);
		}
		usort($get_terms, function($x, $y) {
			$x = !empty($x->order) ? $x->order : 999;
			$y = !empty($y->order) ? $y->order : 999;
			return $x - $y;
		});
		foreach ( $get_terms as $term ) {
			$terms[] = array('name' => $term->term_id, 'title' => $term->name, 'type' => $taxonomy['name']);
		}
	}
	return $terms;
}

function a4h_widgets_get_nav_menus() {
	$menus = array();
	$nav_menus = wp_get_nav_menus();
	if ( $nav_menus ) {
		foreach ( $nav_menus as $nav_menu )  {
			$menus[] = array('name' => $nav_menu->term_id, 'title' => $nav_menu->name);
		}
	}
	return $menus;
}

function a4h_admin_notices_to_members_show($wp_admin_bar) {
	if ( !a4h_options('notices_to_members') ) return;

	global $pagenow;

	$notices = a4h_options('notices_to_members');
	$notices_updated = get_option(THEME_VAR.'_notices_to_members_updated');
	
	$notices_locations = a4h_filter('notices_to_members_locations', array('index.php', 'post.php', 'post-new.php'));

	if ( !in_array($pagenow, $notices_locations) ) return;

	$pattern = '@(http(s)?://)?(([a-zA-Z0-9])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
	$output = preg_replace($pattern, '<a href="http$2://$3" target="_blank">$0</a>', $notices);
	$output .= $notices_updated ? '<hr>منذ '.human_time_diff($notices_updated) : '';
	$output = '<div class="a4h-admin-notes-to-members"><pre>'.$output.'</pre></div>';
	echo $output;
}
add_action('admin_notices', 'a4h_admin_notices_to_members_show');

function a4h_admin_notices_to_members_update($option_name, $old_values, $new_values) {
	if ( $option_name != THEME_VAR_OPTIONS ) return;

	if ( $old_values['notices_to_members'] == $new_values['notices_to_members'] ) return;

	$current_time = date('m/d/Y h:i:s a', time());
	update_option(THEME_VAR.'_notices_to_members_updated', strtotime($current_time));
}
add_action('update_option', 'a4h_admin_notices_to_members_update', 10, 3);