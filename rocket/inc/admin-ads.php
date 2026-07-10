<?php

new a4h_admin_page_ads(array(
	'slug' => THEME_VAR_ADS,
	'field_type' => 'ads',
	'title' => 'الإعلانات',
	'menu_order' => 510,
));

class a4h_admin_page_ads {

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
	<div class="a4h-admin-page">
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
				<li><a href="#a4h-tab-adsense">إعدادات أدسنس</a></li>
				<li><a href="#a4h-tab-rs">مشاركة أرباح أدسنس</a></li>
				<li><a href="#a4h-tab-ads-txt">ملف ads.txt</a></li>
				<li><a href="#a4h-tab-stats">تفاصيل الإعلانات</a></li>
				<li><a href="#a4h-tab-misc-ads">إعلانات متفرقة</a></li>
				<li><a href="#a4h-tab-singular-ads">إعلانات المقالات/الصفحات</a></li>
				<li><a href="#a4h-tab-archive-ads">إعلانات التصنيفات/الأقسام</a></li>
				<li><a href="#a4h-tab-widgets">إعلانات الودجات</a></li>
				<li><a href="#a4h-tab-shortcodes">أكواد مختصرة</a></li>
			</ul>
		</div>	
		<div class="a4h-admin-page-main">
			<div id="a4h-tab-notes" class="a4h-tab">

<?php a4h_admin_page_fields(
	'textarea',
	'admin_notes',
	'ملاحظات الإعلانات',
	array(
		'class' => 'private-notes',
		'notes' => 'ملاحظات تظهر للأدمن فقط في هذه الصفحة',
	)
); ?>
								
			</div>
			<div id="a4h-tab-misc-ads" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">إعلانات تظهر في كل صفحات الموقع</div>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'enable_misc_ads',
	'تفعيل الإعلانات المتفرقة',
); ?>

<?php a4h_admin_page_fields(
	'repeater',
	'locations_ads_misc',
	'إعلانات متفرقة',
	array(
		'fields' => array(
			array(
				'ad',
				'',
			),
		),
		'add_row_label' => 'إضافة إعلان',
	)
); ?>
								
			</div>
			<div id="a4h-tab-singular-ads" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">إعلانات تظهر داخل المقالات/الصفحات فقط وتظهر في منطقة المحتوى</div>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'enable_singular_ads',
	'تفعيل إعلانات المقالات/الصفحات',
); ?>

<?php a4h_admin_page_fields(
	'repeater',
	'locations_ads_singular',
	'إعلانات المقالات/الصفحات',
	array(
		'fields' => array(
			array(
				'ad',
				'',
			),
		),
		'add_row_label' => 'إضافة إعلان',
	)
); ?>
								
			</div>
			<div id="a4h-tab-archive-ads" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">إعلانات تظهر داخل المقالات/الصفحات فقط وتظهر في منطقة المحتوى</div>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'enable_archive_ads',
	'تفعيل إعلانات التصنيفات/الأقسام',
); ?>

<?php a4h_admin_page_fields(
	'repeater',
	'locations_ads_archive',
	'إعلانات التصنيفات/الأقسام',
	array(
		'fields' => array(
			array(
				'ad',
				'',
			),
		),
		'add_row_label' => 'إضافة إعلان',
	)
); ?>
								
			</div>
			<div id="a4h-tab-widgets" class="a4h-tab">

					<div class="a4h-admin-page-tab-notes">إعلانات تظهر في قوائم الودجات الموجودة في أماكن متعددة في الموقع</div>

<?php a4h_admin_page_fields(
	'checkbox_with_rules',
	'enable_widget_ads',
	'تفعيل إعلانات الودجات',
); ?>

<div class="a4h-admin-page-field" style="padding: 5em 1em; text-align: center;">
	<a class="button" target="_blank" href="<?php echo admin_url('widgets.php'); ?>">إدارة الودجات</a>
