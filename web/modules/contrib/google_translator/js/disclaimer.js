/**
 * @file
 * File disclaimer.js.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.googleTranslatorDisclaimer = {

    getCookie: function (name) {
      // Check for google translations cookies.
      var i, x, y, cookies = document.cookie.split(";");
      for (i = 0; i < cookies.length; i++) {
        x = cookies[i].substr(0, cookies[i].indexOf("="));
        y = cookies[i].substr(cookies[i].indexOf("=") + 1);
        x = x.replace(/^\s+|\s+$/g, "");
        if (x == name) {
          return unescape(y);
        }
      }
    },

    attach: function (context, settings) {
      var config = settings.googleTranslatorDisclaimer || {},
        $disclaimerLink = $(config.jquerySelector, context),
        swap = function () {
          $disclaimerLink.replaceWith(config.element);
        };

      // When the user has previously activated google translate, the cookie
      // will be set and we can proceed straight to exposing the language
      // button without the disclaimer interstitial.
      if ($disclaimerLink.length &&
          typeof this.getCookie('googtrans') != 'undefined') {
        swap();
      }
      else {
        // Listen for user click on the translate interstitial (disclaimer) link.
        $disclaimerLink.click(function (event) {

          // Show the disclaimer text if available.
          if (config.disclaimer &&
              config.disclaimer.trim().length > 0) {

            // Don't show the interstitial if it's already there.
            if ($('#__dimScreen', context).length == 0) {
              var accept = '<a href="#" class="accept-terms">' + config.acceptText + '</a>',
                cancel = '<a href="#" class="do-not-accept-terms">' + config.dontAcceptText + '</a>',
                message = '<div class="message">' + config.disclaimer + '<div>' + accept + ' ' + cancel + '</div></div>';
              $('<div id="__dimScreen"><div class="overlay-wrapper"></div></div>', context).css({
                height : '100%',
                left : '0px',
                position : 'fixed',
                top : '0px',
                width : '100%',
                zIndex : '700'
              }).appendTo(document.body);

              // Attach message text.
              $('#__dimScreen .overlay-wrapper', context).after(message);

              // Focus on accept link when modal appears.
              $('#__dimScreen .message a.accept-terms', context).focus();

              // Accepted terms.
              $('#__dimScreen .message a.accept-terms', context).click(function (event) {
                $('#__dimScreen', context).remove();
                swap();
                $.getScript('//cdn.jsdelivr.net/gh/liamdanger/jQuery.DOMNodeAppear@master/jquery.domnodeappear.js')
                  .done(function () {
                    $('.goog-te-gadget', context).DOMNodeAppear(function () {
                      setTimeout(function () {
                        // Focus on the gadget.
                        $('a.goog-te-menu-value', context).focus();
                      }, 500);
                    }, '.goog-te-gadget');
                  });
              });

              // Attach esc key to cancel action terms action.
              $(document, context).keyup(function (e) {
                if (e.keyCode == 27) {
                  $('#__dimScreen', context).remove();
                  $disclaimerLink.focus();
                }
              });
              // Cancel, did not accept terms.
              $('#__dimScreen .message a.do-not-accept-terms', context).click(function (event) {
                $('#__dimScreen', context).remove();
                // Plant the focus back where we left it.
                $disclaimerLink.focus();
              });

              $('#__dimScreen .overlay-wrapper', context).css({
                background : '#000',
                height : '100%',
                left : '0px',
                opacity : '0',
                position : 'absolute',
                top : '0px',
                width : '100%',
                zIndex : '760'
              }).fadeTo(100, 0.75, function (event) { });
            }
          }

          // If the disclaimer text is not available, then just show the widget.
          else {
            swap();
          }
        });
      }
    }

  }
})(jQuery, Drupal);
