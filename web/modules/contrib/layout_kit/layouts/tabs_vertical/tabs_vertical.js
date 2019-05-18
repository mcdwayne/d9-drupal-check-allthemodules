/**
 * @file
 * Layout Kit - Tabs: vertical JS.
 */
(function ($, Drupal) {

  /**
   * Layout Kit - Tabs: vertical Drupal JS behavior.
   */
  Drupal.behaviors.layout_kit_tabs_vertical = {
    attach: function (context, settings) {

      // Execute tabs vertical.
      tabs_vertical('.layout--kit-tabs-vertical', settings, context);
    }
  }

  /**
   * Init tabs vertical using jQuery.
   *
   * @param string tabs_vertical
   *   Tabs vertical main selector.
   * @param object settings
   *   Drupal settings.
   * @param object context
   *   Drupal context.
   */
  function tabs_vertical(tabs_vertical, settings, context) {
    $(tabs_vertical, context).once('layout-kit-tabs-vertical').each(function () {
      $(this).tabs(settings.options).tabs();
      $(this).find('li').removeClass("ui-corner-top").addClass("ui-corner-left");
    });
  }
})(jQuery, Drupal);
