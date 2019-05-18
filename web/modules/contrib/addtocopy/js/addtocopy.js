/**
 * @file
 * Contains the definition of the behaviour jsAddToCopy.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Attaches the JS test behavior to weight div.
   */
  Drupal.behaviors.jsAddToCopy = {
    attach: function (context, settings) {
      var text = drupalSettings.addtocopy.addtocopy.htmlcopytxt.split('[link]').join(window.location.href);
      $(drupalSettings.addtocopy.addtocopy.selector, context).addtocopy({
        htmlcopytxt: text,
        minlen: drupalSettings.addtocopy.addtocopy.minlen,
        addcopyfirst: parseInt(drupalSettings.addtocopy.addtocopy.addcopyfirst)
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