</div>

			</div>
			<div id="a4h-tab-shortcodes" class="a4h-tab">

					<div class="a4h-admin-page-tab-notes">إعلانات يمكن إضافتها في أي مكان داخل المقالات/الصفحات عبر أكواد مختصرة</div>

	<?php a4h_admin_page_fields(
		'checkbox_with_rules',
		'enable_shortcode_ads',
		'تفعيل الأكواد المختصرة',
	); ?>

	<?php a4h_admin_page_fields(
		'repeater',
		'shortcode_ads',
		'أكواد مختصرة',
		array(
			'fields' => array(
				array(
					'ad',
					'',
				),
			),
			'add_row_label' => 'إضافة إعلان',
		)
	); ?>

			</div>
			<div id="a4h-tab-adsense" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">تتيح لك إضافة إعلانات أدسنس جاهزة في الموقع بدون الحاجة للحصول على شفرات من الحساب<p></p>يمكنك إضافة الأكواد التالية مقابل الشفرات:<p></p><ul class="ul-disc"><li><mark>[adsense]</mark> للإعلانات المتجاوبة</li><li><mark>[adsense 300x250]</mark> لإعلان مقاس 300x250</li></ul></div>

<?php a4h_admin_page_fields(
	'text',
	'adsense>client',
	'معرف حساب أدسنس',
	array(
		'class' => 'ltr sm-input',
		'notes' => 'قم بإدخال الرقم التعريفي للناشر (Publisher ID) الخاص بحسابك. مثال: <mark>pub-1234567891234567</mark> أو <mark>ca-pub-1234567891234567</mark>',
	),
); ?>

<?php a4h_admin_page_fields(
	'text',
	'adsense>channel',
	'رقم القناة المخصصة (اختياري)',
	array(
		'class' => 'ltr sm-input',
		'notes' => 'قم بإدخال رقم القناة المخصصة (Custom channel ID) الخاصة بحسابك',
	),
); ?>

<?php a4h_admin_page_fields(
	'text',
	'adsense>slot',
	'الرقم التعريفي للوحدة الإعلانية (اختياري)',
	array(
		'class' => 'ltr sm-input',
		'notes' => 'قم بإدخال الرقم التعريفي للوحدة الإعلانية (Ad slot ID) الخاصة بحسابك',
	),
); ?>

<?php a4h_admin_page_fields(
	'text',
	'adsense>url',
	'رابط الموقع (اختياري)',
	array(
		'class' => 'ltr sm-input',
		'notes' => 'قم بإدخال رابط الموقع (Page URL) الخاص بحسابك. مثال: <mark>example.com</mark>',
	),
); ?>

			</div>
			<div id="a4h-tab-rs" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">مشاركة الأرباح تتيح اقتسام أرباح الإعلانات من نوع (جوجل أدسنس) الموجودة في المقالات بين الموقع والأعضاء عن طريق تبديل شفرة الإعلانات بين حساب الموقع وحساب العضو</div>

<?php a4h_admin_page_fields(
	'checkbox',
	'enable_rs',
	'تفعيل مشاركة الأرباح',
	array(
		'notes' => 'للسماح بظهور إعلانات الأعضاء في مقالاتهم والربح منها',
	),
); ?>

<?php a4h_admin_page_fields(
	'checkbox',
	'enable_rs_for_contributors',
	'السماح لرتبة "مساهم" باستخدام مشاركة الأرباح',
	array(
		'notes' => 'رتبة "مساهم" (Contributor) هي الرتبة المسموح لها بإرسال مقالات للمراجعة لكن لا يمكنها تعديل المقالات المنشورة',
	),
); ?>

<?php a4h_admin_page_fields(
	'checkbox',
	'enable_rs_adsense_url',
	'السماح بإضافة رابط الموقع (Page URL) في شفرة أدسنس',
	array(
		'notes' => 'يتم التعديل على شفرة أدسنس وإضافة رابط موقع فيها عبر المتغير <mark>data-page-url</mark>. عادة يكون هذا الرابط يخص موقع مقبول في حساب أدسنس ويتيح هذا عرض إعلانات أدسنس على أي مواقع أخرى حتى لو كانت غير مقبولة في هذا الحساب.',
	),
); ?>

<?php a4h_admin_page_fields(
	'number',
	'rs_ratio',
	'نسبة ظهور إعلانات الأعضاء (%)',
	array(
		'min' => 0,
		'max' => 100,
		'step' => 1,
		'notes' => '<ul class="ul-disc"><li>إذا قمت بإدخال القيمة <mark>80</mark> فستظهر إعلانات الأعضاء بنسبة <mark>80%</mark> وستظهر إعلانات الموقع بنسبة <mark>20%</mark></li><li>إذا قمت بإدخال القيمة <mark>100</mark> فستظهر إعلانات الأعضاء طوال الوقت ولن تظهر إعلانات الموقع</li></ul>',
	)
); ?>

