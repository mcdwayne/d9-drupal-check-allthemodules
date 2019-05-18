// Use the serviceworker ASAP.
self.addEventListener('install', (event) => event.waitUntil(self.skipWaiting()));
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

/**
 *  use network with cache fallback.
 */
self.addEventListener('fetch', (event) => {
  /**
   * @param {Response} response
   *
   * @return {Promise}
   */
  function cacheNetworkResponse(response) {
    // Don't cache redirects, errors or response bigger than 3MB.
    if (response.ok && response.headers.get('Content-length') < 3 * 1024 * 1024) {
      // Copy now and not in the then() because by that time it's too late,
      // the request has already been used and can't be touched again.
      const copy = response.clone();
      caches
        .open('pwa')
        .then((cache) => cache.put(event.request, copy));
    }
    return response;
  }

  function networkWithCacheFallback (request) {

    function cacheFallback(error) {
      return caches
        .match(request)
        .then((response) => {
          // if not found in cache, return default offline content
          // only if this is a navigation request.
          if (!response) {
            if (request.mode === 'navigate') {
              return caches.match('/offline');
            }
            // Return an error for missing assets.
            return new Response('', {status: 523, statusText: 'Origin Is Unreachable'});
          }
          return response;
        });
    }

    return fetch(request)
      .then(cacheNetworkResponse)
      .catch(cacheFallback);
  }

  // Make sure the url is one we don't exclude from cache.
  if (event.request.method === 'GET') {
    event.respondWith(networkWithCacheFallback(event.request));
  }
});

