(function($, BUE, Editor) {
'use strict';

/**
 * @file
 * Defines editor autocomplete.
 */

/**
 * Editor autocomplete builder.
 */
BUE.buildEditorAc = function(E) {
  E.ac = {};
  // Set destroyer
  E.bind('destroy', BUE.destroyEditorAc);
  // Set textarea controller
  E.bind('controlTextarea', BUE.acControlTextarea);
  // Define tag autocompletion
  if (E.settings.acTags) {
    E.addAc('>', BUE.acHtmlTags);
  }
};

/**
 * Editor autocomplete destroyer.
 */
BUE.destroyEditorAc = function(E) {
  delete E.ac;
};

/**
 * Textarea controller of editor ac.
 */
BUE.acControlTextarea = function(E, textarea) {
  $(textarea).bind('keypress.bue', BUE.eAcTextareaKeypress);
};

/**
 * Keypress event of ac textarea.
 */
BUE.eAcTextareaKeypress = function(e) {
  // Make sure character insertion is not prevented.
  if (!e.isDefaultPrevented()) {
    var E = BUE.editorOf(this), chr = String.fromCharCode(e.which);
    // Allow autocomplete handler prevent the default by returning false.
    return E.fireAc(chr) !== false;
  }
};

/**
 * Autocomplete handler for html tags.
 */
BUE.acHtmlTags = function(E) {
  var match, cursor = E.getRange().start, content = E.getContent().substr(0, cursor), i = content.lastIndexOf('<');
  if (i != -1 && content.charAt(cursor - 1) !== '/') {
    if (match = content.substr(i + 1).match(/^([a-z][a-z0-9]*)(?:\s[^>]*)?$/)) {
      if (!BUE.selfClosing(match[1])) {
        return '</'+ match[1] + '>';
      }
    }
  }
};



/**
 * Adds an autocomplete handler.
 */
Editor.addAc = function(str, action) {
  this.ac[str] = action;
};

/**
 * Returns an autocomplete handler.
 */
Editor.getAc = function(str) {
  return this.ac[str];
};

/**
 * Removes an autocomplete handler.
 */
Editor.removeAc = function(str) {
  delete this.ac[str];
};

/**
 * Fires an autocomplete handler.
 */
Editor.fireAc = function(str) {
  var handler = this.getAc(str);
  if (handler) {
    if (handler.call) {
      handler = handler.call(handler, this, str);
    }
    if (typeof handler === 'string') {
      this.setSelection(handler, 'start');
    }
  }
  return handler;
};

})(jQuery, BUE, BUE.Editor.prototype);