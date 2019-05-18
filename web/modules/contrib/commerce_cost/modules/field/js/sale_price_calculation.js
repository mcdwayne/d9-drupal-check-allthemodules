/**
 * @file
 * Defines sale price based off product price, cost and markup.
 */
(function ($, Drupal) {
  'use strict';

  /**
   * Determine markup given a cost and price.
   *
   * @param {number} cost
   *   A product cost.
   * @param {number} price
   *   A product price.
   *
   * @returns {string}
   *   The product's markup, formatted to two decimal places.
   */
  function markup(cost, price) {
    return ((price - cost) / cost * 100).toFixed(2);
  }

  /**
   * Determine margin given a cost and price.
   *
   * @param {number} cost
   *   A product cost.
   * @param {number} price
   *   A product price.
   *
   * @returns {string}
   *   The product's margin, formatted to two decimal places.
   */
  function margin(cost, price) {
    return ((price - cost) / price * 100).toFixed(2);
  }

  /**
   * Determine price given a markup and cost.
   *
   * @param {number} markup
   *   A product markup percentage.
   * @param {number} cost
   *   A product cost.
   *
   * @returns {string}
   *   The product's price, formatted to two decimal places.
   */
  function markupGetPrice(markup, cost) {
    return (cost * (1 + markup / 100)).toFixed(2);
  }

  /**
   * Determine margin given a cost and price.
   *
   * @param {number} margin
   *   A product margin percentage.
   * @param {number} cost
   *   A product cost.
   *
   * @returns {string}
   *   The product's price, formatted to two decimal places.
   */
  function marginGetPrice(margin, cost) {
    return (cost / (1 - margin / 100)).toFixed(2);
  }

  Drupal.behaviors.commerce_cost_calculation = {
    attach: function (context, settings) {
      var markupClass = 'field--name-field-markup-percentage';
      var priceClass = '.field--name-' + drupalSettings.commerce_cost_field.price_field;
      var costClass = '.field--name-' + drupalSettings.commerce_cost_field.cost_field;
      var calculationType = drupalSettings.commerce_cost_field.calculation_type;
      var markupLabel = calculationType === 'markup' ? Drupal.t('Markup') : Drupal.t('Margin');
      var calculator = calculationType === 'markup' ? markup : margin;
      var getPrice = calculationType === 'markup' ? markupGetPrice : marginGetPrice;

      // Commerce price DOM elements.
      var $markup = '';
      var $price = $(priceClass).find('input');
      var $cost = $(costClass).find('input');

      // The markup textfield.
      var markupElement = '<label for="markup-percentage" class="markup-field-label">'+ markupLabel + '</label>';
      markupElement += '<input type="text" size="10" id="markup-percentage" class="form-text ' + markupClass + '">';
      markupElement += '<span class="field-suffix">%</span>';
      $markup = $(markupElement);
      if ($('#markup-percentage').length === 0) {
        $markup.insertAfter($(costClass));
      }
      markupClass = '.' + markupClass;
      $markup = $(markupClass);

      // Default value for already set product's margin.
      if ($cost.val() !== '' && $price.val() !== '') {
        $markup.val(calculator($cost.val(), $price.val()));
      }

      // Cost and Markup fields changed - set price.
      $cost.add($markup).on('keyup', function() {
        if ($cost.val() !== '' && $markup.val() !== '') {
          var priceNumber = $price.get(0);
          var value = getPrice($markup.val(), $cost.val());
          animateTextField($(priceNumber), value);
        }
      });

      // Price field changed - set markup.
      $price.on('keyup', function() {
        if ($price.val() !== '' && $cost.val() !== '') {
          var value = calculator($cost.val(), $price.val());
          animateTextField($markup, value);
        }
      });

      /**
       * Provide an animation on textfields highlighting value change.
       */
      function animateTextField($obj, val) {
        if (isNaN(val)) {
          return;
        }
        $obj
          .val(val)
          .addClass('commerce-cost-value-changed');

        setTimeout(function() {
          $obj.removeClass('commerce-cost-value-changed');
        }, 500);
      }

    }
  };

})(jQuery, Drupal);