<?php a4h_admin_page_fields(
	'repeater',
	'rs_ratio_custom',
	'نسبة ظهور مخصصة للأعضاء',
	array(
		'fields' => array(
			array(
				'number',
				'user_id',
				'رقم العضو',
				array(
					'min' => 1,
					'step' => 1,
				),
			),
			array(
				'number',
				'user_ratio',
				'نسبة الظهور',
				array(
					'min' => 0,
					'max' => 100,
					'step' => 1,
				),
			),
		),
		'add_row_label' => 'إضافة عضو',
	)
); ?>

<?php a4h_hook('admin_ads_rs_ads_extra_setting'); ?>

			</div>
			<div id="a4h-tab-ads-txt" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">ملف ads.txt هو ملف خاص بالإعلانات ويتم إنشاءه في الموقع. <a target="_blank" href="https://support.google.com/adsense/answer/7532444?hl=ar" class="underline">المزيد من المعلومات</a></div>

<?php a4h_admin_page_fields(
	'checkbox',
	'enable_ads_txt_file',
	'تفعيل إنشاء ملف ads.txt',
	array(
		'notes' => 'عند تفعيل هذا الخيار سيتم الإنشاء التلقائي للملف واستبدال أي ملف آخر موجود',
	),
); ?>

<?php a4h_admin_page_fields(
	'textarea',
	'ads_txt_file_extra_codes',
	'أكواد إضافية لملف ads.txt',
	array(
		'class' => 'ltr',
		'notes' => '<p>استخدم هذا الحقل لإضافة أي أكواد أخرى إلى ملف ads.txt.</p>
	<p>مثال: <mark>google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0</mark></p>
	<p>ملحوظة: يتم إضافة كود أدسنس الموجود في حقل (معرف حساب أدسنس) وأكواد أدسنس الخاصة بالأعضاء تلقائيا في الملف.</p>
	<p><a target="_blank" href="/ads.txt" class="underline">عرض ملف ads.txt</a></p>',
	),
); ?>

			</div>
			<div id="a4h-tab-stats" class="a4h-tab">

				<div class="a4h-admin-page-tab-notes">هنا تظهر تفاصيل الإعلانات المفعلة في الموقع والمضافة عبر نظام الإعلانات في الموقع لسهولة التتبع</div>

<table class="a4h-admin-table">
	<tr>
		<th>وصف</th>
		<th>مكان</th>
		<th>مصدر</th>
		<th>تفاصيل</th>
	</tr>

<?php
function a4h_ads_stats_extract_domain_from_code($code) {
	$domains = [];
    if ( strpos($code, '[adsense') !== false ) {
        $domains[] = 'جوجل أدسنس';
    } else if ( strpos($code, '[widgets_list') !== false ) {
        $domains[] = 'قائمة ودجات';
    } else if ( strpos($code, '[block') !== false ) {
        $domains[] = 'بلوك';
    }
    $dom = str_get_html($code);
	foreach ( $dom->find('script') as $script ) {
        $src = $script->getAttribute('src');
        if ( !empty($src) ) {
            $parsed_url = parse_url($src);
            if ( $parsed_url !== false && isset($parsed_url['host'])) {
                $domains[] = $parsed_url['host'];
            } elseif ( preg_match('/\/\/([^\/]+)/', $src, $matches) ) {
                $domains[] = $matches[1];
            }
        }
	}
   return implode(' - ', $domains);
}
function a4h_ads_stats_ad_row($ad, $ad_group = '') {
	$details_arr = array();
	$details_arr[] = !empty($ad['hide']) ? ( $ad['hide'] == 'mobile' ? 'مخفي على الموبايل' : 'مخفي على الديسكتوب' ) : '';
	$details_arr[] = !empty($ad['force']) ? 'إجبار على الظهور' : '';
	$details_arr[] = !empty($ad['rules']) ? 'كود شرط ظهور: <mark>'.$ad['rules'].'</mark>' : '';
	$details_arr = array_filter($details_arr);
	$details_str = implode('', array_map(function($detail) {
		return sprintf('<li>%s</li>', $detail);
	}, $details_arr));
	$details_str = $details_arr ? sprintf('<ul class="ul-disc">%s</ul>', $details_str) : '';
    return sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
		!empty($ad['label']) ? $ad['label'] : '------',
		!empty(a4h_admin_page_ads_locations()[$ad_group]) ? a4h_admin_page_ads_locations()[$ad_group][$ad['location']] : $ad_group,
		!empty($ad['code']) ? a4h_ads_stats_extract_domain_from_code($ad['code']) : '',
		$details_str,
	);
}

