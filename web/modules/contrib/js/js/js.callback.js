(function ($, Drupal, JS) {

  /**
   * jQuery plugin for a JS Callback.
   *
   * @param {string|object} [callback]
   *   The specific callback to invoke.
   * @param {object} [options]
   *   Any additional options to pass to the jQuery.ajax() call.
   *
   * @return {jQuery}
   *   The chainable jQuery object.
   */
  $.fn.jsCallback = function (callback, options) {
    options = (typeof callback === 'object' && callback) || (typeof options === 'object' && options) || {};
    callback = typeof callback === 'string' && callback || null;
    return this.each(function () {
      // Ensure that our default data does not get overridden.
      var data = $.extend(true, {}, options.data);
      if (callback) {
        data.js_callback = callback;
        data.js_token = JS.getToken(callback);
      }
      JS.ajax($.extend(true, {
        type: 'POST',
        data: data,
        $trigger: $(this)
      }, options));
    });
  };

  var $document = $(document);
  $document
    .on('click', 'a[data-js-callback], :button[data-js-callback], [data-js-bind=click][data-js-callback]', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $(e.currentTarget).jsCallback();
    })

})(jQuery, Drupal, Drupal.JS);
