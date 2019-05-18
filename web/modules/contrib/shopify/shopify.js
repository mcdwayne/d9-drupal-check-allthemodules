/**
 * @file
 * Defines Javascript behaviors for the Shopify module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.shopify = {};

  /**
   * Display an "Added to cart" message by sending a POST request to the backend.
   */
  Drupal.shopify.attachAddToCartMessage = function ($ctx) {
    var $forms = $ctx.find('form.shopify-add-to-cart-form');
    $forms.unbind('submit').submit(function (e) {
      var $form = $(this);
      e.preventDefault();
      $.post(drupalSettings.path.baseUrl + 'shopify/added-to-cart', {
        variant_id: $form.data('variant-id'),
        quantity: $form.find('input[name="quantity"]').val()
      }, function (data) {
        $form.get(0).submit();
      });
    });
  };

  /**
   * Displays the Shopify cart total if available.
   */
  Drupal.shopify.displayCartTotal = function ($ctx) {
    var $cartBlocks = $ctx.find('.shopify-cart-total');
    if ($cartBlocks.length) {
      $cartBlocks.each(function (i, el) {
        $.ajax({
          type: 'GET',
          url: '//' + drupalSettings.shopify.shop.domain + '/cart.json',
          dataType: 'jsonp',
          success: function (data) {
            $(el).text(data.item_count);
          }
        });
      });
    }
  };

  /**
   * Behaviors for Shopify.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches Shopify events.
   */
  Drupal.behaviors.shopify = {
    attach: function (context) {
      var $context = $(context);
      Drupal.shopify.attachAddToCartMessage($context);
      Drupal.shopify.displayCartTotal($context);
    }
  };

})(jQuery, Drupal, drupalSettings);
