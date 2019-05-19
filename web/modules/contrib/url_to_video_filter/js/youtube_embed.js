/**
 * @file
 * Replaces placeholders for youtube videos with thumbnails and videos.
 */

 /*global jQuery, Drupal, drupalSettings*/
 /*jslint white:true, this, browser:true*/

(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Handles youtube thumbnail embedding as well as iframe insertion.
   */
  function youtubeVideoEnable(context) {

    $(context).find(".youtube-player").once("youtube-video-enable").each(function () {

      // Declare the variables to be used in the script.
      var IDParts, youtubeID, youtubePreview, queryString;

      // The YouTube ID is stored in the data-youtube-id attribute of the
      // placeholder in the HTML. This value may or may not have a query string
      // appended to it, so the value is split by the query string separator (?)
      // if it exists in the ID.
      IDParts = $(this).data("youtube-id").split("?");
      // Fetch the unique YouTube ID.
      youtubeID = IDParts[0];
      // Fetch the query string. This will be undefined if there was no query
      // string in the HTML placeholder.
      queryString = IDParts[1];

      // As JavaScript is enabled, the no-js class is removed.
      $(this).parent().removeClass("no-js");

      // Check if the videos are set to autoload (rather than using thumbnail)
      // placeholders).
      if (drupalSettings.urlToVideoFilter.autoload) {
        // Need to handle embedding differently if there is a query string.
        if (queryString) {
          // Embed the iframe into the page with the query string.
          $(this).empty().append($("<iframe/>").attr("src", "//www.youtube.com/embed/" + youtubeID + "?" + queryString + "&enablejsapi=1").attr("frameborder", "0").attr("class", "player-iframe youtube-iframe"));
        }
        else {
          // Embed the iframe into the page.
          $(this).empty().append($("<iframe/>").attr("src", "//www.youtube.com/embed/" + youtubeID + "?autoplay=0&autohide=2&border=0&wmode=opaque&enablejsapi=1").attr("frameborder", "0").attr("class", "player-iframe youtube-iframe"));
        }
      }
      // Videos are not set to autoload, and will instead show a thumbnail
      // placeholder.
      else {

        // Check if Webp images have been enabled.
        if (drupalSettings.urlToVideoFilter.youtubeWebp) {
          // Fetch the Webp URL for the thumbnail.
          youtubePreview = "//i.ytimg.com/vi_webp/" + youtubeID + "/sddefault.webp";
        }
        else {
          // Fetch the URL for the thumbnail.
          youtubePreview = "//i.ytimg.com/vi/" + youtubeID + "/hqdefault.jpg";
        }

        // Empty the placeholder, and replace it with the YouTube thumbnail.
        // Also add a click handler to the image so the video will play when
        // the thumbnail is clicked.
        $(this).empty().append($("<span/>").append($("<div/>", {class:"player-thumb",style:"background-image:url(" + youtubePreview + ")"})).append($("<span/>", {class:"play-button"})).click(function (e) {

          // Stop the default click action.
          e.stopPropagation();

          // The content is about to be replaced, so .detachBehaviors() is first
          // run on the content about to be deleted, so as to remove any handler
          // preventing memory leaks.
          Drupal.detachBehaviors($(this).parent().parent()[0]);

          // Need to handle embedding differently if there is a query string.
          if (queryString) {
            // Embed the iframe into the page with the query string.
            $(this).replaceWith($("<iframe/>").attr("src", "//www.youtube.com/embed/" + youtubeID + "?" + queryString + "&autoplay=1&enablejsapi=1").attr("frameborder", "0").attr("class", "player-iframe youtube-iframe"));
          }
          else {
            // Embed the iframe into the page.
            $(this).replaceWith($("<iframe/>").attr("src", "//www.youtube.com/embed/" + youtubeID + "?autoplay=1&autohide=2&border=0&wmode=opaque&enablejsapi=1").attr("frameborder", "0").attr("class", "player-iframe youtube-iframe"));
          }
        }));
      }
    });
  }

  Drupal.behaviors.urlToVideoFilterYoutube = {
    attach:function (context) {
      youtubeVideoEnable(context);
    },
    detach:function (context) {
      $(".youtube-player", context).unbind("click");
    }
  };

}(jQuery, Drupal, drupalSettings));
