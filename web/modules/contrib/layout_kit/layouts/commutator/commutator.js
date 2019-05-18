/**
 * @file
 * Layout Kit - Conmutator JS.
 */
(function ($, Drupal) {

  /**
   * Layout Kit - Conmutator Drupal JS behavior.
   */
  Drupal.behaviors.layout_kit_commutator = {
    attach: function (context, settings) {

      // Execute conmutator.
      commutator('.layout--kit-commutator', settings, context);
    }
  }

  /**
   * Init conmutator using jQuery.
   *
   * @param string commutator
   *   Conmutator main selector.
   * @param object settings
   *   Drupal settings.
   * @param object context
   *   Drupal context.
   */
  function commutator(commutator, settings, context) {
    $(commutator, context).once('layout-kit-commutator').each(function () {
      var first_item = $(this).find('.layout--kit-commutator__item').first();
      var other_items = $(this).find('.layout--kit-commutator__item').not(first_item);
      // Conmutator is really accordions where all the sections can be
      // closed/opened at once, first one open by default.
      $(first_item).accordion(settings.first_options);
      $(other_items).accordion(settings.options);
    });
  }
})(jQuery, Drupal);
