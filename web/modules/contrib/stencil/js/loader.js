(function(window, document, drupalSettings) {

  'use strict';

  // We could remove this when https://github.com/ionic-team/stencil/issues/179
  // is closed, but it's actually less code than all the loaders combined.
  if (drupalSettings && drupalSettings.stencilRegistries) {
    var needs_polyfill = !(window.customElements && window.fetch);
    var components = [];
    for (var i in drupalSettings.stencilRegistries) {
      var registry = drupalSettings.stencilRegistries[i];
      // Create a global namespace for this registry.
      (window[registry.namespace] = window[registry.namespace] || {}).components = registry.components || [];
      // Load the appropriate script based on the capabilities of the browser.
      var script = document.createElement('script');
      if (needs_polyfill) {
        script.setAttribute('src', registry.root + registry.namespace + '/' + registry.corePolyfilled);
      }
      else {
        script.setAttribute('src', registry.root + registry.namespace + '/' + registry.core);
      }
      // Add required attributes to the loader.
      script.setAttribute('data-path', registry.root + registry.namespace + '/');
      script.setAttribute('data-namespace', registry.namespace);
      script.setAttribute('data-core', registry.core.replace(/.*\//, ''));
      document.head.appendChild(script);

      for (var j in registry.components) {
        components.push(registry.components[j][0].toLowerCase());
      }
    }
    // Hide web components until they've been loaded.
    var style = document.createElement('style');
    style.setAttribute('data-styles', '');
    style.textContent = components.join(',') + '{visibility:hidden}.💎{visibility:inherit}';
    document.head.insertBefore(style, document.head.firstChild);
  }

})(window, document, drupalSettings);
