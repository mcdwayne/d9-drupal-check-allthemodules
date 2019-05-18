(function ($, Drpual) {

  'use strict';

  Drupal.behaviors.quickCodeTree = {
    attach: function (context) {
      var $context = $(context);

      $context.find('.folder-icon').once('processed').each(function () {
        var $li = $(this).parent();
        $(this).on('click', function() {
          $li.toggleClass('expanded');
        });
      });
    }
  };

})(jQuery, Drupal);
