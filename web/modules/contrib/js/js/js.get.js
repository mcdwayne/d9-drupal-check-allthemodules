(function ($, Drupal, JS) {

  JS.behaviors.jsGet = {
    init: function () {
      // Convert the "js_get" parameter to a proper "js_callback" parameter.
      if (this.options.data.js_get && !this.options.data.js_callback) {
        this.options.data.js_callback = this.options.data.js_get;
        delete this.options.data.js_get;
      }
    }
  };

  /**
   * jQuery plugin for returning the contents of an internal URL.
   *
   * Any URL passed or extracted will be filtered for external sites. It can be
   * relative to the domain's root or absolute. If, however, the URL is
   * considered external the browser will automatically redirect and no AJAX
   * request will be invoked.
   *
   * Below are optional arguments you can pass to this method. If none are
   * passed, then this method will attempt to extract the URL from the element
   * itself. Only "a[href]" elements can be used for URLs. If the element has a
   * "[data-target]" attribute, it will be used to extract the "a[href]" value
   * of that target instead. If the current element is both "a[href]" and
   * "a[data-target]", then the "a[href]" value of the target will be used
   * instead. If no URL can be extracted, then the AJAX call is not invoked.
   *
   * @param {string|object} [path]
   *   If passed argument is a string, this is the internal URL to retrieve.
   *   If passed argument is an object, it will be used as options (see below).
   * @param {object} [options]
   *   Any additional options to pass to the jQuery.ajax() call.
   *
   * @return {jQuery}
   *   The chainable jQuery object.
   */
  $.fn.jsGet = function (path, options) {
    options = (typeof path === 'object' && path) || (typeof options === 'object' && options) || {};
    path = typeof path === 'string' && path || '';
    return this.each(function () {
      var $this = $(this);
      var data = $.extend(true, {path: path}, options.data);
      var $target = $($this.data('target'));
      if (!data.path && ($this.is('a[href]') || $target[0])) {
        data.path = $target.attr('href') || $this.attr('href') || '';
      }
      data.jsCallback = data.jsGet || data.js_get;
      delete data.jsGet;
      delete data.js_get;
      if (data.path) {
        Drupal.JS.ajax($.extend(true, {
          data: data,
          $trigger: $this
        }, options));
      }
    });
  };

  var $document = $(document);
  $document
    .on('click', 'a[data-js-get], :button[data-js-get], [data-js-bind=click][data-js-get]', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $(e.currentTarget).jsGet();
    })

})(jQuery, Drupal, Drupal.JS);
