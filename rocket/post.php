<li class="<?php echo a4h_get_item_class($args); ?>" data-post-type="<?php echo get_post_type(); ?>">
    <?php a4h_hook('post_start', $args); ?>
    <div class="item-inner">
        <a class="item-link" href="<?php the_permalink(); ?>" title="<?php echo get_post_meta(get_the_ID(), 'short_title', true) ?: get_the_title(); ?>"></a>
        <?php a4h_post_image($args); ?>
        <div class="item-content">
            <?php a4h_hook('post_content_start', $args); ?>
            <h4>
                <?php a4h_hook('post_title_before', $args); ?>
                    <div class="item-title"><?php echo get_post_meta(get_the_ID(), 'short_title', true) ?: get_the_title(); ?></div>
                <?php a4h_hook('post_title_after', $args); ?>
            </h4>
            <?php a4h_hook('post_content_end', $args); ?>
        </div>
    </div>
    <?php a4h_hook('post_end', $args); ?>
</li>