(function ($, window, Drupal, drupalSettings) {

  /**
   * Initiates klaviyo analytics.
   *
   * @type {{attach: Drupal.behaviors.klaviyoAnalytics.attach}}
   */
  Drupal.behaviors.klaviyoAnalytics = {
    attach: function (context) {
      $('body').once('klaviyoAnalytics').each(function () {
        window._learnq = window._learnq || [];
        window._learnq.push(['account', drupalSettings['commerce_klaviyo']['public_key']]);

        (function () {
          var b = document.createElement('script'); b.type = 'text/javascript'; b.async = true;
          b.src = ('https:' === document.location.protocol ? 'https://' : 'http://') + 'a.klaviyo.com/media/js/analytics/analytics.js';
          var a = document.getElementsByTagName('script')[0];
          a.parentNode.insertBefore(b, a);
        })();

        if (drupalSettings['commerce_klaviyo'].hasOwnProperty('identify')) {
          window._learnq.push(['identify', drupalSettings['commerce_klaviyo']['identify']]);
        }

        if (drupalSettings['commerce_klaviyo'].hasOwnProperty('track')) {
          $.each(drupalSettings['commerce_klaviyo']['track'], function (index, track) {
            window._learnq.push(['track', track['event'], track['properties']]);
          });
        }

        if (drupalSettings['commerce_klaviyo'].hasOwnProperty('trackViewedItem')) {
          $.each(drupalSettings['commerce_klaviyo']['trackViewedItem'], function (index, track) {
            window._learnq.push(['trackViewedItem', track]);
          });
        }
      });
    }
  };

})(jQuery, window, Drupal, drupalSettings);
