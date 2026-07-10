<?php
/**
 * Dedicated admin control panel.
 *
 * A tabbed settings screen (Appearance → Manchit) covering identity/colors,
 * typography, layout, article display, SEO & Google News/Discover, the Ads
 * Manager and social profiles. Saves to the `manchit_options` and `manchit_ads`
 * option stores. Everything is nonce-protected and capability-gated.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the admin menu.
 */
function manchit_admin_menu() {
	add_theme_page(
		__( 'إعدادات قالب Manchit', 'manchit' ),
		__( 'إعدادات Manchit', 'manchit' ),
		'edit_theme_options',
		'manchit-settings',
		'manchit_render_settings_page'
	);
}
add_action( 'admin_menu', 'manchit_admin_menu' );

/**
 * Tabs definition.
 *
 * @return array
 */
function manchit_settings_tabs() {
	return array(
		'general' => __( 'عام والهوية', 'manchit' ),
		'fonts'   => __( 'الخطوط', 'manchit' ),
		'layout'  => __( 'التخطيط', 'manchit' ),
		'article' => __( 'المقالات', 'manchit' ),
		'seo'     => __( 'السيو وجوجل نيوز', 'manchit' ),
		'ads'     => __( 'إدارة الإعلانات', 'manchit' ),
		'social'  => __( 'التواصل الاجتماعي', 'manchit' ),
	);
}

/**
 * Handle form submission.
 */
