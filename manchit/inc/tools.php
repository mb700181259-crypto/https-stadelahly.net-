<?php
/**
 * Tools: custom code injection, safe JSON import/export/reset, and diagnostics.
 *
 * Import uses JSON (never unserialize), validates type/size/structure, only
 * accepts known option keys, backs up the current settings first, and reports
 * what was applied vs rejected. All actions are nonce-protected and gated on
 * `manage_options`.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Custom code injection (admin-provided; capability-gated at save)
 * ---------------------------------------------------------------------- */

/**
 * Inline custom CSS + custom head code.
 */
function manchit_output_custom_head() {
	if ( is_admin() ) {
		return;
	}
	$css = (string) manchit_get_option( 'custom_css', '' );
	if ( '' !== trim( $css ) ) {
		echo "<style id=\"manchit-custom-css\">\n" . $css . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin CSS.
	}
	$head = (string) manchit_get_option( 'custom_head', '' );
	if ( '' !== trim( $head ) ) {
		echo $head . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin head code.
	}
}
add_action( 'wp_head', 'manchit_output_custom_head', 99 );

/**
 * Custom code right after <body>.
 */
function manchit_output_body_open() {
	$code = (string) manchit_get_option( 'custom_body_open', '' );
	if ( '' !== trim( $code ) ) {
		echo $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin code.
	}
}
add_action( 'wp_body_open', 'manchit_output_body_open' );

/**
 * Custom footer code.
 */
function manchit_output_custom_footer() {
	if ( is_admin() ) {
		return;
	}
	$code = (string) manchit_get_option( 'custom_footer', '' );
	if ( '' !== trim( $code ) ) {
		echo $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin code.
	}
}
add_action( 'wp_footer', 'manchit_output_custom_footer', 99 );

/* -------------------------------------------------------------------------
 * Export
 * ---------------------------------------------------------------------- */

/**
 * Handle settings export (download JSON).
 */
function manchit_handle_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'صلاحيات غير كافية.', 'manchit' ) );
	}
	check_admin_referer( 'manchit_export' );

	$payload = array(
		'schema'  => 'manchit-settings',
		'version' => MANCHIT_VERSION,
		'options' => get_option( 'manchit_options', array() ),
		'ads'     => get_option( 'manchit_ads', array() ),
	);
	$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=manchit-settings-' . gmdate( 'Ymd' ) . '.json' );
	echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
	exit;
}
add_action( 'admin_post_manchit_export', 'manchit_handle_export' );

/* -------------------------------------------------------------------------
 * Import
 * ---------------------------------------------------------------------- */

/**
 * Handle settings import (validated JSON upload).
 */
function manchit_handle_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'صلاحيات غير كافية.', 'manchit' ) );
	}
	check_admin_referer( 'manchit_import' );

	$redirect = admin_url( 'themes.php?page=manchit-settings&tab=tools' );

	if ( empty( $_FILES['manchit_import_file']['tmp_name'] ) ) {
		set_transient( 'manchit_tools_notice', __( 'لم يتم اختيار ملف.', 'manchit' ), 30 );
		wp_safe_redirect( $redirect );
		exit;
	}
	// Size guard (256 KB is plenty for settings).
	if ( (int) $_FILES['manchit_import_file']['size'] > 262144 ) {
		set_transient( 'manchit_tools_notice', __( 'الملف كبير جداً.', 'manchit' ), 30 );
		wp_safe_redirect( $redirect );
		exit;
	}
	$raw  = file_get_contents( $_FILES['manchit_import_file']['tmp_name'] ); // phpcs:ignore
	$data = json_decode( (string) $raw, true );

	if ( ! is_array( $data ) || ( $data['schema'] ?? '' ) !== 'manchit-settings' ) {
		set_transient( 'manchit_tools_notice', __( 'ملف غير صالح (ليس ملف إعدادات Manchit).', 'manchit' ), 30 );
		wp_safe_redirect( $redirect );
		exit;
	}

	// Backup current settings before applying.
	update_option( 'manchit_options_backup', get_option( 'manchit_options', array() ) );
	update_option( 'manchit_ads_backup', get_option( 'manchit_ads', array() ) );

	$applied  = 0;
	$rejected = 0;

	// Options: accept only known keys.
	if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
		$defaults = manchit_default_options();
		$clean    = get_option( 'manchit_options', array() );
		if ( ! is_array( $clean ) ) {
			$clean = array();
		}
		foreach ( $data['options'] as $k => $v ) {
			if ( array_key_exists( $k, $defaults ) ) {
				$clean[ $k ] = $v;
				$applied++;
			} else {
				$rejected++;
			}
		}
		update_option( 'manchit_options', $clean );
	}
	// Ads: replace whole structure (validated shape).
	if ( isset( $data['ads'] ) && is_array( $data['ads'] ) ) {
		$ads = wp_parse_args( $data['ads'], manchit_default_ads() );
		update_option( 'manchit_ads', $ads );
		$applied++;
	}

	set_transient(
		'manchit_tools_notice',
		sprintf( /* translators: 1: applied 2: rejected */ __( 'تم الاستيراد. طُبّق %1$d مفتاحاً، ورُفض %2$d مجهول. (نسخة احتياطية محفوظة)', 'manchit' ), $applied, $rejected ),
		60
	);
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_manchit_import', 'manchit_handle_import' );

