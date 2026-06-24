// CSRF helper: automatically attaches X-CSRF-Token header to fetch requests
(function () {
  try {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta ? meta.getAttribute('content') : (window.CK_CSRF_TOKEN || null);
    if (!token) return;

    const _fetch = window.fetch.bind(window);
    window.fetch = function (input, init = {}) {
      init.headers = init.headers || {};
      // normalize Headers instance
      if (init.headers instanceof Headers) {
        init.headers.set('X-CSRF-Token', token);
      } else if (Array.isArray(init.headers)) {
        init.headers.push(['X-CSRF-Token', token]);
      } else {
        init.headers['X-CSRF-Token'] = token;
      }
      return _fetch(input, init);
    };
  } catch (e) {
    // fail silently
    console.warn('CSRF helper not initialized', e);
  }
})();
