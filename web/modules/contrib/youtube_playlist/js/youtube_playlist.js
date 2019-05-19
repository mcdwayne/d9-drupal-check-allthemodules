(function($){
  Drupal.behaviors.loadPlaylist = {
    attach: function (context, settings) {
      var player;
      var load_api_interval;
      $('.playlist', context).on('click', '.playlist-item', function(){
        var videoId = $(this).attr('data-videoid');
        var video = settings.videos[videoId];
        $('.video-info .video-date').text(video.date);
        $('.video-info .video-title').text(video.title);
        player.loadVideoById({videoId: videoId, suggestedQuality: 'large'});
        $('.playlist-item_selected').removeClass('playlist-item_selected');
        $(this).addClass('playlist-item_selected');
      });
      if($('#'+settings.player_id, context).length) {
        console.log('player', YT.Player);
        if(!YT.loaded) {
          load_api_interval = setInterval(function(){
            if(!YT.loaded) {
              return;
            }
            clearInterval(load_api_interval);
            player = new YT.Player(settings.player_id, {
              height: '405',
              width: '720',
              playerVars: {
                modestbranding: true,
                rel: false,
                color: 'red'
              },
              videoId: settings.video.video_id
            });
          }, 10);
        } else {
          player = new YT.Player(settings.player_id, {
            height: '405',
            width: '720',
            playerVars: {
              modestbranding: true,
              rel: false,
              color: 'red'
            },
            videoId: settings.video.video_id
          });
        }
      }
    }
  }
})(jQuery);