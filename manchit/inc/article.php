<?php
/**
 * Single-article enhancements: optional subtitle, per-post layout, a sources
 * (citations) block, a comfortable reading font-size control, and a Facebook
 * comments option kept strictly separate from WordPress comments.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MANCHIT_SUBTITLE_META = '_manchit_subtitle';
const MANCHIT_LAYOUT_META   = '_manchit_article_layout';

/* -------------------------------------------------------------------------
 * Per-post meta box (subtitle + layout)
 * ---------------------------------------------------------------------- */

/**
 * Register the article settings meta box.
 */
function manchit_article_metabox() {
	add_meta_box( 'manchit_article_box', __( 'إعدادات المقال (Manchit)', 'manchit' ), 'manchit_article_metabox_render', array( 'post' ), 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'manchit_article_metabox' );

/**
 * Render the meta box.
 *
 * @param WP_Post $post Post.
 */
function manchit_article_metabox_render( $post ) {
	wp_nonce_field( 'manchit_article_meta', 'manchit_article_nonce' );
	$subtitle = get_post_meta( $post->ID, MANCHIT_SUBTITLE_META, true );
	$layout   = get_post_meta( $post->ID, MANCHIT_LAYOUT_META, true );
	?>
	<p>
		<label for="manchit_subtitle"><strong><?php esc_html_e( 'عنوان فرعي (اختياري)', 'manchit' ); ?></strong></label>
		<input type="text" id="manchit_subtitle" name="manchit_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'سطر توضيحي يظهر أسفل العنوان', 'manchit' ); ?>">
	</p>
	<p>
		<label for="manchit_article_layout"><strong><?php esc_html_e( 'تخطيط المقال', 'manchit' ); ?></strong></label>
		<select id="manchit_article_layout" name="manchit_article_layout">
			<option value="" <?php selected( $layout, '' ); ?>><?php esc_html_e( 'الافتراضي (حسب الإعدادات)', 'manchit' ); ?></option>
			<option value="standard" <?php selected( $layout, 'standard' ); ?>><?php esc_html_e( 'قياسي (مع الشريط الجانبي)', 'manchit' ); ?></option>
			<option value="narrow" <?php selected( $layout, 'narrow' ); ?>><?php esc_html_e( 'عمود مركزي مريح (بلا شريط)', 'manchit' ); ?></option>
			<option value="wide" <?php selected( $layout, 'wide' ); ?>><?php esc_html_e( 'عريض (بلا شريط)', 'manchit' ); ?></option>
		</select>
	</p>
	<?php
}

/**
 * Save the meta box.
 *
 * @param int $post_id Post ID.
 */
function manchit_article_metabox_save( $post_id ) {
	if ( empty( $_POST['manchit_article_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['manchit_article_nonce'] ), 'manchit_article_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$subtitle = isset( $_POST['manchit_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['manchit_subtitle'] ) ) : '';
	if ( '' !== $subtitle ) {
		update_post_meta( $post_id, MANCHIT_SUBTITLE_META, $subtitle );
	} else {
		delete_post_meta( $post_id, MANCHIT_SUBTITLE_META );
	}
	$layout = isset( $_POST['manchit_article_layout'] ) ? sanitize_key( $_POST['manchit_article_layout'] ) : '';
	$layout = in_array( $layout, array( 'standard', 'narrow', 'wide' ), true ) ? $layout : '';
	if ( '' !== $layout ) {
		update_post_meta( $post_id, MANCHIT_LAYOUT_META, $layout );
	} else {
		delete_post_meta( $post_id, MANCHIT_LAYOUT_META );
	}
}
add_action( 'save_post_post', 'manchit_article_metabox_save' );

/**
 * Get the current post's subtitle.
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function manchit_get_subtitle( $post_id = null ) {
	return (string) get_post_meta( $post_id ?: get_the_ID(), MANCHIT_SUBTITLE_META, true );
}

/**
 * Resolve the effective article layout (per-post overrides the global option).
 *
 * @return string standard|narrow|wide
 */
function manchit_article_layout() {
	$per = get_post_meta( get_the_ID(), MANCHIT_LAYOUT_META, true );
	if ( in_array( $per, array( 'standard', 'narrow', 'wide' ), true ) ) {
		return $per;
	}
	$global = manchit_get_option( 'article_layout', 'standard' );
	return in_array( $global, array( 'standard', 'narrow', 'wide' ), true ) ? $global : 'standard';
}

/* -------------------------------------------------------------------------
 * Comments system (WP / Facebook / both) — kept separate
 * ---------------------------------------------------------------------- */

/**
 * Which comment system to render.
 *
 * @return string wp|facebook|both
 */
function manchit_comments_system() {
	$s = manchit_get_option( 'comments_system', 'wp' );
	return in_array( $s, array( 'wp', 'facebook', 'both' ), true ) ? $s : 'wp';
}

/**
 * Render the Facebook comments embed (requires an App ID). Loads the SDK once.
 */
function manchit_facebook_comments() {
	$app_id = manchit_get_option( 'fb_app_id', '' );
	$locale = str_replace( '-', '_', get_locale() ) ?: 'ar_AR';
	?>
	<div class="mn-fb-comments">
		<div id="fb-root"></div>
		<script async defer crossorigin="anonymous"
			src="https://connect.facebook.net/<?php echo esc_attr( $locale ); ?>/sdk.js#xfbml=1&version=v19.0<?php echo $app_id ? '&appId=' . esc_attr( $app_id ) : ''; ?>"></script>
		<div class="fb-comments" data-href="<?php echo esc_url( get_permalink() ); ?>" data-width="100%" data-numposts="10"></div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Sources / citations block
 * ---------------------------------------------------------------------- */

/**
 * [sources] wrapper with inner [src url="..."]label[/src] items.
 *
 * @param array  $atts    Attributes.
 * @param string $content Enclosed content.
 * @return string
 */
function manchit_sources_shortcode( $atts, $content = '' ) {
	if ( ! $content || ! preg_match_all( '/\[src(?:\s+url="([^"]*)")?\](.*?)\[\/src\]/s', $content, $m, PREG_SET_ORDER ) ) {
		return '';
	}
	$out = '<aside class="mn-sources" aria-label="' . esc_attr__( 'المصادر', 'manchit' ) . '"><h3 class="mn-sources__title">' . esc_html__( 'المصادر والمراجع', 'manchit' ) . '</h3><ol class="mn-sources__list">';
	foreach ( $m as $item ) {
		$url   = trim( $item[1] );
		$label = trim( wp_strip_all_tags( $item[2] ) );
		if ( '' === $label ) {
			continue;
		}
		if ( $url ) {
			$out .= '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow">' . esc_html( $label ) . '</a></li>';
		} else {
			$out .= '<li>' . esc_html( $label ) . '</li>';
		}
	}
	$out .= '</ol></aside>';
	return $out;
}
add_shortcode( 'sources', 'manchit_sources_shortcode' );
