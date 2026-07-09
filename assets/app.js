if (window.location.pathname.endsWith('/offline.html') && navigator.onLine) {
  window.location.replace('/');
}

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js?v=8').catch(() => {});
  });
}
