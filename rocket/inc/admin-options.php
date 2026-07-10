<?php

new a4h_admin_page_options(array(
	'slug' => THEME_VAR_OPTIONS,
	'field_type' => 'options',
	'title' => 'الإعدادات',
	'menu_order' => 500,
));

class a4h_admin_page_options {

		static $page_vars;

		function __construct($page_vars) {
			$this::$page_vars = $page_vars;
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('admin_bar_menu', array($this, 'admin_bar_link'), $this::$page_vars['menu_order']);
			add_action('admin_init', array($this, 'register_settings'));
		}

		function admin_menu() {
			$menu = add_menu_page($this::$page_vars['title'], THEME_NAME.' - '.$this::$page_vars['title'], 'manage_options', $this::$page_vars['slug'], array($this, 'page_content'), a4h_theme_vars('a4h_icon'), $this::$page_vars['menu_order']);
			add_action('load-'.$menu, 'a4h_admin_page_scripts');
		}

		function admin_bar_link($wp_admin_bar) {
			$wp_admin_bar->add_menu(array('parent' => THEME_VAR_OPTIONS, 'id' => 'ab-link-'.$this::$page_vars['menu_order'], 'title' => $this::$page_vars['title'], 'href' => admin_url('admin.php?page='.$this::$page_vars['slug'])));
		}

		function register_settings() {
			register_setting($this::$page_vars['slug'], $this::$page_vars['slug'], 'a4h_admin_options_sanitize_callback');
		}

		function page_content() {
			a4h_hook('admin_page_content_'.$this::$page_vars['field_type']);
			?>
<div class="wrap">
	<form method="post" action="options.php" class="a4h-admin-form">
		<?php settings_fields($this::$page_vars['slug']); ?>
<!-- content start -->
	<div class="a4h-admin-page" data-type="<?php echo $this::$page_vars['field_type']; ?>">
		<div class="a4h-admin-page-placeholder">
			<div class="a4h-admin-page-placeholder-modal">
				<div class="a4h-loader"></div>
				<div class="a4h-loader-text"><span class="saving">جاري الحفظ...</span><span class="saved">تم الحفظ</span></div>
			</div>
		</div>
		<div class="a4h-admin-page-header">
			<div class="a4h-admin-page-header-logo"><img src="<?php echo a4h_theme_vars('a4h_logo'); ?>"></a></div>
			<div class="a4h-admin-page-header-heading"><?php echo THEME_NAME; ?></div>
			<div class="a4h-admin-page-header-version">النسخة <?php echo THEME_VERSION; ?></div>
			<div class="a4h-admin-page-header-title"><?php echo $this::$page_vars['title']; ?></div>
			<div class="a4h-admin-flex-grow"></div>
			<a target="_blank" href="<?php echo SUPPORT_LINK; ?>" class="a4h-admin-support-link button">الدعم الفني</a>
		</div>
		<div class="a4h-admin-page-links">
			<ul>
				<li><a href="#a4h-tab-notes">ملاحظات</a></li>
				<li><a href="#a4h-tab-members-notices">تنبيهات للأعضاء</a></li>
				<li><a href="#a4h-tab-news-ticker">شريط الأخبار</a></li>
				<li><a href="#a4h-tab-css">أكواد CSS</a></li>
				<li><a href="#a4h-tab-js">أكواد JavaScript</a></li>
				<li><a href="#a4h-tab-social">المواقع الاجتماعية</a></li>
				<li><a href="#a4h-tab-identity">هوية الموقع</a></li>
				<li><a href="#a4h-tab-design">تصميم الموقع</a></li>
				<li><a href="#a4h-tab-singular">المقالات/الصفحات</a></li>
				<li><a href="#a4h-tab-archives">الأقسام/التصنيفات</a></li>
				<li><a href="#a4h-tab-misc">خيارات متنوعة</a></li>
				<li><a href="#a4h-tab-header">بناء الهيدر</a></li>
				<li><a href="#a4h-tab-footer">بناء الفوتر</a></li>
				<li><a href="#a4h-tab-overlay-menu">بناء القائمة المنسدلة</a></li>
				<li><a href="#a4h-tab-home">بناء الصفحة الرئيسية</a></li>
			</ul>
		</div>	
		<div class="a4h-admin-page-main">
			<div id="a4h-tab-notes" class="a4h-tab">

<?php a4h_admin_page_fields(
	'textarea',
	'admin_notes',
	'ملاحظات الإعدادات',
	array(
		'class' => 'private-notes',
		'notes' => 'ملاحظات تظهر للأدمن فقط في هذه الصفحة',
	)
); ?>
								
			</div>
			<div id="a4h-tab-members-notices" class="a4h-tab">

<?php a4h_admin_page_fields(
	'textarea',
	'notices_to_members',
	'تنبيهات للأعضاء',
	array(
		'notes' => 'تنبيهات تظهر للأعضاء في لوحة التحكم',
	)
); ?>
								
			</div>
			<div id="a4h-tab-news-ticker" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">شريط مثبت يعرض خبر عشوائي من الأخبار المضافة</div>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'news_ticker_enable',
	'تفعيل شريط الأخبار',
); ?>

<?php a4h_admin_page_fields(
	'repeater',
	'news_ticker_items',
	'الأخبار',
	array(
		'fields' => array(
			array(
				'text',
				'url',
				'الرابط',
				array(
					'placeholder' => 'https://',
					'class' => 'ltr',
				),
			),
			array(
				'text',
				'title',
				'العنوان',
			),
		),
		'add_row_label' => 'إضافة خبر',
	)
); ?>

