<?php get_header(); ?>
<article class="primary primary-singular<?php echo a4h_singular_continue_reading_class(); ?>" role="main" data-post-id="<?php echo get_the_ID(); ?>" data-next_post="<?php echo a4h_singular_get_adjacent_post_permalink(); ?>">
    <?php a4h_hook('singular_start'); ?>
    <?php if ( a4h_options('singular_primary_header') != 'inside' ) { ?>
        <div class="primary-header" <?php if ( a4h_options('singular_primary_header') == 'before_with_overlay' ) { echo a4h_singular_header_overlay_atts(); } ?>>
            <div class="container">
                <div class="primary-header-inner singular-header">
                    <?php a4h_hook('singular_header_start'); ?>
                    <?php a4h_singular_terms(); ?>
                    <?php a4h_singular_title(); ?>
                    <?php a4h_singular_secondary_title(); ?>
                    <?php a4h_hook('singular_header_end'); ?>
                </div>
            </div>
        </div>
    <?php } ?>
    <div class="primary-content">
        <div class="container">
            <div class="primary-content-inner">
                <?php a4h_hook('singular_side'); ?>
                <?php a4h_widgets_area('singular_side'); ?>
                <div class="primary-content-primary">
                    <?php a4h_hook('singular_content_primary_start'); ?>
                    <div class="primary-content-body">
                        <div class="primary-content-content singular-content">
                            <?php a4h_hook('singular_content_start'); ?>
                            <?php if ( a4h_options('singular_primary_header') == 'inside' ) { ?>
                                <div class="primary-content-header singular-header">
                                    <?php a4h_hook('singular_header_start'); ?>
                                    <?php a4h_singular_terms(); ?>
                                    <?php a4h_singular_title(); ?>
                                    <?php a4h_singular_secondary_title(); ?>
                                    <?php a4h_hook('singular_header_end'); ?>
                                </div>
                            <?php } ?>
                            <?php get_template_part(a4h_filter('singular_content_template', 'singular-content')); ?>
                            <?php a4h_hook('singular_content_end'); ?>
                        </div>
                    </div>
                    <?php a4h_hook('singular_content_primary_end'); ?>
                    <?php a4h_widgets_area('singular_end'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php a4h_hook('singular_end'); ?>
</article>
<?php a4h_hook('singular_after'); ?>
<?php a4h_widgets_area('singular_after'); ?>
<?php comments_template(); ?>
<?php get_footer(); ?>