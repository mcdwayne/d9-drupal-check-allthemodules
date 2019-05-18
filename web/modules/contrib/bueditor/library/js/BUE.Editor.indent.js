(function($, BUE, Editor) {
'use strict';

/**
 * @file
 * Defines editor indentation.
 */

/**
 * Editor indent builder.
 */
BUE.buildEditorIndent = function(E) {
  if (E.settings.indent) {
    E.addShortcut('TAB', BUE.editorIndent);
    E.addShortcut('Shift+TAB', BUE.editorUnindent);
    E.addShortcut('ENTER', BUE.editorAutoindent);
    E.addShortcut('Ctrl+Alt+TAB', BUE.editorToggleIndent);
  }
};

/**
 * Indent handler.
 */
BUE.editorIndent = function(E) {
  return BUE.editorIndentCommon(E, 'indent');
};

/**
 * Unindent handler.
 */
BUE.editorUnindent = function(E) {
  return BUE.editorIndentCommon(E, 'unindent');
};

/**
 * Autoindent handler.
 */
BUE.editorAutoindent = function(E) {
  return BUE.editorIndentCommon(E, 'autoindent');
};

/**
 * Common handler for indentation.
 */
BUE.editorIndentCommon = function(E, method) {
  var settings = E.settings;
  if (!settings.indent) {
    // As a shortcut handler indicate that the shortcut is disabled so the default action can be performed.
    return false;
  }
  E[method](settings.indentStr || '  ');
};

/**
 * Toggle indentation on/off
 */
BUE.editorToggleIndent = function(E) {
  var settings = E.settings;
  settings.indent = !settings.indent;
};



/**
 * Indents the selection with a string.
 */
Editor.indent = function(str) {
  var E = this, sel = E.getSelection();
  // Not multiline. Just prepend the string.
  if (!sel || sel.indexOf('\n') == -1) {
    E.wrapSelection(str, '');
  }
  // Process lines
  else {
    var range = E.getRange(), content = E.getContent(), start = range.start, end = range.end,
    lines = sel.split('\n'), lineStart = content.substr(0, start).lastIndexOf('\n') + 1, len = str.length;
    E.setContent(content.substr(0, lineStart) + str + content.substring(lineStart, start) + lines.join('\n' + str) + content.substr(end));
    E.setRange(start == lineStart ? start : start + len, end + lines.length * len);
  }
};

/**
 * Unindents the selection with a string.
 */
Editor.unindent = function(str) {
  var i, E = this, content = E.getContent(), range = E.getRange(),
  start = range.start, end = range.end,
  blockStart = content.substr(0, start).lastIndexOf('\n') + 1,
  block = content.substring(blockStart, end), newBlock,
  lines = block.split('\n'), newLines = [],
  re = new RegExp('^' + BUE.regesc(str.charAt(0)) + '{1,' + str.length + '}');
  // Unindent each line.
  for (i = 0; i < lines.length; i++) {
    newLines[i] = lines[i].replace(re, '');
  }
  // Check if the content has changed
  newBlock = newLines.join('\n');
  i = newBlock.length;
  if (i !== block.length) {
    E.setContent(content.substr(0, blockStart) + newBlock + content.substr(end));
    E.setRange(Math.max(blockStart, start + newLines[0].length - lines[0].length), blockStart + i);
  }
};

/**
 * Autoindents new line with a string.
 */
Editor.autoindent = function(str) {
  var match, content = this.getContent(), start = this.getRange().start,
  lineStart = content.substr(0, start).lastIndexOf('\n') + 1, insert = '\n';
  if (start != lineStart) {
    if (match = content.substr(lineStart).match(new RegExp('^(' + BUE.regesc(str.charAt(0)) + '+)'))) {
      insert += match[1];
    }
  }
  this.setSelection(insert, 'end');
};

})(jQuery, BUE, BUE.Editor.prototype);