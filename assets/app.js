if (window.location.pathname.endsWith('/offline.html') && navigator.onLine) {
  window.location.replace('/');
}

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js?v=14').catch(() => {});
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
