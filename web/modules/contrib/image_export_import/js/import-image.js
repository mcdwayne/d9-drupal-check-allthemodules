(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.import_images = {
    attach: function (context, settings) {
      // Hide file field incase of export.
      if ($('.form-item-import-type select option:selected').val() === 'export') {
        $('.form-item-importimage-csv').hide();
        $('.form-item-upload-zip').hide();
      }
      if ($('.form-item-import-type select option:selected').val() === 'delete') {
        $('.form-item-importimage-csv').hide();
        $('.form-item-upload-zip').hide();
      }
      // Manage form based on action type.
      $('.form-item-import-type select', context).once().on('change', function () {
        if (this.value === 'import') {
          $('.form-item-importimage-csv').show();
          $('.form-item-upload-zip').show();
        }
        else {
          $('.form-item-importimage-csv').hide();
          $('.form-item-upload-zip').hide();
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
