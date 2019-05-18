(function($, BUE, Editor) {
'use strict';

/**
 * @file
 * Defines editor button management.
 */

/**
 * Editor buttons builder.
 */
BUE.buildEditorButtons = function(E) {
  E.buttons = {};
  E.bind('destroy', BUE.destroyEditorButtons);
};

/**
 * Editor buttons destroyer.
 */
BUE.destroyEditorButtons = function(E) {
  for (var i in E.buttons) {
    E.buttons[i].destroy();
  }
  delete E.buttons;
};

/**
 * Adds a button by definition/id.
 */
Editor.addButton = function(def) {
  var template, E = this;
  // Get definition by id
  if (typeof def === 'string') {
    def = BUE.getButtonDefinition(def);
  }
  if (def) {
    // Normal button
    if (def.code) {
      if (def.id && !E.getButton(def.id)) {
        E.appendButton(new BUE.Button(def));
      }
    }
    // Template button
    else if (template = def.template) {
      if (template.call) {
        try {
          template = template.call(def, E, $);
        }
        catch (e) {
          template = false;
          BUE.delayError(e);
        }
      }
      if (template) {
        $(E.toolbarEl).append(template);
      }
    }
  }
};

/**
 * Appends a Button object.
 */
Editor.appendButton = function(Button) {
  var shortcut, E = this;
  // Remove from old editor
  Button.remove();
  // Add to this editor
  E.buttons[Button.id] = Button;
  E.toolbarEl.appendChild(Button.el);
  if (shortcut = Button.shortcut) {
    E.addShortcut(shortcut, Button.el);
  }
  Button.el.bueEid = E.id;
  Button.Editor = E;
};

/**
 * Removes a button.
 */
Editor.removeButton = function(Button) {
  var shortcut, E = this, buttons = E.buttons;
  if (typeof Button === 'string') {
    Button = E.getButton(Button);
  }
  if (Button && Button.Editor === E) {
    // Remove shortcut
    if (shortcut = Button.shortcut) {
      if (E.getShortcut(shortcut) === Button.el) {
        E.removeShortcut(shortcut);
      }
    }
    // Remove buttons ref
    delete buttons[Button.id];
    if (Button === E.lastFiredButton) {
      delete E.lastFiredButton;
    }
    // Remove the editor ref
    delete Button.Editor;
    Button.el.bueEid = null;
    // Remove element
    BUE.removeEl(Button.el);
  }
};

/**
 * Returns a button by id.
 */
Editor.getButton = function(bid) {
  return this.buttons[bid];
};

/**
 * Fires a button.
 */
Editor.fireButton = function(Button) {
  var code, parts, ret, E = this;
  if (typeof Button === 'string') {
    Button = E.getButton(Button);
  }
  if (Button) {
    delete E.preventFocus;
    if (code = Button.code) {
      E.lastFiredButton = Button;
      // Insert code
      if (typeof code === 'string') {
        parts = code.split('|');
        if (parts.length == 2) {
          E.wrapSelection(parts[0], parts[1]);
        }
        else {
          E.setSelection(code, 'end');
        }
      }
      // Execute callback
      else {
        try {
          ret = code.call(Button, E, $);
        }
        catch (e) {
          BUE.delayError(e);
        }
      }
    }
    // Restore focus
    if (!E.preventFocus) {
      E.focus();
    }
  }
  return ret;
};

/**
 * Enables/disables editor buttons except one.
 */
Editor.toggleButtonsDisabled = function(state, Button) {
  var i, buttons = this.buttons;
  // Default to last fired button.
  if (Button == null) {
    Button = this.lastFiredButton;
  }
  this.toggleState('buttonsDisabled', state);
  state = this.buttonsDisabled;
  for (i in buttons) {
    if (buttons[i] !== Button) {
      buttons[i].toggleDisabled(state);
    }
  }
  // Toggle the pressed state of the given button
  if (Button) {
    Button.togglePressed(state);
  }
};

})(jQuery, BUE, BUE.Editor.prototype);