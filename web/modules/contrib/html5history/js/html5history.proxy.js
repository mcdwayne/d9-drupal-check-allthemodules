/**
 * @file
 * HTML 5 History proxy implementation with custom events.
 *
 * @see HistoryProxy
 */

(function (window, Drupal) {
  'use strict';

  // Polyfill for IE9+ CustomEvent.
  var CustomEvent = null;
  if (typeof window.CustomEvent === "function") {
    CustomEvent = window.CustomEvent;
  }
  else {
    CustomEvent = function(event, params) {
      params = params || { bubbles: false, cancelable: false, detail: undefined };
      var evt = document.createEvent("CustomEvent");
      evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
      return evt;
    }
    CustomEvent.prototype = window.Event.prototype;
  }

  /**
   * Provides a proxy for the History class that emits events on stack change.
   *
   * @constructor
   *
   * @param {@Window} The window object containing the history entry to be
   * proxied. Events will be delegated to the window the history object is
   * attached to.
   */
  function HistoryProxy(window) {
    var self = this;

    /**
     * @type {Window}
     */
    this._window = window;

    // Emit the 'changestate' event when a 'popstate' occurs.
    this._window.addEventListener('popstate', function(evt) {
      self._emit('changestate', evt.state);
    });
  }

  /**
   * Proxy method for History.back().
   *
   * Can emit a 'popstate' and 'changestate'.
   */
  HistoryProxy.prototype.back = function() {
    this._window.history.back();
  }

  /**
   * Proxy method for History.forward().
   *
   * Can emit a 'popstate' and 'changestate'.
   */
  HistoryProxy.prototype.forward = function() {
    this._window.history.forward();
  }

  /**
   * Proxy method for History.go().
   *
   * Can emit a 'popstate' and 'changestate'.
   */
  HistoryProxy.prototype.go = function(cursor) {
    this._window.history.go(cursor);
  }

  /**
   * Proxy method for History.pushState().
   *
   * Can emit a 'pushstate' and 'changestate'.
   */
  HistoryProxy.prototype.pushState = function(state, title, url) {
    this._window.history.pushState(state, title, url);
    this._emit('pushstate', state);
    this._emit('changestate', state);
  }

  /**
   * Proxy method for History.replaceState().
   *
   * Can emit a 'pushstate' and 'changestate'.
   */
  HistoryProxy.prototype.replaceState = function(state, title, url) {
    this._window.history.replaceState(state, title, url);
    this._emit('replacestate', state);
    this._emit('changestate', state);
  }

  /**
   * Emits a custom event on the window object this proxy is bound to.
   *
   * @param {string} name
   *   The custom event name.
   * @param {object} state
   *   The history state object as specified in the HTML 5 spec.
   */
  HistoryProxy.prototype._emit = function(name, state) {
    this._window.dispatchEvent(new CustomEvent(name, {
      target: this._window,
      bubbles: true,
      cancelable: false,
      state: state
    }));
  }

  /**
   * @type {HistoryProxy}
   */
  Drupal.html5history = new HistoryProxy(window);

})(window, Drupal);
