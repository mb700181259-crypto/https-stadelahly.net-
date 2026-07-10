<?php
new a4h_widget_html_code;
class a4h_widget_html_code extends WP_Widget {

	static $page_vars = array(
		'name' => 'html_code',
		'title' => 'كود HTML',	
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
	echo !empty($instance['code']) ? do_shortcode($instance['code']) : '';
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

		a4h_admin_widget_fields($this, $instance, 'code', 'الكود', 'textarea', array('class' => 'code-input'));
	}
}