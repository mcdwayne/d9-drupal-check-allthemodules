/**
 * @file
 * vv scripts.
 */

(function ($) {
Drupal.behaviors.veftaBehavior = {
  attach: function(context, settings) {
    var veftaOuter = $('.vefta-outer');
    veftaOuter.each(function() {
      // Define variables.
      var veftaTrigger = $(this).find('.vefta-trigger');
      var veftaStill = $(this).find('.vefta-still');
      var veftaVideo = $(this).find('.vefta-video');
      var veftaVideoIframe = veftaVideo.find('iframe');
      // Hide video by default.
      veftaVideo.hide();
      // Stop video if it starts by default.
      if (veftaVideoIframe.length) {
        veftaVideoIframe.prop('src', veftaVideoIframe.prop('src').replace('?autoplay=1', '?autoplay=0'));
      }
      // Click behavior.
      veftaTrigger.click(function() {
        // Stop other video's.
        var otherVideos = $('.vefta-outer');
        otherVideos.each(function() {
          var me = $(this);
          me.removeClass('video-active');
          me.find('.vefta-video').hide();
          me.find('.vefta-still').show();
          ownIframe = me.find('iframe');
          if (ownIframe.length) {
            ownIframe.prop('src', ownIframe.prop('src').replace('?autoplay=1', '?autoplay=0'));
          }
        });
        // Play current video.
        $(this).closest('.vefta-outer').addClass('video-active');
        veftaVideo.show();
        veftaStill.hide();
        veftaVideoIframe.prop('src', veftaVideoIframe.prop('src').replace('?autoplay=0', '?autoplay=1'));
      });
    });
  }
};
}(jQuery));
