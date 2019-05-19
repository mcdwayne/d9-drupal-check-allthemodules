(function ($, Drupal) {
  Drupal.behaviors.summoner_test = {
    attach: function (context, settings) {
      var $link = $('#summoner-test-link', context);
      $link.click(function(event) {
        event.preventDefault();
        Drupal.summon('summoner_test/summoner.testlib', function() {
          $('.summoner-test-css').summonerTestPlugin();
        });
      });
    }
  };
}(jQuery, Drupal));