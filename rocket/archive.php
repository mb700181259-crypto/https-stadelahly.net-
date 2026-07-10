<?php get_header(); ?>
<div class="primary primary-archive" role="main">
    <?php a4h_hook('archive_start'); ?>
    <?php if ( a4h_options('archive_primary_header') != 'inside' ) { ?>
        <div class="primary-header" <?php if ( a4h_options('archive_primary_header') == 'before_with_overlay' ) { echo a4h_archive_header_overlay_atts(); } ?>>
            <div class="container">
                <div class="primary-header-inner archive-header">
                    <?php a4h_hook('archive_header_start'); ?>
                    <?php a4h_archive_title(); ?>
                    <?php a4h_archive_children(); ?>
                    <?php a4h_archive_siblings(); ?>
                    <?php a4h_archive_description(); ?>
                    <?php a4h_archive_post_type_filter(); ?>
                    <?php a4h_hook('archive_header_end'); ?>
                </div>
            </div>
        </div>
    <?php } ?>
    <div class="primary-content">
        <div class="container">
            <div class="primary-content-inner">
                <?php a4h_hook('archive_side'); ?>
                <?php a4h_widgets_area('archive_side'); ?>
                <div class="primary-content-primary">
                    <?php a4h_hook('archive_content_primary_start'); ?>
                    <div class="primary-content-body">
                        <div class="primary-content-content archive-content">
                            <?php a4h_hook('archive_content_start'); ?>
                            <?php if ( a4h_options('archive_primary_header') == 'inside' ) { ?>
                                <div class="primary-content-header archive-header">
                                    <?php a4h_hook('archive_header_start'); ?>
                                    <?php a4h_archive_title(); ?>
                                    <?php a4h_archive_children(); ?>
                                    <?php a4h_archive_siblings(); ?>
                                    <?php a4h_archive_description(); ?>
                                    <?php a4h_archive_post_type_filter(); ?>
                                    <?php a4h_hook('archive_header_end'); ?>
                                </div>
                            <?php } ?>
                            <?php get_template_part(a4h_filter('archive_content_template', 'archive-content')); ?>
                            <?php a4h_hook('archive_content_end'); ?>
                        </div>
                    </div>
                    <?php a4h_hook('archive_content_primary_end'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php a4h_hook('archive_end'); ?>
</div>
<?php get_footer(); ?>