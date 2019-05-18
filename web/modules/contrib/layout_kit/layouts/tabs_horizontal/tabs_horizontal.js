/**
 * @file
 * Layout Kit - Commutator JS.
 */
(function ($, Drupal) {

  /**
   * Layout Kit - Tabs: horizontal Drupal JS behavior.
   */
  Drupal.behaviors.layout_kit_tabs_horizontal = {
    attach: function (context, settings) {

      // Execute tabs horizontal.
      tabs_horizontal('.layout--kit-tabs-horizontal', settings, context);
    }
  }

  /**
   * Init tabs horizontal using jQuery.
   *
   * @param string tabs_horizontal
   *   Tabs horizontal main selector.
   * @param object settings
   *   Drupal settings.
   * @param object context
   *   Drupal context.
   */
  function tabs_horizontal(tabs_horizontal, settings, context) {
    $(tabs_horizontal, context).once('layout-kit-tabs-horizontal').each(function () {
      $(this).tabs(settings.options);
    });
  }
})(jQuery, Drupal);
