<?php
new a4h_widget_posts_list;
class a4h_widget_posts_list extends WP_Widget {

	static $page_vars = array(
		'name' => 'posts-list',
		'title' => 'قائمة مقالات/صفحات',
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
	a4h_posts_list($instance);
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
		$defaults['post_type'] = 'post';
		$defaults['term_type'] = 'any';
		$defaults['terms'] = array();
		$defaults['taxonomy_relation'] = 'OR';
		$defaults['special_posts'] = 'none';
		$defaults['author_type'] = 'any';
		$defaults['post_count'] = 6;
		$defaults['order_by'] = 'modified';
		$defaults['views_interval'] = 'month';
        $instance = wp_parse_args((array) $instance, $defaults);

		a4h_admin_widget_fields($this, $instance, 'title', 'العنوان', 'text');

		a4h_admin_widget_fields($this, $instance, 'post_type', 'النوع', 'radio', array('options' => a4h_widgets_get_post_types()));

		$terms = a4h_widgets_get_terms($instance['post_type']);
		if ( $terms ) {
			$term_type = array();
			$term_type[] = array('name' => 'any', 'title' => 'كل التصنيفات');
			$term_type[] = array('name' => 'current', 'title' => 'تصنيفات الصفحة/المقالة المعروضة');
			$term_type[] = array('name' => 'selected', 'title' => 'تصنيفات مختارة <span class="dashicons dashicons-arrow-down-alt"></span>');
			a4h_admin_widget_fields($this, $instance, 'term_type', 'التصنيفات', 'radio', array('options' => $term_type));

			a4h_admin_widget_fields($this, $instance, 'terms', 'التصنيفات المختارة', 'multi_select', array('options' => $terms));

			a4h_admin_widget_fields($this, $instance, 'extra_terms', 'أرقام تصنيفات ووسوم إضافية مفصولة بشرطة (-)', 'text', array('class' => 'ltr'));
		}

		$taxonomies = a4h_widgets_get_taxonomies($instance['post_type']);
		if ( $taxonomies ) {
			a4h_admin_widget_fields($this, $instance, 'taxonomies', 'المصدر من', 'multi_select', array('options' => $taxonomies));
		}

		$taxonomy_relation = array();
		$taxonomy_relation[] = array('name' => 'OR', 'title' => 'وجود أي تصنيف');
		$taxonomy_relation[] = array('name' => 'AND', 'title' => 'وجود كل التصنيفات');
		a4h_admin_widget_fields($this, $instance, 'taxonomy_relation', 'شرط الظهور', 'radio', array('options' => $taxonomy_relation));

		$special_posts = array();
		$special_posts[] = array('name' => 'none', 'title' => 'بدون');
		$special_posts[] = array('name' => 'viewed_by_visitor', 'title' => 'مقالات/صفحات شاهدها الزائر الحالي');
		$special_posts[] = array('name' => 'viewed_by_others', 'title' => 'مقالات/صفحات شاهدها الزوار الآخرين');
		$special_posts[] = array('name' => 'sticky', 'title' => 'مقالات/صفحات مثبتة');
		$special_posts[] = array('name' => 'selected', 'title' => 'مقالات/صفحات مختارة <span class="dashicons dashicons-arrow-down-alt"></span>');
		a4h_admin_widget_fields($this, $instance, 'special_posts', 'مقالات/صفحات خاصة', 'radio', array('options' => $special_posts));

		a4h_admin_widget_fields($this, $instance, 'selected_posts_ids', 'أرقام مقالات/صفحات مختارة مفصولة بشرطة (-)', 'text', array('class' => 'ltr'));

		a4h_admin_widget_fields($this, $instance, 'exclude_current', 'لا تعرض المقالة/الصفحة الحالية', 'checkbox');

		$author_type = array();
		$author_type[] = array('name' => 'any', 'title' => 'أي كاتب');
		$author_type[] = array('name' => 'current', 'title' => 'كاتب الصفحة/المقالة المعروضة');
		$author_type[] = array('name' => 'selected', 'title' => 'كتاب مختارون <span class="dashicons dashicons-arrow-down-alt"></span>');
		a4h_admin_widget_fields($this, $instance, 'author_type', 'الكاتب', 'radio', array('options' => $author_type));

		a4h_admin_widget_fields($this, $instance, 'authors', 'أرقام الكتاب المختارين مفصولة بشرطة (-)', 'text', array('class' => 'ltr'));
		
		$orderby = array();
		$orderby[] = array('name' => 'modified', 'title' => 'تاريخ التحديث');
		$orderby[] = array('name' => 'date', 'title' => 'تاريخ النشر');
		$orderby[] = array('name' => 'rand', 'title' => 'عشوائي');
		$orderby[] = array('name' => 'comment_count', 'title' => 'الأكثر تعليقا');
		$orderby[] = array('name' => 'views', 'title' => 'الأكثر مشاهدة <span class="dashicons dashicons-arrow-down-alt"></span>');
		a4h_admin_widget_fields($this, $instance, 'order_by', 'ترتيب بواسطة', 'radio', array('options' => $orderby, 'notes' => a4h_tools('enable_count_views') ? '' : 'ملحوظة: احتساب عدد المشاهدات غير مفعل من أدوات القالب'));

		$orderby = array();
		$orderby[] = array('name' => 'day', 'title' => 'يوم');
		$orderby[] = array('name' => 'week', 'title' => 'أسبوع');
		$orderby[] = array('name' => 'month', 'title' => 'شهر');
		$orderby[] = array('name' => 'total', 'title' => 'طوال الوقت');
		a4h_admin_widget_fields($this, $instance, 'views_interval', 'الأكثر مشاهدة خلال', 'radio', array('options' => $orderby));

		echo '<div class="widget-fields-wrapper widget-field">';
		a4h_admin_widget_fields($this, $instance, 'post_count', 'العدد', 'number', array('max' => 500));
		a4h_admin_widget_fields($this, $instance, 'posted_in_x_days', 'تاريخ النشر خلال', 'number', array('max' => 3000, 'notes' => 'يوم'));
		a4h_admin_widget_fields($this, $instance, 'updated_in_x_days', 'تاريخ التحديث خلال', 'number', array('max' => 3000, 'notes' => 'يوم'));
		a4h_admin_widget_fields($this, $instance, 'offset', 'ابدأ من المقالة رقم', 'number', array('max' => 50));
		echo '</div>';

		a4h_admin_widget_fields($this, $instance, 'custom_posts_args', 'متقدم: متغير استعلام مخصص', 'text', array('class' => 'ltr'));

        a4h_admin_widget_fields($this, $instance, 'custom_style', 'كود ستايل مخصص', 'textarea', array('class' => 'ltr', 'rows' => 4));

        a4h_admin_widget_fields($this, $instance, 'slider', 'سلايدر', 'checkbox');
	}
}