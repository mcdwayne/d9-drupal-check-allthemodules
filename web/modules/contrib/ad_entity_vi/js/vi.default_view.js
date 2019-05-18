/**
 * @file
 * JS View implementation for 'Video Intelligence' ads view plugin.
 */

(function ($, Drupal, drupalSettings, window) {

  Drupal.ad_entity = Drupal.ad_entity || window.adEntity || {};
  Drupal.ad_entity.viewHandlers = Drupal.ad_entity.viewHandlers || {};
  var viConfig = new drupalSettings.ad_entity_vi.ViConfig();

  Drupal.ad_entity.viewHandlers.vi_default = {
    initialize: function (containers, context, settings) {
      $('script[src="//s.vi-serve.com/source.js"]')[0].onload = function() {
        var source = 'https://s.vi-serve.com/source.js';
        var config = viConfig.getConfig();
        window.vi.run(config, null, source);
        var $container = $('.ad-entity-container[data-ad-entity-type="vi"]');
        if ($container.length) {
          $container.removeClass('not-initialized');
          $container.addClass('initialized');
          $container.data('initialized', true);
          $container.removeClass('empty');
          $container.addClass('not-empty');
        }
      };
    },

    detach: function (containers, context, settings) {},
  };

}(jQuery, Drupal, drupalSettings, window));
