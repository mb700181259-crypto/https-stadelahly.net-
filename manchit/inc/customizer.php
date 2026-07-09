<?php
/**
 * Customizer integration (visual quick-controls with live preview).
 *
 * The deep configuration lives in the dedicated admin panel; the Customizer
 * exposes the most-used visual controls (colors, layout, theme mode) so admins
 * can preview them live. All settings write to the single `manchit_options`
 * store so there is one source of truth.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer controls.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function manchit_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

	// Panel.
	$wp_customize->add_panel(
		'manchit_panel',
		array(
			'title'    => __( 'إعدادات قالب Manchit', 'manchit' ),
			'priority' => 10,
		)
	);

	// --- Colors ---
	$wp_customize->add_section(
		'manchit_colors',
		array(
			'title' => __( 'الألوان', 'manchit' ),
			'panel' => 'manchit_panel',
		)
	);
	manchit_add_color( $wp_customize, 'brand_color', __( 'اللون الأساسي', 'manchit' ), '#d5011a' );
	manchit_add_color( $wp_customize, 'accent_color', __( 'اللون المميز', 'manchit' ), '#ffb300' );

	// --- Layout ---
	$wp_customize->add_section(
		'manchit_layout',
		array(
			'title' => __( 'التخطيط', 'manchit' ),
			'panel' => 'manchit_panel',
		)
	);
	manchit_add_select(
		$wp_customize,
		'layout',
		__( 'تخطيط الأرشيف والمقال', 'manchit' ),
		'right-sidebar',
		array(
			'right-sidebar' => __( 'شريط جانبي يمين', 'manchit' ),
			'left-sidebar'  => __( 'شريط جانبي يسار', 'manchit' ),
			'full'          => __( 'عرض كامل بدون شريط', 'manchit' ),
		)
	);
	manchit_add_select(
		$wp_customize,
		'default_theme_mode',
		__( 'الوضع الافتراضي', 'manchit' ),
		'auto',
		array(
			'auto'  => __( 'تلقائي (حسب النظام)', 'manchit' ),
			'light' => __( 'نهاري', 'manchit' ),
			'dark'  => __( 'ليلي', 'manchit' ),
		)
	);

	// Live-preview brand color.
	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'blogname',
			array(
				'selector'        => '.mn-site-title',
				'render_callback' => function () {
					return get_bloginfo( 'name' );
				},
			)
		);
	}
}
add_action( 'customize_register', 'manchit_customize_register' );

/**
 * Helper to register a color control bound to manchit_options[key].
 */
function manchit_add_color( $wp_customize, $key, $label, $default ) {
	$wp_customize->add_setting(
		"manchit_options[$key]",
		array(
			'type'              => 'option',
			'default'           => $default,
			'transport'         => 'postMessage',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			"manchit_color_$key",
			array(
				'label'    => $label,
				'section'  => 'manchit_colors',
				'settings' => "manchit_options[$key]",
			)
		)
	);
}

/**
 * Helper to register a select control bound to manchit_options[key].
 */
function manchit_add_select( $wp_customize, $key, $label, $default, $choices ) {
	$wp_customize->add_setting(
		"manchit_options[$key]",
		array(
			'type'              => 'option',
			'default'           => $default,
			'transport'         => 'refresh',
			'sanitize_callback' => function ( $val ) use ( $choices ) {
				return array_key_exists( $val, $choices ) ? $val : '';
			},
		)
	);
	$wp_customize->add_control(
		"manchit_control_$key",
		array(
			'type'     => 'select',
			'label'    => $label,
			'section'  => 'manchit_layout',
			'settings' => "manchit_options[$key]",
			'choices'  => $choices,
		)
	);
}

/**
 * Customizer live-preview script.
 */
function manchit_customize_preview_js() {
	wp_enqueue_script(
		'manchit-customize-preview',
		MANCHIT_URI . 'assets/js/customize-preview.js',
		array( 'customize-preview' ),
		MANCHIT_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'manchit_customize_preview_js' );
