<?php

function a4h_ads_locations_ads_insertion() {
    $ads_misc = array_filter((array)a4h_ads('locations_ads_misc'));
    $ads_singular = array_filter((array)a4h_ads('locations_ads_singular'));
    $ads_archive = array_filter((array)a4h_ads('locations_ads_archive'));
    if ( $ads_misc ) {
        $ads_misc = array_map(function($ad) {
            return array_merge(array('group' => 'misc'), $ad);
        }, $ads_misc);
    }
    if ( $ads_singular ) {
        $ads_singular = array_map(function($ad) {
            return array_merge(array('group' => 'singular'), $ad);
        }, $ads_singular);
    }
	if ( $ads_archive ) {
        $ads_archive = array_map(function($ad) {
            return array_merge(array('group' => 'archive'), $ad);
        }, $ads_archive);
    }
    $ads = array_merge($ads_misc, $ads_singular, $ads_archive);
    $ads = array_filter($ads, function($ad) {
        return !empty($ad['code']);
    });
    if ( !$ads ) return;
	foreach ( $ads as $ad ) {
        $ad_location = $ad['location'] == 'sticky' ? 'body_end' : $ad['location'];
		add_action('a4h_hook_'.$ad_location, function() use ($ad) {
			echo a4h_ads_ad_output($ad);
		});
	}
}
add_action('init', 'a4h_ads_locations_ads_insertion');

function a4h_ads_shortcode_ads_registration() {
    $ads = array_filter((array)a4h_ads('shortcode_ads'));
    $ads = array_filter($ads, function($ad) {
        return !empty($ad['shortcode']) && !empty($ad['code']);
    });
    if ( !$ads ) return;
    $ads = array_map(function($ad) {
        return array_merge(array('group' => 'shortcode'), $ad);
    }, $ads);
    $ads = array_values($ads);
	foreach ( $ads as $ad ) {
        $ad_scode = str_replace(array('[', ']'), array(), $ad['shortcode']);
		add_shortcode($ad_scode, function() use ($ad) {
            return a4h_ads_ad_output($ad, false); }
        );
    }
}
add_action('init', 'a4h_ads_shortcode_ads_registration');

