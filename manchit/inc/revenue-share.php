<?php
/**
 * Author revenue sharing (transparent, opt-in).
 *
 * Lets a multi-author news site share ad revenue with its OWN writers: each
 * author stores their personal ad code in their profile, and on their own
 * articles that code replaces the site's in-content ads a configurable
 * percentage of the time. This is the legitimate, site-owner-controlled model.
 *
 * IMPORTANT: This never injects any third party's ads, never phones home, and
 * takes no cut for the theme author. 100% under the site owner's control.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MANCHIT_RS_META = 'manchit_author_ad_code';

/**
 * Whether revenue sharing is enabled.
 *
 * @return bool
 */
function manchit_rs_enabled() {
	return (bool) manchit_get_option( 'rs_enable', 0 );
}

/**
 * Default ratio (percentage of eligible impressions that show the author code).
 *
 * @return int 0..100
 */
function manchit_rs_ratio() {
	$r = (int) manchit_get_option( 'rs_ratio', 50 );
	return max( 0, min( 100, $r ) );
}

/**
 * Get the current post author's ad code (sanitized-at-save, raw at output).
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function manchit_rs_author_code( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	if ( ! $post_id ) {
		return '';
	}
	$author_id = (int) get_post_field( 'post_author', $post_id );
	return (string) get_user_meta( $author_id, MANCHIT_RS_META, true );
}

/**
 * Decide — once per request, deterministically — whether THIS pageview shows the
 * author's ads instead of the site's, based on the ratio.
 *
 * @return bool
 */
function manchit_rs_show_author_ads() {
	static $decision = null;
	if ( null !== $decision ) {
		return $decision;
	}
	if ( ! manchit_rs_enabled() || ! is_singular( 'post' ) ) {
		$decision = false;
		return $decision;
	}
	if ( '' === trim( manchit_rs_author_code() ) ) {
		$decision = false;
		return $decision;
	}
	// Per-author override ratio (user meta) falls back to the global ratio.
	$author_id = (int) get_post_field( 'post_author', get_the_ID() );
	$ratio     = get_user_meta( $author_id, 'manchit_author_ad_ratio', true );
	$ratio     = '' === $ratio ? manchit_rs_ratio() : max( 0, min( 100, (int) $ratio ) );

	$decision = wp_rand( 1, 100 ) <= $ratio;
	return $decision;
}

/**
 * Swap an in-content ad unit's code with the author's code when this pageview
 * is an "author impression". Hooked into the ad markup builder via filter.
 *
 * @param string $code Original site ad code.
 * @param array  $unit Ad unit.
 * @return string
 */
function manchit_rs_filter_ad_code( $code, $unit ) {
	// Only swap in-content / after-content units (the shared inventory).
	$shared = apply_filters( 'manchit_rs_shared_locations', array( 'in_content', 'after_content', 'before_content' ) );
	if ( ! in_array( $unit['location'] ?? '', $shared, true ) ) {
		return $code;
	}
	if ( manchit_rs_show_author_ads() ) {
		$author_code = trim( manchit_rs_author_code() );
		if ( '' !== $author_code ) {
			return $author_code;
		}
	}
	return $code;
}
add_filter( 'manchit_ad_code', 'manchit_rs_filter_ad_code', 10, 2 );

/* -------------------------------------------------------------------------
 * User profile fields
 * ---------------------------------------------------------------------- */

/**
 * Render the author ad-code fields on the user profile screen.
 *
 * @param WP_User $user User being edited.
 */
function manchit_rs_profile_fields( $user ) {
	if ( ! manchit_rs_enabled() ) {
		return;
	}
	// Only for users who can publish posts (authors/editors/admins).
	if ( ! user_can( $user->ID, 'edit_published_posts' ) && ! current_user_can( 'edit_users' ) ) {
		return;
	}
	$code  = get_user_meta( $user->ID, MANCHIT_RS_META, true );
	$ratio = get_user_meta( $user->ID, 'manchit_author_ad_ratio', true );
	?>
	<h2><?php esc_html_e( 'مشاركة أرباح الإعلانات (Manchit)', 'manchit' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="manchit_author_ad_code"><?php esc_html_e( 'كود الإعلان الخاص بك', 'manchit' ); ?></label></th>
			<td>
				<textarea name="manchit_author_ad_code" id="manchit_author_ad_code" rows="4" class="large-text code" dir="ltr"><?php echo esc_textarea( $code ); ?></textarea>
				<p class="description"><?php esc_html_e( 'الصق كود AdSense الخاص بك. سيظهر على مقالاتك بنسبة الظهور أدناه بدلاً من إعلانات الموقع.', 'manchit' ); ?></p>
			</td>
		</tr>
		<?php if ( current_user_can( 'edit_users' ) ) : ?>
			<tr>
				<th><label for="manchit_author_ad_ratio"><?php esc_html_e( 'نسبة ظهور إعلانات هذا الكاتب (%)', 'manchit' ); ?></label></th>
				<td>
					<input type="number" min="0" max="100" name="manchit_author_ad_ratio" id="manchit_author_ad_ratio" value="<?php echo esc_attr( '' === $ratio ? '' : (int) $ratio ); ?>" placeholder="<?php echo esc_attr( manchit_rs_ratio() ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( 'اتركها فارغة لاستخدام النسبة العامة. مثال: 50 = نصف مشاهدات مقالاته تعرض كوده.', 'manchit' ); ?></p>
				</td>
			</tr>
		<?php else : ?>
			<tr>
				<th><?php esc_html_e( 'نسبة ظهور إعلاناتك', 'manchit' ); ?></th>
				<td><strong><?php echo esc_html( ( '' === $ratio ? manchit_rs_ratio() : (int) $ratio ) . '%' ); ?></strong></td>
			</tr>
		<?php endif; ?>
	</table>
	<?php
	wp_nonce_field( 'manchit_rs_profile', 'manchit_rs_nonce' );
}
add_action( 'show_user_profile', 'manchit_rs_profile_fields' );
add_action( 'edit_user_profile', 'manchit_rs_profile_fields' );

/**
 * Save the author ad-code fields.
 *
 * @param int $user_id Edited user ID.
 */
function manchit_rs_save_profile_fields( $user_id ) {
	if ( empty( $_POST['manchit_rs_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['manchit_rs_nonce'] ), 'manchit_rs_profile' ) ) {
		return;
	}
	// A user may edit their own code; only user-managers may set the ratio.
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	if ( isset( $_POST['manchit_author_ad_code'] ) ) {
		// Ad code may contain <script> (AdSense) — allowed, author/admin only.
		$raw = wp_unslash( $_POST['manchit_author_ad_code'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		update_user_meta( $user_id, MANCHIT_RS_META, trim( (string) $raw ) );
	}
	if ( current_user_can( 'edit_users' ) && isset( $_POST['manchit_author_ad_ratio'] ) ) {
		$val = trim( (string) wp_unslash( $_POST['manchit_author_ad_ratio'] ) );
		if ( '' === $val ) {
			delete_user_meta( $user_id, 'manchit_author_ad_ratio' );
		} else {
			update_user_meta( $user_id, 'manchit_author_ad_ratio', max( 0, min( 100, (int) $val ) ) );
		}
	}
}
add_action( 'personal_options_update', 'manchit_rs_save_profile_fields' );
add_action( 'edit_user_profile_update', 'manchit_rs_save_profile_fields' );
