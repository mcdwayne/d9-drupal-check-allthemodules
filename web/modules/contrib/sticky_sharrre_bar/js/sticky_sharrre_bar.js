/**
 * @file
 * Sticky Sharrre Bar UI.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  $.exists = function (selector) {
    return ($(selector).length > 0);
  };

  /**
   * Attaches the Sticky Sharrre Bar behavior to each block element.
   */
  Drupal.behaviors.stickySharrreBarRender = {
    attach: function (context) {

      var enableTracking = (drupalSettings.googleanalytics && drupalSettings.stickySharrreBar.useGoogleAnalyticsTracking === 1) ? true : false,
        blockRegion = drupalSettings.stickySharrreBar.blockRegion,
        isCustomSelector = drupalSettings.stickySharrreBar.isCustomSelector,
        selector = '',
        $block = $('.block-sticky-sharrre-bar', context);

      if (isCustomSelector) {
        selector = blockRegion;
      }
      else {
        // Try to find class, id or html tag of region.
        if ($.exists('.' + blockRegion)) {
          selector = '.' + blockRegion + ':first';
        }
        else if ($.exists('#' + blockRegion)) {
          selector = '#' + blockRegion;
        }
        else if ($.exists(blockRegion)) {
          selector = blockRegion + ':first';
        }
        else {
          return;
        }
      }

      // Attach the Waypoint and Sticky libraries to the selector.
      // Move the output code after selector.
      new Waypoint.Sticky({
        element: $block.insertAfter(selector).find('.sticky_sharrre_bar')
      });

      // The "sharrre" plugin requires this object.
      var buttons = {
        googlePlus: {},
        facebook: {},
        twitter: {},
        linkedin: {},
        digg: {},
        delicious: {},
        stumbleupon: {},
        pinterest: {},
        tumblr: {} // TODO: Available from v2.0.0 in "Sharrre" plugin.
      };

      $.each(drupalSettings.stickySharrreBar.providers, function (provider) {
        if (provider) {
          var currentProvider = {};
          currentProvider[provider] = true;

          $('#' + provider, context).sharrre({
            share: currentProvider,
            template: '<a class="share ' + provider + '" href="#">' + Drupal.t('Share on <span class="provider_name">!provider</span>', {'!provider': provider}, {}) + '</a></div><span class="count"><a href="#">{total}</a></span>',
            enableHover: false,
            enableTracking: enableTracking,
            enableCounter: true,
            buttons: buttons,
            urlCurl: (provider === 'stumbleupon' || provider === 'googlePlus') ? '/sharrre' : '',
            click: function (api, options) {
              api.simulateClick();
              api.openPopup(provider);
            }
          });
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
