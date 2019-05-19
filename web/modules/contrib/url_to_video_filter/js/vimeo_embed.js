/**
 * @file
 * Converts Vimeo placeholders to embedded vimeo videos.
 */

/*global jQuery, Drupal, drupalSettings*/
/*jslint white:true, this, browser:true*/

(function ($, Drupal, drupalSettings) {
  "use strict";

  function vimeoVideoEnable(context) {

    $(context).find(".vimeo-player").once("vimeo-video-enable").each(function () {
      var IDParts, vimeoID, queryString;

      // Get the VimeoID as well as the query string (if it exists) from the
      // placeholder.
      IDParts = $(this).attr("data-vimeo-id").split("?");
      vimeoID = IDParts[0];
      // If there is no query string, this will be undefined.
      queryString = IDParts[1];

      // Remove the no-js class, since there is JS.
      $(this).parent().removeClass("no-js");

      // If the settings have been set to autoload the Vimeo video:
      if (drupalSettings.urlToVideoFilter.autoload) {
        // Embedding happens differently depending on whether or not there is a
        // query string.
        if (queryString) {
          $(this).empty().append($("<iframe/>").attr("src", "//player.vimeo.com/video/" + vimeoID + "?" + queryString).attr("frameborder", "0").attr("class", "player-iframe vimeo-iframe"));
        }
        else {
          $(this).empty().append($("<iframe/>").attr("src", "//player.vimeo.com/video/" + vimeoID + "?autopause=1&autoplay=0&badge=1&byline=1&loop=0&portrait=1&autopause=1&fullscreen=1").attr("frameborder", "0").attr("class", "player-iframe vimeo-iframe"));
        }
      }
      else {

        // The video has not been set to autoload. So an AJAX query is run in
        // order to get the thumbnail from Vimeo.
        $.ajax({
          // The URL is hardcoded to https, as using http causes a redirect
          // that modern browsers won't allow by default.
          url:"https://vimeo.com/api/v2/video/" + vimeoID + ".json",
          context:$(this),
          success:function (data) {
            // When a large thumbnail was found for the video:
            if (data[0] && data[0].thumbnail_large) {
              // Replace the placeholder contents with the thumbnail, and add
              // a click handler onto the thumbnail to play the video when the
              // thumbnail is clicked.
              $(this).empty().append($("<span/>").append($("<img/>", {class:"player-thumb", src:data[0].thumbnail_large})).append($("<span/>", {class:"play-button"})).click(function (e) {

                // Stop the default click action.
                e.stopPropagation();

                // The content is about to be replaced with an iframe, so any
                // handlers on the content that is to be replaced, is passed
                // through detachBehaviors, to remove the handlers.
                Drupal.detachBehaviors($(this).parent().parent()[0]);

                // Replace the thumbnail with the iframe.
                // Replacement differs depending on whether there is a query
                // string.
                if (queryString) {
                  $(this).replaceWith($("<iframe/>").attr("src", "//player.vimeo.com/video/" + vimeoID + "?" + queryString).attr("frameborder", "0").attr("class", "player-iframe vimeo-iframe"));
                }
                else {
                  $(this).replaceWith($("<iframe/>").attr("src", "//player.vimeo.com/video/" + vimeoID + "?autopause=1&autoplay=1&badge=1&byline=1&loop=0&portrait=1&autopause=1&fullscreen=1").attr("frameborder", "0").attr("class", "player-iframe vimeo-iframe"));
                }
              }));
            }
            // No thumbnail was returned Vimeo.
            else {
              // Replace the placeholder contents with a play button.
              $(this).empty().append($("<span/>").append($("<span/>", {class:"play-button"})).click(function (e) {

                // Stop the default click action.
                e.stopPropagation();

                // The content is about to be replaced with an iframe, so any
                // handlers on the content that is to be replaced, is passed
                // through detachBehaviors, to remove the handlers.
                Drupal.detachBehaviors($(this).parent().parent()[0]);

                // Replace the thumbnail with the iframe.
                // Replacement differs depending on whether there is a query
                // string.
                if (queryString) {
                  $(this).replaceWith($("<iframe/>").attr("src", "//player.vimeo.com/video/" + vimeoID + "?" + queryString).attr("frameborder", "0").attr("class", "player-iframe vimeo-iframe"));
                }
                else {
                  $(this).replaceWith($("<iframe/>").attr("src", "//player.vimeo.com/video/" + vimeoID + "?autopause=1&autoplay=1&badge=1&byline=1&loop=0&portrait=1&autopause=1&fullscreen=1").attr("frameborder", "0").attr("class", "player-iframe vimeo-iframe"));
                }
              }));
            }
          }
        });
      }
    });
  }

  Drupal.behaviors.urlToVideoFilterVimeo = {
    attach:function (context) {
      vimeoVideoEnable(context);
    },
    detach:function (context) {
      $(".vimeo-player", context).unbind("click");
    }
  };

}(jQuery, Drupal, drupalSettings));
