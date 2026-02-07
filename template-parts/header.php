<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="site-header-inner">
        <?php get_template_part('template-parts/header/logo'); ?>
        <?php get_template_part('template-parts/header/menu'); ?>
        <?php get_template_part('template-parts/header/burger'); ?>
    </div>
</header>
