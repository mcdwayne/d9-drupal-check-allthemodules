/**
 * @file
 * Contains textareatabs/textareatabs.
 */

(function ($, Drupal) {
  Drupal.behaviors.textAreaTabs = {
    attach: function() {
      $("textarea").on("keydown", {}, this.insertTab);
    },

    /**
     * Based on TTabs from http://interface.eyecon.ro/.
     *
     * @param {Object} e Event
     * @returns {boolean}
     */
    insertTab: function(e) {
      var pressedKey = e.charCode || e.keyCode || -1;
      var oldScrollTop, start, end;

      if (pressedKey === 9) {
        if (window.event) {
          window.event.cancelBubble = true;
          window.event.returnValue = false;
        } else {
          e.preventDefault();
          e.stopPropagation();
        }

        // Save current scroll position for later restoration.
        oldScrollTop = this.scrollTop;

        if (this.createTextRange) {
          document.selection.createRange().text = "\t";
          this.onblur = function() { this.focus(); this.onblur = null; }
        } else if (this.setSelectionRange) {
          start = this.selectionStart;
          end = this.selectionEnd;
          this.value = this.value.substring(0, start) + "\t" + this.value.substr(end);
          this.setSelectionRange(start + 1, start + 1);
          this.focus();
        }

        this.scrollTop = oldScrollTop;

        return false;
      }
    }
  };
}(jQuery, Drupal));