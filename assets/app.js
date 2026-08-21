if (window.location.pathname.endsWith('/offline.html') && navigator.onLine) {
  window.location.replace('/');
}

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js?v=16').catch(() => {});
  });
}

const yuvaIsStandalone =
  window.matchMedia('(display-mode: standalone)').matches ||
  window.navigator.standalone === true;

document.documentElement.classList.toggle('pwa-mode', yuvaIsStandalone);

document.addEventListener('click', (event) => {
  const link = event.target.closest('a[href^="#"]');
  if (!link) return;
  const target = document.querySelector(link.getAttribute('href'));
  if (!target) return;
  event.preventDefault();
  target.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

let yuvaDeferredInstallPrompt = null;

const installPanel = document.getElementById('pwa-install-panel');
const installButton = document.getElementById('pwa-install-button');

window.addEventListener('beforeinstallprompt', (event) => {
  event.preventDefault();
  yuvaDeferredInstallPrompt = event;

  if (installPanel && installButton) {
    installPanel.hidden = false;
  }
});

if (installButton) {
  installButton.addEventListener('click', async () => {
    if (!yuvaDeferredInstallPrompt) {
      return;
    }

    yuvaDeferredInstallPrompt.prompt();
    await yuvaDeferredInstallPrompt.userChoice.catch(() => null);
    yuvaDeferredInstallPrompt = null;

    if (installPanel) {
      installPanel.hidden = true;
    }
  });
}

window.addEventListener('appinstalled', () => {
  yuvaDeferredInstallPrompt = null;

  if (installPanel) {
    installPanel.hidden = true;
  }
});

(() => {
  const userAgent = navigator.userAgent || '';
  const platform = navigator.platform || '';
  const touchMac = platform === 'MacIntel' && navigator.maxTouchPoints > 1;
  const isIOS = /iPad|iPhone|iPod/.test(userAgent) || touchMac;
  const isAndroid = /Android/.test(userAgent);
  const isWindows = /Windows/.test(userAgent);
  const isMac = /Macintosh|Mac OS X/.test(userAgent) && !touchMac;

  const target = isIOS
    ? 'install-ios'
    : isAndroid
      ? 'install-android'
      : isWindows
        ? 'install-windows'
        : isMac
          ? 'install-mac'
          : '';

  if (target) {
    const radio = document.getElementById(target);
    if (radio) {
      radio.checked = true;
    }
  }
})();

(() => {
  const header = document.querySelector('[data-public-header]');
  const menuButton = header?.querySelector('.public-menu-button');
  const navigation = header?.querySelector('#public-navigation');

  if (!header || !menuButton || !navigation) {
    return;
  }

  const closeMenu = () => {
    header.classList.remove('is-menu-open');
    menuButton.setAttribute('aria-expanded', 'false');
    menuButton.querySelector('.sr-only').textContent = 'Open navigation';
  };

  menuButton.addEventListener('click', () => {
    const isOpen = header.classList.toggle('is-menu-open');
    menuButton.setAttribute('aria-expanded', String(isOpen));
    menuButton.querySelector('.sr-only').textContent = isOpen
      ? 'Close navigation'
      : 'Open navigation';
  });

  navigation.addEventListener('click', (event) => {
    if (event.target.closest('a') && window.matchMedia('(max-width: 1120px)').matches) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMenu();
      header.querySelector('.public-login-menu')?.removeAttribute('open');
    }
  });

  window.addEventListener('resize', () => {
    if (!window.matchMedia('(max-width: 1120px)').matches) {
      closeMenu();
    }
  });
})();

(() => {
  const page = document.querySelector('.horizon-home');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  if (!page || reduceMotion.matches || !('IntersectionObserver' in window)) {
    return;
  }

  const sections = Array.from(page.querySelectorAll('main > section'));
  sections.forEach((section) => section.setAttribute('data-horizon-reveal', ''));

  document.documentElement.classList.add('horizon-motion-ready');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) {
        return;
      }

      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, {
    rootMargin: '0px 0px -8% 0px',
    threshold: 0.08,
  });

  sections.forEach((section) => observer.observe(section));
})();
