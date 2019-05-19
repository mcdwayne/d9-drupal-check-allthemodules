(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.YasmDatatables = {
    attach: function (context, settings) {
      var locale = drupalSettings.datatables.locale;
      if (locale === '') {
        $('.datatable').dataTable({
          order: [],
          destroy: true
        });
      }
      else {
        $('.datatable').dataTable({
          language: {url: locale},
          order: [],
          retrieve: true,
          destroy: true
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
