/**
 * @file
 * Javascript functionality for VideoJs
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.videoJs = {
    attach: function (context, settings) {
      if(typeof settings.video_js != 'undefined') {

        // We insert the html into the element
        var element = settings.video_js.element;
        var html = settings.video_js.html;
        $(element).prepend(html);
        var options = {};

        var player = videojs('my-player', options, function onPlayerReady() {

          // In this context, `this` is the player that was created by Video.js.
          this.play();

          // How about an event listener?
          // this.on('ended', function() {
          //   videojs.log('Awww...over so soon?!');
          // });
        });

      }
      updateSize(element);
      // set events
      $(window).resize( function() {
        updateSize(element);
      });
    }
  };

  function updateSize(element) {
    var containerW = $(element).outerWidth() < $(window).width() ? $(element).outerWidth() : $(window).width(),
      containerH = $(element).outerHeight() < $(window).height() ? $(element).outerHeight() : $(window).height(),
      containerAspect = containerW/containerH;
    var mediaAspect = 16/9;
    var vidEl = '#my-player';

    if (containerAspect < mediaAspect) {
      // taller
      $(vidEl)
        .width(containerH*mediaAspect)
        .height(containerH);
      // if (!settings.shrinkable) {
      $(vidEl)
        .css('top',0)
        .css('left',-(containerH*mediaAspect-containerW)/2)
        .css('height',containerH);
      // } else {
      //   $(vidEl)
      //     .css('top',-(containerW/mediaAspect-containerH)/2)
      //     .css('left',0)
      //     .css('height',containerW/mediaAspect);
      // }
      $(vidEl+'_html5_api')
        .css('width',containerH*mediaAspect)
        .css('height',containerH);
      $(vidEl+'_flash_api')
        .css('width',containerH*mediaAspect)
        .css('height',containerH);
    } else {
      // wider
      $(vidEl)
        .width(containerW);
      $(vidEl)
        .css('top',-(containerW/mediaAspect-containerH)/2)
        .css('left',0)
        .css('height',containerW/mediaAspect);
    }
  }


})(jQuery, Drupal, drupalSettings);
