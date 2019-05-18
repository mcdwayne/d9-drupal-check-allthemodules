/**
 * @file
 * Contains commerce_recent_purchase_popup scripts.
 */

(function($, Drupal) {
  'use strict';

  Drupal.behaviors.commerce_recent_purchase_popup = {
    attach: function(context, settings) {
      var $recentPurchaseBlock = $('.recent-purchase-block', context);
      $recentPurchaseBlock
        .once('commerce-recent-purchase-block')
        .each(function() {
          var $this = $(this);
          // Close button.
          $this.find('.recent-purchase-close').click(function() {
            $this.remove();
          });

          // Rotate blocks based on settings.
          if (
            !settings ||
            !settings.recentPurchasePopupBlockSettings ||
            !settings.recentPurchasePopupBlockSettings.delay ||
            !settings.recentPurchasePopupBlockSettings.interval ||
            !settings.recentPurchasePopupBlockSettings.time_to_show
          ) {
            return;
          }

          var delay = parseInt(settings.recentPurchasePopupBlockSettings.delay);
          var interval = parseInt(
            settings.recentPurchasePopupBlockSettings.interval
          );
          var timeToShow = parseInt(
            settings.recentPurchasePopupBlockSettings.time_to_show
          );

          var $blocks = $this.find('.recent-purchase-container');
          var i = 0;

          setTimeout(function() {
            (function loopBlocks(n) {
              i = n++;

              $blocks
                .eq(i)
                .slideToggle()
                .delay(timeToShow)
                .slideToggle();

              if (n < $blocks.length) {
                setTimeout(loopBlocks, interval, n);
              }
            })(0);
          }, delay);
        });
    }
  };
})(jQuery, Drupal);
