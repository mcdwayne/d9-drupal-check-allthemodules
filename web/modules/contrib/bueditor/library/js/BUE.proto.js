(function($, BUE) {
'use strict';

/**
 * @file
 * Defines prototype extenders.
 */

/**
 * Prototype extender: Event manager
 * Defines event management methods.
 */
BUE.protos.event = {

  /**
   * Binds an handler by type.
   */
  bind: function(type, handler) {
    var events = this.events, handlers = events[type];
    if (!handlers) handlers = events[type] = {};
    handlers[''+handler] = handler;
  },
  
  /**
   * Unbinds an handler by type.
   */
  unbind: function(type, handler) {
    var events = this.events, handlers = events[type];
    if (handlers) {
      if (handler) {
        delete handlers[''+handler];
      }
      else {
        delete events[type];
      }
    }
  },
  
  /**
   * Triggers handlers by type.
   */
  trigger: function(type) {
    var i, handler, args, handlers = this.events[type];
    if (handlers) {
      args = Array.prototype.slice.call(arguments, 1);
      args.unshift(this);
      for (i in handlers) {
        if (handler = handlers[i]) {
          if (handler.apply) {
            handler.apply(this, args);
          }
        }
      }
    }
  }
};

/**
 * Prototype extender: State manager
 * Defines state management methods.
 */
BUE.protos.state = {

  /**
   * Sets a state by name.
   */
  setState: function(name) {
    if (!this[name]) {
      this[name] = true;
      $(this.el).addClass(name);
    }
  },

  /**
   * Unsets a state by name.
   */
  unsetState: function(name) {
    if (this[name]) {
      this[name] = false;
      $(this.el).removeClass(name);
    }
  },

  /**
   * Toggles a state by name.
   */
  toggleState: function(name, state) {
    if (state == null) state = !this[name];
    this[state ? 'setState' : 'unsetState'](name);
  }
  
};

/**
 * Extends an object with prototype extenders.
 */
BUE.extendProto = function(obj) {
  var i, protos = BUE.protos, names = arguments;
  for (i = 1; i < names.length; i++) {
    BUE.extend(obj, protos[names[i]]);
  }
  return obj;
};

})(jQuery, BUE);