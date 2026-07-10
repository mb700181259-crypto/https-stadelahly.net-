<?php
new a4h_widget_widgets_list;
class a4h_widget_widgets_list extends WP_Widget {

	static $page_vars = array(
		'name' => 'widgets-list',
		'title' => 'قائمة ودجات',
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
	$id = $instance['widgets_list_id'] ?? '';
	$tabbed = $instance['tabbed'] ?? '';

	a4h_widgets_area('widgets_list_'.$id, true, $tabbed);
	?>
<?php
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
		$defaults['widgets_list_id'] = 1;
        $instance = wp_parse_args((array) $instance, $defaults);

		a4h_admin_widget_fields($this, $instance, 'title', 'العنوان', 'text');

		$widgets_lists = array();
		for ( $i = 1; $i <= a4h_theme_vars('widgets_lists_count'); $i++ ) {
			$widgets_lists[] = array('name' => $i, 'title' => '#'.$i);
		}
		a4h_admin_widget_fields($this, $instance, 'widgets_list_id', 'قائمة الودجات', 'radio', array('options' => $widgets_lists));

		a4h_admin_widget_fields($this, $instance, 'tabbed', 'جعل الودجات مبوبة', 'checkbox');	
	}
}