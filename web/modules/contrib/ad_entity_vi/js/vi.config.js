/**
 * @file
 * Video Intelligence config.
 */

(function($, Drupal, drupalSettings) {

  /**
   * Video Intelligence config class.
   *
   * @constructor
   */
  function ViConfig() {

    /**
     * Gets config.
     *
     * @returns {object}
     *   The config:
     *   - ChannelID: string,
     *   - PublisherID: string,
     *   - PlacementID: string,
     *   - IAB_Category: string,
     *   - AdUnitType: string,
     *   - Keywords: string,
     *   - Language: string,
     *   - playerConfig:
     *     - maxrun: string,
     *     - midrolltime: string,
     *     - maximp: string,
     *     - vastretry: string},
     *     - DivID: string
     */
    this.getConfig = function() {
      var config = {
        ChannelID: this.getSettingValue('channel_id'),
        PublisherID: this.getSettingValue('publisher_id'),
        PlacementID: this.getSettingValue('placement_id'),
        IAB_Category: this.getSettingValue('iab_category'),
        AdUnitType: this.getSettingValue('ad_unit_type'),
        Keywords: this.getSettingValue('keywords'),
        Language: this.getSettingValue('language'),
        playerConfig: {
          maxrun: this.getSettingValue('maxrun'),
          midrolltime: this.getSettingValue('midrolltime'),
          maximp: this.getSettingValue('maximp'),
          vastretry: this.getSettingValue('vastretry'),
        },
        DivID: 'vi-ai'
      };

      if (this.getSettingValue('bg_color')) {
        config['BG_Color'] = this.getSettingValue('bg_color')
      }
      if (this.getSettingValue('text_color')) {
        config['Text_Color'] = this.getSettingValue('text_color')
      }
      if (this.getSettingValue('font')) {
        config['Font'] = this.getSettingValue('font')
      }
      if (this.getSettingValue('font_size')) {
        config['FontSize'] = this.getSettingValue('font_size')
      }

      return config;
    };

    /**
     * Gets Drupal setting value id.
     *
     * @param {string} name
     *   The value name.
     *
     * @returns {string|null}
     *   The Drupal setting value, null if not found.
     */
    this.getSettingValue = function(name) {
      if (typeof drupalSettings.ad_entity_vi[name] !== undefined) {
        return drupalSettings.ad_entity_vi[name];
      }
      return null;
    };

  }

  // Export the class.
  drupalSettings.ad_entity_vi.ViConfig = ViConfig;

})(jQuery, Drupal, drupalSettings);
