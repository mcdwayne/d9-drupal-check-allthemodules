(function($, BUE, Editor) {
'use strict';

/**
 * @file
 * Defines editor textarea management.
 */

/**
 * Takes control of a textarea.
 */
Editor.controlTextarea = function(textarea) {
  var parent, E = this, existing = E.textarea;
  if (textarea && textarea !== existing) {
    // Restore the existing one
    if (existing) E.restoreTextarea();
    // Use the new one
    if (parent = textarea.parentNode) {
      parent.insertBefore(E.el, textarea);
    }
    E.textareaWrapperEl.appendChild(textarea);
    textarea.className += ' bue-textarea';
    textarea.bueEid = E.id;
    E.textarea = textarea;
    // Set events
    $(textarea).bind('focus.bue', BUE.eTextareaFocus).bind('blur.bue', BUE.eTextareaBlur).bind('keydown.bue', BUE.eTextareaKeydown).bind('keypress.bue', BUE.eTextareaKeypress);
    // Fix selection loss on blur in IE9 and earlier.
    if (('onbeforeactivate' in textarea) && !window.atob) {
      $(textarea).bind('beforeactivate.bue', BUE.eTextareaBeforeactivate).bind('beforedeactivate.bue', BUE.eTextareaBeforedeactivate);
    }
    E.trigger('controlTextarea', textarea, existing);
  }
};

/**
 * Restores the editor textarea.
 */
Editor.restoreTextarea = function() {
  var E = this, textarea = E.textarea;
  if (textarea) {
    E.trigger('restoreTextarea', textarea);
    $(textarea).unbind('.bue').removeClass('bue-textarea').insertAfter(E.el);
    textarea.bueEid = E.textarea = E.storedRange = null;
  }
};

/**
 * Returns the editor textarea.
 */
Editor.getTextarea = function() {
  return this.textarea;
};




/**
 * Focus event of editor textarea.
 */
BUE.eTextareaFocus = function(e) {
  var E = BUE.editorOf(this);
  BUE.active = E;
  E.setState('focused');
};

/**
 * Blur event of editor textarea.
 */
BUE.eTextareaBlur = function(e) {
  var E = BUE.editorOf(this);
  E.unsetState('focused');
};

/**
 * Keydown event of editor textarea.
 */
BUE.eTextareaKeydown = function(e) {
  return BUE.eFireShortcut.call(this, e);
};

/**
 * Beforeactivate event of editor textarea.
 */
BUE.eTextareaBeforeactivate = function(e) {
  // Restore selection range.
  var E = BUE.editorOf(this), range = E.storedRange;
  if (range) {
    E.storedRange = null;
    E.setRange(range);
  }
};

/**
 * Beforedeactivate event of editor textarea.
 */
BUE.eTextareaBeforedeactivate = function(e) {
  // Store selection range
  var E = BUE.editorOf(this);
  E.storedRange = E.getRange();
};

})(jQuery, BUE, BUE.Editor.prototype);