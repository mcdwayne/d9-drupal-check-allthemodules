/**
 * This callback will redirect to the relevant URL.
 */
callbacks.add(function () {
  "prevent:nomunge";
  var
    storage = localStorage,
    trail = JSON.parse(storage.getItem('drupal.navigation.trail'));

  if (trail && trail.length) {
    // Always log.
    prevent = false;
    var url = trail.shift();
    if (url) {
      storage.setItem('drupal.navigation.trail', JSON.stringify(trail));
      window.location = url;
    }
  }
  else {
    storage.removeItem('drupal.navigation.trail');
  }
});
