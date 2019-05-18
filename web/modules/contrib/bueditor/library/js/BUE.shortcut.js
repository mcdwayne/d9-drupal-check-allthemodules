(function($, BUE) {
'use strict';

/**
 * @file
 * Defines shortcut manager.
 */

/**
 * Prototype extender: Shortcut manager
 */
BUE.protos.shortcut = {

  /**
   * Adds a shortcut handler.
   */
  addShortcut: function(shortcut, handler) {
    this.shortcuts[shortcut.toUpperCase()] = handler;
  },
  
  /**
   * Returns a shortcut handler.
   */
  getShortcut: function(shortcut) {
    return this.shortcuts[shortcut.toUpperCase()];
  },
  
  /**
   * Removes a shortcut handler.
   */
  removeShortcut: function(shortcut) {
    delete this.shortcuts[shortcut.toUpperCase()];
  },
  
  /**
   * Executes a shortcut handler.
   * Returns true if shortcut exists and is executed successfully.
   */
  fireShortcut: function(shortcut) {
    var handler = this.getShortcut(shortcut);
    if (handler) {
      // DOM element
      if (handler.click) {
        handler.click();
        return true;
      }
      // Callback
      if (handler.call) {
        // Shortcuts returning false are considered disabled.
        return handler.call(handler, this) !== false;
      }
    }
  }
};


/**
 * Builds a shortcut string from an event.
 */
BUE.eBuildShortcut = function(e) {
  var symbol, key = e.keyCode, shortcut = '';
  if (key && (symbol = BUE.getKeySymbols(key))) {
    if (e.ctrlKey) shortcut += 'CTRL+';
    if (e.altKey) shortcut += 'ALT+';
    if (e.shiftKey) shortcut += 'SHIFT+';
    shortcut += symbol;
  }
  return shortcut;
};

/**
 * Returns key symbols allowed in shortcuts.
 */
BUE.getKeySymbols = function(key) {
  var i, symbols = BUE.keySymbols;
  if (!symbols) {
    // Custom keys
    symbols = BUE.keySymbols = {
      8: 'BACKSPACE',
      9: 'TAB',
      13: 'ENTER',
      27: 'ESC',
      32: 'SPACE',
      37: 'LEFT',
      38: 'UP',
      39: 'RIGHT',
      40: 'DOWN'
    };
    // Add numbers
    for (i = 0; i < 10; i++) {
      symbols[48 + i] = '' + i;
    }
    // Add letters
    for (i = 65; i < 91; i++) {
      symbols[i] = String.fromCharCode(i);
    }
    // Add function keys
    for (i = 1; i < 13; i++) {
      symbols[111 + i] = 'F' + i;
    }
  }
  return (0 in arguments) ? symbols[key] : symbols;
};

})(jQuery, BUE);