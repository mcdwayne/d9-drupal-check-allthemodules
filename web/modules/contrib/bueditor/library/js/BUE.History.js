(function($, BUE) {
'use strict';

/**
 * @file
 * Defines editor history object.
 */

/**
 * History constructor
 */
BUE.History = function(E) {
  this.construct(E);
};

/**
 * History prototype.
 */
var History = BUE.History.prototype;

/**
 * Constructs the history.
 */
History.construct = function(E) {
  var H = this;
  BUE.extend(H, BUE.getHistoryDefaults(), E.settings.history);
  H.states = [];
  H.current = -1;
  H.Editor = E;
};

/**
 * Destroys the history.
 */
History.destroy = function() {
  this.states = this.Editor = null;
};

/**
 * Saves a new state.
 */
History.save = function(bypassCheck) {
  var len, value, states, H = this, E = H.Editor;
  // Check if locked
  if (bypassCheck || !H.locked) {
    H.locked = true;
    H.delayUnlock();
    // Delete redo-states if any.
    while(H.states[H.current + 1]) {
      H.states.pop();
    }
    value = E.getContent();
    states = H.states;
    len = states.length;
    // Check if the last saved value has changed.
    if (!len || value !== states[len - 1].value) {
      // Check if limit reached
      if (len == H.limit) {
        states.shift();
        len--;
      }
      H.current = len;
      return states[len] = {
        value: value,
        scrollTop: E.getTextarea().scrollTop,
        range: E.getRange()
      };
    }
  }
};

/**
 * Goes to the previous state.
 */
History.undo = function() {
  var H = this, len = H.states.length;
  if (len) {
    // Save current state before going back from the last step.
    if (H.current == len - 1) {
      H.save(true);
    }
    return H.goto(H.current - 1);
  }
};

/**
 * Goes to the next state.
 */
History.redo = function() {
  return this.goto(this.current + 1);
};

/**
 * Goes to a state by index.
 */
History.goto = function(index) {
  var state, H = this, E = H.Editor;
  if (state = H.states[index]) {
    H.locked = true;
    E.setContent(state.value);
    E.setRange(state.range);
    E.getTextarea().scrollTop = state.scrollTop;
    H.current = index;
    H.locked = false;
  }
  return state;
};

/**
 * Schedules unlocking.
 */
History.delayUnlock = function() {
  var H = this;
  clearTimeout(H.unlockTimer);
  H.unlockTimer = setTimeout(function() {
    H.locked = false;
    H = null;
  }, H.period);
};

/**
 * Triggers state saving based on a keyup event.
 */
History.handleKeyup = function(e) {
  var key = e.keyCode, keys = this.keys;
  if (e.ctrlKey) keys = keys.ctrl;
  if (keys && keys[key]) {
    this.save();
  }
};


/**
 * Returns default settings of history.
 */
BUE.getHistoryDefaults = function(e) {
  return {
    // Maximum number of saved states
    limit: 100,
    // Min time(ms) need to wait between state savings
    period: 1000,
    // Key codes triggering state saving. (backspace, enter, space, del, comma, dot, Ctrl+V|X)
    keys: {8: 1, 13: 1, 32: 1, 46: 1, 188: 1, 190: 1, ctrl: {86: 1, 88: 1}}
  };
};


})(jQuery, BUE);