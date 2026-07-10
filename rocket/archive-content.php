<?php a4h_hook('archive_posts_before'); ?>
<?php a4h_hook('alter_archive_query_start'); ?>
<?php a4h_hook('archive_posts_before_altered'); ?>
<?php if ( have_posts() ) { ?>
    <?php a4h_archive_pagination('top'); ?>
    <div class="items-list-outer posts-list-outer">
        <?php a4h_hook('archive_posts_lists_start'); ?>
        <ul class="items-list posts-list" data-posts-type="<?php echo get_query_var('posts_type', get_post_type()); ?>">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part(apply_filters('a4h_filter_post_template', 'post')); ?>
            <?php endwhile; ?>
            <?php echo a4h_items_dummy(); ?>
        </ul>
    </div>
    <?php a4h_archive_pagination('bottom'); ?>
<?php } else { ?>
    <?php a4h_no_content(); ?>
<?php } ?>
<?php a4h_hook('archive_posts_after_altered'); ?>
<?php a4h_hook('alter_archive_query_end'); ?>
<?php a4h_hook('archive_posts_after'); ?>