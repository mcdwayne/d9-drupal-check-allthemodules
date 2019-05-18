/**
 * @file insert Non-Breaking Space for CKEditor
 * Copyright (C) 2016 Kevin Wenger of Antistatique
 * Create a command and enable the Ctrl+Space shortcut
 * to insert a non-breaking space in CKEditor
 * Also add a non-breaking space button
 */

/* global jQuery Drupal CKEDITOR */

(function($, Drupal, CKEDITOR) {
  "use strict";

  CKEDITOR.plugins.add("nbsp", {
    icons: "nbsp",
    hidpi: true,

    beforeInit: function(editor) {
      editor.addContentsCss(this.path + "css/ckeditor.nbsp.css");
    },
    init: function(editor) {
      // Insert &nbsp; if Ctrl+Space is pressed:
      editor.addCommand("insertNbsp", {
        exec: function(editor) {
          editor.insertHtml('<span class="nbsp">&nbsp;</span>');
        }
      });
      editor.setKeystroke(CKEDITOR.CTRL + 32 /* space */, "insertNbsp");

      // Register the toolbar button.
      if (editor.ui.addButton) {
        editor.ui.addButton("DrupalNbsp", {
          label: Drupal.t("Non-breaking space"),
          command: "insertNbsp",
          icon: this.path + "icons/nbsp.png"
        });
      }
    }
  });
})(jQuery, Drupal, CKEDITOR);
