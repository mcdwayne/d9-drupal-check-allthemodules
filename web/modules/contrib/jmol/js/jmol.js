/**
 * @file
 * Produces a Jmol object using the full-featured library.
 *
 * All Jmol objects in the current page are rendered by this one script.
 */

(function ($, Drupal, drupalSettings, Jmol) {
  'use strict';
  Drupal.behaviors.jmol = {
    attach: function () {
      var jmol_array = drupalSettings.jmol;
      $.each(jmol_array, function (key, jmol_object) {
        var div_id = key;
        var Info = jmol_object.info;
        $('#' + div_id).once('jmol').html(Jmol.getAppletHtml('jmolApplet0', Info));
//        $('#' + div_id).html(Jmol.getAppletHtml('jmolApplet0', Info));
      });
    }
  };
}(jQuery, Drupal, drupalSettings, Jmol));
