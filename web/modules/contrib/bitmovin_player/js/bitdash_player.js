/**
 * @file
 */

(function ($) {

  /**
   * Renders the bitdash player.
   */
  Drupal.behaviors.bitdashPlayer = {
    attach: function (context, settings) {
      var key = {
        key: Drupal.settings.bitdash_player.player_key,
      };

      // Initialize all players.
      $('.bitdash-player').once('bitdash-player').each(function () {
        var $elem = $(this);
        var data = $elem.data();
        var player_id = $elem.attr('id');
        var player = bitdash(player_id);

        var config = {
          source: {
            dash: data.dash,
            hls: data.hls,
            progressive: data.progressive,
            poster: data.poster
          },
          playback: {
            autoplay: data.autoplay
          },
        };

        var btconf = $.extend(key, config);
        player.setup(btconf).then(function (value) {

        }, function (reason) {
          console.log('Could not play file')
        });
      });

    }
  };

})(jQuery);
