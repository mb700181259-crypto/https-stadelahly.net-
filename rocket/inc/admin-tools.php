<?php

new a4h_admin_page_tools(array(
	'slug' => THEME_VAR_TOOLS,
	'field_type' => 'tools',
	'title' => 'الأدوات',
	'menu_order' => 520,
));

class a4h_admin_page_tools {

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
				<li><a href="#a4h-tab-theme">القالب</a></li>
				<li><a href="#a4h-tab-import">استيراد الإعدادات</a></li>
				<li><a href="#a4h-tab-export">تصدير الإعدادات</a></li>
				<li><a href="#a4h-tab-reset">الإعدادات الافتراضية</a></li>
				<li><a href="#a4h-tab-help">مساعدة</a></li>
			</ul>
		</div>	
		<div class="a4h-admin-page-main">
			<div id="a4h-tab-theme" class="a4h-tab">

<?php a4h_admin_page_fields(
	'checkbox',
	'enable_updates',
	'إظهار تنبيه التحديثات',
	array(
		'notes' => 'سيظهر تنبيه في لوحة التحكم في حالة وجود تحديث للقالب',
	),
); ?>

<?php a4h_admin_page_fields(
	'checkbox',
	'enable_count_views',
	'تفعيل احتساب مشاهدات المقالات/الصفحات',
	array(
		'notes' => 'تفعيل هذا الخيار يتيح ترتيب المقالات حسب المشاهدات ولكنه له تأثير على التحميل على السيرفر',
	),
); ?>

			</div>
			<div id="a4h-tab-import" class="a4h-tab">

<?php a4h_admin_page_fields(
	'import_settings',
	'',
	'استيراد الإعدادات',
	array(
		'notes' => 'قم برفع ملف الإعدادات المحفوظ مسبقا',
	),
); ?>

			</div>
			<div id="a4h-tab-export" class="a4h-tab">

<?php a4h_admin_page_fields(
	'export_settings',
	'',
	'استيراد الإعدادات',
	array(
		'notes' => 'سيتم حفظ ملف الإعدادات على جهازك',
	),
); ?>

			</div>
			<div id="a4h-tab-reset" class="a4h-tab">

<?php a4h_admin_page_fields(
	'reset_settings',
	'',
	'استعادة الإعدادات الافتراضية',
); ?>

			</div>
			<div id="a4h-tab-help" class="a4h-tab">

<ul class="ul-disc">
	<li><a target="_blank" href="https://rocket.arb4host.net/docs/">شرح التركيب والاستخدام</a></li>
	<li><a target="_blank" href="https://cp.arb4host.net/">الدعم الفني والمبيعات</a></li>
</ul>

			</div>
		</div>
		<div class="a4h-admin-page-footer">
			<?php submit_button('', 'primary'); ?>
		</div>
	</div>
<!-- content end -->
	</form>
</div>
	<?php
		}

}