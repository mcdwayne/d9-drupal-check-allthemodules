/**
 * @file
 * Provides JavaScript additions to entity embed dialog.
 *
 * This file provides popup windows for previewing embedded entities from the
 * embed dialog.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.bibciteFootnotesReplaceCitations = {
    attach: function attach(context, settings) {
      /*
      if (CKEDITOR.instances) {
        for (var instance in CKEDITOR.instances) {
          var editor = CKEDITOR.instances[instance];
          var config = editor.config;
          var name = editor.name;
          editor.destroy();
          CKEDITOR.replace(name, config);
        }
      }
      */
    }
  }
})(jQuery, Drupal);
