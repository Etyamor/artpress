<?php
$share_url   = urlencode(get_permalink());
$share_title = urlencode(get_the_title());
$general_opts = get_option('artpress_general', []);
?>
<div class="flex items-center gap-3">
    <span class="text-sm font-medium text-foreground-secondary"><?php esc_html_e('Share:', 'artpress'); ?></span>
    <?php if ($general_opts['share_facebook'] ?? true) : ?>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>"
           target="_blank" rel="noopener noreferrer"
           aria-label="<?php esc_attr_e('Share on Facebook', 'artpress'); ?>"
           class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-border text-muted hover:bg-accent hover:text-white hover:border-accent transition-colors">
            <?php get_template_part('template-parts/icons/facebook'); ?>
        </a>
    <?php endif; ?>
    <?php if ($general_opts['share_x'] ?? true) : ?>
        <a href="https://x.com/intent/post?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
           target="_blank" rel="noopener noreferrer"
           aria-label="<?php esc_attr_e('Share on X', 'artpress'); ?>"
           class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-border text-muted hover:bg-accent hover:text-white hover:border-accent transition-colors">
            <?php get_template_part('template-parts/icons/x-twitter'); ?>
        </a>
    <?php endif; ?>
    <?php if ($general_opts['share_linkedin'] ?? true) : ?>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>"
           target="_blank" rel="noopener noreferrer"
           aria-label="<?php esc_attr_e('Share on LinkedIn', 'artpress'); ?>"
           class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-border text-muted hover:bg-accent hover:text-white hover:border-accent transition-colors">
            <?php get_template_part('template-parts/icons/linkedin'); ?>
        </a>
    <?php endif; ?>
</div>
