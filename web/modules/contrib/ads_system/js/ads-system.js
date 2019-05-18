/**
 * @file Script Ads System.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  function gatherAdsBlocks() {
    var adTypes = [];
    var blocks = $('.block-entity-ads');

    $.each(blocks, function (index, value) {
      adTypes.push($(value).attr('id'));
    });

    return adTypes;
  }

  // Ad system init behavior.
  Drupal.behaviors.adsSystem = {
    attach: function (context, settings) {
      var adTypes = gatherAdsBlocks();

      // Load ads per blockType.
      if (adTypes.length > 0) {
        $.ajax({
          url: Drupal.url('ads/getall'),
          type: 'POST',
          data: {'adTypes[]': adTypes},
          dataType: 'json',
          success: function (results) {
            var screenW = screen.width;

            $.each(results, function (blockType, adIds) {
              $.each(adIds, function (adId, ad) {
                if (screenW >= ad.breakpoint_min.size && screenW <= ad.breakpoint_max.size) {
                  $('#' + blockType).text('').append(ad.render);
                  $('#' + blockType + ' .ad')
                    .css('width', ad.size.w + 'px');
                }
              });

            });
          }
        });
      }

    }
  };
})(jQuery, Drupal, drupalSettings);
