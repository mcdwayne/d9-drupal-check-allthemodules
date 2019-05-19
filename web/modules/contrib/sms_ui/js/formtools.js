/**
 * Extend jQuery to add function to insert some text at current caret position.
 */

/* jQuery closure */
(function($, Drupal, drupalSettings) {

  "use strict";

  $.fn.extend({
    insertAtCaret: function (text) {
      this.each(function () {
        if (this.selectionStart || this.selectionStart == 0) {
          var sP = this.selectionStart;
          var eP = this.selectionEnd;

          this.focus();
          this.value = this.value.substring(0, sP) + text + this.value.substring(eP, this.value.length);
          this.selectionStart = this.selectionEnd = sP + text.length;
        }
        else if (document.selection && this.selection) {  // Selections were saved
          this.focus();
          this.selection.text = text;
          this.selection.select();
        }
        else if (document.selection) {  // No selections saved, fall back to default behaviour
          this.focus();
          var rge = document.selection.createRange();
          rge.text = text;
          rge.select();
        }
        else {
          // Last gasp fallback option, append at the end. (Need to review this)
          this.value += text;
          this.focus();
        }
      });
    },

    insertAtCaret2: function (text) {
      this.each(function () {
        if (document.selection) {
          this.focus();
          document.selection.createRange().text = text;
        }
        else if (this.selectionStart || this.selectionStart == 0) {
          var sP = this.selectionStart;
          var eP = this.selectionEnd;
          var sT = this.scrollTop;

          this.value = this.value.substring(0, sP) + text + this.value.substring(eP, this.value.length);
          this.focus();
          this.selectionStart = this.selectionEnd = sP + text.length;
          this.scrollTop = sT;
        }
        else {
          // Poor fallback option, need to review this
          this.value += text;
          this.focus();
        }
      });
    }
  });

})(jQuery, Drupal, drupalSettings);
