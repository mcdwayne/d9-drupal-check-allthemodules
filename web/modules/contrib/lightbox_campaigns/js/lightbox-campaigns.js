/**
 * @file
 * Lightbox Campaigns module featherlight handling.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  var initialized;

  /**
   * Set the `initialized` var once initialization is complete.
   */
  function init() {
    if (!initialized) {
      initialized = true;
      loadCampaigns();
    }
  }

  /**
   * Looks for and loads lightbox campaigns that should be loaded.
   */
  function loadCampaigns() {
    if (typeof drupalSettings.lightbox_campaigns !== 'undefined') {
      drupalSettings.lightbox_campaigns.forEach(function ($campaign) {
        var $localStorageKey = 'lightboxCampaignsCampignShown-' + $campaign.id;
        var $last = localStorage.getItem($localStorageKey);

        if ($campaign.prevent_trigger !== '1'
          && $.now() - $last > $campaign.reset_timer * 1000) {
          $.featherlight($campaign.callback, {
            type: 'ajax',
            afterOpen: function () {
              localStorage.setItem($localStorageKey, $.now());
            }
          });
        }
      });
    }
  }

  /**
   * Only execute the loadCampaigns function once per page load.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Checks for final page load initialization.
   */
  Drupal.behaviors.lightboxCampaignsDisplay = {
    attach: function (context) {
      init();
    }
  };

})(jQuery, Drupal, drupalSettings);
