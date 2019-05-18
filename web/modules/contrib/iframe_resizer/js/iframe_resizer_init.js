(function ($, Drupal, iframeResizerSettings) {

  'use strict';

  // Set up the iFrame Resizer library's options.
  var options = {};
  if (iframeResizerSettings.advanced.override_defaults) {
    if (iframeResizerSettings.advanced.options.maxHeight === -1) {
      iframeResizerSettings.advanced.options.maxHeight = Infinity;
    }

    if (iframeResizerSettings.advanced.options.maxWidth === -1) {
      iframeResizerSettings.advanced.options.maxWidth = Infinity;
    }

    options = iframeResizerSettings.advanced.options;
  }

  Drupal.behaviors.initIframeResizer = {
    attach: function (context, settings) {
      var selector = 'iframe';
      if (typeof settings.iframeResizer.advanced.targetSelectors !== 'undefined') {
        selector = settings.iframeResizer.advanced.targetSelectors;
      }
      $(selector, context).iFrameResize(options);
    }
  };

})(jQuery, Drupal, drupalSettings.iframeResizer);
