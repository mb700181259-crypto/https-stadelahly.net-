<?php
if ( !comments_open() ) return;
if ( ( !a4h_options('enable_singular_comments_wp') || !a4h_options('enable_singular_comments_wp_rules') ) && ( !a4h_options('enable_singular_comments_fb') || !a4h_options('enable_singular_comments_fb_rules') ) ) return;

if ( get_query_var('hide_comments', false) ) return;
?>

<?php a4h_hook('singular_comments_before'); ?>

<section id="comments">
    <div class="container">
        <div class="widget boxed">
            <div class="widget-inner">
                <header class="widget-header">
                    <div class="widget-title">
                        <h3><?php _e('Comments'); ?></h3>
                    </div>
                </header>
                <div class="widget-content">

<?php if ( a4h_options('enable_singular_comments_fb') && a4h_options('enable_singular_comments_fb_rules') ) { ?>
    <div class="comments-item" data-type="fb">
        <div class="fb-comments" data-href="<?php echo wp_get_shortlink(); ?>" data-width="100%" data-numposts="10"></div>
        <div id="fb-root"></div><script async defer crossorigin="anonymous" src="https://connect.facebook.net/<?php echo a4h_theme_vars('fb_locale'); ?>/sdk.js#xfbml=1&version=v15.0&autoLogAppEvents=1" nonce="8dbyRiBd"></script>
    </div>
<?php } ?>

<?php if ( a4h_options('enable_singular_comments_wp') && a4h_options('enable_singular_comments_wp_rules') ) { ?>
    <div class="comments-item" data-type="wp">
        <?php if ( have_comments() ) { ?>
            <div class="comments-lists<?php echo get_option('show_avatars') ? ' has-avatars': ''; ?>">
                <ul class="comments-list">
                    <?php wp_list_comments(array('style' => 'ul', 'short_ping' => true, 'avatar_size' => 100)); ?>
                </ul>
            </div>
            <?php a4h_comments_pagination(); ?>
        <?php } ?>
        <?php
            ob_start();
                comment_form();
                $comments_form = ob_get_contents();
            ob_end_clean();
            $comments_form = a4h_filter('html_content_filter', $comments_form, 'comment_form');
            echo $comments_form;
        ?>
        <?php wp_enqueue_script('comment-reply'); ?>
    </div>
<?php } ?>

                </div>
            </div>
        </div>
    </div>
</section>