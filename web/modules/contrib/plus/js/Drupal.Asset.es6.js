/**
 * @file
 * Drupal+ Asset.
 */
((Drupal) => {
  'use strict';

  /**
   * Private properties.
   *
   * @type {Object<String, WeakMap>}
   *
   * @private
   */
  const _ = {
    assetType: new WeakMap(),
  };

  /**
   * @class Asset
   */
  class Asset extends Drupal.Url {
    constructor() {
      super();
      _.assetType.set(this, null);
    }

    get assetType() {
      return this.getPrivateProperty(_, 'assetType', () => {
        const hash = this.hash;
        const query = this.query;
        let url = this.absolute.replace(/#.*$/, '').replace(/\?.*$/, '');
        let assetType = 'js';

        // Detect libraries.
        if (/^@[\w]+/.test(url)) {
          assetType = 'library';
          url = url.replace(/^@/, '');
        }
        else {
          const match = url.match(/^(css|js):\/\/|\.(css|js)$/);
          if (match) {
            assetType = match[1] || match[2] || 'js';
          }
          url = url.replace(/^(css|js):\/\//, '');
        }

        if (this.absolute !== url + query + hash) {
          this.absolute = url + query + hash;
        }

        return assetType;
      });
    }

    /**
     * Setter for assetType.
     *
     * @param {*} value
     *   The value to set.
     */
    set assetType(value) {
      Drupal.error('The type of an asset is determined automatically from the absolute URL and cannot be set manually.');
    }
  }

  /**
   * Export to Drupal.
   *
   * @type {Asset}
   */
  Drupal.Asset = Asset;
})(window.Drupal);