function a4h_ads_ad_output($ad, $echo = true) {
    if ( empty(a4h_ads('enable_'.$ad['group'].'_ads')) ) return;
    if ( empty($ad['status']) ) return;

    $ad_rules = !empty($ad['rules']) ? $ad['rules'] : '';
    if ( $ad_rules && eval("return !($ad_rules);") ) return;
    
    $group_rules = a4h_ads('enable_'.$ad['group'].'_ads_rules');
    if ( empty($ad['force']) && !$group_rules ) return;

	$singular_rules = is_singular() && get_post_meta(get_the_ID(), 'disable_ads', true);
    if ( empty($ad['force']) && $singular_rules ) return;

    $ad_visibility = !empty($ad['hide']) ? ( $ad['hide'] == 'desktop' ? 'mobile' : 'desktop' ) : '';

    $ad_class = !empty($ad['css']) ? ' '.$ad['css'] : '';

    $ad_location = !empty($ad['location']) ? $ad['location'] : 'shortcode';
    
    $has_wrap = in_array($ad_location, array('head_end', 'body_start', 'body_end')) ? false : true;

	$has_wrap = strstr($ad['code'], '[widgets_list') ? false : $has_wrap;
	$has_wrap = strstr($ad['code'], '[template') ? false : $has_wrap;

    $ad_output = do_shortcode($ad['code']);

	$ad_close = '<a href="#" class="inserted-sticky-close" title="'.__('Close', THEME_TEXT_DOMAIN).'">'.a4h_icon('close').'</a>';

	$ad_output = $ad_location == 'sticky' ? sprintf('<div class="container"><div class="inserted-inner">%s%s</div></div>', $ad_output, $ad_close) : $ad_output;

    $ad_output = $has_wrap ? sprintf('<div class="%s%s" data-location="%s" data-visibility="%s">
    %s
    </div>', 'inserted', $ad_class, $ad_location, $ad_visibility, $ad_output) : $ad_output;

    if ( $echo ) {
        echo $ad_output;
    } else {
        return $ad_output;
    }
}

function a4h_ads_adsense_code($args = array()) {
	$has_size = !empty($args[0]) ? true : false;
	$size = $has_size ? explode('x', $args[0]) : '';
	$append = $has_size && sizeof($size) == 2 ? 'style="display: inline-block; width: '.$size[0].'px; height: '.$size[1].'px"' : 'data-ad-format="auto" style="display: block"';

	$admin_adsense = a4h_ads('adsense');
    $user_adsense = a4h_ads_rs_get_post_author_adsense();

    $is_author_ads = a4h_ads_rs_is_author_ads();
    
    $adsense = $is_author_ads ? $user_adsense : $admin_adsense;

	$code = '';
	$code .= '<div class="adsense-unit">';
	$code .= '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>';
    $code .= '<ins class="adsbygoogle" data-ad-client="%1$s" data-ad-channel="%2$s" data-ad-slot="%3$s" data-page-url="%4$s" %5$s></ins>';
    $code .= a4h_ads_rs_js();
    $code .= '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
    $code .= '</div>';

	if ( is_amp() ) {
		$code = '<amp-ad type="adsense" width="auto" height="250" data-ad-client="%1$s" data-ad-channel="%2$s" data-ad-slot="%3$s" data-page-url="%4$s"></amp-ad>';
	}
	
	$code = sprintf($code, $adsense['client'] ?? '', $adsense['channel'] ?? '', $adsense['slot'] ?? '', $adsense['url'] ?? '', $append);

	return $code;
}
add_shortcode('adsense', 'a4h_ads_adsense_code');

function a4h_ads_rs_get_post_author_adsense() {
	global $post;
	if ( !is_singular() ) return;

    $post_author = a4h_filter('rs_ads_post_author', $post->post_author);

	$author_data = get_userdata($post_author);
	if ( !a4h_ads('enable_rs_for_contributors') && array_intersect(array('subscriber', 'contributor'), $author_data->roles) ) return;

	$adsense = get_user_meta($post_author, 'adsense', true);
	if ( empty($adsense['client']) ) return;

    $adsense = array_filter($adsense);

    if ( !empty($adsense['url']) && !a4h_ads('enable_rs_adsense_url') ) {
        unset($adsense['url']);
    }
    
	return $adsense;
}

function a4h_ads_rs_is_author_ads() {
    if ( !is_singular() ) return;
    
    $ads_rs_ratio = a4h_filter('ads_rs_ratio', a4h_ads('rs_ratio'));
	$ads_rs_random = a4h_theme_vars('random');

    $user_adsense = a4h_ads_rs_get_post_author_adsense();

	return a4h_ads('enable_rs') && $user_adsense && $ads_rs_random < $ads_rs_ratio;
}

function a4h_ads_rs_ads_custom_ratio($default) {
	if ( !is_singular() ) return $default;
	if ( !a4h_ads('rs_ratio_custom') ) return $default;

	global $post;
	$post_author = $post->post_author;
	$custom_ratios = a4h_ads('rs_ratio_custom');

	foreach ( $custom_ratios as $custom_ratio ) {
		if ( empty($custom_ratio['user_id']) || empty($custom_ratio['user_ratio']) ) continue;
		if ( $custom_ratio['user_id'] != $post_author ) continue;
		return (int)$custom_ratio['user_ratio'];
	}

	return $default;
}
add_filter('a4h_filter_ads_rs_ratio', 'a4h_ads_rs_ads_custom_ratio');

function a4h_admin_ads_rs_profile_fields_add($user) {
	if ( !a4h_ads('enable_rs') ) return;
	if ( !a4h_ads('enable_rs_for_contributors') && !current_user_can('edit_published_posts') ) return;

	$author_adsense = get_the_author_meta('adsense', $user->ID);
	$author_client = !empty($author_adsense['client']) ? esc_attr($author_adsense['client']) : '';
	$author_channel = !empty($author_adsense['channel']) ? esc_attr($author_adsense['channel']) : '';
	$author_slot = !empty($author_adsense['slot']) ? esc_attr($author_adsense['slot']) : '';
	$author_url = !empty($author_adsense['url']) ? esc_attr($author_adsense['url']) : '';
	?>
    <div id="a4h-admin-section_rs-ads" class="a4h-admin-section">
		<h3>مشاركة أرباح جوجل أدسنس</h3>
		<table class="form-table">
			<tr>
				<th><label for="adsense_client">معرف حساب جوجل أدسنس</label></th>
				<td>
					<input type="text" name="adsense[client]" id="adsense_client" value="<?php echo $author_client; ?>" class="regular-text" style="text-align: left; direction: ltr;" /><br />
					<span class="description">قم بإدخال الرقم التعريفي للناشر (Publisher ID) الخاص بحسابك. مثال: <mark>pub-1234567891234567</mark> أو <mark>ca-pub-1234567891234567</mark></span>
				</td>
			</tr>
			<tr>
				<th><label for="adsense_channel">رقم القناة المخصصة (اختياري)</label></th>
				<td>
					<input type="text" name="adsense[channel]" id="adsense_channel" value="<?php echo $author_channel; ?>" class="regular-text" style="text-align: left; direction: ltr;" /><br />
					<span class="description">قم بإدخال رقم القناة المخصصة (Custom channel ID) الخاصة بحسابك. مثال: <mark>1234567890</mark></span>
				</td>
			</tr>
			<tr>
				<th><label for="adsense_slot">الرقم التعريفي للوحدة الإعلانية (اختياري)</label></th>
				<td>
					<input type="text" name="adsense[slot]" id="adsense_slot" value="<?php echo $author_slot; ?>" class="regular-text" style="text-align: left; direction: ltr;" /><br />
					<span class="description">قم بإدخال الرقم التعريفي للوحدة الإعلانية (Ad slot ID) الخاصة بحسابك. مثال: <mark>1234567890</mark></span>
				</td>
			</tr>
            <?php if ( a4h_ads('enable_rs_adsense_url') ) { ?>
                <tr>
                    <th><label for="adsense_url">رابط الموقع (اختياري)</label></th>
                    <td>
                        <input type="text" name="adsense[url]" id="adsense_url" value="<?php echo $author_url; ?>" class="regular-text" style="text-align: left; direction: ltr;" /><br />
                        <span class="description">قم بإدخال رابط الموقع (Page URL) الخاص بحسابك. مثال: <mark>example.com</mark></span>
                    </td>
                </tr>
            <?php } ?>
			<tr>
				<th><label for="adsense_ratio">نسبة ظهور إعلاناتك</label></th>
				<td>
					<?php
						global $user_id;
						$user_ratio = a4h_filter('ads_rs_ratio', a4h_ads('rs_ratio'));
						$custom_ratios = a4h_ads('rs_ratio_custom');
						if ( $custom_ratios ) {
							foreach ( $custom_ratios as $custom_ratio ) {
								if ( empty($custom_ratio['user_id']) || empty($custom_ratio['user_ratio']) ) continue;
								if ( $custom_ratio['user_id'] != $user_id ) continue;
								$user_ratio = $custom_ratio['user_ratio'];
							}
						}
					?>
					<?php echo $user_ratio; ?>% 
				</td>
			</tr>
			<?php a4h_hook('rs_ads_profile_fields_extra_show', $user); ?>
		</table>
	</div>
	<?php
}
add_action('show_user_profile', 'a4h_admin_ads_rs_profile_fields_add');
add_action('edit_user_profile', 'a4h_admin_ads_rs_profile_fields_add');

function a4h_admin_ads_rs_profile_fields_update($user_id) {
	if ( !a4h_ads('enable_rs') ) return;
	if ( !a4h_ads('enable_rs_for_contributors') && !current_user_can('edit_published_posts') ) return;
	if ( !current_user_can('edit_user', $user_id) ) return;

	$adsense['client'] = trim(preg_replace("/[^ \w-]/", "", $_POST['adsense']['client']));
	$adsense['channel'] = trim(preg_replace("/[^ \w-]/", "", $_POST['adsense']['channel']));
	$adsense['slot'] = trim(preg_replace("/[^ \w-]/", "", $_POST['adsense']['slot']));
	$adsense['url'] = urlencode(str_replace(array('https://', 'http://'), '', $_POST['adsense']['url']));
	
	update_user_meta($user_id, 'adsense', $adsense);
}
add_action('personal_options_update', 'a4h_admin_ads_rs_profile_fields_update');
add_action('edit_user_profile_update', 'a4h_admin_ads_rs_profile_fields_update');

function a4h_admin_ads_ads_txt_file_create() {
	if ( !a4h_ads('enable_ads_txt_file') ) return;
	if ( get_transient(THEME_VAR.'_ads_txt_file') ) return;

	set_transient(THEME_VAR.'_ads_txt_file', 1 , 3600);

	$output = '';

	$extra_codes = !empty(a4h_ads('ads_txt_file_extra_codes')) ? a4h_ads('ads_txt_file_extra_codes') : '';
	
	$output .= $extra_codes."\n";
	
	$adsense_list = array();
	$users = get_users(array('fields' => array('id')));
	foreach ( $users as $user ) {
		if ( $adsense = get_user_meta($user->ID, 'adsense', true) ) {
			$adsense_list[] = !empty($adsense['client']) ? $adsense['client'] : '';
		}
	}

	$admin_adsense = a4h_ads('adsense');
	if ( !empty($admin_adsense['client']) ) {
		$adsense_list[] = $admin_adsense['client'];
	}

	$adsense_list = array_filter(array_unique($adsense_list));
	$adsense_list = preg_replace('~\D~', '', $adsense_list);
	rsort($adsense_list);

	foreach ( $adsense_list as $adsense_pub ) {
		$output .= 'google.com, pub-'.$adsense_pub.', DIRECT, f08c47fec0942fa0'."\n";
	}
	
	unlink($_SERVER['DOCUMENT_ROOT']."/ads.txt");

	$fp = fopen($_SERVER['DOCUMENT_ROOT']."/ads.txt", "wb");
	fwrite($fp, $output);
	fclose($fp);
}
add_action('admin_init', 'a4h_admin_ads_ads_txt_file_create');


function a4h_ads_rs_js() {
    if ( !a4h_ads('enable_rs') ) return;

    ob_start();
    ?>
    <script>
        (function() {
            let adsenseDiv = document.currentScript.parentNode.closest('.adsense-unit');
            if ( !adsenseDiv ) return;
                            
            if ( typeof ads_rs_random === 'undefined' ) {
                ads_rs_random = parseInt(Math.random() * 100);
            }
            
            let admin_adsense = theme_js_vars.admin_adsense;
            let author_adsense = theme_js_vars.author_adsense;
            let ads_rs_ratio = theme_js_vars.ads_rs_ratio;

            let is_author_ads = ads_rs_random < ads_rs_ratio && author_adsense;
            let adsense_data = is_author_ads ? author_adsense : admin_adsense;
            
            let adsenseUnit = adsenseDiv.querySelector('.adsense-unit .adsbygoogle');
            if ( !adsenseUnit ) return;

            if ( typeof adsense_data !== 'undefined' ) {
                adsenseUnit.setAttribute('data-ad-client', adsense_data.client || '');
                adsenseUnit.setAttribute('data-ad-channel', adsense_data.channel || '');
                adsenseUnit.setAttribute('data-ad-slot', adsense_data.slot || '');
                adsenseUnit.setAttribute('data-page-url', adsense_data.url || '');
            }
        })();
    </script>

    <?php
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
}

