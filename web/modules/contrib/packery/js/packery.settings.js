/**
 * @file
 * packery.settings.js
 */

(function (Drupal) {

  Drupal.behaviors.packery = {
    attach: function (context, settings) {
      for (var group in settings.packery) {
        this.attachInit(group, settings.packery[group]);
      }
    },

    /**
     * Find element to attach Packery on.
     */
    attachInit: function(group, options) {
      var element = document.querySelector('.' + group + '.packery');
      this.attachFlickity(element, options);
    },

    /**
     * Attach & expose Packery instance.
     */
    attachFlickity: function(element, options) {
      var packery = new Packery(element, options.settings);
      this.imagesLoaded(packery, options.extend.images_loaded);

      Drupal.packery.instance.push(packery);
    },

    /**
     * Provides imagesLoaded (plugin) support.
     */
    imagesLoaded: function(packery, flag) {
      if (flag) {
        imagesLoaded(packery, function(instance) {
          packery.layout();
        });
      }
    }
  }

  /**
   * Packery namespace.
   *
   * @namespace
   */
  Drupal.packery = {

    /**
     * Track Packery instance.
     */
    instance: []
  }

})(Drupal);