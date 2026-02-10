<div class="bg-accent-foreground">
    <div class="max-w-6xl mx-auto px-6 py-12 flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <h2 class="text-xl font-bold text-white">
                <?php esc_html_e('Subscribe to our newsletter', 'artpress'); ?>
            </h2>
            <p class="text-accent-light mt-1">
                <?php esc_html_e('Get the latest posts delivered straight to your inbox.', 'artpress'); ?>
            </p>
        </div>
        <div class="relative w-full md:w-auto">
            <form id="subscribe-form" class="flex"
                  data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                  data-nonce="<?php echo esc_attr(wp_create_nonce('artpress_subscribe')); ?>">
                <input
                    type="email"
                    name="email"
                    required
                    placeholder="<?php esc_html_e('Your email address', 'artpress'); ?>"
                    class="px-4 py-3 rounded-l-lg bg-accent-deep text-white placeholder-accent-on-dark border border-accent-hover focus:outline-none focus:border-accent-on-dark w-full md:w-72"
                >
                <button
                    type="submit"
                    class="px-6 py-3 bg-accent-hover hover:bg-accent text-white font-semibold rounded-r-lg transition-colors cursor-pointer"
                >
                    <?php esc_html_e('Subscribe', 'artpress'); ?>
                </button>
            </form>
            <p id="subscribe-message" class="hidden absolute left-0 right-0 mt-1 text-sm"></p>
        </div>
    </div>
</div>
