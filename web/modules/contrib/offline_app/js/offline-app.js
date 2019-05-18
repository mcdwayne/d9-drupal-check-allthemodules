// Check if a new cache is available on page load.
window.addEventListener('load', function (e) {
  'use strict';
  window.applicationCache.addEventListener('updateready', function (e) {
    if (window.applicationCache.status === window.applicationCache.UPDATEREADY) {
      // Browser downloaded a new app cache.
      var offline_messages = document.getElementById('offline-messages');
      var offline_messages_update_ready_text = document.getElementById('offline-messages-update-ready-text');
      if (offline_messages_update_ready_text) {
        offline_messages.innerHTML = offline_messages_update_ready_text.getAttribute('content');
        offline_messages.style.display = 'block';
        setTimeout(function () {
          offline_messages.style.display = 'none';
        }, 5000);
      }
    }
  }, false);

  window.applicationCache.addEventListener('cached', function (e) {
    if (window.applicationCache.status === window.applicationCache.IDLE) {
      // Browser downloaded the manifest for the first time.
      var offline_messages = document.getElementById('offline-messages');
      var offline_messages_first_time_text = document.getElementById('offline-messages-first-time-text');
      if (offline_messages_first_time_text) {
        offline_messages.innerHTML = offline_messages_first_time_text.getAttribute('content');
        offline_messages.style.display = 'block';
        setTimeout(function () {
          offline_messages.style.display = 'none';
        }, 5000);
      }
    }
  }, false);
}, false);
