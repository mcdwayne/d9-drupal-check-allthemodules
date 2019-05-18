(function ($, Drupal) {

  'use strict';
  
  $.fn.globalGatewayEmmitRegionChange = function(data) {
    $.event.trigger('global_gateway:region:changed', { region: data.region.toLowerCase() });
  };
  
  function ggToggleBodyClasses(region, once) {
    var $body = {};
    
    if (once == true) {
      $body = $('body').once();
    }
    else {
      $body = $('body');
    }
    
    $body.attr('class',
      function(i, c){
        return c.replace(/(^|\s)region-\S+/g, '');
      })
      .addClass('region-' + region);
  }
  
  $(window).on('global_gateway:region:changed', function (event, data) {
    ggToggleBodyClasses(data.region, false);
  });

  Drupal.behaviors.global_gateway = {
    attach: function (context, settings) {
      // Set current langcode for the region class by default.
      $(window).on('load', function () {
        ggToggleBodyClasses(drupalSettings.regionCode.toLowerCase(), false);
      });
      $('.global-gateway-switcher-form select.global-gateway-region', context).each(function() {
        ggToggleBodyClasses($(this).val().toLowerCase(), true);
      });
    }
  };

})(jQuery, Drupal);
