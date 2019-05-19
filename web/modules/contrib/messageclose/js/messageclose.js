(function ($) {
  'use strict';
  Drupal.behaviors.messageclose = {
    attach: function (context) {

      // Prepend a close button to each message.
      $('.messages:not(.messageclose-processed)').each(function () {
        $(this).addClass('messageclose-processed');
        $(this).prepend('<a href="#" class="messageclose" title="' + Drupal.t('close') + '">&times;</a>');
      });

      // When a close button is clicked hide this message.
      $('.messages a.messageclose').click(function (event) {
        event.preventDefault();
        $(this).parent().fadeOut('slow', function () {
          var messages_left = $('.messages__wrapper').children().size();
          if (messages_left === 1) {
            $('.messages__wrapper').remove();
          }
          else {
            $(this).remove();
          }
        });
      });

    }
  };
}(jQuery));
