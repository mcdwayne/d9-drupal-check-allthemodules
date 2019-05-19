(function ($) {

  var SmartContentManager = Drupal.smart_content.SmartContentManager;

  Drupal.behaviors.decisionAgentClientSide = {
    attach: function (context, settings) {
      SmartContentManager.attach('data-smart-content-client', context);
    }
  };
  SmartContentManager.plugin = SmartContentManager.plugin || {};
  SmartContentManager.plugin.client = SmartContentManager.plugin.client || {};
  SmartContentManager.plugin.client.decisionAgentClientSide = {
    process: function (Decision) {
      if (Decision.processed) {
        return;
      }

      var earlier_variation_not_appeased = false;
      for (var i = 0; i < Decision.variations.length; i++) {
        // If Decision processed, stop further processing.
        if (Decision.processed) {
          return;
        }

        var Variation = Decision.variations[i];
        // If not appeased, loop through conditions and check process status.
        if(!Variation.appeased) {
          Variation.checkAppeased();
        }

        // If after processing conditions, variation still not appeased, restrict later variants from processing Decision.
        if(!Variation.appeased) {
          var earlier_variation_not_appeased = true;
        }
        // Process variation if previous Variations appeased and evaluate false.
        if(Variation.appeased && !Variation.processed && !earlier_variation_not_appeased) {
          Variation.processed = true;

          // Evaluate condition
          Variation.evaluateClientside();

          // Initialize variable for broadcast after a winner is selected.
          let winner = null;
          // Variation.result = Boolean(Math.round(Math.random()));
          if(Variation.result) {
            Decision.processed = true;
            Decision.params = [Decision.name.split(/\.(.+)/)[0], Decision.name.split(/\.(.+)/)[1], Variation.id];
            winner = Variation;
          } else {
            if(i == (Decision.variations.length - 1)) {
              Decision.processed = true;
              if(Decision.default_variation) {
                Decision.params = [Decision.name.split(/\.(.+)/)[0], Decision.name.split(/\.(.+)/)[1], Decision.default_variation];
                winner = Variation;
              }
            }
          }
          SmartContentManager.invoke('Variation', 'postExecute', winner);
        }
      }
    },
    execute: function (Decision) {
      if(Decision.params.length === 3) {

        var url = '/ajax/smart-content/decision/' + Decision.params.join('/');
        if(Decision.context.length !== 0) {
          var query = Object.keys(Decision.context).reduce(function(a,k){a.push(k+'='+encodeURIComponent(Decision.context[k]));return a},[]).join('&')
          var url = url + '?' + query;
        }
        var ajaxObject = new Drupal.ajax({
          url: url,
          progress: false,
          success: function (response, status) {
            for (var i in response) {
              if (response.hasOwnProperty(i) && response[i].command && this.commands[response[i].command]) {
                this.commands[response[i].command](this, response[i], status);
              }
            }
          }
        });
        ajaxObject.execute();
      }
    }
  };
  SmartContentManager.plugin.Variation = SmartContentManager.plugin.Variation || {};
  SmartContentManager.plugin.Variation.decisionAgentClientSide = {
    init: function (Variation) {
      Variation.processed = false;
      Variation.result = false;
    }
  };

  SmartContentManager.plugin.Condition = SmartContentManager.plugin.Condition || {};
  SmartContentManager.plugin.Condition.decisionAgentClientSide = {
    init: function (Condition) {
      Condition.result = false;
    }
  }
})(jQuery);