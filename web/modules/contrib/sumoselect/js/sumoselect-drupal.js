/**
 * @file
 * Attaches behaviors for sumoselect.
 */

(function($, Drupal, drupalSettings) {
  'use strict';

  // Update sumoselect elements when state has changed.
  $(document).on('state:disabled', 'select', function (e) {
    $(e.target).trigger('sumoselect:updated');
  });

  Drupal.behaviors.sumoselect = {

    settings: {},

    /**
     * Drupal attach behavior.
     */
    attach: function(context, drupalSettings) {
      this.settings = this.getSettings(drupalSettings);
      this.getElements(context).once('sumoselect').each(function (i, element) {
        this.createSumoselect(element);
      }.bind(this));
    },

    /**
     * Creates a sumoselect instance for a specific element.
     *
     * @param {jQuery|HTMLElement} element
     *   The element.
     */
    createSumoselect: function(element) {
      var $element = $(element);
      $element.SumoSelect(this.getElementOptions($element));
    },

    /**
     * Retrieves the elements that should be converted into instances.
     *
     * @param {jQuery|Element} context
     *   A DOM Element, Document, or jQuery object to use as context.
     */
    getElements: function (context) {
      var $context = $(context || document);
      var $elements = $context.find(this.settings.selector)
        .not($context.find(this.settings.selectorToExclude))
        .add($context.find(this.settings.selectorToInclude));
      return $elements;
    },

    /**
     * Retrieves options used to create an instance based on an element.
     *
     * @param {jQuery|HTMLElement} element
     *   The element to process.
     *
     * @return {Object}
     *   The options object used to instantiate an instance with.
     */
    getElementOptions: function (element) {
      var $element = $(element);
      var options = $.extend({}, this.settings.options);

      // TODO: massage options dependent on magick element classes.

      return options;
    },

    /**
     * Retrieves the settings passed from Drupal.
     *
     * @param {Object} [drupalSettings]
     *   Passed Drupal settings object, if any.
     */
    getSettings: function (drupalSettings) {
      return $.extend(true, {}, this.settings, drupalSettings && drupalSettings.sumoselect || drupalSettings.sumoselect);
    }

};

})(jQuery, Drupal, drupalSettings);
