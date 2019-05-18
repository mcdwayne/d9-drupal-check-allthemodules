(function($, BUE, Editor) {
'use strict';

/**
 * @file
 * Defines editor undo/redo history.
 */

/**
 * Editor history builder.
 */
BUE.buildEditorHistory = function(E) {
  // Create history
  E.history = new BUE.History(E);
  // Set destroyer
  E.bind('destroy', BUE.destroyEditorHistory);
  // Add shortcuts
  E.addShortcut('Ctrl+Z', BUE.editorUndo);
  E.addShortcut('Ctrl+Y', BUE.editorRedo);
  E.addShortcut('Ctrl+Shift+Z', BUE.editorRedo);
  // Set textarea controller
  E.bind('controlTextarea', BUE.historyControlTextarea);
};

/**
 * Editor history destroyer.
 */
BUE.destroyEditorHistory = function(E) {
  E.history.destroy();
  delete E.history;
};

/**
 * Textarea controller of editor history.
 */
BUE.historyControlTextarea = function(E, textarea) {
  $(textarea).bind('keyup.bue', BUE.eHistoryTextareaKeyup).one('focus.bue', BUE.eHistoryTextareaFocus);
};

/**
 * Keyup event of history textarea.
 */
BUE.eHistoryTextareaKeyup = function(e) {
  BUE.editorOf(this).history.handleKeyup(e);
};

/**
 * Focus event of history textarea.
 */
BUE.eHistoryTextareaFocus = function(e) {
  var H = BUE.editorOf(this).history;
  // Save history for the first time.
  if (H.current == -1) {
    H.save();
  }
};

/**
 * Shortcut handler for editor undo.
 */
BUE.editorUndo = function(E) {
  E.undo();
};

/**
 * Shortcut handler for editor redo.
 */
BUE.editorRedo = function(E) {
  E.redo();
};

/**
 * Undo last action.
 */
Editor.undo = function() {
  return this.history.undo();
};

/**
 * Redo last action.
 */
Editor.redo = function() {
  return this.history.redo();
};

})(jQuery, BUE, BUE.Editor.prototype);