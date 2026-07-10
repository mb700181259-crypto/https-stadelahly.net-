<?php

function a4h_shortcode_site_name() {
	return get_bloginfo('blog_name');
}
add_shortcode('site_name', 'a4h_shortcode_site_name');

function a4h_shortcode_site_description() {
	return get_bloginfo('description');
}
add_shortcode('site_description', 'a4h_shortcode_site_description');

function a4h_shortcode_site_logo() {
	return a4h_site_logo(false);
}
add_shortcode('site_logo', 'a4h_shortcode_site_logo');

function a4h_shortcode_post_id() {
	return get_the_ID();
}
add_shortcode('post_id', 'a4h_shortcode_post_id');

function a4h_shortcode_post_title() {
	return get_the_title();
}
add_shortcode('post_title', 'a4h_shortcode_post_title');

function a4h_shortcode_post_link() {
	return get_the_permalink();
}
add_shortcode('post_link', 'a4h_shortcode_post_link');

function a4h_shortcode_post_short_link() {
	return wp_get_shortlink();
}
add_shortcode('post_short_link', 'a4h_shortcode_post_short_link');

function a4h_shortcode_post_share_links() {
	return a4h_singular_share('inside', false);
}
add_shortcode('share', 'a4h_shortcode_post_share_links');

function a4h_shortcode_current_year() {
    return date('Y');
}
add_shortcode('current_year', 'a4h_shortcode_current_year');

function a4h_shortcode_citation($args = array()) {
	if ( is_admin() ) return;
	
	$id = !empty($args['id']) ? $args['id'] : '';
	
	return sprintf(' <a id="c_ref-%s" href="#c_note-%s" title="%s" class="citation-ref"><sup>[%s]</sup></a>', $id, $id, __('Jump to citation', THEME_TEXT_DOMAIN), $id);
}
add_shortcode('citation', 'a4h_shortcode_citation');

function a4h_shortcode_timer($args = array()) {
	if ( is_admin() ) return;

	$time = !empty($args['time']) ? $args['time'] : '';
	if ( !$time ) return;
	
	$title = !empty($args['title']) ? '<div class="timer-title">'.$args['title'].'</div>' : '';

	$output = sprintf('
		<div class="timer" datetime="%s">
			%s
			<div class="timer-counter">
				<span class="timer-counter-item" data-type="seconds">
					<span class="value">&nbsp;</span>
					<span class="label">'.__('Seconds', THEME_TEXT_DOMAIN).'</span>
				</span>
				<span class="timer-counter-item" data-type="minutes">
					<span class="value">&nbsp;</span>
					<span class="label">'.__('Minutes', THEME_TEXT_DOMAIN).'</span>
				</span>
				<span class="timer-counter-item" data-type="hours">
					<span class="value">&nbsp;</span>
					<span class="label">'.__('Hours', THEME_TEXT_DOMAIN).'</span>
				</span>	
				<span class="timer-counter-item" data-type="days">
					<span class="value">&nbsp;</span>
					<span class="label">'.__('Days', THEME_TEXT_DOMAIN).'</span>
				</span>
			</div>
		</div>', $time, $title);

	return $output;
}
add_shortcode('timer', 'a4h_shortcode_timer');

function a4h_shortcode_get_template($args = array()) {
	if ( is_admin() ) return;

	ob_start();
    	get_template_part($args['name'], '', $args);
    	$output = ob_get_contents();
    ob_end_clean();

    return $output;
}
add_shortcode('template', 'a4h_shortcode_get_template');

function a4h_shortcode_question_answer($args, $content = '') {
	if ( is_admin() ) return;

	if ( empty($args['question']) || empty($content) ) return;

	$questions_output = '';
	$questions_output .= '<div class="singular-section singular-questions" itemscope="" itemtype="https://schema.org/FAQPage">';
	$questions_output .= '<div class="singular-question" itemscope="" itemprop="mainEntity" itemtype="https://schema.org/Question">';
	$questions_output .= '<div class="singular-section-content">';
	$questions_output .= '<div class="question-header" itemprop="name">';
	$questions_output .= '<div class="question-title">'.$args['question'].'</div>';
	$questions_output .= '<span class="icon-toggle">'.a4h_icon('chevron-down').'</span>';
	$questions_output .= '</div>';
	$questions_output .= '<div class="question-content" itemscope="" itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">';
	$questions_output .= '<div itemprop="text">'.wpautop(make_clickable($content)).'</div>';
	$questions_output .= '</div>';
	$questions_output .= '</div>';
	$questions_output .= '</div>';
	$questions_output .= '</div>';

	return $questions_output;
}
add_shortcode('question', 'a4h_shortcode_question_answer');

function a4h_shortcode_widgets_list($args) {
	if ( is_admin() ) return;

    $id = $args[0] ?? '';
    if ( !$id ) return;
	
	$widgets_list_id = 'widgets_list_'.$id;
	if ( !is_active_sidebar($widgets_list_id) ) return;
	
	ob_start();
		a4h_widgets_area($widgets_list_id);
    	$output = ob_get_contents();
    ob_end_clean();

	$output = '</div>'.$output.'<div class="singular-body">';
    
	return $output;
}
add_shortcode('widgets_list', 'a4h_shortcode_widgets_list');

function a4h_shortcode_authors_list($args = array()) {
    $authors_args = array();
    $authors_args['has_published_posts'] = true;
    $authors_args['orderby'] = 'display_name';
    $authors_args['order'] = 'ASC';
    $authors_args['fields'] = 'all';
    $authors_args['role__in'] = !empty($args['groups']) ? explode(',', $args['groups']) : array('author', 'editor');
    $authors_args = a4h_filter('authors_list_args', $authors_args);

    $authors_list = new WP_User_Query($authors_args);

    $authors = $authors_list->get_results();

    if ( empty($authors) ) return;

    $output = '';
    
    $output .= '<div class="authors-list">';

    foreach ( $authors as $author ) {
        $last_post_date = '';
        
        $last_post = get_posts(array('author' => $author->ID, 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC'));
        if ( !$last_post ) continue;

        $last_post_date = strtotime($last_post[0]->post_modified);

        $active_in_days = $args['active_in_days'] ?? '';

        $active_in_days = $active_in_days ? strtotime(sprintf('-%s days', $active_in_days)) : '';

        if ( $active_in_days && $last_post_date < $active_in_days ) continue;

        $output .= a4h_singular_author_block_output($author->ID);
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('authors_list', 'a4h_shortcode_authors_list');