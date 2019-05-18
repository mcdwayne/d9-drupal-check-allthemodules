(function($, BUE) {
'use strict';

/**
 * @file
 * Defines cross browser selection handlers.
 */

/**
 * Sets selection range of a textarea.
 */
BUE.setSelectionRange = function(textarea, start, end) {
  // Standard
  if (textarea.setSelectionRange) {
    textarea.setSelectionRange(start, end);
  }
  // IE 8 and lower
  else if (textarea.createTextRange) {
    var range = textarea.createTextRange();
    range.collapse();
    range.moveEnd('character', end);
    range.moveStart('character', start);
    range.select();
  }
};

/**
 * Returns selection range of a textarea.
 */
BUE.getSelectionRange = function(textarea) {
  var start = textarea.selectionStart, end = textarea.selectionEnd;
  // Non-standard
  if (typeof start !== 'number') {
    start = end = 0;
    // IE 8 and lower
    var range1, range2, docSel = document.selection;
    if (docSel && docSel.createRange) {
      try {
        textarea.focus();
        range1 = docSel.createRange();
        range2 = range1.duplicate();
        // Move new range to 0:length of textarea
        range2.moveToElementText(textarea);
        // Move from 0 to start
        for (; range2.compareEndPoints('StartToStart', range1) < 0; start++) {
          range2.moveStart('character', 1);
        }
        // Move from start to end
        for (end = start; range2.compareEndPoints('StartToEnd', range1) < 0; end++) {
          range2.moveStart('character', 1);
        }
      } catch(e) {}
    }
  }
  return {start: start, end: end};
};

/**
 * Returns textarea value which is processed to resolve inconsistency between
 * substring position and selection position in some browsers(IE).
 */
BUE.getTextareaValue = function(textarea) {
  return BUE.text(textarea.value);
};

/**
 * Sets textarea value by keeping the scroll intact.
 */
BUE.setTextareaValue = function(textarea, value) {
  var scrollTop = textarea.scrollTop;
  textarea.value = value;
  textarea.scrollTop = scrollTop;
  // Trigger textarea change but not too frequently.
  clearTimeout(textarea.bueChangeTimeout);
  textarea.bueChangeTimeout = setTimeout(function() {
    $(textarea).change();
    textarea = null;
  }, 250);
};

/**
 * Check IE's document selection mode.
 */
BUE.ieMode = !!(!document.createElement('textarea').setSelectionRange && document.selection && document.selection.createRange);

/**
 * Standardize the given text to be used in editor.
 * In old IE there is an inconsistency between selection position and substring position
 * because newline (\r\n) is treated as a single character in selection operations.
 * We convert \r\n to \n to make string operations consistent with selection operations.
 */
BUE.text = BUE.ieMode ? function(str) {
  return ('' + str).replace(/\r\n/g, '\n');
} : function(str) {
  return '' + str;
};

})(jQuery, BUE);