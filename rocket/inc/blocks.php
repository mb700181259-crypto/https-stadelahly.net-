<?php

function a4h_blocks_post_type_register() {
	$args = array();
	$args['labels'] = array(
		'name' => 'بلوكات',
		'singular_name' => 'بلوك',
		'all_items' => 'كل البلوكات',
		'view_item' => 'عرض البلوك',
		'add_new_item' => 'أضف بلوك جديد',
		'add_new' => 'أضف جديد',
		'edit_item' => 'تحرير البلوك',
		'update_item' => 'تحديث البلوك',
		'search_items' => 'بحث البلوكات',
	);
	$args['public'] = false;
	$args['show_ui'] = true;
	$args['exclude_from_search'] = true;
	$args['publicly_queryable '] = false;
	$args['supports'] = array('title', 'author', 'editor');
	$args['menu_position'] = 99999;
	$args['menu_icon'] = 'dashicons-tagcloud';
	$args['capabilities'] = array(
		'edit_post'          => 'update_core',
		'read_post'          => 'update_core',
		'delete_post'        => 'update_core',
		'edit_posts'         => 'update_core',
		'edit_others_posts'  => 'update_core',
		'delete_posts'       => 'update_core',
		'publish_posts'      => 'update_core',
		'read_private_posts' => 'update_core'
	);
	register_post_type('block', $args);
}
add_action('init', 'a4h_blocks_post_type_register');

function a4h_blocks_post_type_column($columns) {
	unset($columns['views']);
	$columns['scode'] = 'الكود المختصر';
	return $columns;
}
add_filter('manage_block_posts_columns' , 'a4h_blocks_post_type_column');

function a4h_blocks_post_type_custom_columns($column_name, $post_id) {
	if ( $column_name != 'scode' ) return;
	if ( get_post_status($post_id) != 'publish' ) return;

	echo '<input type="text" readonly value="[block '.$post_id.']" style="direction: ltr; text-align: left;" onclick="this.select();">';
}
add_filter('manage_block_posts_custom_column' , 'a4h_blocks_post_type_custom_columns', 10, 2);

function a4h_blocks_post_screen_meta_box() {
	add_meta_box('a4h_block_scode', 'الكود المختصر', 'a4h_blocks_post_screen_meta_box_callback', 'block', 'advanced', 'high');
}
add_action('load-post.php', 'a4h_blocks_post_screen_meta_box');
add_action('load-post-new.php', 'a4h_blocks_post_screen_meta_box');

function a4h_blocks_post_screen_meta_box_callback($post) {
    echo '<input type="text" readonly value="[block '.get_the_ID().']" style="direction: ltr; text-align: left;" onclick="this.select();">';
}

function a4h_blocks_content_shortcode($args) {
    $id = $args[0] ?? '';
    if ( !$id ) return;

	$block = get_post($id);
	if ( !$block ) return;

    $output = wpautop(get_post_field('post_content', $block));
    $output = a4h_filter('html_content_filter', $output, 'block_content');
    
	return $output;
}
add_shortcode('block', 'a4h_blocks_content_shortcode');

function a4h_blocks_description($wp_admin_bar) {
	if ( get_current_screen()->post_type != 'block' ) return;
	if ( !in_array($GLOBALS['pagenow'], array('edit.php')) ) return;
    
	$output = '<div class="a4h-admin-notes-to-members"><pre>البلوكات عبارة عن صفحات يمكن إنشاء أي محتوى فيها ثم إدراج هذا المحتوى فيما بعد في المقالات أو الصفحات عن طريق أكواد مختصرة (Shortcodes)</pre></div>';
	echo $output;
}
add_action('admin_notices', 'a4h_blocks_description');