			</div>
			<div id="a4h-tab-social" class="a4h-tab">
				
<?php a4h_admin_page_fields(
	'repeater',
	'social_links',
	'المواقع الاجتماعية',
	array(
		'fields' => array(
			array(
				'text',
				'url',
				'رابط الموقع',
				array(
					'placeholder' => 'https://',
					'class' => 'ltr',
				),
			),
		),
		'add_row_label' => 'إضافة موقع',
	)
); ?>

			</div>
			<div id="a4h-tab-css" class="a4h-tab">

<?php a4h_admin_page_fields(
	'textarea',
	'custom_css',
	'أكواد CSS',
	array(
		'class' => 'ltr css-textarea',
		'notes' => 'لا تقم بإضافة وسم <mark>&lt;style&gt;&lt;/style&gt;</mark>',
		'rows' => 20,
	)
); ?>
				
			</div>
			<div id="a4h-tab-js" class="a4h-tab">

<?php a4h_admin_page_fields(
	'textarea',
	'custom_js_header',
	'أكواد JavaScript & Meta قبل &lt;/head&gt;',
	array(
		'class' => 'ltr js-textarea',
	)
); ?>

<?php a4h_admin_page_fields(
	'textarea',
	'custom_js_footer',
	'أكواد JavaScript قبل &lt;/body&gt;',
	array(
		'class' => 'ltr js-textarea',
	)
); ?>

			</div>
			<div id="a4h-tab-identity" class="a4h-tab">

<?php a4h_admin_page_fields(
	'color',
	'site_color',
	'لون الموقع',
); ?>

<?php a4h_admin_page_fields(
	'image',
	'site_logo',
	'لوجو الموقع',
); ?>

<?php a4h_admin_page_fields(
	'image',
	'site_logo_dark',
	'لوجو الموقع للثيم الغامق',
); ?>

<?php a4h_admin_page_fields(
	'site_icon',
	'site_icon',
	'أيقونة الموقع',
); ?>
		
			</div>
			<div id="a4h-tab-design" class="a4h-tab">

<?php a4h_admin_page_fields(
	'radio',
	'site_font',
	'خط الموقع',
	array(
		'options' => array('Readex Pro' => 'Readex Pro', 'Noto Kufi Arabic' => 'Noto Kufi Arabic', 'Rubik' => 'Rubik'),
	),
); ?>

<?php a4h_admin_page_fields(
	'radio',
	'site_theme',
	'ثيم الموقع',
	array(
		'options' => array('light' => 'فاتح', 'dark' => 'غامق'),
		'notes' => 'إذا كنت ترغب بالسماح للمستخدمين بتغيير ثيم الموقع قم بإضافة زرار تغيير الثيم إلى الهيدر أو الفوتر أو القائمة المنسدلة',
	),
); ?>

<?php a4h_admin_page_fields(
	'radio',
	'overlay_panels_theme',
	'ثيم اللوحات المنسدلة',
	array(
		'options' => array('auto' => 'ثيم الموقع', 'light' => 'فاتح', 'dark' => 'غامق'),
		'notes' => 'اللوحات المنسدلة تشمل القائمة المنسدلة واستمارة البحث المنسدلة',

	),
); ?>

<?php a4h_admin_page_fields(
	'radio',
	'overlay_panels_position',
	'موضع اللوحات المنسدلة',
	array(
		'options' => array('over_body' => 'تغطي الموقع', 'below_header' => 'أسفل الهيدر'),
	),
); ?>

<?php a4h_admin_page_fields(
	'radio',
	'widgets_style',
	'تصميم الودجات',
	array(
		'options' => array('boxed' => 'صندوق', 'notboxed' => 'بدون صندوق'),
	),
); ?>

<?php a4h_admin_page_fields(
	'radio',
	'primary_style',
	'تصميم المحتوى الرئيسي',
	array(
		'options' => array('boxed' => 'صندوق', 'notboxed' => 'بدون صندوق'),
	),
); ?>

<?php a4h_admin_page_fields(
	'radio',
	'singular_primary_header',
	'هيدر المحتوى الرئيسي للمقالات/الصفحات',
	array(
		'options' => array('before' => 'قبل المحتوى', 'before_with_overlay' => 'قبل المحتوى بخلفية سوداء', 'inside' => 'داخل المحتوى'),
	),
); ?>

<?php a4h_admin_page_fields(
	'radio',
	'archive_primary_header',
	'هيدر المحتوى الرئيسي للأقسام/التصنيفات',
	array(
		'options' => array('before' => 'قبل المحتوى', 'before_with_overlay' => 'قبل المحتوى بخلفية سوداء', 'inside' => 'داخل المحتوى'),
	),
); ?>

<?php a4h_admin_page_fields(
	'radio',
	'side_layouts_mode',
	'نظام قوائم الودجات الجانبية',
	array(
		'options' => array('static' => 'ثابت', 'fixed' => 'متحرك'),
	),
); ?>

<?php a4h_admin_page_fields(
	'radio',
	'header_mode',
	'نظام الهيدر',
	array(
		'options' => array('static' => 'ثابت', 'fixed' => 'متحرك', 'dynamic' => 'حركة ديناميكية'),
	),
); ?>
		
