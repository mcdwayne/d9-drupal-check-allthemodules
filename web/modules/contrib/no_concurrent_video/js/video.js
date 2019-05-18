/**
 * Created by bcgreen on 5/17/17.
 * JavaScript for the no_concurrent_video Drupal 8 module
 **/

jQuery(document).ready(function () {
  var videoClass = drupalSettings.no_concurrent_video.video_class;
  var videos = jQuery("video." + videoClass);
  for (var i = 0; i < videos.length; i++) {
    videos.eq(i).on('play', function () {
      for (var j = 0; j < videos.length; j++) {
        if (videos[j].src != this.src) {
          videos[j].pause();
        }
      }
    });
  }
});
