const bar = document.getElementById('reading-progress');

if (bar) {
  function getActiveArticle() {
    const articles = document.querySelectorAll('.infinite-article');
    if (articles.length === 0) return null;

    let active = articles[0];
    for (const article of articles) {
      const rect = article.getBoundingClientRect();
      if (rect.top <= 0) {
        active = article;
      } else {
        break;
      }
    }
    return active;
  }

  function update() {
    const articles = document.querySelectorAll('.infinite-article');

    if (articles.length > 0) {
      const article = getActiveArticle();
      if (article) {
        const rect = article.getBoundingClientRect();
        const articleHeight = article.offsetHeight;
        const scrolled = -rect.top;
        const pct = articleHeight > 0
          ? Math.min(100, Math.max(0, (scrolled / articleHeight) * 100))
          : 0;
        bar.style.width = `${pct}%`;
      }
    } else {
      const scrollable = document.documentElement.scrollHeight - window.innerHeight;
      const pct = scrollable > 0 ? (window.scrollY / scrollable) * 100 : 0;
      bar.style.width = `${pct}%`;
    }
  }

  window.addEventListener('scroll', update, { passive: true });
  update();

  // Re-run when new articles are appended
  const container = document.getElementById('infinite-article-container');
  if (container) {
    new MutationObserver(update).observe(container, { childList: true });
  }
}
