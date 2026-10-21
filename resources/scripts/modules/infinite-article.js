if (typeof artpressInfinite === 'undefined') {
  // Feature not enabled or not on a single post — bail out
} else {
  const { limit } = artpressInfinite;
  const container = document.getElementById('infinite-article-container');
  const trigger = document.getElementById('infinite-article-trigger');
  const current = document.querySelector('.infinite-article');

  // Script types the browser would execute. Anything else (JSON-LD, or the
  // `text/plain` placeholders ad plugins use to hold ads back until the
  // visitor consents) must be left untouched.
  const EXECUTABLE_TYPES = [
    '',
    'module',
    'text/javascript',
    'application/javascript',
    'text/ecmascript',
    'application/ecmascript',
  ];

  const isExecutable = (script) => {
    const type = (script.getAttribute('type') || '').trim().toLowerCase();
    return EXECUTABLE_TYPES.indexOf(type) !== -1;
  };

  const isAlreadyOnPage = (src) =>
    Array.prototype.some.call(document.querySelectorAll('script[src]'), (s) => s.src === src);

  /**
   * Take the scripts out of a freshly parsed article before it is attached to
   * the page, leaving a comment node behind to mark where each one belongs.
   *
   * Markup inserted as a parsed node never executes its scripts, so they have
   * to be recreated by hand. Pulling them first and putting them back one by
   * one guarantees each runs exactly once, in order, in its original position
   * (ad code often reads document.currentScript to find its own slot).
   */
  function detachScripts(article) {
    const pending = [];

    article.querySelectorAll('script').forEach((script) => {
      if (!isExecutable(script)) return;

      const marker = document.createComment('artpress-script');
      script.replaceWith(marker);
      pending.push({ marker, script });
    });

    return pending;
  }

  function runScript({ marker, script }) {
    return new Promise((resolve) => {
      const fresh = document.createElement('script');
      Array.prototype.forEach.call(script.attributes, (attr) => {
        fresh.setAttribute(attr.name, attr.value);
      });

      if (!script.src) {
        fresh.textContent = script.textContent;
        marker.replaceWith(fresh);
        resolve();
        return;
      }

      // Loaders like adsbygoogle.js are already on the page from the first
      // article — running them a second time is at best wasted work and at
      // worst re-triggers page-level behaviour.
      if (isAlreadyOnPage(fresh.src)) {
        marker.remove();
        resolve();
        return;
      }

      const done = () => resolve();
      fresh.addEventListener('load', done);
      fresh.addEventListener('error', done);
      marker.replaceWith(fresh);

      // Async scripts make no ordering promises, so don't hold up the rest.
      if (script.hasAttribute('async')) resolve();
    });
  }

  async function runScripts(pending) {
    for (const entry of pending) {
      try {
        await runScript(entry);
      } catch {
        entry.marker.remove();
      }
    }
  }

  const UNFILLED_SLOT = 'ins.adsbygoogle:not([data-adsbygoogle-status])';

  // Pushes that actually reached AdSense while the current article was being
  // activated, counted by the guard below.
  let adsensePushes = 0;

  /**
   * Keep AdSense's queue from being pushed more times than there are empty
   * slots, and count the pushes that do get through.
   *
   * Once adsbygoogle.js is live a push is processed on the spot and raises an
   * uncaught TagError when every <ins> on the page is already filled. That is
   * easy to hit here: AdSense claims the slots of a freshly inserted article
   * on its own, so the push scripts shipped alongside those slots — which have
   * to be re-run, since markup inserted as a parsed node never executes its
   * scripts — can find nothing left to do.
   *
   * @return {Function} Call to restore the original push.
   */
  function guardAdsensePush() {
    const queue = (window.adsbygoogle = window.adsbygoogle || []);
    const native = queue.push;

    if (typeof native !== 'function') return () => {};

    const hadOwnPush = Object.prototype.hasOwnProperty.call(queue, 'push');

    queue.push = function (...args) {
      // Before the loader arrives the queue is still a plain array: pushes are
      // only stored, so there is nothing to guard against yet.
      if (!Array.isArray(queue) && !document.querySelector(UNFILLED_SLOT)) {
        return queue.length;
      }

      adsensePushes++;

      return native.apply(queue, args);
    };

    return () => {
      if (hadOwnPush) queue.push = native;
      else delete queue.push;
    };
  }

  /**
   * AdSense only fills an <ins> slot that has been pushed onto its queue. Ad
   * plugins ship a push script next to every slot they render and those have
   * just run, so this only covers slots left over — hand-placed ad code, or
   * another plugin's markup.
   */
  function fillAdSenseSlots(article) {
    const unfilled = article.querySelectorAll(UNFILLED_SLOT).length;

    for (let i = adsensePushes; i < unfilled; i++) {
      try {
        window.adsbygoogle.push({});
      } catch {
        break;
      }
    }
  }

  /**
   * Advanced Ads holds ads back as base64 `text/plain` scripts until the
   * visitor has consented. Its own listener only decodes them when the consent
   * state changes, so content arriving after consent was given has to be
   * decoded here.
   */
  function decodeConsentedAds(article) {
    const privacy = window.advads && window.advads.privacy;
    if (!privacy || typeof privacy.decode_ad !== 'function') return;
    if (privacy.state !== 'accepted' && privacy.state !== 'not_needed') return;

    article.querySelectorAll('script[type="text/plain"][data-tcf="waiting-for-consent"]').forEach((script) => {
      try {
        privacy.decode_ad(script);
      } catch {
        /* leave the placeholder in place */
      }
    });
  }

  async function activate(article) {
    const pending = detachScripts(article);

    adsensePushes = 0;
    const releaseAdsensePush = guardAdsensePush();

    try {
      await runScripts(pending);
      fillAdSenseSlots(article);
    } finally {
      releaseAdsensePush();
    }

    decodeConsentedAds(article);

    document.dispatchEvent(
      new CustomEvent('artpress:article-loaded', {
        detail: { article, postId: parseInt(article.dataset.postId, 10) || 0 },
      })
    );
  }

  async function fetchArticle(url) {
    const endpoint = new URL(url, window.location.href);
    endpoint.searchParams.set('artpress_infinite', '1');

    const res = await fetch(endpoint.toString(), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    if (!res.ok) return null;

    // Parsed in an inert document: nothing runs, loads or renders until the
    // article is attached below. Falls back to picking the article out of a
    // full page, in case a cache served the complete post instead.
    const doc = new DOMParser().parseFromString(await res.text(), 'text/html');

    return doc.querySelector('.infinite-article');
  }

  if (container && trigger && current) {
    if ('scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }

    let loading = false;
    let loaded = 0;
    let done = false;
    let nextUrl = current.dataset.nextUrl || '';

    // Loading spinner
    const spinner = document.createElement('div');
    spinner.className = 'flex justify-center py-10 hidden';
    spinner.innerHTML = '<div class="w-8 h-8 border-4 border-border border-t-accent rounded-full animate-spin"></div>';
    container.parentNode.insertBefore(spinner, trigger);

    function finish() {
      done = true;
      spinner.remove();
      trigger.remove();
    }

    async function loadNext() {
      if (loading || done || !nextUrl || loaded >= limit) return;
      loading = true;
      spinner.classList.remove('hidden');

      try {
        const article = await fetchArticle(nextUrl);

        if (!article) {
          finish();
          return;
        }

        container.appendChild(article);
        nextUrl = article.dataset.nextUrl || '';
        loaded++;

        await activate(article);

        if (loaded >= limit || !nextUrl) {
          finish();
        }
      } catch {
        finish();
      } finally {
        loading = false;
        spinner.classList.add('hidden');
      }
    }

    if (nextUrl) {
      // Trigger observer — load next post when trigger enters viewport
      const triggerObserver = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
          loadNext();
        }
      }, { rootMargin: '400px' });

      triggerObserver.observe(trigger);
    } else {
      finish();
    }

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