?>
	<tr>
		<td colspan="4" class="a4h-admin-table-heading">إعلانات متفرقة</td>
	</tr>
<?php

$ads = array_filter((array)a4h_ads('locations_ads_misc'));
$ads = array_filter($ads, function($ad_key) use($ads) {
	return $ad_key != 'sample' && !empty($ads[$ad_key]['status']);
}, ARRAY_FILTER_USE_KEY);
foreach ( $ads as $ad ) {
	echo a4h_ads_stats_ad_row($ad, 'misc');
}

?>
	<tr>
		<td colspan="4" class="a4h-admin-table-heading">إعلانات المقالات/الصفحات</td>
	</tr>
<?php

$ads = array_filter((array)a4h_ads('locations_ads_singular'));
$ads = array_filter($ads, function($ad_key) use($ads) {
	return $ad_key != 'sample' && !empty($ads[$ad_key]['status']);
}, ARRAY_FILTER_USE_KEY);
foreach ( $ads as $ad ) {
	echo a4h_ads_stats_ad_row($ad, 'singular');
}

?>
	<tr>
		<td colspan="4" class="a4h-admin-table-heading">إعلانات التصنيفات/الأقسام</td>
	</tr>
<?php

$ads = array_filter((array)a4h_ads('locations_ads_archive'));
$ads = array_filter($ads, function($ad_key) use($ads) {
	return $ad_key != 'sample' && !empty($ads[$ad_key]['status']);
}, ARRAY_FILTER_USE_KEY);
foreach ( $ads as $ad ) {
	echo a4h_ads_stats_ad_row($ad, 'archive');
}

?>
	<tr>
		<td colspan="4" class="a4h-admin-table-heading">إعلانات الودجات</td>
	</tr>
<?php

$ads = array();

$sidebars = array();
$sidebars['home_'] = 'الصفحة الرئيسية';
$sidebars['widgets_list_'] = 'قائمة ودجات';
$sidebars['header_after'] = 'أسفل الهيدر';
$sidebars['footer_before'] = 'أعلى الفوتر';
$sidebars['archive_side'] = 'جانب الأقسام والتصنيفات';
$sidebars['singular_side'] = 'جانب المقالات';
$sidebars['singular_middle'] = 'منتصف المقالات';
$sidebars['singular_end'] = 'نهاية المقالات';
$sidebars['singular_after'] = 'أسفل المقالات';

global $sidebars_widgets;
foreach ( $sidebars_widgets as $sidebar_id => $sidebar_widgets ) {
	if ( $sidebar_id == 'wp_inactive_widgets' || empty($sidebar_widgets) ) {
		continue;
	}
	foreach ( $sidebar_widgets as $widget_index => $widget_id ) {
		$widget_base = _get_widget_id_base($widget_id);
		$widget_base_instance = get_option('widget_'.$widget_base);
		$widget_number = str_replace($widget_base.'-', '', $widget_id);
		$widget_instance = !empty($widget_base_instance) ? $widget_base_instance[$widget_number] : 0;

		if ( !empty($widget_instance['is_ad_widget']) ) {

            preg_match('/\d+/', $sidebar_id, $matches);

            $number = isset($matches[0]) ? $matches[0] : null;

            $sidebar_without_number = preg_replace('/\d+/', '', $sidebar_id);

			$widget_instance['location'] = $sidebars[$sidebar_without_number];
            $widget_instance['location'] .= $number ? ' #'.$number : '';
            $widget_instance['rules'] = $widget_instance['widget_rules'] ?? '';
			$ads[] = $widget_instance;
		}
	}
}
$ads = array_filter($ads, function($ad_key) use($ads) {
	return empty($ads[$ad_key]['widget_disable']);
}, ARRAY_FILTER_USE_KEY);
foreach ( $ads as $ad ) {
	echo a4h_ads_stats_ad_row($ad, $ad['location']);
}

?>
	<tr>
		<td colspan="4" class="a4h-admin-table-heading">أكواد مختصرة</td>
	</tr>
<?php

$ads = array_filter((array)a4h_ads('shortcode_ads'));
$ads = array_filter($ads, function($ad_key) use($ads) {
	return $ad_key != 'sample' && !empty($ads[$ad_key]['status']);
}, ARRAY_FILTER_USE_KEY);
foreach ( $ads as $ad ) {
	echo a4h_ads_stats_ad_row($ad);
}

?>
</table>
								
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