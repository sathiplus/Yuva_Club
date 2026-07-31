(() => {
  const body = document.body;
  if (!body.classList.contains('student-app')) return;

  const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  body.classList.toggle('is-standalone', standalone);

  const finishLoading = () => {
    body.classList.remove('is-loading');
    body.classList.add('is-ready');
  };
  window.addEventListener('load', finishLoading, { once: true });
  window.setTimeout(finishLoading, 1800);

  const links = [...document.querySelectorAll('[data-app-nav]')];
  const announcer = document.createElement('div');
  announcer.className = 'app-navigation-status';
  announcer.setAttribute('role', 'status');
  announcer.setAttribute('aria-live', 'polite');
  announcer.setAttribute('aria-atomic', 'true');
  body.appendChild(announcer);

  const targetFromHash = () => {
    const rawHash = window.location.hash.slice(1);
    if (!rawHash) return document.getElementById('app-home');
    try {
      return document.getElementById(decodeURIComponent(rawHash));
    } catch (_error) {
      return document.getElementById('app-home');
    }
  };

  const setActive = (key) => {
    links.forEach((link) => {
      const active = link.dataset.appNav === key;
      link.classList.toggle('is-active', active);
      if (active) link.setAttribute('aria-current', 'page');
      else link.removeAttribute('aria-current');
    });
  };

  const sectionFromHash = () => {
    const section = targetFromHash()?.closest('[data-app-section]');
    return section?.dataset.appSection || 'home';
  };

  let navigationLockUntil = 0;
  const focusHashTarget = () => {
    const target = targetFromHash();
    if (!target) return;
    const nearby = target.classList.contains('app-anchor') ? target.nextElementSibling : target;
    const focusTarget = nearby?.matches?.('h1, h2') ? nearby : nearby?.querySelector?.('h1, h2') || target;
    if (!focusTarget.hasAttribute('tabindex')) focusTarget.setAttribute('tabindex', '-1');
    focusTarget.focus({ preventScroll: true });
    const label = (focusTarget.textContent || '').trim();
    announcer.textContent = label ? `${label} opened` : 'Section opened';
    navigationLockUntil = Date.now() + 900;
  };

  const handleHashNavigation = (moveFocus) => {
    setActive(sectionFromHash());
    if (moveFocus) window.requestAnimationFrame(focusHashTarget);
  };

  handleHashNavigation(false);
  window.addEventListener('hashchange', () => handleHashNavigation(true));
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href*="#"]');
    if (!link) return;
    const url = new URL(link.href, window.location.href);
    if (url.pathname === window.location.pathname && url.hash === window.location.hash) {
      window.requestAnimationFrame(focusHashTarget);
    }
  });

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      if (Date.now() < navigationLockUntil) return;
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (visible) setActive(visible.target.dataset.appSection);
    }, { rootMargin: '-20% 0px -58% 0px', threshold: [0.05, 0.25, 0.5] });
    document.querySelectorAll('[data-app-section]').forEach((section) => observer.observe(section));
  }
})();
