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
//      var Info = drupalSettings.jmol.mydiv.info;
      var jmol_array = drupalSettings.jmol;
      $.each(jmol_array, function (key, jmol_object) {
        var div_id = key;
        var Info = jmol_object.info;
        Jmol._document = null;
        Jmol.getTMApplet('jmol', Info);
//      $('#mydiv').once('jmol').html(jmol._code);
        $('#' + div_id).once('jmol').html(jmol._code);
      });
    }
  };
}(jQuery, Drupal, drupalSettings, Jmol));