function manchit_handle_settings_save() {
	if ( empty( $_POST['manchit_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['manchit_settings_nonce'] ), 'manchit_save_settings' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$tab = isset( $_POST['manchit_tab'] ) ? sanitize_key( $_POST['manchit_tab'] ) : 'general';

	if ( 'ads' === $tab ) {
		manchit_save_ads();
	} else {
		manchit_save_options();
	}

	add_settings_error( 'manchit', 'saved', __( 'تم حفظ الإعدادات بنجاح.', 'manchit' ), 'updated' );
	set_transient( 'manchit_settings_notice', 'saved', 30 );
}
add_action( 'admin_init', 'manchit_handle_settings_save' );

/**
 * Sanitize + persist general option fields.
 */
function manchit_save_options() {
	$in       = isset( $_POST['manchit_options'] ) && is_array( $_POST['manchit_options'] ) ? wp_unslash( $_POST['manchit_options'] ) : array();
	$current  = manchit_get_options();
	$defaults = manchit_default_options();
	$clean    = $current;

	foreach ( $in as $key => $value ) {
		if ( ! array_key_exists( $key, $defaults ) ) {
			continue;
		}
		$clean[ $key ] = manchit_sanitize_option( $key, $value );
	}

	// Unchecked checkboxes are not present in POST — reconcile per current tab.
	$tab_keys = manchit_option_keys_for_tab( sanitize_key( $_POST['manchit_tab'] ?? 'general' ) );
	foreach ( $tab_keys as $key => $type ) {
		if ( 'checkbox' === $type && ! isset( $in[ $key ] ) ) {
			$clean[ $key ] = 0;
		}
	}

	// Social is a nested array.
	if ( isset( $in['social'] ) && is_array( $in['social'] ) ) {
		$clean['social'] = array_map( 'esc_url_raw', array_map( 'wp_unslash', $in['social'] ) );
	}
	// Share networks multi.
	if ( isset( $in['share_networks'] ) ) {
		$clean['share_networks'] = array_map( 'sanitize_key', (array) $in['share_networks'] );
	}

	update_option( 'manchit_options', $clean );
}

/**
 * Sanitize a single option by key/type.
 *
 * @param string $key   Option key.
 * @param mixed  $value Raw value.
 * @return mixed
 */
function manchit_sanitize_option( $key, $value ) {
	$colors  = array( 'brand_color', 'accent_color' );
	$urls    = array( 'organization_logo', 'publisher_logo', 'fallback_image' );
	$ints    = array( 'base_font_size', 'toc_min_headings', 'related_count', 'excerpt_length', 'footer_columns', 'ticker_category' );
	$raw     = array( 'copyright_text' );

	if ( in_array( $key, $colors, true ) ) {
		return sanitize_hex_color( $value ) ?: '';
	}
	if ( in_array( $key, $urls, true ) ) {
		return esc_url_raw( $value );
	}
	if ( in_array( $key, $ints, true ) ) {
		return (int) $value;
	}
	if ( in_array( $key, $raw, true ) ) {
		return wp_kses_post( $value );
	}
	if ( is_array( $value ) ) {
		return array_map( 'sanitize_text_field', $value );
	}
	return sanitize_text_field( $value );
}

/**
 * Persist ads from the Ads Manager tab.
 */
function manchit_save_ads() {
	$ads = manchit_get_ads();

	$ads['enabled']        = ! empty( $_POST['manchit_ads']['enabled'] ) ? 1 : 0;
	$ads['lazy']           = ! empty( $_POST['manchit_ads']['lazy'] ) ? 1 : 0;
	$ads['hide_logged_in'] = ! empty( $_POST['manchit_ads']['hide_logged_in'] ) ? 1 : 0;

	$units = array();
	if ( ! empty( $_POST['manchit_ads']['units'] ) && is_array( $_POST['manchit_ads']['units'] ) ) {
		$locations = array_keys( manchit_ad_locations() );
		foreach ( wp_unslash( $_POST['manchit_ads']['units'] ) as $unit ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$code = trim( (string) ( $unit['code'] ?? '' ) );
			if ( '' === $code && '' === trim( (string) ( $unit['title'] ?? '' ) ) ) {
				continue;
			}
			$units[] = array(
				'title'     => sanitize_text_field( $unit['title'] ?? '' ),
				'location'  => in_array( $unit['location'] ?? '', $locations, true ) ? $unit['location'] : 'before_content',
				// Ad code may contain <script>/AdSense — allowed for admins only.
				'code'      => $code,
				'paragraph' => max( 1, (int) ( $unit['paragraph'] ?? 3 ) ),
				'devices'   => in_array( $unit['devices'] ?? 'all', array( 'all', 'mobile', 'desktop' ), true ) ? $unit['devices'] : 'all',
				'status'    => ! empty( $unit['status'] ) ? 1 : 0,
			);
		}
	}
	$ads['units'] = $units;

	update_option( 'manchit_ads', $ads );

	// Revenue-share settings live on the ads tab but belong to manchit_options.
	$opts              = manchit_get_options();
	$opts['rs_enable'] = ! empty( $_POST['manchit_options']['rs_enable'] ) ? 1 : 0;
	if ( isset( $_POST['manchit_options']['rs_ratio'] ) ) {
		$opts['rs_ratio'] = max( 0, min( 100, (int) $_POST['manchit_options']['rs_ratio'] ) );
	}
	update_option( 'manchit_options', $opts );
}

/**
 * Map of option keys → field type for a tab (used to reconcile checkboxes).
 *
 * @param string $tab Tab key.
 * @return array
 */
function manchit_option_keys_for_tab( $tab ) {
	$map = array(
		'general' => array( 'show_theme_toggle' => 'checkbox' ),
		'layout'  => array(
			'sticky_header'         => 'checkbox',
			'hide_header_on_scroll' => 'checkbox',
			'show_topbar'           => 'checkbox',
			'show_breadcrumbs'      => 'checkbox',
			'show_news_ticker'      => 'checkbox',
			'optimize_css'          => 'checkbox',
			'prefetch_links'        => 'checkbox',
			'ads_preconnect'        => 'checkbox',
			'webp_swap'             => 'checkbox',
			'preload_featured'      => 'checkbox',
			'lazy_iframes'          => 'checkbox',
		),
		'article' => array(
			'show_featured_image'   => 'checkbox',
			'show_author_box'       => 'checkbox',
			'show_post_views'       => 'checkbox',
			'show_reading_progress' => 'checkbox',
			'show_toc'              => 'checkbox',
			'show_share'            => 'checkbox',
			'show_related'          => 'checkbox',
			'show_prev_next'        => 'checkbox',
		),
		'seo'     => array(
			'enable_schema'           => 'checkbox',
			'enable_og'               => 'checkbox',
			'enable_twitter_cards'    => 'checkbox',
			'enable_websearch_schema' => 'checkbox',
			'enable_speakable'        => 'checkbox',
		),
	);
	return $map[ $tab ] ?? array();
}

/**
 * Render the settings page.
 */
function manchit_render_settings_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$tabs        = manchit_settings_tabs();
	$current_tab = isset( $_GET['tab'] ) && isset( $tabs[ sanitize_key( $_GET['tab'] ) ] ) ? sanitize_key( $_GET['tab'] ) : 'general';
	$o           = manchit_get_options();
	?>
	<div class="wrap manchit-admin">
		<h1 class="manchit-admin__brand">
			<span class="manchit-admin__logo">Manchit</span>
			<small><?php esc_html_e( 'قالب أخبار احترافي مُحسّن لجوجل نيوز واكتشاف جوجل', 'manchit' ); ?></small>
		</h1>

		<?php if ( get_transient( 'manchit_settings_notice' ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'تم حفظ الإعدادات بنجاح.', 'manchit' ); ?></p></div>
			<?php delete_transient( 'manchit_settings_notice' ); ?>
		<?php endif; ?>

		<nav class="nav-tab-wrapper manchit-tabs">
			<?php foreach ( $tabs as $slug => $label ) : ?>
				<a class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>"
					href="<?php echo esc_url( admin_url( 'themes.php?page=manchit-settings&tab=' . $slug ) ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="<?php echo esc_url( admin_url( 'themes.php?page=manchit-settings&tab=' . $current_tab ) ); ?>" class="manchit-form">
			<?php wp_nonce_field( 'manchit_save_settings', 'manchit_settings_nonce' ); ?>
			<input type="hidden" name="manchit_tab" value="<?php echo esc_attr( $current_tab ); ?>">

			<div class="manchit-panel">
				<?php
				$callback = 'manchit_tab_' . $current_tab;
				if ( function_exists( $callback ) ) {
					call_user_func( $callback, $o );
				}
				?>
			</div>

			<p class="submit">
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'حفظ التغييرات', 'manchit' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Field helpers
 * ---------------------------------------------------------------------- */

/**
 * Render a labelled row.
 *
 * @param string $label Label.
 * @param string $field Field HTML.
 * @param string $desc  Description.
 */
function manchit_field_row( $label, $field, $desc = '' ) {
	printf(
		'<div class="manchit-row"><div class="manchit-row__label">%s</div><div class="manchit-row__control">%s%s</div></div>',
		esc_html( $label ),
		$field, // built by trusted helpers below
		$desc ? '<p class="description">' . esc_html( $desc ) . '</p>' : ''
	);
}

/**
 * Text/number/color/url input.
 */
function manchit_input( $key, $value, $type = 'text', $attrs = '' ) {
	return sprintf(
		'<input type="%s" name="manchit_options[%s]" value="%s" class="regular-text" %s>',
		esc_attr( $type ),
		esc_attr( $key ),
		esc_attr( $value ),
		$attrs
	);
}

/**
 * Checkbox toggle.
 */
function manchit_toggle( $key, $value, $label = '' ) {
	return sprintf(
		'<label class="manchit-switch"><input type="checkbox" name="manchit_options[%s]" value="1" %s><span class="manchit-slider"></span> %s</label>',
		esc_attr( $key ),
		checked( 1, (int) $value, false ),
		esc_html( $label )
	);
}

/**
 * Select.
 */
function manchit_select( $key, $value, $choices ) {
	$out = sprintf( '<select name="manchit_options[%s]" class="regular-text">', esc_attr( $key ) );
	foreach ( $choices as $val => $label ) {
		$out .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( $value, $val, false ), esc_html( $label ) );
	}
	$out .= '</select>';
	return $out;
}

/* -------------------------------------------------------------------------
 * Tab renderers
 * ---------------------------------------------------------------------- */

function manchit_tab_general( $o ) {
	manchit_field_row( __( 'اللون الأساسي', 'manchit' ), manchit_input( 'brand_color', $o['brand_color'], 'text', 'data-manchit-color="1"' ), __( 'لون الهوية الرئيسي للموقع.', 'manchit' ) );
	manchit_field_row( __( 'اللون المميز', 'manchit' ), manchit_input( 'accent_color', $o['accent_color'], 'text', 'data-manchit-color="1"' ) );
	manchit_field_row( __( 'الوضع الافتراضي', 'manchit' ), manchit_select( 'default_theme_mode', $o['default_theme_mode'], array(
		'auto'  => __( 'تلقائي (حسب النظام)', 'manchit' ),
		'light' => __( 'نهاري', 'manchit' ),
		'dark'  => __( 'ليلي', 'manchit' ),
	) ) );
	manchit_field_row( __( 'زر تبديل الوضع', 'manchit' ), manchit_toggle( 'show_theme_toggle', $o['show_theme_toggle'], __( 'إظهار زر الوضع الليلي/النهاري في الهيدر', 'manchit' ) ) );
	manchit_field_row( __( 'اسم الناشر (Organization)', 'manchit' ), manchit_input( 'organization_name', $o['organization_name'] ), __( 'يُستخدم في بيانات Schema. اتركه فارغاً لاستخدام اسم الموقع.', 'manchit' ) );
	manchit_field_row( __( 'شعار الناشر (Logo)', 'manchit' ), manchit_input( 'publisher_logo', $o['publisher_logo'], 'text', 'data-manchit-media="1"' ), __( 'رابط شعار مربّع/أفقي واضح — مطلوب لجوجل نيوز.', 'manchit' ) );
	manchit_field_row( __( 'صورة افتراضية للمقالات', 'manchit' ), manchit_input( 'fallback_image', $o['fallback_image'], 'text', 'data-manchit-media="1"' ), __( 'تُستخدم عند غياب الصورة البارزة (للمشاركة والاكتشاف).', 'manchit' ) );
	manchit_field_row( __( 'نص حقوق النشر', 'manchit' ), sprintf( '<textarea name="manchit_options[copyright_text]" class="large-text" rows="2">%s</textarea>', esc_textarea( $o['copyright_text'] ) ) );
}

function manchit_tab_fonts( $o ) {
	$choices = array();
	foreach ( manchit_font_choices() as $key => $data ) {
		$choices[ $key ] = $data['label'];
	}
	manchit_field_row( __( 'خط المتن', 'manchit' ), manchit_select( 'body_font', $o['body_font'], $choices ), __( 'خط النصوص. "خط النظام" أسرع خيار (بدون تحميل).', 'manchit' ) );
	manchit_field_row( __( 'خط العناوين', 'manchit' ), manchit_select( 'heading_font', $o['heading_font'], $choices ) );
	manchit_field_row( __( 'حجم الخط الأساسي (px)', 'manchit' ), manchit_input( 'base_font_size', $o['base_font_size'], 'number', 'min="14" max="20"' ), __( 'بين 14 و 20 بكسل.', 'manchit' ) );
	echo '<p class="manchit-hint">' . esc_html__( 'يتم تحميل الخطوط بتقنية display:swap مع preload لضمان عدم اختفاء النص وسرعة LCP.', 'manchit' ) . '</p>';
}

function manchit_tab_layout( $o ) {
	manchit_field_row( __( 'تخطيط الصفحات', 'manchit' ), manchit_select( 'layout', $o['layout'], array(
		'right-sidebar' => __( 'شريط جانبي يمين', 'manchit' ),
		'left-sidebar'  => __( 'شريط جانبي يسار', 'manchit' ),
		'full'          => __( 'عرض كامل', 'manchit' ),
	) ) );
	manchit_field_row( __( 'نمط الصفحة الرئيسية', 'manchit' ), manchit_select( 'homepage_style', $o['homepage_style'], array(
		'magazine' => __( 'مجلة (هيرو + شبكة)', 'manchit' ),
		'grid'     => __( 'شبكة', 'manchit' ),
		'list'     => __( 'قائمة', 'manchit' ),
	) ) );
	manchit_field_row( __( 'هيدر ثابت', 'manchit' ), manchit_toggle( 'sticky_header', $o['sticky_header'], __( 'تثبيت الهيدر عند التمرير', 'manchit' ) ) );
	manchit_field_row( __( 'إخفاء الهيدر عند النزول', 'manchit' ), manchit_toggle( 'hide_header_on_scroll', $o['hide_header_on_scroll'], __( 'إظهاره عند الصعود فقط', 'manchit' ) ) );
	manchit_field_row( __( 'الشريط العلوي', 'manchit' ), manchit_toggle( 'show_topbar', $o['show_topbar'], __( 'إظهار الشريط العلوي (التاريخ/قائمة علوية)', 'manchit' ) ) );
	manchit_field_row( __( 'مسار التنقل (Breadcrumbs)', 'manchit' ), manchit_toggle( 'show_breadcrumbs', $o['show_breadcrumbs'], __( 'إظهار مسار التنقل', 'manchit' ) ) );
	manchit_field_row( __( 'شريط الأخبار العاجلة', 'manchit' ), manchit_toggle( 'show_news_ticker', $o['show_news_ticker'], __( 'شريط متحرك بأحدث الأخبار', 'manchit' ) ) );
	manchit_field_row( __( 'أعمدة التذييل', 'manchit' ), manchit_input( 'footer_columns', $o['footer_columns'], 'number', 'min="1" max="4"' ) );

	echo '<hr><p class="manchit-hint">' . esc_html__( 'تحسينات الأداء (Core Web Vitals):', 'manchit' ) . '</p>';
	manchit_field_row( __( 'تحسين تسليم CSS', 'manchit' ), manchit_toggle( 'optimize_css', $o['optimize_css'], __( 'Critical CSS مضمّن + تحميل غير حاجب للعرض', 'manchit' ) ), __( 'يزيل تحذير "Render-blocking CSS". أطفئه فقط لو لاحظت وميض تنسيق.', 'manchit' ) );
	manchit_field_row( __( 'جلب مسبق للروابط', 'manchit' ), manchit_toggle( 'prefetch_links', $o['prefetch_links'], __( 'تصفّح شبه فوري (Speculation Rules)', 'manchit' ) ) );
	manchit_field_row( __( 'preconnect لشبكات الإعلانات', 'manchit' ), manchit_toggle( 'ads_preconnect', $o['ads_preconnect'], __( 'تسريع أول طلب إعلان عند تفعيل الإعلانات', 'manchit' ) ) );
	manchit_field_row( __( 'تفضيل صور WebP', 'manchit' ), manchit_toggle( 'webp_swap', $o['webp_swap'], __( 'استخدام نسخة .webp إن وُجدت بجانب الصورة', 'manchit' ) ), __( 'فعّله إن كان لديك مولّد WebP (إضافة/سيرفر/CDN).', 'manchit' ) );
	manchit_field_row( __( 'preload صورة المقال', 'manchit' ), manchit_toggle( 'preload_featured', $o['preload_featured'], __( 'تحميل مسبق لصورة LCP في المقال', 'manchit' ) ) );
	manchit_field_row( __( 'lazy-load للـ iframes', 'manchit' ), manchit_toggle( 'lazy_iframes', $o['lazy_iframes'], __( 'تأجيل تحميل يوتيوب/الإطارات', 'manchit' ) ) );
}

function manchit_tab_article( $o ) {
	manchit_field_row( __( 'الصورة البارزة', 'manchit' ), manchit_toggle( 'show_featured_image', $o['show_featured_image'], __( 'عرض الصورة البارزة في المقال', 'manchit' ) ) );
	manchit_field_row( __( 'صندوق الكاتب', 'manchit' ), manchit_toggle( 'show_author_box', $o['show_author_box'], __( 'عرض نبذة الكاتب أسفل المقال', 'manchit' ) ) );
	manchit_field_row( __( 'عداد المشاهدات', 'manchit' ), manchit_toggle( 'show_post_views', $o['show_post_views'], __( 'إظهار عدد المشاهدات', 'manchit' ) ) );
	manchit_field_row( __( 'شريط تقدم القراءة', 'manchit' ), manchit_toggle( 'show_reading_progress', $o['show_reading_progress'], __( 'شريط علوي يوضح نسبة القراءة', 'manchit' ) ) );
	manchit_field_row( __( 'جدول المحتويات', 'manchit' ), manchit_toggle( 'show_toc', $o['show_toc'], __( 'إنشاء جدول محتويات تلقائي', 'manchit' ) ) );
	manchit_field_row( __( 'أقل عدد عناوين للجدول', 'manchit' ), manchit_input( 'toc_min_headings', $o['toc_min_headings'], 'number', 'min="2" max="10"' ) );
	manchit_field_row( __( 'أزرار المشاركة', 'manchit' ), manchit_toggle( 'show_share', $o['show_share'], __( 'إظهار أزرار مشاركة المقال', 'manchit' ) ) );
	manchit_field_row( __( 'مقالات ذات صلة', 'manchit' ), manchit_toggle( 'show_related', $o['show_related'], __( 'عرض مقالات ذات صلة', 'manchit' ) ) );
	manchit_field_row( __( 'عدد المقالات ذات الصلة', 'manchit' ), manchit_input( 'related_count', $o['related_count'], 'number', 'min="2" max="12"' ) );
	manchit_field_row( __( 'التنقل بين المقالات', 'manchit' ), manchit_toggle( 'show_prev_next', $o['show_prev_next'], __( 'روابط المقال السابق/التالي', 'manchit' ) ) );
	manchit_field_row( __( 'طول المقتطف (كلمات)', 'manchit' ), manchit_input( 'excerpt_length', $o['excerpt_length'], 'number', 'min="8" max="60"' ) );
}

function manchit_tab_seo( $o ) {
	echo '<p class="manchit-hint">' . esc_html__( 'يتوقف قالبنا تلقائياً عن إخراج البيانات المنظمة و Open Graph إذا وجد إضافة سيو (Rank Math / Yoast) لتفادي التكرار.', 'manchit' ) . '</p>';
	manchit_field_row( __( 'تفعيل البيانات المنظمة (Schema)', 'manchit' ), manchit_toggle( 'enable_schema', $o['enable_schema'], __( 'إخراج NewsArticle / Organization / WebSite', 'manchit' ) ) );
	manchit_field_row( __( 'نوع المقال في Schema', 'manchit' ), manchit_select( 'schema_article_type', $o['schema_article_type'], array(
		'NewsArticle' => 'NewsArticle (موصى به للأخبار)',
		'Article'     => 'Article',
		'BlogPosting' => 'BlogPosting',
	) ), __( 'اختر NewsArticle لأهلية جوجل نيوز.', 'manchit' ) );
	manchit_field_row( __( 'Open Graph', 'manchit' ), manchit_toggle( 'enable_og', $o['enable_og'], __( 'وسوم المشاركة على فيسبوك وواتساب', 'manchit' ) ) );
	manchit_field_row( __( 'بطاقات تويتر (X)', 'manchit' ), manchit_toggle( 'enable_twitter_cards', $o['enable_twitter_cards'], __( 'بطاقة صورة كبيرة', 'manchit' ) ) );
	manchit_field_row( __( 'حساب X للموقع', 'manchit' ), manchit_input( 'twitter_site', $o['twitter_site'], 'text', 'placeholder="@example"' ) );
	manchit_field_row( __( 'معاينة الصور (Discover)', 'manchit' ), manchit_select( 'max_image_preview', $o['max_image_preview'], array(
		'large'    => __( 'كبيرة (موصى به لاكتشاف جوجل)', 'manchit' ),
		'standard' => __( 'قياسية', 'manchit' ),
		'none'     => __( 'بدون', 'manchit' ),
	) ), __( 'max-image-preview:large ضروري لظهور الصور في Google Discover.', 'manchit' ) );
	manchit_field_row( __( 'صندوق بحث Sitelinks', 'manchit' ), manchit_toggle( 'enable_websearch_schema', $o['enable_websearch_schema'], __( 'يفعّل صندوق البحث في نتائج جوجل (Google Suggest)', 'manchit' ) ) );
	manchit_field_row( __( 'Speakable (المساعد الصوتي)', 'manchit' ), manchit_toggle( 'enable_speakable', $o['enable_speakable'], __( 'تحديد الأجزاء القابلة للقراءة صوتياً', 'manchit' ) ) );
}

function manchit_tab_social( $o ) {
	$social   = wp_parse_args( (array) $o['social'], manchit_default_options()['social'] );
	$networks = array(
		'facebook'  => 'Facebook',
		'x'         => 'X (Twitter)',
		'instagram' => 'Instagram',
		'youtube'   => 'YouTube',
		'telegram'  => 'Telegram',
		'tiktok'    => 'TikTok',
		'whatsapp'  => 'WhatsApp',
		'rss'       => 'RSS',
	);
	foreach ( $networks as $key => $label ) {
		manchit_field_row(
			$label,
			sprintf( '<input type="url" name="manchit_options[social][%s]" value="%s" class="regular-text" placeholder="https://">', esc_attr( $key ), esc_attr( $social[ $key ] ?? '' ) )
		);
	}
}

/**
 * Ads Manager tab (custom repeater — not the generic option form).
 *
 * @param array $o Options (unused; ads use their own store).
 */
function manchit_tab_ads( $o ) {
	$ads       = manchit_get_ads();
	$locations = manchit_ad_locations();
	?>
	<div class="manchit-ads-global">
		<label class="manchit-switch"><input type="checkbox" name="manchit_ads[enabled]" value="1" <?php checked( 1, (int) $ads['enabled'] ); ?>><span class="manchit-slider"></span> <?php esc_html_e( 'تفعيل الإعلانات', 'manchit' ); ?></label>
		<label class="manchit-switch"><input type="checkbox" name="manchit_ads[hide_logged_in]" value="1" <?php checked( 1, (int) $ads['hide_logged_in'] ); ?>><span class="manchit-slider"></span> <?php esc_html_e( 'إخفاء الإعلانات عن الأعضاء المسجلين', 'manchit' ); ?></label>
	</div>
	<p class="manchit-hint manchit-hint--good"><?php esc_html_e( '‏١٠٠٪ من أرباح الإعلانات لك — هذا القالب لا يحقن أي إعلانات لصالح المطوّر ولا يأخذ أي نسبة أرباح.', 'manchit' ); ?></p>

	<div id="manchit-ad-units">
		<?php
		$units = ! empty( $ads['units'] ) ? $ads['units'] : array( array( 'title' => '', 'location' => 'before_content', 'code' => '', 'paragraph' => 3, 'devices' => 'all', 'status' => 1 ) );
		foreach ( $units as $i => $unit ) :
			manchit_render_ad_unit_row( $i, $unit, $locations );
		endforeach;
		?>
	</div>

	<p>
		<button type="button" class="button" id="manchit-add-ad"><?php esc_html_e( '+ إضافة وحدة إعلانية', 'manchit' ); ?></button>
	</p>

	<hr>
	<h2><?php esc_html_e( 'مشاركة أرباح الإعلانات مع الكتّاب', 'manchit' ); ?></h2>
	<p class="manchit-hint"><?php esc_html_e( 'يتيح لكل كاتب وضع كود AdSense الخاص به في ملفه الشخصي، ليظهر على مقالاته بنسبة تحددها بدل إعلانات الموقع. شفاف بالكامل وتحت تحكمك — بلا أي حقن أو نسبة للمطوّر.', 'manchit' ); ?></p>
	<?php
	$o_rs = manchit_get_options();
	manchit_field_row( __( 'تفعيل مشاركة الأرباح', 'manchit' ), manchit_toggle( 'rs_enable', $o_rs['rs_enable'], __( 'تفعيل النظام', 'manchit' ) ) );
	manchit_field_row( __( 'النسبة العامة لظهور إعلانات الكاتب (%)', 'manchit' ), manchit_input( 'rs_ratio', $o_rs['rs_ratio'], 'number', 'min="0" max="100"' ), __( 'مثال: 50 = نصف مشاهدات مقالات الكاتب تعرض كوده. يمكن تخصيص نسبة لكل كاتب من ملفه.', 'manchit' ) );
	?>

	<script type="text/template" id="manchit-ad-template">
		<?php manchit_render_ad_unit_row( '__INDEX__', array( 'title' => '', 'location' => 'before_content', 'code' => '', 'paragraph' => 3, 'devices' => 'all', 'status' => 1 ), $locations ); ?>
	</script>
	<?php
}

/**
 * Render a single ad unit row.
 *
 * @param int|string $i         Index.
 * @param array      $unit      Unit data.
 * @param array      $locations Location choices.
 */
function manchit_render_ad_unit_row( $i, $unit, $locations ) {
	$name = "manchit_ads[units][$i]";
	?>
	<div class="manchit-ad-unit">
		<div class="manchit-ad-unit__head">
			<input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $unit['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'اسم الوحدة (مثال: بانر أعلى المقال)', 'manchit' ); ?>" class="regular-text">
			<label class="manchit-switch"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[status]" value="1" <?php checked( 1, (int) ( $unit['status'] ?? 1 ) ); ?>><span class="manchit-slider"></span> <?php esc_html_e( 'مفعّل', 'manchit' ); ?></label>
			<button type="button" class="button-link manchit-remove-ad" aria-label="<?php esc_attr_e( 'حذف', 'manchit' ); ?>">&times;</button>
		</div>
		<div class="manchit-ad-unit__grid">
			<label><?php esc_html_e( 'الموضع', 'manchit' ); ?>
				<select name="<?php echo esc_attr( $name ); ?>[location]" class="manchit-ad-location">
					<?php foreach ( $locations as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $unit['location'] ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="manchit-ad-paragraph"><?php esc_html_e( 'بعد الفقرة رقم', 'manchit' ); ?>
				<input type="number" min="1" name="<?php echo esc_attr( $name ); ?>[paragraph]" value="<?php echo esc_attr( $unit['paragraph'] ?? 3 ); ?>">
			</label>
			<label><?php esc_html_e( 'الأجهزة', 'manchit' ); ?>
				<select name="<?php echo esc_attr( $name ); ?>[devices]">
					<option value="all" <?php selected( $unit['devices'] ?? 'all', 'all' ); ?>><?php esc_html_e( 'الكل', 'manchit' ); ?></option>
					<option value="mobile" <?php selected( $unit['devices'] ?? '', 'mobile' ); ?>><?php esc_html_e( 'الجوال فقط', 'manchit' ); ?></option>
					<option value="desktop" <?php selected( $unit['devices'] ?? '', 'desktop' ); ?>><?php esc_html_e( 'سطح المكتب فقط', 'manchit' ); ?></option>
				</select>
			</label>
		</div>
		<label class="manchit-ad-code"><?php esc_html_e( 'كود الإعلان (AdSense / HTML / شورت كود)', 'manchit' ); ?>
			<textarea name="<?php echo esc_attr( $name ); ?>[code]" rows="4" class="large-text code" dir="ltr"><?php echo esc_textarea( $unit['code'] ?? '' ); ?></textarea>
		</label>
	</div>
	<?php
}
