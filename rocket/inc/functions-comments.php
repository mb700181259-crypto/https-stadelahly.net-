<?php

function a4h_comments_comment_classes($classes, $class, $comment_id) {
	$comment = get_comment($comment_id);
	$user = get_userdata($comment->user_id);
	if ( user_can($user, 'edit_others_posts') ) {
		$classes[] = 'byteam';
	}
	return $classes;
}
add_filter('comment_class', 'a4h_comments_comment_classes', 100, 3);

function a4h_comments_remove_comment_fields($fields) {
    unset($fields['url']);
    return $fields;
}
add_filter('comment_form_default_fields', 'a4h_comments_remove_comment_fields');

function a4h_comments_pagination() {
	if ( get_comment_pages_count() < 2 ) return;
    
	?>
	<nav class="nav-pages nav-pages-comments">
		<span class="label visually-hidden"><?php _e('Pages'); ?>:</span>
		<a href="#" class="nav-show-more"><?php _e('Show More', THEME_TEXT_DOMAIN); ?></a>
		<?php echo a4h_theme_vars('loading_html'); ?>
		<div class="nav-pages-inner">
			<?php paginate_comments_links() ?>
		</div>
	</nav>
	<?php
}