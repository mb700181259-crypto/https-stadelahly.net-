<?php
new a4h_widget_terms_list;
class a4h_widget_terms_list extends WP_Widget {

	static $page_vars = array(
		'name' => 'terms-list',
		'title' => 'قائمة تصنيفات/وسوم',	
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
	a4h_terms_list($instance);
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
		$defaults['taxonomy'] = 'category';
		$defaults['order_by'] = 'name';
		$defaults['parent_type'] = 'none';

        $instance = wp_parse_args((array) $instance, $defaults);

		a4h_admin_widget_fields($this, $instance, 'title', 'العنوان', 'text');

		a4h_admin_widget_fields($this, $instance, 'taxonomy', 'النوع', 'radio', array('options' => a4h_widgets_get_taxonomies()));

		$parent_type = array();
		$parent_type[] = array('name' => 'none', 'title' => 'أي تصنيف');
		$parent_type[] = array('name' => 'current', 'title' => 'التصنيف الحالي');
		$parent_type[] = array('name' => 'selected', 'title' => 'تصنيف مختار <span class="dashicons dashicons-arrow-down-alt"></span>');
		a4h_admin_widget_fields($this, $instance, 'parent_type', 'التصنيف الأب', 'radio', array('options' => $parent_type));

		a4h_admin_widget_fields($this, $instance, 'parent', 'رقم التصنيف الأب المختار', 'number');

		a4h_admin_widget_fields($this, $instance, 'include', 'تضمين أرقام تصنيفات فقط مفصولة بشرطة (-)', 'text', array('class' => 'ltr'));

		a4h_admin_widget_fields($this, $instance, 'exclude', 'استثناء أرقام تصنيفات مفصولة بشرطة (-)', 'text', array('class' => 'ltr'));

		a4h_admin_widget_fields($this, $instance, 'terms_count', 'عدد التصنيفات المعروضة', 'number');
		
		$orderby = array();
		$orderby[] = array('name' => 'name', 'title' => 'الاسم');
		$orderby[] = array('name' => 'slug', 'title' => 'الاسم اللطيف (slug)');
		$orderby[] = array('name' => 'id', 'title' => 'الرقم (ID)');
		$orderby[] = array('name' => 'count', 'title' => 'عدد المقالات');
		$orderby[] = array('name' => 'order_field', 'title' => 'حقل الترتيب');
		a4h_admin_widget_fields($this, $instance, 'order_by', 'ترتيب بواسطة', 'radio', array('options' => $orderby));

		a4h_admin_widget_fields($this, $instance, 'show_empty', 'عرض التصنيفات الفارغة', 'checkbox');

		a4h_admin_widget_fields($this, $instance, 'show_children_of_none_only', 'عرض التصنيفات الرئيسية فقط', 'checkbox');

        a4h_admin_widget_fields($this, $instance, 'custom_style', 'كود ستايل مخصص', 'textarea', array('class' => 'ltr', 'rows' => 4));

        a4h_admin_widget_fields($this, $instance, 'slider', 'سلايدر', 'checkbox');
	}
}