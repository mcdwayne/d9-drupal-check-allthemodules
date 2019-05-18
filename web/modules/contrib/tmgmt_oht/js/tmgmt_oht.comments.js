(function ($) {
  'use strict';

  Drupal.behaviors.tmgmt_oht = {
    attach: function (context, settings) {

      var commentSubmitted = $('input:hidden[name=comment_submitted]').val();
      if (commentSubmitted === '1') {
        $('html, body').animate({
          scrollTop: $('#edit-oht-comments').offset().top
        }, 200);
      }
    }
  };

})(jQuery);
