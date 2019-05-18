/**
 * @file
 * Attaches behaviors for the Drupal Swipebox module.
 */

(function ($) {

  Drupal.behaviors.dsbox = {
    attach: function (context, settings) {

    $(".dsbox").swipebox();
    $(".dsbox-video").swipebox();
    // User agent match for android ensures the use of swipebox button navigation.
    if(navigator.userAgent.match(/Android/i)){window.scrollTo(0,1);}
  
    }
  }
    
})(jQuery);
