
(function ($) {

  'use strict';

  Drupal.behaviors.betterMessages = {
    attach: function (context, settings) {

      var betterMessages = settings.better_messages;
      var message_box = $('#better-messages-default.better-messages-overlay', context).once('better-messages');

      if (message_box.length > 0) {
        /* jQuery UI Enhancements */
        if (betterMessages.jquery_ui.draggable == '1') {
          message_box.draggable();
        }
        if (betterMessages.jquery_ui.resizable == '1') {
          message_box.resizable();
        }

        /* Functions to determine the popin/popout animation */
        betterMessages.open = function () {
          var method = 'fadeIn';
          switch (betterMessages.popin.effect) {
            case 'fadeIn':
            case 'slideDown':
              method = betterMessages.popin.effect;
              break;
          }
          message_box[method](betterMessages.popin.duration);
        };
        betterMessages.close = function () {
          var method = 'fadeOut';
          switch (betterMessages.popout.effect) {
            case 'fadeOut':
              break;
            case 'slideUp':
              method = betterMessages.popout.effect;
              break;
          }
          message_box[method](betterMessages.popout.duration);
        };

        /* Function to determine closing count */
        betterMessages.countDownClose = function (seconds) {
          if (!message_box.hasClass('better-messages-has-errors') || !betterMessages.disable_autoclose) {
            if (seconds > 0) {
              seconds--;
              if (betterMessages.show_countdown == '1') {
                $('.better-messages-timer', message_box).text(Drupal.t('Closing in !seconds seconds', {'!seconds': seconds}));
              }
              if (seconds > 0) {
                betterMessages.countDown = setTimeout(function () {
                  betterMessages.countDownClose(seconds);
                }, 1000);
              }
              else {
                betterMessages.close();
              }
            }
          }
        };

        if (betterMessages.width) {
          message_box.css('width', betterMessages.width);
        }

        /* Determine Popup Message position */
        var vertical = betterMessages.vertical;
        var horizontal = betterMessages.horizontal;
        var cssPosition = {};
        switch (betterMessages.position) {
          case 'center':
            vertical = ($(window).height() - message_box.height()) / 2;
            horizontal = ($(window).width() - message_box.width()) / 2;
            cssPosition = {top: vertical + 'px', left: horizontal + 'px'};
            break;
          case 'tl':
            cssPosition = {top: vertical + 'px', left: horizontal + 'px'};
            break;
          case 'tr':
            cssPosition = {top: vertical + 'px', right: horizontal + 'px'};
            break;
          case 'bl':
            cssPosition = {bottom: vertical + 'px', left: horizontal + 'px'};
            break;
          case 'br':
            cssPosition = {bottom: vertical + 'px', right: horizontal + 'px'};
            break;
        }
        message_box.css(cssPosition);

        /* Here we control closing and opening effects and controls */
        setTimeout(function () {
          betterMessages.open();
        }, betterMessages.opendelay * 1000);

        if (betterMessages.autoclose != 0) {
          betterMessages.countDownClose(betterMessages.autoclose);
        }
        if (betterMessages.hover_autoclose == '1') {
          message_box.hover(function () {
            clearTimeout(betterMessages.countDown);
            $('.better-messages-timer', message_box).fadeOut('slow');
          });
        }
        $('.better-messages-close', message_box).click(function (event) {
          betterMessages.close();
          event.preventDefault();
        });

        /* Esc key handler for closing the message. This doesn't work on Safari or Chrome
         See the issue here: http://code.google.com/p/chromium/issues/detail?id=14635
         */
        $(document).keypress(function (e) {
          if (e.keyCode == 27) {
            betterMessages.close();
            return e.preventDefault();
          }
        });
      }
    }
  };
})(jQuery);
