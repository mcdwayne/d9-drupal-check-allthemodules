/**
 * @file
 * MMenu behaviors.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * MMenu triggers.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.mmenu = {
    attach: function (context, drupalSettings) {

      $(context).find("nav.mmenu-nav").mmenu({
        // options
        navbar : {
          title: drupalSettings.mmenu_simple.mmenu.options__navbar__title,
        }
      }, {
        // configuration
        offCanvas: {
          pageSelector: drupalSettings.mmenu_simple.mmenu.pageSelector
        }
      });

      var API = $("nav.mmenu-nav").data("mmenu");

      $("span.mmenu-trigger__text, span.mmenu-trigger__icon").click(function() {
        API.open();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
