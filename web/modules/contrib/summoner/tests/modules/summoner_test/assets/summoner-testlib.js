(function($, Drupal) {

  Drupal.behaviors.summoner_testlib = {
    attach: function (context, settings) {
      // Add a jQuery plugin in attach to proof callbacks are executed after
      // attach.
      $.fn.summonerTestPlugin = function() {
        $(this).addClass('summoner-test-css-red');
      };
    }
  };

}(jQuery, Drupal));