<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="sticky top-0 z-50 w-full bg-white border-b border-border">
    <div class="mx-auto max-w-6xl px-6 flex items-center justify-between h-16">
        <?php get_template_part('template-parts/header/logo'); ?>
        <?php get_template_part('template-parts/header/menu'); ?>
        <?php get_template_part('template-parts/header/burger'); ?>
    </div>
</header>
