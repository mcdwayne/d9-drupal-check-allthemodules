/**
 * @file
 * flickity.settings.js
 */

(function (Drupal) {

  Drupal.behaviors.flickity = {
    attach: function (context, settings) {
      this.context = context;

      for (var group in settings.flickity) {
        this.attachInit(group, settings.flickity[group]);
      }
    },

    /**
     * Find element to attach Flickity on.
     */
    attachInit: function(group, options) {
      var elements = this.context.querySelectorAll('.' + group + '.flickity:not(.flickity-enabled)');

      for (var i = 0; i < elements.length; i++) {
        this.attachFlickity(elements[i], options.settings);
      }
    },

    /**
     * Attach & expose Flickity instance.
     */
    attachFlickity: function(element, settings) {
      var flickity = new Flickity(element, settings);
      Drupal.flickity.instance.push(flickity);
    }
  }

  /**
   * Flickity namespace exposure.
   *
   * @namespace
   */
  Drupal.flickity = {

    /**
     * Track Flickity instance.
     */
    instance: []
  }

})(Drupal);