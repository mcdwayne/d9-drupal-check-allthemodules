/**
 * @file
 * JS View implementation for 'smart_default' ads view plugin.
 */

(function ($, Drupal, drupalSettings, window) {
  if (typeof sas == "undefined") {
    return;
  }

  sas = sas || {};
  sas.cmd = sas.cmd || [];
  Drupal.ad_entity = Drupal.ad_entity || window.adEntity || {};
  Drupal.ad_entity.viewHandlers = Drupal.ad_entity.viewHandlers || {};
  var $window = $(window);
  var smart = new drupalSettings.ad_entity_smart.Smart();

  sas.cmd.push(function () {
    sas.setup({
      networkid: smart.getNetworkId(),
      domain: smart.getDomain(),
      async: true
    });
  });

  // Listen to refresh ads event.
  $window.on('smart:refreshAllAds', function(e) {
    sas.refresh();
  });

  Drupal.ad_entity.viewHandlers.smart_default = {
    initialize: function (containers, context, settings) {
      var formatIds = smart.getAllNotInitializedAdFormatIds().join(',');
      if (formatIds) {
        // Initialize smart ads.
        sas.cmd.push(function () {
          var targeting = smart.getTarget();

          // Set the global variable. It is needed for the Smart AdServer.
          window.sas_target = targeting;

          sas.call("onecall", {
            siteId: smart.getSiteId(),
            pageName: smart.getPageName(),
            formatId: formatIds,
            target: targeting
          },
          {
            'onNoad': function(data){
              if (data.formatId) {
                var $container = smart.getAdContainerById(data.formatId);
                $container.addClass('empty');
                $container.removeClass('not-empty');
              }
              $window.trigger('smart:onNoad', [data]);
            },
            'onLoad': function(data){
              if (data.formatId) {
                var $container = smart.getAdContainerById(data.formatId);
                $container.removeClass('not-initialized');
                $container.addClass('initialized');
                $container.data('initialized', true);
                if (data.hasAd === true) {
                  $container.removeClass('empty');
                  $container.addClass('not-empty');
                }
              }
              $window.trigger('smart:onLoad', [data]);
            },
            'beforeRender': function(data){
              $window.trigger('smart:beforeRender', [data]);
            }
          });
        });

        // Render ads.
        for (var id in containers) {
          if (containers.hasOwnProperty(id)) {
            var container = containers[id];
            if (!$(container).hasClass('initialized')) {
              var adTag = $('.smart-ad', container[0]);
              // Remove other ads tags with the same id.
              $('#' + adTag.attr('id')).not(adTag).remove();
              var formatId = adTag.data('ad-id');
              sas.cmd.push(function () {
                sas.render(formatId);
              });
            }
          }
        }
      }
    },

    detach: function (containers, context, settings) {},
  };

}(jQuery, Drupal, drupalSettings, window));
