/**
 * @file
 * Custom admin-side JS for the webtexttool module.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.webtexttooladmin = {
    attach: function (context, settings) {
      var wait;
      // Start analysing.
      $('#edit-analyse-page').triggerHandler('click');
      $('.node-form').keypress(function () {
        clearTimeout(wait);
        wait = setTimeout(function () {
          $('#edit-analyse-page').triggerHandler('click');
        }, 1000);
      });

      if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.on('instanceReady', function (ev) {
          var editor = ev.editor;
          // Check if there is a change on the editor.
          editor.on('change', function () {
            clearTimeout(wait);
            wait = setTimeout(function () {
              $('#edit-analyse-page').triggerHandler('click');
            }, 1000);
          });
        });
      }
    }
  };
})(jQuery);
