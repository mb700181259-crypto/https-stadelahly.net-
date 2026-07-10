<?php
new a4h_widget_ads;
class a4h_widget_ads extends WP_Widget {

	static $page_vars = array(
		'name' => 'insert',
		'title' => 'إعلان',	
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
	$ad = array();
	$ad['status'] = 1;
	$ad['group'] = 'widget';
	$ad['code'] = !empty($instance['code']) ? $instance['code'] : '';
	$ad['force'] = !empty($instance['force']) ? $instance['force'] : '';
	a4h_ads_ad_output($ad, true);
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
		$instance['is_ad_widget'] = 1;
		$instance['status'] = 1;
		$instance['group'] = 'widget';
		return $instance;
	}

	function form($instance) {
		$defaults = array();
        $instance = wp_parse_args((array) $instance, $defaults);

		a4h_admin_widget_fields($this, $instance, 'title', 'العنوان', 'text');

		a4h_admin_widget_fields($this, $instance, 'code', 'الكود', 'textarea', array('class' => 'code-input'));

		a4h_admin_widget_fields($this, $instance, 'force', 'إجبار على الظهور', 'checkbox');
		
		a4h_admin_widget_fields($this, $instance, 'label', 'وصف الإعلان', 'text');
	}
}