(function (Drupal) {
  'use strict';

  Drupal.behaviors.smart_content_demandbase = {
    attach() {
      Drupal.smart_content.SmartContentManager.plugin = Drupal.smart_content.SmartContentManager.plugin || {};
      Drupal.smart_content.SmartContentManager.plugin.Field = Drupal.smart_content.SmartContentManager.plugin.Field || {};
      Drupal.smart_content.SmartContentManager.plugin.Field.demandbaseSmartCondition = {
        init: function (Field) {
          // Check if the Demandbase object is available.
          if (typeof window.Demandbase === 'undefined') {
            console.error('Demandbase tag not found.');
            Field.complete(null, true);
          }
          else {
            // pluginId[0] = base plugin ID
            // pluginId[1] = Demandbase field key
            let pluginId = Field.pluginId.split(':');
            if (pluginId[0] !== 'demandbase') {
              return;
            }
            Field.claim();
            let i = 0;
            Field.processingInteval = setInterval(function () {
              i++;
              if (!Field.processed && window.Demandbase && window.Demandbase.IP && window.Demandbase.IP.CompanyProfile) {
                if (window.Demandbase.IP.CompanyProfile && typeof pluginId[1] !== 'undefined' && typeof window.Demandbase.IP.CompanyProfile[pluginId[1]] !== 'undefined') {
                  Field.complete(window.Demandbase.IP.CompanyProfile[pluginId[1]], true);
                }
                else {
                  Field.complete(null, true);
                }
                clearInterval(Field.processingInteval);
              }
              if (i >= 20) {
                Field.complete(null, true);
                clearInterval(Field.processingInteval);
              }
            }, 200);
          }
        }
      }
    }
  }

})(Drupal);
