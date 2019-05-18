'use strict';

(function ($, Drupal, window) {

  /**
   * Convert the callbacks to a use the window function object.
   *
   * @param callbacks
   *   An array of callbacks to search for.
   * @param options
   *   An array of options where the callbacks are defined.
   *
   * @returns {*}
   */
  let convertCallbackOptions = function(callbacks, options) {
    if (!Array.isArray(callbacks)) {
      return;
    }
    callbacks.forEach(function (callback) {
      if (typeof options[callback] === 'undefined') {
        return;
      }
      let fn_string = options[callback];

      if (typeof window[fn_string] !== 'function') {
        return;
      }
      options[callback] = window[fn_string];
    })

    return options;
  }

  Drupal.behaviors.markjsSearch = {
    attach: function (context, settings) {
      let callbacks = [
        'each',
        'done',
        'filter',
        'noMatch'
      ]
      let options = convertCallbackOptions(
        callbacks,
        settings.markjs_search.options
      );
      let configs = settings.markjs_search.configs;

      let selector = $(configs.selector, context);
      let keywordInput = $("input[name='keyword']", context);

      if (configs.placeholder) {
        keywordInput.attr(
          'placeholder',
          Drupal.t(configs.placeholder)
        )
      }

      keywordInput.on('input', function() {
        let keyword = $(this).val();

        selector.unmark({
          done: function() {
            selector.mark(keyword, options);
          }
        });
      });
    }
  }
})(jQuery, Drupal, window);
