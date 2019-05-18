(function($, BUE, Editor) {
'use strict';

/**
 * @file
 * Defines editor popup management.
 */

/**
 * Editor popups builder.
 */
BUE.buildEditorPopups = function(E) {
  E.popups = {};
  E.bind('destroy', BUE.destroyEditorPopups);
};

/**
 * Editor popups destroyer.
 */
BUE.destroyEditorPopups = function(E) {
  for (var i in E.popups) {
    E.popups[i].destroy();
  }
  delete E.popups;
};

/**
 * Creates an editor popup.
 */
Editor.createPopup = function(name, title, content, opt) {
  var Popup = this.popups[name];
  if (!Popup) {
    opt = BUE.extend({name: name, Editor: this}, opt);
    Popup = this.popups[name] = new BUE.Popup(title, content, opt);
  }
  return Popup;
};

/**
 * Returns an editor popup.
 */
Editor.getPopup = function(name) {
  return this.popups[name];
};

/**
 * Removes an editor popup.
 */
Editor.removePopup = function(Popup) {
  if (typeof Popup === 'string') {
    Popup = this.getPopup(Popup);
  }
  if (Popup && Popup.Editor === this) {
    Popup.close();
    delete this.popups[Popup.name];
    delete Popup.Editor;
    BUE.removeEl(Popup.el);
  }
};

/**
 * Creates an editor dialog.
 */
Editor.createDialog = function(name, title, content, opt) {
  opt = BUE.extend({type: 'dialog'}, opt);
  return this.createPopup(name, title, content, opt);
};

/**
 * Creates and opens a tag dialog.
 */
Editor.tagDialog = function(tag, fields, opt) {
  if (!opt || !opt.ignoreSelection) {
    this.populateTagFields(tag, fields);
  }
  return this.createTagDialog(tag, fields, opt).open();
};

/**
 * Creates a tag dialog.
 */
Editor.createTagDialog = function(tag, fields, opt) {
  // Allow opt to be the title.
  opt = typeof opt === 'string' ? {title: opt} : opt || {};
  // Prepare dialog name. Allow a custom name(for multiple dialogs of the same tag)
  var name = opt.name || tag + '-tag-dialog';
  var Popup = this.getPopup(name) || this.createDialog(name, null, null, {tag: tag});
  Popup.setTitle(opt.title || BUE.t('Tag editor - @tag', {'@tag': tag.toUpperCase()}));
  Popup.setContent(BUE.createTagForm(tag, fields, opt));
  return Popup;
};

/**
 * Returns a tag dialog.
 */
Editor.getTagDialog = function(tag) {
  return this.getPopup(tag + '-tag-dialog');
};

/**
 * Creates and opens the tag chooser popup.
 */
Editor.tagChooser = function(tagData, opt) {
  var name = 'tag-chooser', Popup = this.getPopup(name);
  if (!Popup) {
    Popup = this.createPopup(name, null, null, {type: 'quick'});
  }
  Popup.setContent(BUE.createTagChooserEl(tagData, opt));
  return Popup.open();
};

/**
 * Returns the initial position of a popup with respect to editor UI.
 */
Editor.defaultPopupPosition = function(Popup) {
  var left, top, buttonPos, buttonEl, popupWidth, diff, Button = this.lastFiredButton;
  if (!Button) {
    return $(this.textarea).offset();
  }
  buttonEl = Button.el;
  buttonPos = $(buttonEl).offset();
  popupWidth = Popup.el.offsetWidth || 50;
  left = buttonPos.left - popupWidth/2 + buttonEl.offsetWidth/2;
  top = buttonPos.top + buttonEl.offsetHeight;
  // Check right boundary
  diff = (left + popupWidth) - (window.innerWidth || document.documentElement.clientWidth);
  if (diff > 0) left -= diff;
  return {left: Math.max(10, left), top: top};
};

/**
 * Populates tag fields by the current selection.
 */
Editor.populateTagFields = function(tag, fields) {
  BUE.populateTagFieldsBySelection(tag, fields, this.getSelection());
};

/**
 * Populates tag fields by the given selection.
 */
BUE.populateTagFieldsBySelection = function(tag, fields, selection) {
  if (selection) {
    var values = {html: selection};
    var htmlObj = BUE.parseHtml(selection, tag);
    if (htmlObj) {
      values = htmlObj.attributes;
      values.html = htmlObj.html || '';
    }
    for (var field, i = 0; field = fields[i]; i++) {
      if (values[field.name] != null) {
        field.value = values[field.name];
      }
    }
  }
};

})(jQuery, BUE, BUE.Editor.prototype);