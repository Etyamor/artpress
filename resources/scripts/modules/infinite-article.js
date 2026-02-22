if (typeof artpressInfinite === 'undefined') {
  // Feature not enabled or not on a single post — bail out
} else {
  const { ajaxUrl, nonce, limit, postId } = artpressInfinite;
  const container = document.getElementById('infinite-article-container');
  const trigger = document.getElementById('infinite-article-trigger');

  if (container && trigger) {
    if ('scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }

    let loading = false;
    let loaded = 0;
    let done = false;
    const excludedIds = [parseInt(postId, 10)];

    // Loading spinner
    const spinner = document.createElement('div');
    spinner.className = 'flex justify-center py-10 hidden';
    spinner.innerHTML = '<div class="w-8 h-8 border-4 border-border border-t-accent rounded-full animate-spin"></div>';
    container.parentNode.insertBefore(spinner, trigger);

    async function loadNext() {
      if (loading || done || loaded >= limit) return;
      loading = true;
      spinner.classList.remove('hidden');

      const body = new FormData();
      body.append('action', 'artpress_load_next_post');
      body.append('nonce', nonce);
      body.append('current_post_id', excludedIds[excludedIds.length - 1]);
      excludedIds.forEach(id => body.append('excluded_ids[]', id));

      try {
        const res = await fetch(ajaxUrl, { method: 'POST', body });
        const json = await res.json();

        if (!json.success || json.data.empty) {
          done = true;
          spinner.remove();
          trigger.remove();
          return;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = json.data.html;
        const article = wrapper.firstElementChild;
        container.appendChild(article);

        excludedIds.push(json.data.postId);
        loaded++;

        if (loaded >= limit) {
          done = true;
          trigger.remove();
        }
      } catch {
        done = true;
      } finally {
        loading = false;
        spinner.classList.add('hidden');
      }
    }

    // Trigger observer — load next post when trigger enters viewport
    const triggerObserver = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting) {
        loadNext();
      }
    }, { rootMargin: '400px' });

    triggerObserver.observe(trigger);

    // URL tracking — scroll-based for reliability
    let ticking = false;
    function updateUrl() {
      const articles = document.querySelectorAll('.infinite-article');
      let active = null;
      for (const article of articles) {
        if (article.getBoundingClientRect().top <= window.innerHeight * 0.3) {
          active = article;
        }
      }
      if (active) {
        const url = active.dataset.url;
        const title = active.dataset.title;
        if (url && window.location.href !== url) {
          history.replaceState(null, '', url);
          document.title = title;
        }
      }
      ticking = false;
    }

    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(updateUrl);
        ticking = true;
      }
    }, { passive: true });
  }
}
