            <?php a4h_widgets_area('footer_before'); ?>
            <?php a4h_hook('footer_before'); ?>
        </main>
        <footer id="footer">
            <?php a4h_layout_builder('footer_mobile'); ?>
            <?php a4h_layout_builder('footer_desktop'); ?>
            <?php a4h_theme_copyrights(); ?>
        </footer>
    </div>
    <?php a4h_hook('site_after'); ?>
	<?php a4h_overlay_loading(); ?>
	<?php a4h_overlay_menu(); ?>
    <?php a4h_overlay_search(); ?>
    <?php a4h_news_ticker(); ?>
    <?php a4h_scroll_top(); ?>
    <?php wp_footer(); ?>
    <?php a4h_hook('body_end'); ?>
</body>
</html>