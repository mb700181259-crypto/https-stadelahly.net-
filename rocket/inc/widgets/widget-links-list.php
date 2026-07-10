<?php
new a4h_widget_links_list;
class a4h_widget_links_list extends WP_Widget {

	static $page_vars = array(
		'name' => 'links-list',
		'title' => 'قائمة تصفح',	
	);

	function __construct() {
		$page_vars = $this::$page_vars;
		parent::__construct(THEME_VAR.'_'.$page_vars['name'], THEME_NAME.' | '.$page_vars['title']);
		add_action('widgets_init', function() {
			register_widget(get_class($this));
		});
	}

	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', !empty($instance['title']) ? $instance['title'] : '', $instance, $this->id_base);
		echo $before_widget;
		if ( $title ) { echo $before_title.$title.$after_title; }
//output start
$nav_menu = !empty($instance['nav_menu']) ? $instance['nav_menu'] : '';
if ( !$nav_menu ) return;

$nav_menu_args = array();
$nav_menu_args['walker'] = new Walker_Links_list();
$nav_menu_args['container_class'] = 'items-list-outer links-list-outer';
$nav_menu_args['menu_class'] = 'items-list links-list';
$nav_menu_args['menu'] = $nav_menu;
$nav_menu_args['echo'] = false;
$nav_menu_args['custom_style'] = $instance['custom_style'] ?: '';
$nav_menu_args['slider'] = $instance['slider'] ?: '';

$nav_menu = wp_nav_menu($nav_menu_args);
if ( !$nav_menu ) return;

echo $nav_menu;
//output end
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance = array();
		foreach ( $new_instance as $item_key => $item_value ) {
			$instance[$item_key] = $item_value;
		}
		if ( !empty($instance['title']) ) {
			$instance['title'] = sanitize_text_field($instance['title']);
		}
		return $instance;
	}

	function form($instance) {
		$defaults = array();

        $instance = wp_parse_args((array) $instance, $defaults);

		a4h_admin_widget_fields($this, $instance, 'title', 'العنوان', 'text');

		a4h_admin_widget_fields($this, $instance, 'nav_menu', 'قائمة التصفح', 'radio', array('options' => a4h_widgets_get_nav_menus()));

        a4h_admin_widget_fields($this, $instance, 'custom_style', 'كود ستايل مخصص', 'textarea', array('class' => 'ltr', 'rows' => 4));

        a4h_admin_widget_fields($this, $instance, 'slider', 'سلايدر', 'checkbox');
	}
}