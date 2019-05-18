/**
 * @file
 * Provides the js functionality to the module
 */

/**
 * Implements Drupal.behaviors
 */

(function ($, drupalSettings) {
  'use strict';
  Drupal.behaviors.selects = {
    attach: function(context, settings) {
      $(document).one('ready', function () {
        var value = drupalSettings.select_parent;
        var id = drupalSettings.id_parent;
        if (value != '' && value != null) {
          var select = $('#' + id + '-0-select-parent');
          select.val(value).trigger('change');
          var delta = 0;
          $(document).ajaxComplete(function() {
            selectsCallback(delta, id);
            delta++;
          });
        }
      });

      function selectsCallback(delta, id) {
        var flag = 0;
        var childValue = drupalSettings['select_child_'+delta];
        if (flag == 0) {
          $('select[data-drupal-selector="'+ id +'-0-select-child-'+delta+'"]').val(childValue).trigger('change');
          flag = 1;
        }
      }
    }
  };
}(jQuery, drupalSettings));
