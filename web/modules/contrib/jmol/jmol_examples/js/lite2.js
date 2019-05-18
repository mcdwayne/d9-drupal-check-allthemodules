/**
 * @file
 * Produces a Jmol object using the lite library.
 *
 * All Jmol objects in the current page are rendered by this one script.
 */

(function ($, Drupal, drupalSettings, Jmol) {
  'use strict';
  Drupal.behaviors.jmol = {
    attach: function () {
      var Info = drupalSettings.jmol.mydiv.info;
      Jmol._document = null;
      Jmol.getTMApplet('jmol', Info);
      $('#apphere').html(jmol._code);
      $('#spinstuff').html('<a href="javascript:jmol.spin(true)">spin ON</a> <a href="javascript:jmol.spin(false)">spin OFF</a>');
    }
  };
}(jQuery, Drupal, drupalSettings, Jmol));
