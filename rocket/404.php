<?php get_header(); ?>
<div id="primary-error" class="primary">
    <div class="primary-content">
        <div class="container">
            <div class="primary-content-inner">
                <div class="primary-content-primary">
                    <div class="primary-content-body">
                        <div class="error-icon"><?php echo a4h_theme_vars('error_image'); ?></div>
                        <?php a4h_archive_title('<h1 class="error-title">', '</h1>'); ?>
                        <p class="error-description"><?php _e('It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', THEME_TEXT_DOMAIN); ?></p>
                        <?php get_search_form(); ?>
                        <?php a4h_hook('error_content'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>