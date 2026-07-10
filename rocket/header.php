<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<?php a4h_hook('head_end'); ?>
</head>
<body <?php body_class(); ?> data-theme="<?php echo a4h_site_theme(); ?>" data-bs-theme="<?php echo a4h_site_theme(); ?>">
	<?php a4h_hook('body_start'); ?>
	<div id="site">
		<header id="header">
			<?php a4h_layout_builder('header_mobile'); ?>
			<?php a4h_layout_builder('header_desktop'); ?>
		</header>
		<main id="main">
			<?php a4h_hook('header_after'); ?>
			<?php a4h_widgets_area('header_after'); ?>