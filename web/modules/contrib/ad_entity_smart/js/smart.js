/**
 * @file
 * Smart js helper library.
 */

(function($, Drupal, drupalSettings) {

  /**
   * Smart ad helper class.
   *
   * @constructor
   */
  function Smart() {

    /**
     * Gets ad container object.
     *
     * @param {int} formatId
     *   The ad format id.
     *
     * @returns {jQuery}
     *   The ad container object.
     */
    this.getAdContainerById = function (formatId) {
      return $('#sas_' + formatId).parent();
    };

    /**
     * Gets all the ad format ids.
     *
     * @returns {int[]}
     *   The ad format ids.
     */
    this.getAllAdFormatIds = function() {
      var ids = [];
      $('.smart-ad').each(function() {
        ids.push($(this).data('ad-id'));
      });
      return ids;
    };

    /**
     * Gets all the not initialized ads.
     *
     * @returns {jQuery}
     *   The ad format.
     */
    this.getAllNotInitializedAds = function() {
      return $('.ad-entity-container.not-initialized .smart-ad');
    };

    /**
     * Gets all the not initialized ad format ids.
     *
     * @returns {int[]}
     *   The ad format ids.
     */
    this.getAllNotInitializedAdFormatIds = function() {
      var ids = [];
      this.getAllNotInitializedAds().each(function() {
        ids.push($(this).data('ad-id'));
      });
      return ids;
    };

    /**
     * Gets site id.
     *
     * @returns {int}
     *   The site id.
     */
    this.getSiteId = function() {
      return drupalSettings.ad_entity_smart.site_id;
    };

    /**
     * Gets domain.
     *
     * @returns {string}
     *   The domain.
     */
    this.getDomain = function() {
      return drupalSettings.ad_entity_smart.domain;
    };

    /**
     * Gets network id.
     *
     * @returns {string}
     *   The network id.
     */
    this.getNetworkId = function() {
      return drupalSettings.ad_entity_smart.network_id;
    };

    /**
     * Gets page name.
     *
     * @returns {string}
     *   The page name.
     */
    this.getPageName = function() {
      return drupalSettings.ad_entity_smart.page_name
    };

    /**
     * Gets target.
     *
     * @returns {string}
     *   The targeting string.
     */
    this.getTarget = function() {
      var targeting = drupalSettings.ad_entity_smart.targeting;

      // Let other modules to alter ads targeting.
      Drupal.ad_entity_smart = Drupal.ad_entity_smart || {};
      Drupal.ad_entity_smart.targetAlters = Drupal.ad_entity_smart.targetAlters || {};
      if (typeof Drupal.ad_entity_smart.targetAlters === 'object') {
        for (var alter in Drupal.ad_entity_smart.targetAlters) {
          if (typeof Drupal.ad_entity_smart.targetAlters[alter] === 'function') {
            Drupal.ad_entity_smart.targetAlters[alter](targeting);
          }
        }
      }

      return this.stringifyObject(targeting);
    };

    /**
     * Converts object to a string.
     *
     * @param {obj} object
     *   The target object.
     * @param {string} separator
     *   The string separator.
     *
     * @returns {string}
     *   The object string.
     */
    this.stringifyObject = function(object, separator = ';') {
      var result = [];
      for (var key in object) {
        if (object.hasOwnProperty(key)) {
          result.push(key + '=' + object[key]);
        }
      }

      return result.join(separator);
    }
  }

  // Export the class.
  drupalSettings.ad_entity_smart.Smart = Smart;

})(jQuery, Drupal, drupalSettings);
