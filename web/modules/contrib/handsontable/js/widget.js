/**
 * Behavior for 'handsontable-single' widget.
 */
(function ($) {

  'use strict';

  Drupal.behaviors.widget = {
    attach: function () {

      var $containers = $('.handsontable-widget');

      $containers.each(function(index){
        var targetValue = $('#' + $(this).data('target')).val();
        var data = targetValue ? JSON.parse(targetValue) : null;

        function updateValue() {
          var targetId = $(this.rootElement).data('target');
          $('#' + targetId).val(JSON.stringify(this.getData()));
        }

        new Handsontable(this,
          {
            data: data,
            minRows: 1,
            minCols: 2,
            minSpareRows: 1,
            contextMenu: true,
            afterChange: updateValue,
            afterCreateCol: updateValue,
            afterCreateRow: updateValue,
            afterRemoveCol: updateValue,
            afterRemoveRow: updateValue
          });

      });

    }
  }

})(jQuery);
