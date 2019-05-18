(function($, window, document, undefined) {
'use strict';

/**
 * @file
 * Defines bueditor container, jquery plugin, and common methods.
 */

/**
 * BUE container.
 */
var BUE = window.BUE = window.BUE || {
  editors: {},
  popups: {},
  buttonDefinitions: {},
  buttonRegistry: {},
  fileBrowsers: {},
  builders: {},
  protos: {},
  i18n: {},
  counter: 0
};

/**
 * jQuery plugin for BUE.
 */
$.fn.BUE = function(settings) {
  var i, el, callback;
  // Get
  if (settings === 'get') {
    return (el = this[0]) ? BUE.editorOf(el) : false;
  }
  // Attach/detach
  callback = settings === 'detach' ? BUE.detach : BUE.attach;
  for (i = 0; el = this[i]; i++) {
    callback(el, settings);
  }
  return this;
};

/**
 * Attaches a new editor to a textarea.
 */
BUE.attach = function(textarea, settings) {
  var E = BUE.editorOf(textarea);
  if (!E && settings) {
    // Allow custom button definitions in settings
    if (settings.customButtons) {
      // Define registered buttons first so custom ones can override.
      BUE.defineRegisteredButtons();
      BUE.addButtonDefinitions(settings.customButtons);
      settings.customButtons = null;
    }
    E = new BUE.Editor(textarea, settings);
  }
  return E;
};

/**
 * Detaches an editor from a textarea.
 */
BUE.detach = function(textarea) {
  var E = BUE.editorOf(textarea);
  if (E) {
    E.destroy();
  }
  return E;
};

/**
 * Returns an editor by id.
 */
BUE.getEditor = function(id) {
  return BUE.editors[id];
};

/**
 * Returns a popup by id.
 */
BUE.getPopup = function(id) {
  return BUE.popups[id];
};

/**
 * Returns a button definition by id.
 */
BUE.getButtonDefinition = function(id) {
  if (!BUE.definedRB) {
    BUE.defineRegisteredButtons();
  }
  return BUE.buttonDefinitions[id];
};

/**
 * Adds a new button definition.
 */
BUE.addButtonDefinition = function(def) {
  if (def.id) {
    // Check js: prefix in code and template
    var prop = def.code ? 'code' : (def.template ? 'template' : false);
    if (prop && typeof def[prop] === 'string' && def[prop].substr(0, 3) === 'js:') {
      def[prop] = new Function('E', '$', def[prop].substr(3));
    }
    return BUE.buttonDefinitions[def.id] = def;
  }
};

/**
 * Adds multiple button definitions.
 */
BUE.addButtonDefinitions = function(defs) {
  if (defs) {
    for (var i in defs) {
      BUE.addButtonDefinition(defs[i]);
    }
  }
};

/**
 * Registers a button definition callback.
 */
BUE.registerButtons = function(key, callback) {
  BUE.buttonRegistry[key] = callback;
  if (BUE.definedRB) {
    BUE.addButtonDefinitions(callback());
  }
};

/**
 * Defines all registered buttons.
 */
BUE.defineRegisteredButtons = function() {
  if (!BUE.definedRB) {
    var i, callbacks = BUE.buttonRegistry;
    BUE.definedRB = true;
    for (i in callbacks) {
      BUE.addButtonDefinitions(callbacks[i]());
    }
  }
};

/**
 * Translates a string.
 */
BUE.t = function(str, tokens) {
  var token, value, handler, handlers = BUE.i18nHandlers;
  str = BUE.i18n[str] || str || '';
  if (tokens) {
    if (!handlers) {
      handlers = BUE.i18nHandlers = {'@': BUE.plain, '%': BUE.emplain};
    }
    for (token in tokens) {
      value = tokens[token];
      if (handler = handlers[token.charAt(0)]) {
        value = handler(value);
      }
      str = str.replace(token, value);
    }
  }
  return str;
};

/**
 * Extends an object with others.
 */
BUE.extend = function(obj) {
  var i, j, arg, args = arguments;
  if (!obj) obj = {};
  for (i = 1; i < args.length; i++) {
    if (arg = args[i]) {
      for (j in arg) {
        obj[j] = arg[j];
      }
    }
  }
  return obj;
};

/**
 * Escapes regular expression characters.
 */
BUE.regesc = function (str) {
  return str.replace(/([\\\^\$\*\+\?\.\(\)\[\]\{\}\|\:\-])/g, '\\$1');
};

/**
 * Throws an error after a minimum delay.
 */
BUE.delayError = function (err) {
  setTimeout(function() {
    throw err;
    err = null;
  });
};

/**
 * Focuses on an element by suppressing possible errors.
 */
BUE.focusEl = function(el) {
  try {
    el.focus();
  }
  catch(e) {};
};


})(jQuery, window, document);