			</div>
            <div id="a4h-tab-misc" class="a4h-tab">

<?php a4h_admin_page_fields(
	'textarea',
	'site_copyrights',
	'نص حفظ الحقوق',
	array(
		'rows' => 3,
	)
); ?>

<?php a4h_admin_page_fields(
	'text',
	'site_google_analytics_id',
	'Google Analytics - الرقم التعريفي للتتبع',
	array(
		'class' => 'sm-input',
		'notes' => 'مثال <mark>UA-1111111-22</mark> أو <mark>G-654X20LCRV</mark>',
	)
); ?>

<?php a4h_admin_page_fields(
	'checkbox',
	'enable_google_analytics_for_members',
	'تفعيل Google Analytics للأعضاء',
	array(
		'notes' => 'للسماح للأعضاء باستخدام كود Google Analytics خاص بهم يمكنهم من معرفة تقارير مشاهدات مقالاتهم',
	),
); ?>

<?php a4h_admin_page_fields(
	'text',
	'time_format',
	'صيغة الوقت',
	array(
		'class' => 'ltr sm-input',
		'notes' => 'الافتراضي <mark>j F Y - g:ia</mark> ('.wp_date('j F Y - g:ia').')'
	),
); ?>

<?php a4h_admin_page_fields(
	'checkbox',
	'enable_short_time',
	'تفعيل الوقت المختصر',
	array(
		'notes' => 'سيظهر الوقت بصيغة <mark>منذ 9 ساعات</mark> بدلا من <mark>'.wp_date('j F Y - g:ia'),
	),
); ?>

<?php a4h_hook('admin_options_extra_settings'); ?>

			</div>
			<div id="a4h-tab-header" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">الهيدر مكون من عدة صفوف وكل صف مكون من ثلاثة أماكن للعناصر (يمين - وسط - يسار)، قم بإضافة صفوف ثم قم بإضافة عناصر للأماكن</div>

<?php a4h_admin_page_fields(
	'repeater',
	'layout_header_mobile',
	'بناء الهيدر على الموبايل',
	array(
		'fields' => array(
			array(
				'layout_builder',
				'',
				'',
				array(
					'columns' => array('start', 'middle', 'end'),
					'allow_theme' => 1,
				)
			),
		),
		'add_row_label' => 'إضافة صف',
	)
); ?>

<?php a4h_admin_page_fields(
	'repeater',
	'layout_header_desktop',
	'بناء الهيدر على الديسكتوب',
	array(
		'fields' => array(
			array(
				'layout_builder',
				'',
				'',
				array(
					'columns' => array('start', 'middle', 'end'),
					'allow_theme' => 1,
				),
			),
		),
		'add_row_label' => 'إضافة صف',
	)
); ?>
				
			</div>
			<div id="a4h-tab-footer" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">الفوتر مكون من عدة صفوف وكل صف مكون من ثلاثة أماكن للعناصر (يمين - وسط - يسار)، قم بإضافة صفوف ثم قم بإضافة عناصر للأماكن</div>

