(function($, BUE) {
'use strict';

/**
 * @file
 * Defines bueditor editor object.
 */

/**
 * Editor object constructor.
 */
BUE.Editor = function(textarea, settings) {
  this.construct(textarea, settings);
};

/**
 * Extend the prototype with state, event, and shorcut manager.
 */
var Editor = BUE.extendProto(BUE.Editor.prototype, 'state', 'event', 'shortcut');

/**
 * Constructs the editor.
 */
Editor.construct = function(textarea, settings) {
  var i, E = this;
  E.id = 'bue-' + (textarea.id || ++BUE.counter);
  BUE.editors[E.id] = E;
  E.events = {};
  E.shortcuts = {};
  E.settings = settings = BUE.extend({}, settings);
  // Make textarea available to builders.
  E.textarea = textarea;
  // Run editor builders
  BUE.runEditorBuilders(E);
  // Create editor elements and buttons.
  E.createEl();
  // Take control of the textarea.
  E.textarea = null;
  E.controlTextarea(textarea);
  // Trigger ready event
  E.trigger('ready');
};

/**
 * Destroys the editor.
 */
Editor.destroy = function() {
  var i, callbacks, E = this;
  if (E.el) {
    // Trigger destroy
    E.trigger('destroy');
    // Restore textarea
    E.restoreTextarea();
    // Remove editor element.
    $(E.el).remove();
    // Remove other references
    E.el = E.toolbarEl = E.textareaWrapperEl = E.settings = E.shortcuts = E.events = null;
    if (E === BUE.active) BUE.active = null;
    delete BUE.editors[E.id];
  }
};

/**
 * Creates editor elements.
 */
Editor.createEl = function() {
  var tbEl, twEl, i, toolbar, E = this, el = E.el, settings = E.settings, cname = settings.cname;
  if (!el) {
    // Editor element
    el = E.el = document.createElement('div');
    el.id = E.id;
    el.className = 'bue' + (cname ? ' ' + cname : '');
    // Toolbar element
    tbEl = E.toolbarEl = BUE.createEl('<div class="bue-toolbar" role="toolbar"></div>');
    tbEl.onkeydown = BUE.eToolbarKeydown;
    tbEl.onmousedown = BUE.eToolbarMousedown;
    tbEl.bueEid = E.id;
    el.appendChild(tbEl);
    // Textarea wrapper.
    twEl = E.textareaWrapperEl = BUE.createEl('<div class="bue-textarea-wrapper"></div>');
    twEl.onmousedown = BUE.eTwMousedown;
    twEl.bueEid = E.id;
    el.appendChild(twEl);
    // Add buttons.
    if (toolbar = settings.toolbar) {
      for (i = 0; i < toolbar.length; i++) {
        E.addButton(toolbar[i]);
      }
    }
    // Allow focus on the first button
    for (i in E.buttons) {
      E.buttons[i].el.tabIndex = 0;
      break;
    }
  }
  return el;
};




/**
 * Focuses on editor.
 */
Editor.focus = function() {
  BUE.focusEl(this.textarea);
};

/**
 * Sets editor content.
 */
Editor.setContent = function(str) {
  this.history.save();
  BUE.setTextareaValue(this.textarea, str);
};

/**
 * Returns editor content.
 */
Editor.getContent = function() {
  return BUE.getTextareaValue(this.textarea);
};

/**
 * Appends text to the editor content.
 */
Editor.addContent = function(str, joinStr) {
  var content = this.getContent();
  if (content && joinStr) content += joinStr;
  return this.setContent(content + str);
};

/**
 * Returns current selection range.
 */
Editor.getRange = function() {
  var stored = this.storedRange;
  return stored ? BUE.extend({}, stored) : BUE.getSelectionRange(this.textarea);
};

/**
 * Sets selection range.
 * Optionally collapses and/or shifts the selection.
 */
Editor.setRange = function(start, end, collapse, shift) {
  // Allow passing a range object
  if (typeof start === 'object') {
    end = start.end;
    start = start.start;
  }
  // Check collapse
  if (end == null || end < start) {
    end = start;
  }
  else if (collapse) {
    if (collapse === 'start') {
      end = start;
    }
    else {
      start = end;
    }
  }
  if (shift) {
    start += shift;
    end += shift;
  }
  BUE.setSelectionRange(this.textarea, start, end);
  // Update stored selection range
  if (this.storedRange) {
    this.storedRange = {start: start, end: end};
  }
};

/**
 * Returns the selected text.
 */
Editor.getSelection = function() {
  var range = this.getRange();
  return this.getContent().substring(range.start, range.end);
};

/**
 * Sets the selected text.
 */
Editor.setSelection = function(str, collapse) {
  var content = this.getContent(), range = this.getRange(), start = range.start;
  str = BUE.text(str);
  this.setContent(content.substr(0, start) + str + content.substr(range.end));
  return this.setRange(start, start + str.length, collapse);
};

/**
 * Wraps the selection.
 */
Editor.wrapSelection = function(str1, str2, collapse) {
  var content = this.getContent(), range = this.getRange(), start = range.start, end = range.end;
  str1 = BUE.text(str1);
  str2 = BUE.text(str2);
  this.setContent(content.substr(0, start) + str1 + content.substring(start, end) + str2 + content.substr(end));
  return this.setRange(start, end, collapse, str1.length);
};

/**
 * Wraps or restores selected lines.
 */
Editor.wrapLines = function(outerPrefix, prefix, suffix, outerSuffix) {
  var str = this.getSelection().replace(/\r\n|\r/g, '\n'), Esc = BUE.regesc, newstr, re, matches;
  // Empty selection
  if (!str) {
    return this.wrapSelection(outerPrefix + prefix, suffix + outerSuffix);
  }
  // Wrap or restore
  re = new RegExp('^' + Esc(outerPrefix + prefix) + '([\\s\\S]*)' + Esc(suffix + outerSuffix) + '$');
  if (matches = str.match(re)) {
    newstr = matches[1].replace(new RegExp(Esc(suffix) + '\n' + Esc(prefix), 'g'), '\n');
  }
  else {
    newstr = outerPrefix + prefix + str.replace(/\n/g, suffix + '\n' + prefix) + suffix + outerSuffix;
  }
  return this.setSelection(newstr);
};

/**
 * Wraps selected lines with html tags.
 */
Editor.tagLines = function(tag, outerTag, indent) {
  var prefix = (indent === undefined ? '  ' : indent) + '<' + tag + '>', suffix = '</' + tag + '>',
  outerPrefix = outerTag ? '<' + outerTag + '>\n' : '', outerSuffix = outerTag ? '\n</' + outerTag + '>' : '';
  return this.wrapLines(outerPrefix, prefix, suffix, outerSuffix);
};

/**
 * Toggles a html tag in the selection.
 */
Editor.toggleTag = function(tag, attributes, collapse) {
  return this.insertHtmlObj({tag: tag, html: this.getSelection(), attributes: attributes}, collapse, true);
};

/**
 * Inserts an html object extending the current selection.
 */
Editor.insertHtmlObj = function(htmlObj, collapse, toggle) {
  var wrap, sel = this.getSelection(), tag = htmlObj.tag,
  selObj = sel && BUE.parseHtml(sel),
  sameTag = selObj && selObj.tag === tag;
  // Html object and the selection are of the same type
  if (sameTag) {
    // Remove the outer html and exit if this is a toggle
    if (toggle) {
      return this.setSelection(selObj.html, collapse);
    }
    // Create a new html object by extending the current selection.
    htmlObj = {
      tag: tag,
      html: htmlObj.html == null || htmlObj.html === sel ? selObj.html : htmlObj.html,
      attributes: BUE.extend(selObj.attributes, htmlObj.attributes)
    };
  }
  // Consider it as a wrap if no inner html
  else if (!BUE.selfClosing(tag) && !htmlObj.html) {
    wrap = BUE.html(tag, '', htmlObj.attributes);
    return this.wrapSelection(wrap.substr(0, wrap.length - tag.length - 3), '</'+ tag +'>', collapse);
  }
  // Insert html
  return this.setSelection(BUE.html(htmlObj), collapse);
};




/**
 * Returns browse button for an input field.
 */
Editor.browseButton = function(inputName, browseType, buttonLabel) {
  var settings = this.settings, browserName = settings[browseType + 'Browser'] || settings.fileBrowser;
  if (browserName && BUE.fileBrowsers[browserName]) {
    return BUE.browseButton(browserName, inputName, browseType, buttonLabel);
  }
};




/**
 * Keydown event of editor toolbar
 */
BUE.eToolbarKeydown = function(event) {
  var i, $buttons, len, e = $.event.fix(event || window.event), key = e.keyCode, modifier = e.ctrlKey || e.shiftKey || e.altKey;
  // Navigate buttons using the left/right arrow keys
  if (!modifier && (key === 37 || key === 39)) {
    $buttons = $('.bue-button', this).filter(':visible');
    if (len = $buttons.length) {
      i = $buttons.index(document.activeElement);
      $buttons.eq((len + i + key - 38) % len).focus();
    }
    return false;
  }
  // Fire editor shortcut if available.
  // Suppress keys with special functionality on focused button.(TAB, ENTER, SPACE)
  if (key !== 9 && key !== 13 && key !== 32) {
    return BUE.eFireShortcut.call(this, e);
  }
};

/**
 * Mousedown event of editor toolbar
 */
BUE.eToolbarMousedown = function(event) {
  // Redirect the event to the textarea.
  if (this === BUE.eTarget(event)) {
    BUE.editorOf(this).focus();
  }
  // Prevent default(focus)
  return false;
};

/**
 * Mousedown event of textarea wrapper.
 */
BUE.eTwMousedown = function(event) {
  // Redirect the event to the textarea.
  if (this === BUE.eTarget(event)) {
    BUE.editorOf(this).focus();
    // Prevent default only if it is a direct mousedown. Never prevent textarea mousedown.
    return false;
  }
};

/**
 * Event helper for editor shortcut firing.
 */
BUE.eFireShortcut = function(e) {
  var E, shortcut = BUE.eBuildShortcut(e);
  if (shortcut) {
    if (E = BUE.editorOf(this)) {
      // Prevent default if shortcut is executed.
      if (E.fireShortcut(shortcut)) {
        return false;
      }
    }
  }
};

/**
 * Event helper for target finding.
 */
BUE.eTarget = function(event) {
  if (!event) event = window.event;
  return event.target || event.srcElement;
};

/**
 * Retrieves the editor object from an element.
 */
BUE.editorOf = function(el) {
  return el.bueEid ? BUE.getEditor(el.bueEid) : false;
};

/**
 * Runs editor builders.
 */
BUE.runEditorBuilders = function(E) {
  // Run core builders
  BUE.buildEditorPopups(E);
  BUE.buildEditorButtons(E);
  BUE.buildEditorPreview(E);
  BUE.buildEditorHistory(E);
  BUE.buildEditorAc(E);
  BUE.buildEditorIndent(E);
  // Run custom builders
  for (var i in BUE.builders) {
    BUE.builders[i](E);
  }
};


/**
 * Backward compatibility.
 */
Editor.posSelection = Editor.getRange;
Editor.makeSelection = Editor.setRange;
Editor.replaceSelection = Editor.setSelection;
Editor.tagSelection = Editor.wrapSelection;

})(jQuery, BUE);