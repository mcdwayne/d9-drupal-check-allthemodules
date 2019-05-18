(function ($, Drupal, window, document) {
  'use strict';
  Drupal.behaviors.basic = {
    attach: function (context, settings) {
      $('.issue-label-search').on('keyup', function () {
        var query = this.value;
        $('input.issue-label-item').each(function (i, elem) {
          if (elem.value.toUpperCase().indexOf(query.toUpperCase()) !== -1) {
            $(this).parent().show();
          }
          else {
            $(this).parent().hide();
          }
        });
      });
    }
  };
}(jQuery, Drupal, this, this.document));
