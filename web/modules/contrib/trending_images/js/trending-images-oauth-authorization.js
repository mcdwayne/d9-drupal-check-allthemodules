/**
 * @file
 * Trending images authorization enhancements.
 */

(function ($, Drupal, drupalSettings) {

  var event_name = 'custom_trending_images';

   /** Notify of the authenticated status.
   *
   * @param authenticated
   *   Whether the connection is established
   */
  window.trendingImagesAuthenticated = function(authenticated) {
    if (authenticated) {
      $('#trending-images-form')
        .addClass(drupalSettings.custom_trending_images.bodyClass)
        .trigger(event_name);
      window.location.reload();
    }
    else {
      $('#trending-images-form').removeClass(drupalSettings.custom_trending_images.bodyClass);
    }
  };

  /**
   * Determine whether currently API is authorized.
   */
  window.trendingImagesIsAuthenticated = function() {
    return $('#trending-images-form').hasClass(drupalSettings.custom_trending_images.bodyClass);
  };

  Drupal.behaviors.trendingImagesOAuthAuthorization = {
    attach: function (context, settings) {
      $('.custom-instagram_channel-oauth-authorization', context).once('custom-instagram_channel-oauth-authorization').click(function(event) {
        event.preventDefault();

        window.open($(this).attr('href'), 'trending_images', 'height=400,width=600');
      });
    }
  };

  $.fn.reloadPage = function() {
    window.location.reload();
  };

})(jQuery, Drupal, drupalSettings);
