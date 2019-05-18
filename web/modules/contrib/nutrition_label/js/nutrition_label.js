/**
 * @file
 * Attaches behaviors for the Nutrition Label module.
 */

(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.nutrition_label = {

    settings: {
      selector: '[data-nutrition-label-hash]',
      labelSettings: {},
      options: {showLegacyVersion: false}

// see chosen for examples
    },

    /**
     * Drupal attach behavior.
     */
    attach: function(context, settings) {
      this.settings = this.getSettings(settings);
      this.getElements(context).once('nutrition_label').each(function (i, element) {
        this.createNutritionLabel(element);
      }.bind(this));
    },

    /**
     * Creates a Nutrition Label instance for a specific element.
     *
     * @param {jQuery|HTMLElement} element
     *   The element.
     */
    createNutritionLabel: function(element) {
      var $element = $(element);
      var options = this.getElementOptions($element);
      $element.nutritionLabel(options);
      if (options['showServingUnitQuantityAccessible']) {
        $element.find('span[itemprop="servingSize"]').addClass('sr-only');
      }
    },

    /**
     * Retrieves the elements that should be converted into Nutrition Label instances.
     *
     * @param {jQuery|Element} context
     *   A DOM Element, Document, or jQuery object to use as context.
     * @param {string} [selector]
     *   A selector to use, defaults to the default selector in the settings.
     */
    getElements: function (context, selector) {
      var $context = $(context || document);
      var $elements = $context.find(selector || this.settings.selector);
      return $elements;
    },

    /**
     * Retrieves options used to create a Nutrition Label instance based on an element.
     *
     * @param {jQuery|HTMLElement} element
     *   The element to process.
     *
     * @return {Object}
     *   The options object used to instantiate a Nutrition Label instance with.
     */
    getElementOptions: function (element) {
      var $element = $(element);
      var hash = $element.data('nutritionLabelHash');
      var options = $.extend({}, this.settings.options, this.settings.labelSettings[hash]);
      if (options['width'] && !options['width'].match(/^\d+$/)) {
        options.allowCustomWidth = true;
        options.widthCustom = options.width;
      }
      if (options['scrollHeightComparison']) {
        options.scrollLongIngredients = true;
        options.scrollHeightPixel = options.scrollHeightComparison - 5;
      }
      if (options['valueDisclaimer']) {
        options.showDisclaimer = true;
        if (options['scrollDisclaimerHeightComparison']) {
          options.scrollDisclaimer = options.scrollDisclaimerHeightComparison - 5;
        }
      }
      if (options['scrollLongItemNamePixel']) {
        // @todo: unset this sometimes?
        options.scrollLongItemName = true;
        options.scrollLongItemNamePixel2018Override = options['scrollLongItemNamePixel'];
      }
      if (options['urlBottomLink']) {
        options.showBottomLink = true;
      }
      if (!options['itemName']) {
        options.showItemName = false;
      }
      if (!options['brandName']) {
        options.showBrandName = false;
      }
      if (!options['ingredientList']) {
        options.showIngredients = false;
      }
      if (options['naPotassium']) {
        options.naPotassium_2018 = true;
      }
      if (options['valuePotassium']) {
        options.valuePotassium_2018 = options.valuePotassium;
      }
      $.each(options, function(key, val) {
        if (key.indexOf('unit') === 0 && val === null) {
          delete options[key];
        }
      });
      return options;
    },

    /**
     * Retrieves the settings passed from Drupal.
     *
     * @param {Object} [settings]
     *   Passed Drupal settings object, if any.
     */
    getSettings: function (settings) {
      // Requires nutrition_label settings to be defined.
      return $.extend(true, {}, this.settings, settings && settings.nutritionLabel || drupalSettings.nutritionLabel);
    }

};

})(jQuery, Drupal, drupalSettings);
