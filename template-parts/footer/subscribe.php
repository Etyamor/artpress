<div class="bg-accent-foreground">
    <div class="max-w-6xl mx-auto px-6 py-12 flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <h3 class="text-xl font-bold text-white">
                <?php esc_html_e('Subscribe to our newsletter', 'artpress'); ?>
            </h3>
            <p class="text-accent-light mt-1">
                <?php esc_html_e('Get the latest posts delivered straight to your inbox.', 'artpress'); ?>
            </p>
        </div>
        <form class="flex w-full md:w-auto">
            <input
                type="email"
                placeholder="<?php esc_html_e('Your email address', 'artpress'); ?>"
                class="px-4 py-3 rounded-l-lg bg-primary-950 text-white placeholder-accent-on-dark border border-accent-hover focus:outline-none focus:border-accent-on-dark w-full md:w-72"
            >
            <button
                type="submit"
                class="px-6 py-3 bg-primary-500 hover:bg-accent-on-dark text-white font-semibold rounded-r-lg transition-colors cursor-pointer"
            >
                <?php esc_html_e('Subscribe', 'artpress'); ?>
            </button>
        </form>
    </div>
</div>
