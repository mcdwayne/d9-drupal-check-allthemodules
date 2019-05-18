(function ($, Drupal) {
  Drupal.behaviors.fluid = {
    attach: function (context, settings) {
      if (!($('.fl-prefsEditor-separatedPanel-iframe').length)) {
        var modulePath = drupalSettings.modulePath;

        modulePath = '/' + modulePath;

        var langCode = drupalSettings.path.currentLanguage;

        if (langCode == 'en') {
          fluid.uiOptions.prefsEditor('.flc-prefsEditor-separatedPanel', {
            tocTemplate: modulePath + '/infusion/src/components/tableOfContents/html/TableOfContents.html',
            terms: {
              templatePrefix: modulePath + '/infusion/src/framework/preferences/html',
              messagePrefix: modulePath + '/messages/en'
            }
          });
        }
        else if (langCode == 'fr') {
          fluid.uiOptions.prefsEditor('.flc-prefsEditor-separatedPanel', {
            tocTemplate: modulePath + '/toc/fr/TableOfContents.html',
            terms: {
              templatePrefix: modulePath + '/infusion/src/framework/preferences/html',
              messagePrefix: modulePath + '/messages/fr'
            }
          });
        }

        $('.fl-prefsEditor-buttons #show-hide').keypress(function (e) {
          function setFocusThickboxIframe() {
            var iframeRef = document.getElementsByClassName('fl-prefsEditor-separatedPanel-iframe');
            var iframe = $(iframeRef)[0];
            var iframewindow = iframe.contentWindow;

            iframewindow.focus();
          }

          if (navigator.userAgent.indexOf('Firefox') != -1) {
            setTimeout(setFocusThickboxIframe, 100);
          }
          else {
            setTimeout(setFocusThickboxIframe, 100);
          }
        });

        $('.fl-prefsEditor-buttons #reset').focus(function (e) {
          $('.fl-prefsEditor-buttons #show-hide').click();
        })
      }
    }
  }
})(jQuery, Drupal);
