/* eslint func-names: ["error", "never"] */
(function(Drupal, drupalSettings) {
  function addQueryArgs(url, args) {
    const qs = Object.keys(args).map(
      key => `${key}=${encodeURIComponent(args[key])}`,
    );

    if (url === 'edit.php') {
      // 'Manage All Reusable Blocks'
      if (args.post_type && args.post_type === 'wp_block') {
        return `${drupalSettings.path.baseUrl}admin/content/reusable-blocks`;
      }
    }
    return url + (qs ? `?${qs.join('&')}` : '');
  }

  window.wp = window.wp || {};
  window.wp.url = { ...window.wp.url, addQueryArgs };
})(Drupal, drupalSettings);
