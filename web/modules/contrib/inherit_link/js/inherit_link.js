/**
 * @file
 * Inherit link plugin implementation.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.inherit_link = {
    attach: function (context, settings) {
      var config;
      if (typeof settings.inherit_link !== 'undefined') {
        for (var index in settings.inherit_link) {
          config = settings.inherit_link[index];
          inheritLink(config.element_selector, config.link_selector, config.prevent_selector, config.hide_element, config.auto_external);
        }
      }
    }
  };

  /**
   * Inherit link jquery plugin implementation.
   *
   * @param string element_selector
   *   Main element where link is located.
   * @param string link_selector
   *   Link inside main element to inherit.
   * @param string prevent_selector
   *   Prevent this element that may match with main selector.
   * @param boolean hide_element
   *   Hide inherited click element.
   * @param boolean auto_external
   *   Auto detect external links and open in new window.
   */
  function inheritLink(element_selector, link_selector, prevent_selector, hide_element, auto_external) {
    $(element_selector).once('inherit-click').each(function () {
      // This requires "InheritLink" library asset.
      if ($.fn.InheritLink) {
        $(this).InheritLink(link_selector, prevent_selector, hide_element, auto_external);
      }
    });
  }

})(jQuery, Drupal);
