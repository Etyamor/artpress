const bar = document.getElementById('reading-progress');

if (bar) {
  const getArticles = () => document.querySelectorAll<HTMLElement>('.infinite-article');

  function getActiveArticle(): HTMLElement | null {
    const articles = getArticles();
    if (articles.length === 0) return null;

    let active = articles[0];
    for (const article of articles) {
      if (article.getBoundingClientRect().top <= 0) {
        active = article;
      } else {
        break;
      }
    }
    return active;
  }

  function setProgress(pct: number): void {
    bar!.style.width = `${pct}%`;
  }

  function update(): void {
    if (getArticles().length > 0) {
      const article = getActiveArticle();
      if (article) {
        const rect = article.getBoundingClientRect();
        const articleHeight = article.offsetHeight;
        const scrolled = -rect.top;
        const pct = articleHeight > 0
          ? Math.min(100, Math.max(0, (scrolled / articleHeight) * 100))
          : 0;
        setProgress(pct);
      }
    } else {
      const scrollable = document.documentElement.scrollHeight - window.innerHeight;
      setProgress(scrollable > 0 ? (window.scrollY / scrollable) * 100 : 0);
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
