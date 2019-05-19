/**
 * @file
 * Trending images authorization enhancements.
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.trendingImagesOAuthCallback = {
    attach: function (context, settings) {
      $('.trending-images-oauth-callback', context).once('trending-images-oauth-callback').each(function() {
        // Notify the parent window that we are all set.
        if (window.opener && window.opener.trendingImagesAuthenticated) {
          window.opener.trendingImagesAuthenticated(true);
        }

        setTimeout(function() {
          window.close();
        }, 2000);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
