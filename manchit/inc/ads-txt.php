<?php
/**
 * ads.txt manager — safe, non-destructive.
 *
 * Manages authorized-digital-sellers lines without clobbering other networks:
 *  - Shows the current physical ads.txt (if any).
 *  - Merges the admin's managed lines with lines that already exist, de-duped.
 *  - Validates each line's shape before saving.
 *  - Keeps a one-time backup of the original file.
 *  - Only rewrites the physical file when the content actually changes.
 *  - If the file can't be written (permissions/managed host), falls back to a
 *    VIRTUAL /ads.txt served by WordPress — and says which mode is active.
 *  - Capability-gated + nonce-protected via the admin panel save flow.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Path to the physical ads.txt at the site root.
 *
 * @return string
 */
function manchit_ads_txt_path() {
	return trailingslashit( ABSPATH ) . 'ads.txt';
}

/**
 * Read the current physical ads.txt content ('' if none).
 *
 * @return string
 */
function manchit_ads_txt_current() {
	$path = manchit_ads_txt_path();
	return is_readable( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Validate + normalize ads.txt lines. Keeps comments (#) and valid records,
 * drops obviously malformed lines. De-dupes case-insensitively.
 *
 * @param string $raw Raw multiline text.
 * @return array{lines:string[],rejected:string[]}
 */
function manchit_ads_txt_normalize( $raw ) {
	$lines    = preg_split( '/\r\n|\r|\n/', (string) $raw );
	$out      = array();
	$rejected = array();
	$seen     = array();

	foreach ( $lines as $line ) {
		$trim = trim( $line );
		if ( '' === $trim ) {
			continue;
		}
		// Comments and directives (e.g. CONTACT=, SUBDOMAIN=) pass through.
		$is_comment   = ( '#' === $trim[0] );
		$is_directive = (bool) preg_match( '/^[A-Za-z]+=/', $trim );
		// A record: domain, publisher-id, (DIRECT|RESELLER)[, cert-id]
		$is_record = (bool) preg_match( '/^[^,\s]+\s*,\s*[^,\s]+\s*,\s*(DIRECT|RESELLER)\b/i', $trim );

		if ( ! $is_comment && ! $is_directive && ! $is_record ) {
			$rejected[] = $trim;
			continue;
		}
		$key = strtolower( preg_replace( '/\s+/', '', $trim ) );
		if ( isset( $seen[ $key ] ) ) {
			continue; // dedupe
		}
		$seen[ $key ] = true;
		$out[]        = $trim;
	}
	return array( 'lines' => $out, 'rejected' => $rejected );
}

/**
 * Build the merged, managed ads.txt content from the saved option, preserving
 * any lines already present in a physical file that the theme didn't author.
 *
 * @return string
 */
function manchit_ads_txt_build() {
	$managed = manchit_get_ads()['ads_txt'] ?? '';
	$merged  = manchit_ads_txt_normalize( $managed )['lines'];

	// Preserve foreign lines from an existing physical file (other networks).
	$existing = manchit_ads_txt_current();
	if ( '' !== $existing && empty( manchit_get_ads()['ads_txt_managed_only'] ) ) {
		$existing_lines = manchit_ads_txt_normalize( $existing )['lines'];
		$have           = array_map( static function ( $l ) {
			return strtolower( preg_replace( '/\s+/', '', $l ) );
		}, $merged );
		foreach ( $existing_lines as $l ) {
			$key = strtolower( preg_replace( '/\s+/', '', $l ) );
			if ( ! in_array( $key, $have, true ) ) {
				$merged[] = $l;
			}
		}
	}
	return implode( "\n", $merged ) . "\n";
}

/**
 * Persist the ads.txt: try the physical file (with backup); fall back to virtual.
 * Called from the admin save flow. Returns a status message array.
 *
 * @return array{ok:bool,mode:string,message:string}
 */
function manchit_ads_txt_sync() {
	if ( empty( manchit_get_ads()['enable_ads_txt'] ) ) {
		return array( 'ok' => true, 'mode' => 'off', 'message' => __( 'إدارة ads.txt معطّلة.', 'manchit' ) );
	}

	$content = manchit_ads_txt_build();
	$path    = manchit_ads_txt_path();
	$current = manchit_ads_txt_current();

	// No change → do nothing (avoid needless writes).
	if ( rtrim( $current ) === rtrim( $content ) ) {
		return array( 'ok' => true, 'mode' => 'physical', 'message' => __( 'ملف ads.txt محدّث بالفعل (لا تغيير).', 'manchit' ) );
	}

	// Try to write the physical file.
	if ( ( file_exists( $path ) && is_writable( $path ) ) || ( ! file_exists( $path ) && is_writable( ABSPATH ) ) ) {
		// One-time backup of a pre-existing file.
		if ( file_exists( $path ) && ! file_exists( $path . '.manchit-bak' ) ) {
			@copy( $path, $path . '.manchit-bak' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		$written = @file_put_contents( $path, $content ); // phpcs:ignore
		if ( false !== $written ) {
			update_option( 'manchit_ads_txt_mode', 'physical' );
			return array( 'ok' => true, 'mode' => 'physical', 'message' => __( 'تم حفظ ads.txt فعلياً في جذر الموقع (مع نسخة احتياطية).', 'manchit' ) );
		}
	}

	// Fallback: virtual ads.txt served by WordPress.
	update_option( 'manchit_ads_txt_mode', 'virtual' );
	return array(
		'ok'      => true,
		'mode'    => 'virtual',
		'message' => __( 'تعذّرت الكتابة على القرص (صلاحيات) — تم تفعيل ads.txt افتراضي يخدمه ووردبريس على /ads.txt.', 'manchit' ),
	);
}

/**
 * Serve a virtual /ads.txt when the physical file is absent and mode is virtual.
 */
function manchit_ads_txt_virtual() {
	if ( empty( manchit_get_ads()['enable_ads_txt'] ) ) {
		return;
	}
	// Only when there is no real file (a real file always wins).
	if ( file_exists( manchit_ads_txt_path() ) ) {
		return;
	}
	$req = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( '/ads.txt' !== untrailingslashit( (string) $req ) && '/ads.txt' !== (string) $req ) {
		return;
	}
	$content = manchit_ads_txt_build();
	if ( '' === trim( $content ) ) {
		return;
	}
	if ( ! headers_sent() ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
	}
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain validated ads.txt lines.
	exit;
}
add_action( 'init', 'manchit_ads_txt_virtual', 0 );
