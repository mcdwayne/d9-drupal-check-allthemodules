/**
 * @file
 * JQuery functions for analytics.
 *
 * Step 1: Plug your Google Web Property ID into the following code and
 *  place in your theme's html.html.twig above the closing body tag:
 *
 *  <!-- Google Analytics -->
 *  <!-- Global site tag (gtag.js) - Google Analytics -->
 *  <script async src="https://www.googletagmanager.com/gtag/js?id=UA-34092501-10"></script>
 *  <script>
 *    window.dataLayer = window.dataLayer || [];
 *    function gtag(){dataLayer.push(arguments);}
 *    gtag('js', new Date());
 *    gtag('config', 'WEB_PROPERTY_ID');
 *  </script>
 *
 * Step 2: Set up a custom dimension for an Auth0 user in your Google Analytics account
 *  following these instructions:
 *  https://support.google.com/analytics/answer/2709829#set_up_custom_dimensions
 *
 * Step 3: Add custom event tracking below using the following syntax:
 *  attach([element], [category], [label], [action (optional)]);
 */

(function ($, drupalSettings) {

  'use strict';

  Drupal.behaviors.ga_analytics = {
    attach: function (context) {

      var user = drupalSettings.auth0_user;

      // Set value for custom dimension at index 1.
      // If your dimension index is different,
      // change it here.
      ga('set', 'dimension1', user);

      /*
       * Enter each item that needs to have event tracking using the following syntax:
       *
       * attach([element], [category], [label], [action (optional)]);
       *
       * E.g.
       * attach('a.social-twitter', 'Social Media', 'Twitter');
       * attach('div.video-play', 'Video', 'About Us Video', 'Play');
       * attach('a.document', 'Resource PDF', 'Product Information', 'Download');
       */

      function attach(element, category, label, action = 'Click') {
        $(element).click(function () {
          ga('send', {
            hitType: 'event',
            eventCategory: category,
            eventAction: action,
            eventLabel: label
          });
        });
      }

    }
  }
})(jQuery, drupalSettings);