				<?php a4h_admin_page_fields(
	'repeater',
	'layout_footer_mobile',
	'بناء الفوتر على الموبايل',
	array(
		'fields' => array(
			array(
				'layout_builder',
				'',
				'',
				array(
					'columns' => array('middle'),
					'allow_theme' => 1,
				),
			),
		),
		'add_row_label' => 'إضافة صف',
	)
); ?>

<?php a4h_admin_page_fields(
	'repeater',
	'layout_footer_desktop',
	'بناء الفوتر على الديسكتوب',
	array(
		'fields' => array(
			array(
				'layout_builder',
				'',
				'',
				array(
					'columns' => array('start', 'middle', 'end'),
					'allow_theme' => 1,
				),
			),
		),
		'add_row_label' => 'إضافة صف',
	)
); ?>
				
			</div>
			<div id="a4h-tab-overlay-menu" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">القائمة المنسدلة مكون من عدة صفوف، قم بإضافة صفوف ثم قم بإضافة عناصر للأماكن</div>

<?php a4h_admin_page_fields(
	'repeater',
	'layout_overlay_menu',
	'بناء القائمة المنسدلة',
	array(
		'fields' => array(
			array(
				'layout_builder',
				'',
				'',
				array(
					'columns' => array('middle'),
				),
			),
		),
		'add_row_label' => 'إضافة صف',
	)
); ?>

			</div>			
			<div id="a4h-tab-home" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">الصفحة الرئيسية مكونة عن قوائم ودجات مضاف إليها ودجات يمكن التحكم بها من صفحة الودجات</div>

				<div class="a4h-admin-page-field" style="padding: 5em 1em; text-align: center;">
					<a class="button" target="_blank" href="<?php echo admin_url('widgets.php'); ?>">إدارة الودجات</a>
				</div>
				
			</div>
			<div id="a4h-tab-archives" class="a4h-tab">

<?php a4h_admin_page_fields(
	'radio',
	'archive_pagination_mode',
	'نظام التنقل في التصنيفات',
	array(
		'options' => array('numbers' => 'صفحات مرقمة', 'dynamic' => 'زرار عرض المزيد', 'auto' => 'تحميل تلقائي'),
	),
); ?>

<?php a4h_admin_page_fields(
	'checkbox',
	'show_archive_children',
	'عرض التصنيفات الفرعية',
	array(
		'notes' => 'ستظهر قائمة روابط للتصنيفات الفرعية داخل التصنيفات الرئيسية',
	),
); ?>

<?php a4h_admin_page_fields(
	'checkbox',
	'show_archive_siblings',
	'عرض التصنيفات الشقيقة',
	array(
		'notes' => 'ستظهر قائمة روابط للتصنيفات الشقيقة داخل التصنيفات',
	),
); ?>

<?php a4h_admin_page_fields(
	'checkbox',
	'show_archive_description_continue_reading',
	'تفعيل زرار أكمل القراءة لوصف التصنيف',
	array(
		'notes' => 'سيتم اقتطاع جزء من وصف التصنيف ولإكماله يتم الضغط على زرار أكمل القراءة',
	),
); ?>

<?php a4h_admin_page_fields(
	'text',
	'archive_custom_style',
	'كود ستايل مخصص للمقالات',
	array(
		'class' => 'md-input ltr',
        'notes' => 'ادخل كود لتغيير الستايل الافتراضي للمقالات داخل التصنيفات وودجت المقالات',
	),
); ?>

			</div>
			<div id="a4h-tab-singular" class="a4h-tab">

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'show_singular_terms',
	'عرض التصنيفات',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'show_singular_featured_image',
	'عرض الصورة البارزة',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'show_singular_meta',
	'عرض معلومات المقال/الصفحة',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'show_singular_share_top',
	'عرض أزرار المشاركة العلوية',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'show_singular_share_bottom',
	'عرض أزرار المشاركة السفلية',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'show_singular_tags',
	'عرض الوسوم',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'show_singular_author_block',
	'عرض صندوق معلومات الكاتب',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'show_singular_navigation',
	'عرض روابط المقال السابق والتالي',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'enable_singular_comments_wp',
	'تفعيل تعليقات ووردبريس',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'enable_singular_comments_fb',
	'تفعيل تعليقات فيسبوك',
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'enable_singular_continue_reading',
	'تفعيل زرار أكمل القراءة',
	array(
		'notes' => 'سيتم اقتطاع جزء من المقال ولإكماله يتم الضغط على زرار أكمل القراءة',
	),
); ?>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'enable_singular_autoload_next_post',
	'تفعيل التحميل التلقائي للمقال التالي',
	array(
		'notes' => 'سيتم تحميل المقال التالي تلقائيا بعد نهاية المقال المعروض',
	),
); ?>

			</div>
		</div>
		<div class="a4h-admin-page-footer">
			<?php submit_button('', 'primary'); ?>
		</div>
		<?php a4h_admin_page_fields_insertions_output(); ?>
	</div>
<!-- content end -->
	</form>
</div>
	<?php
		}

}