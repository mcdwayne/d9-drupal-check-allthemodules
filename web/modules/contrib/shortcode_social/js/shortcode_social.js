/**
 * @file
 * Makes ajax calls to the shortcodes social oembed URL to embed social posts.
 */

(function ($) {
  'use strict';
  Drupal = Drupal || {};
  Drupal.behaviors = Drupal.behaviors || {};
  Drupal.behaviors.shortcodeSocial = {
    attach: function (context) {
      var embeds = $(".shortcode-social", context);

      embeds.each(function (item) {
        var type = $(this).attr('class').split(' ')[1];
        var current_embed = $(this);
        var embed_url = '';
        switch (type) {
          case 'twitter':
            embed_url = 'https://api.twitter.com/1/statuses/oembed.json?url=' + $(this).attr('data');
            break;

          case 'instagram':
            embed_url = 'https://instagram.com/publicapi/oembed/?url=' + $(this).attr('data');
            break;

          case 'flickr':
            embed_url = '//www.flickr.com/services/oembed.json/?url=' + $(this).attr('data');
            break;

          case 'facebook':
            embed_url = '//www.facebook.com/plugins/post/oembed.json/?url=' + $(this).attr('data');
        }

        // Flickr oembed is broken and doesn't work with jsonp.
        if (type !== 'flickr') {
          $.ajax({
            url: embed_url,
            dataType: "jsonp",
            contentType: "application/json; charset=utf-8",
            success: function (data) {
              current_embed.html(data.html);
            },
            error: function (e, s, t) {
            }
          });
        }
        else {
          $.ajax({
            url: embed_url,
            dataType: "jsonp",
            jsonp: 'jsoncallback',
            success: function (data) {
              current_embed.html(data.html);
            },
            error: function (e, s, t) {
            }
          });
        }
      });
    }
  };
}(jQuery));
