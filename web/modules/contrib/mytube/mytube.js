(function ($) {

Drupal.behaviors.mytube = {
  attach: function (context, settings) {
    $('.mytubetrigger', context).once('mytubetrigger').click(function(){
      $(this).hide();
      $(this).after(unescape($('.mytubeembedcode', this).html()));
      Drupal.attachBehaviors(this);

      // If API usage is enabled, instantiate the API.
      if ($(this).hasClass('mytube-js-api')) {
        Drupal.behaviors.mytube.InitiateYouTubeAPI();
      }

    });

    // Start the video when pressing the Enter button
    $('.mytubetrigger', context).keypress(function(e){
      if(e.which == 13){ // Enter key pressed
        $(this).click(); // Trigger search button click event
      }
    });

  }
};

/**
 * If API usage is enabled, initalize the player once the API is ready.
 */
Drupal.behaviors.mytube.InitiateYouTubeAPI = function(context) {
  if (typeof this.initialized === 'undefined') {
    // Load the iFrame Player API code asynchronously.
    var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    this.initialized = true;
  }
};

})(jQuery);
