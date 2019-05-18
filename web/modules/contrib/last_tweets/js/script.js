(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.lastTweetsAdminBehavior = {
    attach: function (context, settings) {
      var useForAll = $('#edit-use-for-all');
      if (useForAll) {
        if (useForAll.is(':checked')) {
          disable(useForAll);
        }
        else {
          enable(useForAll);
        }
        useForAll.change(function () {
          if ($(this).is(':checked')) {
            disable($(this));
          }
          else {
            enable($(this));
          }
        });
      }
    }
  };

  function disable(el) {
    var fieldset = el.parent().parent().parent().siblings('fieldset');
    fieldset.attr('disabled', 'disabled');
    fieldset.find('input').css('backgroundColor', '#CCCCCC');
  }

  function enable(el) {
    var fieldset = el.parent().parent().parent().siblings('fieldset');
    fieldset.removeAttr('disabled');
    fieldset.find('input').css('backgroundColor', '#fcfcfa');
  }

})(jQuery, Drupal);
