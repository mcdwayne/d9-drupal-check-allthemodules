/**
 * @file
 * Makes ajax calls to the shortcodes audio oembed URL to embed audio posts.
 */

(function ($) {
  'use strict';
  Drupal = Drupal || {};
  Drupal.behaviors = Drupal.behaviors || {};
  Drupal.behaviors.shortcodeaudio = {
    attach: function (context) {
      var embeds = $(".shortcode-soundcloud", context);

      embeds.each(function (item) {
        var type = $(this).attr('class').split(' ')[1];
        var current_embed = $(this);
        var embed_url = '';
        switch (type) {
          case 'soundcloud':
            embed_url = '//soundcloud.com/oembed?format=json&url=' + $(this).attr('data');
            break;
        }

        $.ajax({
          url: embed_url,
          dataType: "json",
          jsonp: false,
          contentType: "application/json; charset=utf-8",
          success: function (data) {
            current_embed.html(data.html);
          },
          error: function (e, s, t) {
          }
        });
      });
    }
  };
}(jQuery));
