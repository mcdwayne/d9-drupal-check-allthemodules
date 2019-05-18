(function($, BUE) {
'use strict';

/**
 * @file
 * Defines bueditor button object.
 */

/**
 * Button constructor
 */
BUE.Button = function(def) {
  this.construct(def);
};

/**
 *  Extend the prototype with state manager.
 */
var Button = BUE.extendProto(BUE.Button.prototype, 'state');

/**
 * Constructs the button.
 */
Button.construct = function(def) {
  if (def) {
    BUE.extend(this, def);
    this.createEl();
  }
};

/**
 * Destroy the button.
 */
Button.destroy = function() {
  this.remove();
  $(this.el).remove();
  delete this.el;
};

/**
 * Creates the button element.
 */
Button.createEl = function() {
  var el = this.el;
  if (!el) {
    el = this.el = BUE.createEl(BUE.buttonHtml(this));
    el.onclick = BUE.eButtonClick;
    el.onmousedown = BUE.eButtonMousedown;
    el.bueBid = this.id;
  }
  return el;
};

/**
 * Toggles disabed state.
 */
Button.toggleDisabled = function(state) {
  this.toggleState('disabled', state);
};

/**
 * Toggles pressed state.
 */
Button.togglePressed = function(state) {
  this.toggleState('pressed', state);
};

/**
 * Removes button from its editor.
 */
Button.remove = function() {
  var E = this.Editor;
  if (E) E.removeButton(this);
};

/**
 * Fires the button action.
 */
Button.fire = function() {
  var E = this.Editor;
  if (E) E.fireButton(this);
};







/**
 * Click event of a button
 */
BUE.eButtonClick = function(event) {
  var Button = BUE.buttonOf(this);
  if (!Button.disabled) {
    Button.fire();
  }
  return false;
};

/**
 * Mousedown event of a button.
 */
BUE.eButtonMousedown = function() {
  var Button = BUE.buttonOf(this);
  // Set active state.
  Button.setState('active');
  // Unset active state on the next mouseup event.
  $(document).one('mouseup', function() {
    Button.unsetState('active');
    Button = null;
  });
  // Prevent default to keep the focus on editor textarea
  return false;
};






/**
 * Returns html of a button definition.
 */
BUE.buttonHtml = function(def) {
  var html, cache = BUE.buttonHtmlCache;
  if (!cache) cache = BUE.buttonHtmlCache = {};
  html = cache[def.id];
  return html != null ? html : (cache[def.id] = BUE.html(BUE.buttonHtmlObj(def)));
};

/**
 * Returns html object of a button definition.
 */
BUE.buttonHtmlObj = function(def) {
  var label = def.label || '', text = def.text || '', cname = def.cname, shortcut = def.shortcut,
  attr = {
    type: 'button',
    tabindex: '-1',
    'class': 'bue-button bue-button--' + def.id,
    title: def.tooltip || label,
    'aria-label': label
  };
  if (cname) {
    if (cname.indexOf('ficon-') != -1) {
      attr['class'] += ' has-ficon';
    }
    attr['class'] += ' ' + cname;
  }
  if (text) {
    attr['class'] += ' has-text';
  }
  if (shortcut) {
    attr.title += ' (' + shortcut + ')';
  }
  return {tag: 'button', attributes: attr, html: text};
};

/**
 * Retrieves the button object from a button element.
 */
BUE.buttonOf = function(el) {
  var E = BUE.editorOf(el);
  return E ? E.buttons[el.bueBid] : false;
};

})(jQuery, BUE);