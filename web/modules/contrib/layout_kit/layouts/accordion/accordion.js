/**
 * @file
 * Layout Kit - Accordion JS.
 */
(function ($, Drupal) {

  /**
   * Layout Kit - Accordion Drupal JS behavior.
   */
  Drupal.behaviors.layout_kit_accordion = {
    attach: function (context, settings) {

      // Execute accordion.
      accordion('.layout--kit-accordion', settings, context);
    }
  }

  /**
   * Init accordion using jQuery.
   *
   * @param string accordion
   *   Accordion main selector.
   * @param object settings
   *   Drupal settings.
   * @param object context
   *   Drupal context.
   */
  function accordion(accordion, settings, context) {
    $(accordion, context).once('layout-kit-accordion').each(function () {
      $(this).accordion(settings.options);
    });
  }
})(jQuery, Drupal);
