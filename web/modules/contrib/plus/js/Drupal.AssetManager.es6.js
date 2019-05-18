/**
 * @file
 * Drupal+ Asset Manager.
 */
(($, Drupal) => {
  'use strict';

  /**
   * @class AssetManager
   */
  class AssetManager {
    constructor() {
      this.css = Drupal.settings.ajaxPageState ? Drupal.settings.ajaxPageState.css : {};
      this.js = Drupal.settings.ajaxPageState ? Drupal.settings.ajaxPageState.js : {};
      this.library = Drupal.settings.ajaxPageState ? Drupal.settings.ajaxPageState.library : {};
      this.modules = {};
    }

    static getAssetType(data) {
      let type = 'js';

      // Detect libraries.
      if (/^@[\w]+/.test(data)) {
        type = 'library';
        data = data.replace(/^@/, '');
      }
      else {
        const match = data.replace(/\?.*/, '').replace(/#.*$/, '').match(/^(css|js):\/\/|\.(css|js)$/);
        if (match) {
          type = match[1] || match[2] || 'js';
        }
        data = data.replace(/^(css|js):\/\//, '');
      }
      return [type, data];
    }

    add(type, data, options) {
      options = options || {};

      // Extract the type from data (e.g. passed a single string).
      if (typeof type === 'string' && typeof data !== 'string') {
        [type, data] = AssetManager.getAssetType(data);
      }

      return new Promise((resolve, reject) => {

      });

      // Handle libraries or URIs that mimic Drupal's PHP like stream wrappers.
      // if (type === 'library' && /^(module|theme|profile|public|private|temp):\/\//.test(data)) {
      // }
      //
      // switch (type) {
      // }
    }
  }


  // Wrap the rest of the file in a DOM ready handler.
  document.addEventListener('DOMContentLoaded', function () {
    // Don't export the AssetManager class globally. If this happened, any
    // script could invoke AssetManager.addToDom directly and bypass the
    // "management" part of this class. Instead create a new instance of the
    // class to ensure assets are properly managed. This class exposes its own
    // public APIs that can essentially do the same as the static method, but
    // maintaining a list of already loaded assets.
    Drupal.assets = new AssetManager();

    /**
     * Create an alias method for easily adding assets.
     *
     * @param {String} [type]
     *   The type of asset to add, e.g. "css", "js", or "library".
     * @param {String} data
     *   The data that will be added. If type is "css" or "js", then this should
     *   be the complete URI of the file to add.
     * @param {Object} [options = {}]
     *   Additional options to pass to the asset manager when adding the data.
     *
     * @return {Promise}
     *   A promise.
     */
    Drupal.add = (type, data, options) => this.proxy(Drupal.assets, 'add', [type, data, options]);
  });
})(window.jQuery, window.Drupal);