/**
 * Restore the pre-import backup.
 */
function manchit_handle_restore() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'صلاحيات غير كافية.', 'manchit' ) );
	}
	check_admin_referer( 'manchit_restore' );
	$ob = get_option( 'manchit_options_backup', null );
	$ab = get_option( 'manchit_ads_backup', null );
	if ( is_array( $ob ) ) {
		update_option( 'manchit_options', $ob );
	}
	if ( is_array( $ab ) ) {
		update_option( 'manchit_ads', $ab );
	}
	set_transient( 'manchit_tools_notice', __( 'تم استرجاع النسخة الاحتياطية.', 'manchit' ), 30 );
	wp_safe_redirect( admin_url( 'themes.php?page=manchit-settings&tab=tools' ) );
	exit;
}
add_action( 'admin_post_manchit_restore', 'manchit_handle_restore' );

/**
 * Reset all theme settings to defaults (keeps a backup).
 */
function manchit_handle_reset() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'صلاحيات غير كافية.', 'manchit' ) );
	}
	check_admin_referer( 'manchit_reset' );
	update_option( 'manchit_options_backup', get_option( 'manchit_options', array() ) );
	update_option( 'manchit_ads_backup', get_option( 'manchit_ads', array() ) );
	delete_option( 'manchit_options' );
	delete_option( 'manchit_ads' );
	set_transient( 'manchit_tools_notice', __( 'تمت استعادة الإعدادات الافتراضية (نسخة احتياطية محفوظة).', 'manchit' ), 30 );
	wp_safe_redirect( admin_url( 'themes.php?page=manchit-settings&tab=tools' ) );
	exit;
}
add_action( 'admin_post_manchit_reset', 'manchit_handle_reset' );

/**
 * Collect diagnostics info.
 *
 * @return array label => value
 */
function manchit_diagnostics() {
	$seo = function_exists( 'manchit_active_seo_plugin' ) ? manchit_active_seo_plugin() : '';
	return array(
		__( 'إصدار القالب', 'manchit' )      => MANCHIT_VERSION,
		__( 'إصدار ووردبريس', 'manchit' )    => get_bloginfo( 'version' ),
		__( 'إصدار PHP', 'manchit' )         => PHP_VERSION,
		__( 'حد الذاكرة', 'manchit' )        => (string) ini_get( 'memory_limit' ),
		__( 'أقصى حجم رفع', 'manchit' )      => size_format( wp_max_upload_size() ),
		__( 'إضافة سيو مكتشفة', 'manchit' )  => $seo ? $seo : __( 'لا يوجد (القالب يتولّى السيو)', 'manchit' ),
		__( 'وضع ads.txt', 'manchit' )       => get_option( 'manchit_ads_txt_mode', __( 'غير مفعّل', 'manchit' ) ),
		__( 'DOMDocument متاح', 'manchit' )  => class_exists( 'DOMDocument' ) ? __( 'نعم', 'manchit' ) : __( 'لا', 'manchit' ),
		__( 'اللغة', 'manchit' )             => get_locale(),
		__( 'RTL', 'manchit' )               => is_rtl() ? __( 'نعم', 'manchit' ) : __( 'لا', 'manchit' ),
	);
}
