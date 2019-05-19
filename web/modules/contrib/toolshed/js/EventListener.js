'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

(function (Toolshed) {
  Toolshed.EventListener = function () {
    /**
     * Constructor for creating a set of event listeners. Window and global
     * events can be registered to global and registered to
     * Toolshed.EventListener.{eventName} namespace. Some of the events in that
     * namespace may have customized event callback.
     *
     * An example of this might be the ones defined in ./screen-events.js which
     * are used by the Toolshed.dock.js and Toolshed.layout.js.
     *
     * @param {DOMElement} elem
     *   DOM element that will be the target of the event.
     * @param {string} eventName
     *   The event.
     * @param {null|object} options
     *   method:
     *     Name of the method to call on all listeners (special cases). Will call
     *     the default "on[this.eventName]" method if left blank.
     *   useCapture:
     *     Use capture instead of bubbling for event propagation.
     *   passive:
     *     Event handlers will not call preventDefault() which can enable browser
     *     optimatization that no longer need to wait for all handlers to complete
     *     before triggering other events like scrolling.
     *   debounce:
     *     Determine if the event only triggers using debounce handling. This means
     *     that events will only fire off after a short delay.
     *
     *     If null or FALSE, no debounce will be used, and the event registered
     *     fires off as soon as the event is raised.
     *
     *     If TRUE then use the default debounce delay. If an integer, than use the
     *     value as the delay in milliseconds.
     */
    function _class(elem, eventName, options) {
      _classCallCheck(this, _class);

      options = options || {}; // options can be left blank.

      this.elem = elem;
      this.event = eventName;
      this.method = options.method || 'on' + Toolshed.pascalCase(eventName);
      this.autoListen = options.autoListen || false;
      this.listeners = [];

      // Check and properly organize the event options to be used later.
      if (options.debounce) {
        this.debounce = typeof options.debounce === 'boolean' ? 100 : options.debounce;
      }

      // Allow for addEventListener options as described here
      // https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
      // I am also employing the https://github.com/WICG/EventListenerOptions
      // as a polyfill, but support will not be available for IE8 and earlier.
      this.eventOpts = {
        capture: options.capture || false,
        passive: options.passive || false
      };
    }

    /**
     * Trigger the event for all the registered listeners. Custom
     * EventListeners are most likely to override this function in order
     * to create implement special functionality, triggered by events.
     *
     * @param {Object} event
     *   The event object that was generated and passed to the event handler.
     */


    _createClass(_class, [{
      key: '_run',
      value: function _run(event) {
        var _this = this;

        this.listeners.forEach(function (listener) {
          return listener[_this.method](event);
        }, this);
      }

      /**
       * Call a method in all listeners, using the event provided. Unlike
       * this._run() this method will check to make sure the listener supports
       * the event being requested.
       *
       * @param {string} method
       *   The name of the method to call from the listeners. Will check that
       *   this method exists before attempting to call.
       * @param {Object} event
       *   The original event object that was passed when event was triggered.
       */

    }, {
      key: '_callEvent',
      value: function _callEvent(method, event) {
        this.listeners.forEach(function (listener) {
          if (listener[method]) {
            listener[method](event);
          }
        });
      }

      /**
       * Ensure that a listener is a valid handler for the event used by this
       * EventListener. This test is for checking the listener before adding it
       * to the list of active listeners for this event.
       *
       * @param {Object} listener
       *   The object to test if it is valid for handling this event.
       *
       * @return {bool}
       *   Boolean to indicate if this listener is valid for handling this event.
       *   _true_ IFF this listener can be added and used with this event object.
       */

    }, {
      key: '_validateListener',
      value: function _validateListener(listener) {
        return Boolean(listener[this.method]);
      }

      /**
       * Trigger the event manaully.
       *
       * @param {Event|null} event
       *   Event data to use with this event.
       *
       * @return {Drupal.Toolshed.EventListener}
       *   Return this instance of this EventListener for the purpose of chaining.
       */

    }, {
      key: 'trigger',
      value: function trigger(event) {
        this._run(event || new Event(this.event), true);
        return this;
      }

      /**
       * Register the event, and keep track of the callback so it can be removed
       * later if we need to disable / remove the listener at a later time.
       *
       * @return {Drupal.Toolshed.EventListener}
       *   Return this instance of this EventListener for the purpose of chaining.
       */

    }, {
      key: 'listen',
      value: function listen() {
        if (!this.callback && (!this.autoListen || this.listeners.length)) {
          this.callback = this.debounce && this.debounce > 0 && Drupal.debounce ? Drupal.debounce(this._run.bind(this), this.debounce) : this._run.bind(this);

          this.elem.addEventListener(this.event, this.callback, this.eventOpts);
        }
        return this;
      }

      /**
       * Stop listening for this event, and unregister from any event listeners.
       *
       * @return {Drupal.Toolshed.EventListener}
       *   Return this instance of this EventListener for the purpose of chaining.
       */

    }, {
      key: 'ignore',
      value: function ignore() {
        if (this.callback) {
          this.elem.removeEventListener(this.event, this.callback);
          delete this.callback;
        }
        return this;
      }

      /**
       * If there is a valid atPos, place the listener at this position,
       * otherwise, just add it to the end of the list. This allows some
       * flexibility to place listeners at the start of the list, or
       * before other listeners.
       *
       * @param {Object} listener
       *   A listener object that contains the a method 'on' + [this.eventName].
       * @param {int} atPos
       *   Index to add the listener at. This allows listeners to be run in
       *   a different order than they maybe registered in.
       *
       * @return {Drupal.Toolshed.EventListener}
       *   Return this instance of this EventListener for the purpose of chaining.
       */

    }, {
      key: 'add',
      value: function add(listener, atPos) {
        if (this._validateListener(listener)) {
          // Ensure that all existing references to this event are removed.
          // Prevents the event from being called more than once unintentionally.
          this.remove(listener);

          if (atPos !== null && atPos >= 0) this.listeners.splice(atPos - 1, 0, listener);else this.listeners.push(listener);

          // We can defer registering this listener until a listener is added.
          if (this.autoListen) this.listen();
        }

        return this;
      }

      /**
       * Add a new listener before an existing listener already in the list.
       * If [before] is null, then insert at the start of the list.
       *
       * @param {Object} listener
       *   A listener object that contains the a method 'on' + [this.eventName].
       * @param {Object} before
       *   Listener object that is used to position the new listener.
       *
       * @return {Drupal.Toolshed.EventListener}
       *   Return this instance of this EventListener for the purpose of chaining.
       */

    }, {
      key: 'addBefore',
      value: function addBefore(listener, before) {
        var pos = 0;
        if (before) {
          pos = this.listeners.indexOf(before);
        }
        return this.add(listener, pos);
      }

      /**
       * Add a new listener after an existing listener already in the list.
       * If [after] is null, then insert at the end of the list.
       *
       * @param {Object} listener
       *  A listener object that contains the a method 'on' + [this.eventName].
       * @param {Object} after
       *  Listener object that is used to position the new listener.
       *
       * @return {Drupal.Toolshed.EventListener}
       *   Return this instance of this EventListener for the purpose of chaining.
       */

    }, {
      key: 'addAfter',
      value: function addAfter(listener, after) {
        var pos = null;
        if (after) {
          pos = this.listeners.indexOf(after);
          pos = pos >= 0 ? pos + 1 : -1;
        }
        return this.add(listener, pos);
      }

      /**
       * Remove the specified listener from the list of event listeners.
       * This assume there should only be one entry pert callback.
       *
       * @param {Object} listener
       *  A listener object that requests to get removed.
       *
       * @return {Drupal.Toolshed.EventListener}
       *   Return this instance of this EventListener for the purpose of chaining.
       */

    }, {
      key: 'remove',
      value: function remove(listener) {
        var pos = this.listeners.indexOf(listener);
        while (pos >= 0) {
          this.listeners.splice(pos, 1);
          pos = this.listeners.indexOf(listener);
        }

        // If there are no listeners and the autoListen option is on, turn off
        // listening. This prevents the event from being called for no reason.
        if (this.autoListen && !this.listeners.length) this.ignore();
        return this;
      }

      /**
       * Clean-up events and data.
       */

    }, {
      key: 'destroy',
      value: function destroy() {
        this.ignore();
      }
    }]);

    return _class;
  }();

  /**
   * Event listener for media query listeners.
   */
  Toolshed.MediaQueryListener = function (_Toolshed$EventListen) {
    _inherits(_class2, _Toolshed$EventListen);

    /**
     * Constructs a new Media Query listener instance.
     *
     * @param {Object[]} breakpoints
     *   An array of breakpoints in the order they should be checked. Each
     *   breakpoint object is expected to have an `mq`, `inverted` and `event`
     *   property which help determine what event to call when a Media Query
     *   listener triggers.
     * @param {Object} options
     *   Defines a set of options for mediaQuery listener objects. Currently
     *   only supports the `autolisten` option.
     */
    function _class2(breakpoints, options) {
      _classCallCheck(this, _class2);

      var _this2 = _possibleConstructorReturn(this, (_class2.__proto__ || Object.getPrototypeOf(_class2)).call(this, null, 'mediaQuery', options));

      _this2.mode = null;
      _this2.bps = [];
      _this2.queryMap = {};

      breakpoints.forEach(function (bp) {
        var mql = window.matchMedia(bp.mediaQuery);
        var event = Toolshed.pascalCase(bp.event);

        _this2.bps.push(mql);
        _this2.queryMap[bp.mediaQuery] = {
          event: event,
          inverted: bp.inverted || false
        };
      }, _this2);
      return _this2;
    }

    /**
     * Alter the current breakpoint mode, and trigger the related events.
     *
     * @param {string} newMode
     *   The name of the breakpoint mode to trigger.
     */


    _createClass(_class2, [{
      key: '_changeMode',
      value: function _changeMode(newMode) {
        var oldMode = this.mode;

        // If the mode changed, trigger the appropriate action.
        if (newMode !== oldMode) {
          if (oldMode) this._callEvent('off' + oldMode);
          if (newMode) this._callEvent('on' + newMode);

          this.mode = newMode;
        }
      }

      /**
       * @inheritdoc
       */

    }, {
      key: '_run',
      value: function _run(mql) {
        var qryInfo = this.queryMap[mql.media] || { event: null, inverted: false };
        var mode = !mql.matches !== !qryInfo.inverted ? qryInfo.event : this.checkBreakpoints();
        this._changeMode(mode);
      }

      /**
       * @inheritdoc
       */

    }, {
      key: '_validateListener',
      value: function _validateListener() {
        // eslint-disable-line class-methods-use-this
        return true;
      }

      /**
       * Check the registered breakpoints in order to see which one is active.
       *
       * @return {string|null}
       *   The query mapped event if a matching breakpoint is found, otherwise
       *   return NULL to mean no event.
       */

    }, {
      key: 'checkBreakpoints',
      value: function checkBreakpoints() {
        for (var i = 0; i < this.bps.length; ++i) {
          var mq = this.bps[i].media;
          var qryInfo = this.queryMap[mq] || { event: null, inverted: false };

          if (!this.bps[i].matches !== !qryInfo.inverted) {
            return qryInfo.event;
          }
        }
      }

      /**
       * @inheritdoc
       */

    }, {
      key: 'listen',
      value: function listen() {
        var _this3 = this;

        if (!this.callback && (!this.autoListen || this.listeners.length)) {
          this.callback = this._run.bind(this);
          this.bps.forEach(function (bp) {
            return bp.addListener(_this3.callback);
          }, this);
        }
      }

      /**
       * @inheritdoc
       */

    }, {
      key: 'ignore',
      value: function ignore() {
        var _this4 = this;

        if (this.callback) {
          this.bps.forEach(function (bp) {
            return bp.removeListener(_this4.callback);
          }, this);
          delete this.callback;
        }
      }
    }]);

    return _class2;
  }(Toolshed.EventListener);
})(Drupal.Toolshed);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIkV2ZW50TGlzdGVuZXIuZXM2LmpzIl0sIm5hbWVzIjpbIlRvb2xzaGVkIiwiRXZlbnRMaXN0ZW5lciIsImVsZW0iLCJldmVudE5hbWUiLCJvcHRpb25zIiwiZXZlbnQiLCJtZXRob2QiLCJwYXNjYWxDYXNlIiwiYXV0b0xpc3RlbiIsImxpc3RlbmVycyIsImRlYm91bmNlIiwiZXZlbnRPcHRzIiwiY2FwdHVyZSIsInBhc3NpdmUiLCJmb3JFYWNoIiwibGlzdGVuZXIiLCJCb29sZWFuIiwiX3J1biIsIkV2ZW50IiwiY2FsbGJhY2siLCJsZW5ndGgiLCJEcnVwYWwiLCJiaW5kIiwiYWRkRXZlbnRMaXN0ZW5lciIsInJlbW92ZUV2ZW50TGlzdGVuZXIiLCJhdFBvcyIsIl92YWxpZGF0ZUxpc3RlbmVyIiwicmVtb3ZlIiwic3BsaWNlIiwicHVzaCIsImxpc3RlbiIsImJlZm9yZSIsInBvcyIsImluZGV4T2YiLCJhZGQiLCJhZnRlciIsImlnbm9yZSIsIk1lZGlhUXVlcnlMaXN0ZW5lciIsImJyZWFrcG9pbnRzIiwibW9kZSIsImJwcyIsInF1ZXJ5TWFwIiwiYnAiLCJtcWwiLCJ3aW5kb3ciLCJtYXRjaE1lZGlhIiwibWVkaWFRdWVyeSIsImludmVydGVkIiwibmV3TW9kZSIsIm9sZE1vZGUiLCJfY2FsbEV2ZW50IiwicXJ5SW5mbyIsIm1lZGlhIiwibWF0Y2hlcyIsImNoZWNrQnJlYWtwb2ludHMiLCJfY2hhbmdlTW9kZSIsImkiLCJtcSIsImFkZExpc3RlbmVyIiwicmVtb3ZlTGlzdGVuZXIiXSwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7QUFFQSxDQUFDLFVBQUNBLFFBQUQsRUFBYztBQUNiQSxXQUFTQyxhQUFUO0FBQ0U7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7OztBQWlDQSxvQkFBWUMsSUFBWixFQUFrQkMsU0FBbEIsRUFBNkJDLE9BQTdCLEVBQXNDO0FBQUE7O0FBQ3BDQSxnQkFBVUEsV0FBVyxFQUFyQixDQURvQyxDQUNYOztBQUV6QixXQUFL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O0FBQ0Y7O0FBRUQ7Ozs7QUFwRkY7QUFBQTtBQUFBLCtCQXVGVztBQUFBOztBQUNQLFlBQUksQ0FBQyxLQUFLYyxRQUFOLEtBQW1CLENBQUMsS0FBS1gsVUFBTixJQUFvQixLQUFLQyxTQUFMLENBQWVXLE1BQXRELENBQUosRUFBbUU7QUFDakUsZUFBS0QsUUFBTCxHQUFnQixLQUFLRixJQUFMLENBQVVLLElBQVYsQ0FBZSxJQUFmLENBQWhCO0FBQ0EsZUFBS2tCLEdBQUwsQ0FBUzFCLE9BQVQsQ0FBaUI7QUFBQSxtQkFBTTRCLEdBQUdnQixXQUFILENBQWUsT0FBS3ZDLFFBQXBCLENBQU47QUFBQSxXQUFqQixFQUFzRCxJQUF0RDtBQUNEO0FBQ0Y7O0FBRUQ7Ozs7QUE5RkY7QUFBQTtBQUFBLCtCQWlHVztBQUFBOztBQUNQLFlBQUksS0FBS0EsUUFBVCxFQUFtQjtBQUNqQixlQUFLcUIsR0FBTCxDQUFTMUIsT0FBVCxDQUFpQjtBQUFBLG1CQUFNNEIsR0FBR2lCLGNBQUgsQ0FBa0IsT0FBS3hDLFFBQXZCLENBQU47QUFBQSxXQUFqQixFQUF5RCxJQUF6RDtBQUNBLGlCQUFPLEtBQUtBLFFBQVo7QUFDRDtBQUNGO0FBdEdIOztBQUFBO0FBQUEsSUFBNENuQixTQUFTQyxhQUFyRDtBQXdHRCxDQXpXRCxFQXlXR29CLE9BQU9yQixRQXpXViIsImZpbGUiOiJFdmVudExpc3RlbmVyLmpzIiwic291cmNlc0NvbnRlbnQiOlsiXG5cbigoVG9vbHNoZWQpID0+IHtcbiAgVG9vbHNoZWQuRXZlbnRMaXN0ZW5lciA9IGNsYXNzIHtcbiAgICAvKipcbiAgICAgKiBDb25zdHJ1Y3RvciBmb3IgY3JlYXRpbmcgYSBzZXQgb2YgZXZlbnQgbGlzdGVuZXJzLiBXaW5kb3cgYW5kIGdsb2JhbFxuICAgICAqIGV2ZW50cyBjYW4gYmUgcmVnaXN0ZXJlZCB0byBnbG9iYWwgYW5kIHJlZ2lzdGVyZWQgdG9cbiAgICAgKiBUb29sc2hlZC5FdmVudExpc3RlbmVyLntldmVudE5hbWV9IG5hbWVzcGFjZS4gU29tZSBvZiB0aGUgZXZlbnRzIGluIHRoYXRcbiAgICAgKiBuYW1lc3BhY2UgbWF5IGhhdmUgY3VzdG9taXplZCBldmVudCBjYWxsYmFjay5cbiAgICAgKlxuICAgICAqIEFuIGV4YW1wbGUgb2YgdGhpcyBtaWdodCBiZSB0aGUgb25lcyBkZWZpbmVkIGluIC4vc2NyZWVuLWV2ZW50cy5qcyB3aGljaFxuICAgICAqIGFyZSB1c2VkIGJ5IHRoZSBUb29sc2hlZC5kb2NrLmpzIGFuZCBUb29sc2hlZC5sYXlvdXQuanMuXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge0RPTUVsZW1lbnR9IGVsZW1cbiAgICAgKiAgIERPTSBlbGVtZW50IHRoYXQgd2lsbCBiZSB0aGUgdGFyZ2V0IG9mIHRoZSBldmVudC5cbiAgICAgKiBAcGFyYW0ge3N0cmluZ30gZXZlbnROYW1lXG4gICAgICogICBUaGUgZXZlbnQuXG4gICAgICogQHBhcmFtIHtudWxsfG9iamVjdH0gb3B0aW9uc1xuICAgICAqICAgbWV0aG9kOlxuICAgICAqICAgICBOYW1lIG9mIHRoZSBtZXRob2QgdG8gY2FsbCBvbiBhbGwgbGlzdGVuZXJzIChzcGVjaWFsIGNhc2VzKS4gV2lsbCBjYWxsXG4gICAgICogICAgIHRoZSBkZWZhdWx0IFwib25bdGhpcy5ldmVudE5hbWVdXCIgbWV0aG9kIGlmIGxlZnQgYmxhbmsuXG4gICAgICogICB1c2VDYXB0dXJlOlxuICAgICAqICAgICBVc2UgY2FwdHVyZSBpbnN0ZWFkIG9mIGJ1YmJsaW5nIGZvciBldmVudCBwcm9wYWdhdGlvbi5cbiAgICAgKiAgIHBhc3NpdmU6XG4gICAgICogICAgIEV2ZW50IGhhbmRsZXJzIHdpbGwgbm90IGNhbGwgcHJldmVudERlZmF1bHQoKSB3aGljaCBjYW4gZW5hYmxlIGJyb3dzZXJcbiAgICAgKiAgICAgb3B0aW1hdGl6YXRpb24gdGhhdCBubyBsb25nZXIgbmVlZCB0byB3YWl0IGZvciBhbGwgaGFuZGxlcnMgdG8gY29tcGxldGVcbiAgICAgKiAgICAgYmVmb3JlIHRyaWdnZXJpbmcgb3RoZXIgZXZlbnRzIGxpa2Ugc2Nyb2xsaW5nLlxuICAgICAqICAgZGVib3VuY2U6XG4gICAgICogICAgIERldGVybWluZSBpZiB0aGUgZXZlbnQgb25seSB0cmlnZ2VycyB1c2luZyBkZWJvdW5jZSBoYW5kbGluZy4gVGhpcyBtZWFuc1xuICAgICAqICAgICB0aGF0IGV2ZW50cyB3aWxsIG9ubHkgZmlyZSBvZmYgYWZ0ZXIgYSBzaG9ydCBkZWxheS5cbiAgICAgKlxuICAgICAqICAgICBJZiBudWxsIG9yIEZBTFNFLCBubyBkZWJvdW5jZSB3aWxsIGJlIHVzZWQsIGFuZCB0aGUgZXZlbnQgcmVnaXN0ZXJlZFxuICAgICAqICAgICBmaXJlcyBvZmYgYXMgc29vbiBhcyB0aGUgZXZlbnQgaXMgcmFpc2VkLlxuICAgICAqXG4gICAgICogICAgIElmIFRSVUUgdGhlbiB1c2UgdGhlIGRlZmF1bHQgZGVib3VuY2UgZGVsYXkuIElmIGFuIGludGVnZXIsIHRoYW4gdXNlIHRoZVxuICAgICAqICAgICB2YWx1ZSBhcyB0aGUgZGVsYXkgaW4gbWlsbGlzZWNvbmRzLlxuICAgICAqL1xuICAgIGNvbnN0cnVjdG9yKGVsZW0sIGV2ZW50TmFtZSwgb3B0aW9ucykge1xuICAgICAgb3B0aW9ucyA9IG9wdGlvbnMgfHwge307IC8vIG9wdGlvbnMgY2FuIGJlIGxlZnQgYmxhbmsuXG5cbiAgICAgIHRoaXMuZWxlbSA9IGVsZW07XG4gICAgICB0aGlzLmV2ZW50ID0gZXZlbnROYW1lO1xuICAgICAgdGhpcy5tZXRob2QgPSBvcHRpb25zLm1ldGhvZCB8fCBgb24ke1Rvb2xzaGVkLnBhc2NhbENhc2UoZXZlbnROYW1lKX1gO1xuICAgICAgdGhpcy5hdXRvTGlzdGVuID0gb3B0aW9ucy5hdXRvTGlzdGVuIHx8IGZhbHNlO1xuICAgICAgdGhpcy5saXN0ZW5lcnMgPSBbXTtcblxuICAgICAgLy8gQ2hlY2sgYW5kIHByb3Blcmx5IG9yZ2FuaXplIHRoZSBldmVudCBvcHRpb25zIHRvIGJlIHVzZWQgbGF0ZXIuXG4gICAgICBpZiAob3B0aW9ucy5kZWJvdW5jZSkge1xuICAgICAgICB0aGlzLmRlYm91bmNlID0gKHR5cGVvZiBvcHRpb25zLmRlYm91bmNlID09PSAnYm9vbGVhbicpID8gMTAwIDogb3B0aW9ucy5kZWJvdW5jZTtcbiAgICAgIH1cblxuICAgICAgLy8gQWxsb3cgZm9yIGFkZEV2ZW50TGlzdGVuZXIgb3B0aW9ucyBhcyBkZXNjcmliZWQgaGVyZVxuICAgICAgLy8gaHR0cHM6Ly9kZXZlbG9wZXIubW96aWxsYS5vcmcvZW4tVVMvZG9jcy9XZWIvQVBJL0V2ZW50VGFyZ2V0L2FkZEV2ZW50TGlzdGVuZXJcbiAgICAgIC8vIEkgYW0gYWxzbyBlbXBsb3lpbmcgdGhlIGh0dHBzOi8vZ2l0aHViLmNvbS9XSUNHL0V2ZW50TGlzdGVuZXJPcHRpb25zXG4gICAgICAvLyBhcyBhIHBvbHlmaWxsLCBidXQgc3VwcG9ydCB3aWxsIG5vdCBiZSBhdmFpbGFibGUgZm9yIElFOCBhbmQgZWFybGllci5cbiAgICAgIHRoaXMuZXZlbnRPcHRzID0ge1xuICAgICAgICBjYXB0dXJlOiBvcHRpb25zLmNhcHR1cmUgfHwgZmFsc2UsXG4gICAgICAgIHBhc3NpdmU6IG9wdGlvbnMucGFzc2l2ZSB8fCBmYWxzZSxcbiAgICAgIH07XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogVHJpZ2dlciB0aGUgZXZlbnQgZm9yIGFsbCB0aGUgcmVnaXN0ZXJlZCBsaXN0ZW5lcnMuIEN1c3RvbVxuICAgICAqIEV2ZW50TGlzdGVuZXJzIGFyZSBtb3N0IGxpa2VseSB0byBvdmVycmlkZSB0aGlzIGZ1bmN0aW9uIGluIG9yZGVyXG4gICAgICogdG8gY3JlYXRlIGltcGxlbWVudCBzcGVjaWFsIGZ1bmN0aW9uYWxpdHksIHRyaWdnZXJlZCBieSBldmVudHMuXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge09iamVjdH0gZXZlbnRcbiAgICAgKiAgIFRoZSBldmVudCBvYmplY3QgdGhhdCB3YXMgZ2VuZXJhdGVkIGFuZCBwYXNzZWQgdG8gdGhlIGV2ZW50IGhhbmRsZXIuXG4gICAgICovXG4gICAgX3J1bihldmVudCkge1xuICAgICAgdGhpcy5saXN0ZW5lcnMuZm9yRWFjaChsaXN0ZW5lciA9PiBsaXN0ZW5lclt0aGlzLm1ldGhvZF0oZXZlbnQpLCB0aGlzKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBDYWxsIGEgbWV0aG9kIGluIGFsbCBsaXN0ZW5lcnMsIHVzaW5nIHRoZSBldmVudCBwcm92aWRlZC4gVW5saWtlXG4gICAgICogdGhpcy5fcnVuKCkgdGhpcyBtZXRob2Qgd2lsbCBjaGVjayB0byBtYWtlIHN1cmUgdGhlIGxpc3RlbmVyIHN1cHBvcnRzXG4gICAgICogdGhlIGV2ZW50IGJlaW5nIHJlcXVlc3RlZC5cbiAgICAgKlxuICAgICAqIEBwYXJhbSB7c3RyaW5nfSBtZXRob2RcbiAgICAgKiAgIFRoZSBuYW1lIG9mIHRoZSBtZXRob2QgdG8gY2FsbCBmcm9tIHRoZSBsaXN0ZW5lcnMuIFdpbGwgY2hlY2sgdGhhdFxuICAgICAqICAgdGhpcyBtZXRob2QgZXhpc3RzIGJlZm9yZSBhdHRlbXB0aW5nIHRvIGNhbGwuXG4gICAgICogQHBhcmFtIHtPYmplY3R9IGV2ZW50XG4gICAgICogICBUaGUgb3JpZ2luYWwgZXZlbnQgb2JqZWN0IHRoYXQgd2FzIHBhc3NlZCB3aGVuIGV2ZW50IHdhcyB0cmlnZ2VyZWQuXG4gICAgICovXG4gICAgX2NhbGxFdmVudChtZXRob2QsIGV2ZW50KSB7XG4gICAgICB0aGlzLmxpc3RlbmVycy5mb3JFYWNoKChsaXN0ZW5lcikgPT4ge1xuICAgICAgICBpZiAobGlzdGVuZXJbbWV0aG9kXSkge1xuICAgICAgICAgIGxpc3RlbmVyW21ldGhvZF0oZXZlbnQpO1xuICAgICAgICB9XG4gICAgICB9KTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBFbnN1cmUgdGhhdCBhIGxpc3RlbmVyIGlzIGEgdmFsaWQgaGFuZGxlciBmb3IgdGhlIGV2ZW50IHVzZWQgYnkgdGhpc1xuICAgICAqIEV2ZW50TGlzdGVuZXIuIFRoaXMgdGVzdCBpcyBmb3IgY2hlY2tpbmcgdGhlIGxpc3RlbmVyIGJlZm9yZSBhZGRpbmcgaXRcbiAgICAgKiB0byB0aGUgbGlzdCBvZiBhY3RpdmUgbGlzdGVuZXJzIGZvciB0aGlzIGV2ZW50LlxuICAgICAqXG4gICAgICogQHBhcmFtIHtPYmplY3R9IGxpc3RlbmVyXG4gICAgICogICBUaGUgb2JqZWN0IHRvIHRlc3QgaWYgaXQgaXMgdmFsaWQgZm9yIGhhbmRsaW5nIHRoaXMgZXZlbnQuXG4gICAgICpcbiAgICAgKiBAcmV0dXJuIHtib29sfVxuICAgICAqICAgQm9vbGVhbiB0byBpbmRpY2F0ZSBpZiB0aGlzIGxpc3RlbmVyIGlzIHZhbGlkIGZvciBoYW5kbGluZyB0aGlzIGV2ZW50LlxuICAgICAqICAgX3RydWVfIElGRiB0aGlzIGxpc3RlbmVyIGNhbiBiZSBhZGRlZCBhbmQgdXNlZCB3aXRoIHRoaXMgZXZlbnQgb2JqZWN0LlxuICAgICAqL1xuICAgIF92YWxpZGF0ZUxpc3RlbmVyKGxpc3RlbmVyKSB7XG4gICAgICByZXR1cm4gQm9vbGVhbihsaXN0ZW5lclt0aGlzLm1ldGhvZF0pO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFRyaWdnZXIgdGhlIGV2ZW50IG1hbmF1bGx5LlxuICAgICAqXG4gICAgICogQHBhcmFtIHtFdmVudHxudWxsfSBldmVudFxuICAgICAqICAgRXZlbnQgZGF0YSB0byB1c2Ugd2l0aCB0aGlzIGV2ZW50LlxuICAgICAqXG4gICAgICogQHJldHVybiB7RHJ1cGFsLlRvb2xzaGVkLkV2ZW50TGlzdGVuZXJ9XG4gICAgICogICBSZXR1cm4gdGhpcyBpbnN0YW5jZSBvZiB0aGlzIEV2ZW50TGlzdGVuZXIgZm9yIHRoZSBwdXJwb3NlIG9mIGNoYWluaW5nLlxuICAgICAqL1xuICAgIHRyaWdnZXIoZXZlbnQpIHtcbiAgICAgIHRoaXMuX3J1bihldmVudCB8fCBuZXcgRXZlbnQodGhpcy5ldmVudCksIHRydWUpO1xuICAgICAgcmV0dXJuIHRoaXM7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogUmVnaXN0ZXIgdGhlIGV2ZW50LCBhbmQga2VlcCB0cmFjayBvZiB0aGUgY2FsbGJhY2sgc28gaXQgY2FuIGJlIHJlbW92ZWRcbiAgICAgKiBsYXRlciBpZiB3ZSBuZWVkIHRvIGRpc2FibGUgLyByZW1vdmUgdGhlIGxpc3RlbmVyIGF0IGEgbGF0ZXIgdGltZS5cbiAgICAgKlxuICAgICAqIEByZXR1cm4ge0RydXBhbC5Ub29sc2hlZC5FdmVudExpc3RlbmVyfVxuICAgICAqICAgUmV0dXJuIHRoaXMgaW5zdGFuY2Ugb2YgdGhpcyBFdmVudExpc3RlbmVyIGZvciB0aGUgcHVycG9zZSBvZiBjaGFpbmluZy5cbiAgICAgKi9cbiAgICBsaXN0ZW4oKSB7XG4gICAgICBpZiAoIXRoaXMuY2FsbGJhY2sgJiYgKCF0aGlzLmF1dG9MaXN0ZW4gfHwgdGhpcy5saXN0ZW5lcnMubGVuZ3RoKSkge1xuICAgICAgICB0aGlzLmNhbGxiYWNrID0gKHRoaXMuZGVib3VuY2UgJiYgdGhpcy5kZWJvdW5jZSA+IDAgJiYgRHJ1cGFsLmRlYm91bmNlKVxuICAgICAgICAgID8gRHJ1cGFsLmRlYm91bmNlKHRoaXMuX3J1bi5iaW5kKHRoaXMpLCB0aGlzLmRlYm91bmNlKSA6IHRoaXMuX3J1bi5iaW5kKHRoaXMpO1xuXG4gICAgICAgIHRoaXMuZWxlbS5hZGRFdmVudExpc3RlbmVyKHRoaXMuZXZlbnQsIHRoaXMuY2FsbGJhY2ssIHRoaXMuZXZlbnRPcHRzKTtcbiAgICAgIH1cbiAgICAgIHJldHVybiB0aGlzO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFN0b3AgbGlzdGVuaW5nIGZvciB0aGlzIGV2ZW50LCBhbmQgdW5yZWdpc3RlciBmcm9tIGFueSBldmVudCBsaXN0ZW5lcnMuXG4gICAgICpcbiAgICAgKiBAcmV0dXJuIHtEcnVwYWwuVG9vbHNoZWQuRXZlbnRMaXN0ZW5lcn1cbiAgICAgKiAgIFJldHVybiB0aGlzIGluc3RhbmNlIG9mIHRoaXMgRXZlbnRMaXN0ZW5lciBmb3IgdGhlIHB1cnBvc2Ugb2YgY2hhaW5pbmcuXG4gICAgICovXG4gICAgaWdub3JlKCkge1xuICAgICAgaWYgKHRoaXMuY2FsbGJhY2spIHtcbiAgICAgICAgdGhpcy5lbGVtLnJlbW92ZUV2ZW50TGlzdGVuZXIodGhpcy5ldmVudCwgdGhpcy5jYWxsYmFjayk7XG4gICAgICAgIGRlbGV0ZSB0aGlzLmNhbGxiYWNrO1xuICAgICAgfVxuICAgICAgcmV0dXJuIHRoaXM7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogSWYgdGhlcmUgaXMgYSB2YWxpZCBhdFBvcywgcGxhY2UgdGhlIGxpc3RlbmVyIGF0IHRoaXMgcG9zaXRpb24sXG4gICAgICogb3RoZXJ3aXNlLCBqdXN0IGFkZCBpdCB0byB0aGUgZW5kIG9mIHRoZSBsaXN0LiBUaGlzIGFsbG93cyBzb21lXG4gICAgICogZmxleGliaWxpdHkgdG8gcGxhY2UgbGlzdGVuZXJzIGF0IHRoZSBzdGFydCBvZiB0aGUgbGlzdCwgb3JcbiAgICAgKiBiZWZvcmUgb3RoZXIgbGlzdGVuZXJzLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtPYmplY3R9IGxpc3RlbmVyXG4gICAgICogICBBIGxpc3RlbmVyIG9iamVjdCB0aGF0IGNvbnRhaW5zIHRoZSBhIG1ldGhvZCAnb24nICsgW3RoaXMuZXZlbnROYW1lXS5cbiAgICAgKiBAcGFyYW0ge2ludH0gYXRQb3NcbiAgICAgKiAgIEluZGV4IHRvIGFkZCB0aGUgbGlzdGVuZXIgYXQuIFRoaXMgYWxsb3dzIGxpc3RlbmVycyB0byBiZSBydW4gaW5cbiAgICAgKiAgIGEgZGlmZmVyZW50IG9yZGVyIHRoYW4gdGhleSBtYXliZSByZWdpc3RlcmVkIGluLlxuICAgICAqXG4gICAgICogQHJldHVybiB7RHJ1cGFsLlRvb2xzaGVkLkV2ZW50TGlzdGVuZXJ9XG4gICAgICogICBSZXR1cm4gdGhpcyBpbnN0YW5jZSBvZiB0aGlzIEV2ZW50TGlzdGVuZXIgZm9yIHRoZSBwdXJwb3NlIG9mIGNoYWluaW5nLlxuICAgICAqL1xuICAgIGFkZChsaXN0ZW5lciwgYXRQb3MpIHtcbiAgICAgIGlmICh0aGlzLl92YWxpZGF0ZUxpc3RlbmVyKGxpc3RlbmVyKSkge1xuICAgICAgICAvLyBFbnN1cmUgdGhhdCBhbGwgZXhpc3RpbmcgcmVmZXJlbmNlcyB0byB0aGlzIGV2ZW50IGFyZSByZW1vdmVkLlxuICAgICAgICAvLyBQcmV2ZW50cyB0aGUgZXZlbnQgZnJvbSBiZWluZyBjYWxsZWQgbW9yZSB0aGFuIG9uY2UgdW5pbnRlbnRpb25hbGx5LlxuICAgICAgICB0aGlzLnJlbW92ZShsaXN0ZW5lcik7XG5cbiAgICAgICAgaWYgKGF0UG9zICE9PSBudWxsICYmIGF0UG9zID49IDApIHRoaXMubGlzdGVuZXJzLnNwbGljZShhdFBvcyAtIDEsIDAsIGxpc3RlbmVyKTtcbiAgICAgICAgZWxzZSB0aGlzLmxpc3RlbmVycy5wdXNoKGxpc3RlbmVyKTtcblxuICAgICAgICAvLyBXZSBjYW4gZGVmZXIgcmVnaXN0ZXJpbmcgdGhpcyBsaXN0ZW5lciB1bnRpbCBhIGxpc3RlbmVyIGlzIGFkZGVkLlxuICAgICAgICBpZiAodGhpcy5hdXRvTGlzdGVuKSB0aGlzLmxpc3RlbigpO1xuICAgICAgfVxuXG4gICAgICByZXR1cm4gdGhpcztcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBBZGQgYSBuZXcgbGlzdGVuZXIgYmVmb3JlIGFuIGV4aXN0aW5nIGxpc3RlbmVyIGFscmVhZHkgaW4gdGhlIGxpc3QuXG4gICAgICogSWYgW2JlZm9yZV0gaXMgbnVsbCwgdGhlbiBpbnNlcnQgYXQgdGhlIHN0YXJ0IG9mIHRoZSBsaXN0LlxuICAgICAqXG4gICAgICogQHBhcmFtIHtPYmplY3R9IGxpc3RlbmVyXG4gICAgICogICBBIGxpc3RlbmVyIG9iamVjdCB0aGF0IGNvbnRhaW5zIHRoZSBhIG1ldGhvZCAnb24nICsgW3RoaXMuZXZlbnROYW1lXS5cbiAgICAgKiBAcGFyYW0ge09iamVjdH0gYmVmb3JlXG4gICAgICogICBMaXN0ZW5lciBvYmplY3QgdGhhdCBpcyB1c2VkIHRvIHBvc2l0aW9uIHRoZSBuZXcgbGlzdGVuZXIuXG4gICAgICpcbiAgICAgKiBAcmV0dXJuIHtEcnVwYWwuVG9vbHNoZWQuRXZlbnRMaXN0ZW5lcn1cbiAgICAgKiAgIFJldHVybiB0aGlzIGluc3RhbmNlIG9mIHRoaXMgRXZlbnRMaXN0ZW5lciBmb3IgdGhlIHB1cnBvc2Ugb2YgY2hhaW5pbmcuXG4gICAgICovXG4gICAgYWRkQmVmb3JlKGxpc3RlbmVyLCBiZWZvcmUpIHtcbiAgICAgIGxldCBwb3MgPSAwO1xuICAgICAgaWYgKGJlZm9yZSkge1xuICAgICAgICBwb3MgPSB0aGlzLmxpc3RlbmVycy5pbmRleE9mKGJlZm9yZSk7XG4gICAgICB9XG4gICAgICByZXR1cm4gdGhpcy5hZGQobGlzdGVuZXIsIHBvcyk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQWRkIGEgbmV3IGxpc3RlbmVyIGFmdGVyIGFuIGV4aXN0aW5nIGxpc3RlbmVyIGFscmVhZHkgaW4gdGhlIGxpc3QuXG4gICAgICogSWYgW2FmdGVyXSBpcyBudWxsLCB0aGVuIGluc2VydCBhdCB0aGUgZW5kIG9mIHRoZSBsaXN0LlxuICAgICAqXG4gICAgICogQHBhcmFtIHtPYmplY3R9IGxpc3RlbmVyXG4gICAgICogIEEgbGlzdGVuZXIgb2JqZWN0IHRoYXQgY29udGFpbnMgdGhlIGEgbWV0aG9kICdvbicgKyBbdGhpcy5ldmVudE5hbWVdLlxuICAgICAqIEBwYXJhbSB7T2JqZWN0fSBhZnRlclxuICAgICAqICBMaXN0ZW5lciBvYmplY3QgdGhhdCBpcyB1c2VkIHRvIHBvc2l0aW9uIHRoZSBuZXcgbGlzdGVuZXIuXG4gICAgICpcbiAgICAgKiBAcmV0dXJuIHtEcnVwYWwuVG9vbHNoZWQuRXZlbnRMaXN0ZW5lcn1cbiAgICAgKiAgIFJldHVybiB0aGlzIGluc3RhbmNlIG9mIHRoaXMgRXZlbnRMaXN0ZW5lciBmb3IgdGhlIHB1cnBvc2Ugb2YgY2hhaW5pbmcuXG4gICAgICovXG4gICAgYWRkQWZ0ZXIobGlzdGVuZXIsIGFmdGVyKSB7XG4gICAgICBsZXQgcG9zID0gbnVsbDtcbiAgICAgIGlmIChhZnRlcikge1xuICAgICAgICBwb3MgPSB0aGlzLmxpc3RlbmVycy5pbmRleE9mKGFmdGVyKTtcbiAgICAgICAgcG9zID0gcG9zID49IDAgPyBwb3MgKyAxIDogLTE7XG4gICAgICB9XG4gICAgICByZXR1cm4gdGhpcy5hZGQobGlzdGVuZXIsIHBvcyk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogUmVtb3ZlIHRoZSBzcGVjaWZpZWQgbGlzdGVuZXIgZnJvbSB0aGUgbGlzdCBvZiBldmVudCBsaXN0ZW5lcnMuXG4gICAgICogVGhpcyBhc3N1bWUgdGhlcmUgc2hvdWxkIG9ubHkgYmUgb25lIGVudHJ5IHBlcnQgY2FsbGJhY2suXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge09iamVjdH0gbGlzdGVuZXJcbiAgICAgKiAgQSBsaXN0ZW5lciBvYmplY3QgdGhhdCByZXF1ZXN0cyB0byBnZXQgcmVtb3ZlZC5cbiAgICAgKlxuICAgICAqIEByZXR1cm4ge0RydXBhbC5Ub29sc2hlZC5FdmVudExpc3RlbmVyfVxuICAgICAqICAgUmV0dXJuIHRoaXMgaW5zdGFuY2Ugb2YgdGhpcyBFdmVudExpc3RlbmVyIGZvciB0aGUgcHVycG9zZSBvZiBjaGFpbmluZy5cbiAgICAgKi9cbiAgICByZW1vdmUobGlzdGVuZXIpIHtcbiAgICAgIGxldCBwb3MgPSB0aGlzLmxpc3RlbmVycy5pbmRleE9mKGxpc3RlbmVyKTtcbiAgICAgIHdoaWxlIChwb3MgPj0gMCkge1xuICAgICAgICB0aGlzLmxpc3RlbmVycy5zcGxpY2UocG9zLCAxKTtcbiAgICAgICAgcG9zID0gdGhpcy5saXN0ZW5lcnMuaW5kZXhPZihsaXN0ZW5lcik7XG4gICAgICB9XG5cbiAgICAgIC8vIElmIHRoZXJlIGFyZSBubyBsaXN0ZW5lcnMgYW5kIHRoZSBhdXRvTGlzdGVuIG9wdGlvbiBpcyBvbiwgdHVybiBvZmZcbiAgICAgIC8vIGxpc3RlbmluZy4gVGhpcyBwcmV2ZW50cyB0aGUgZXZlbnQgZnJvbSBiZWluZyBjYWxsZWQgZm9yIG5vIHJlYXNvbi5cbiAgICAgIGlmICh0aGlzLmF1dG9MaXN0ZW4gJiYgIXRoaXMubGlzdGVuZXJzLmxlbmd0aCkgdGhpcy5pZ25vcmUoKTtcbiAgICAgIHJldHVybiB0aGlzO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIENsZWFuLXVwIGV2ZW50cyBhbmQgZGF0YS5cbiAgICAgKi9cbiAgICBkZXN0cm95KCkge1xuICAgICAgdGhpcy5pZ25vcmUoKTtcbiAgICB9XG4gIH07XG5cbiAgLyoqXG4gICAqIEV2ZW50IGxpc3RlbmVyIGZvciBtZWRpYSBxdWVyeSBsaXN0ZW5lcnMuXG4gICAqL1xuICBUb29sc2hlZC5NZWRpYVF1ZXJ5TGlzdGVuZXIgPSBjbGFzcyBleHRlbmRzIFRvb2xzaGVkLkV2ZW50TGlzdGVuZXIge1xuICAgIC8qKlxuICAgICAqIENvbnN0cnVjdHMgYSBuZXcgTWVkaWEgUXVlcnkgbGlzdGVuZXIgaW5zdGFuY2UuXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge09iamVjdFtdfSBicmVha3BvaW50c1xuICAgICAqICAgQW4gYXJyYXkgb2YgYnJlYWtwb2ludHMgaW4gdGhlIG9yZGVyIHRoZXkgc2hvdWxkIGJlIGNoZWNrZWQuIEVhY2hcbiAgICAgKiAgIGJyZWFrcG9pbnQgb2JqZWN0IGlzIGV4cGVjdGVkIHRvIGhhdmUgYW4gYG1xYCwgYGludmVydGVkYCBhbmQgYGV2ZW50YFxuICAgICAqICAgcHJvcGVydHkgd2hpY2ggaGVscCBkZXRlcm1pbmUgd2hhdCBldmVudCB0byBjYWxsIHdoZW4gYSBNZWRpYSBRdWVyeVxuICAgICAqICAgbGlzdGVuZXIgdHJpZ2dlcnMuXG4gICAgICogQHBhcmFtIHtPYmplY3R9IG9wdGlvbnNcbiAgICAgKiAgIERlZmluZXMgYSBzZXQgb2Ygb3B0aW9ucyBmb3IgbWVkaWFRdWVyeSBsaXN0ZW5lciBvYmplY3RzLiBDdXJyZW50bHlcbiAgICAgKiAgIG9ubHkgc3VwcG9ydHMgdGhlIGBhdXRvbGlzdGVuYCBvcHRpb24uXG4gICAgICovXG4gICAgY29uc3RydWN0b3IoYnJlYWtwb2ludHMsIG9wdGlvbnMpIHtcbiAgICAgIHN1cGVyKG51bGwsICdtZWRpYVF1ZXJ5Jywgb3B0aW9ucyk7XG5cbiAgICAgIHRoaXMubW9kZSA9IG51bGw7XG4gICAgICB0aGlzLmJwcyA9IFtdO1xuICAgICAgdGhpcy5xdWVyeU1hcCA9IHt9O1xuXG4gICAgICBicmVha3BvaW50cy5mb3JFYWNoKChicCkgPT4ge1xuICAgICAgICBjb25zdCBtcWwgPSB3aW5kb3cubWF0Y2hNZWRpYShicC5tZWRpYVF1ZXJ5KTtcbiAgICAgICAgY29uc3QgZXZlbnQgPSBUb29sc2hlZC5wYXNjYWxDYXNlKGJwLmV2ZW50KTtcblxuICAgICAgICB0aGlzLmJwcy5wdXNoKG1xbCk7XG4gICAgICAgIHRoaXMucXVlcnlNYXBbYnAubWVkaWFRdWVyeV0gPSB7XG4gICAgICAgICAgZXZlbnQsXG4gICAgICAgICAgaW52ZXJ0ZWQ6IGJwLmludmVydGVkIHx8IGZhbHNlLFxuICAgICAgICB9O1xuICAgICAgfSwgdGhpcyk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQWx0ZXIgdGhlIGN1cnJlbnQgYnJlYWtwb2ludCBtb2RlLCBhbmQgdHJpZ2dlciB0aGUgcmVsYXRlZCBldmVudHMuXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge3N0cmluZ30gbmV3TW9kZVxuICAgICAqICAgVGhlIG5hbWUgb2YgdGhlIGJyZWFrcG9pbnQgbW9kZSB0byB0cmlnZ2VyLlxuICAgICAqL1xuICAgIF9jaGFuZ2VNb2RlKG5ld01vZGUpIHtcbiAgICAgIGNvbnN0IG9sZE1vZGUgPSB0aGlzLm1vZGU7XG5cbiAgICAgIC8vIElmIHRoZSBtb2RlIGNoYW5nZWQsIHRyaWdnZXIgdGhlIGFwcHJvcHJpYXRlIGFjdGlvbi5cbiAgICAgIGlmIChuZXdNb2RlICE9PSBvbGRNb2RlKSB7XG4gICAgICAgIGlmIChvbGRNb2RlKSB0aGlzLl9jYWxsRXZlbnQoYG9mZiR7b2xkTW9kZX1gKTtcbiAgICAgICAgaWYgKG5ld01vZGUpIHRoaXMuX2NhbGxFdmVudChgb24ke25ld01vZGV9YCk7XG5cbiAgICAgICAgdGhpcy5tb2RlID0gbmV3TW9kZTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBAaW5oZXJpdGRvY1xuICAgICAqL1xuICAgIF9ydW4obXFsKSB7XG4gICAgICBjb25zdCBxcnlJbmZvID0gdGhpcy5xdWVyeU1hcFttcWwubWVkaWFdIHx8IHsgZXZlbnQ6IG51bGwsIGludmVydGVkOiBmYWxzZSB9O1xuICAgICAgY29uc3QgbW9kZSA9ICghbXFsLm1hdGNoZXMgIT09ICFxcnlJbmZvLmludmVydGVkKSA/IHFyeUluZm8uZXZlbnQgOiB0aGlzLmNoZWNrQnJlYWtwb2ludHMoKTtcbiAgICAgIHRoaXMuX2NoYW5nZU1vZGUobW9kZSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQGluaGVyaXRkb2NcbiAgICAgKi9cbiAgICBfdmFsaWRhdGVMaXN0ZW5lcigpIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBjbGFzcy1tZXRob2RzLXVzZS10aGlzXG4gICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBDaGVjayB0aGUgcmVnaXN0ZXJlZCBicmVha3BvaW50cyBpbiBvcmRlciB0byBzZWUgd2hpY2ggb25lIGlzIGFjdGl2ZS5cbiAgICAgKlxuICAgICAqIEByZXR1cm4ge3N0cmluZ3xudWxsfVxuICAgICAqICAgVGhlIHF1ZXJ5IG1hcHBlZCBldmVudCBpZiBhIG1hdGNoaW5nIGJyZWFrcG9pbnQgaXMgZm91bmQsIG90aGVyd2lzZVxuICAgICAqICAgcmV0dXJuIE5VTEwgdG8gbWVhbiBubyBldmVudC5cbiAgICAgKi9cbiAgICBjaGVja0JyZWFrcG9pbnRzKCkge1xuICAgICAgZm9yIChsZXQgaSA9IDA7IGkgPCB0aGlzLmJwcy5sZW5ndGg7ICsraSkge1xuICAgICAgICBjb25zdCBtcSA9IHRoaXMuYnBzW2ldLm1lZGlhO1xuICAgICAgICBjb25zdCBxcnlJbmZvID0gdGhpcy5xdWVyeU1hcFttcV0gfHwgeyBldmVudDogbnVsbCwgaW52ZXJ0ZWQ6IGZhbHNlIH07XG5cbiAgICAgICAgaWYgKCF0aGlzLmJwc1tpXS5tYXRjaGVzICE9PSAhcXJ5SW5mby5pbnZlcnRlZCkge1xuICAgICAgICAgIHJldHVybiBxcnlJbmZvLmV2ZW50O1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQGluaGVyaXRkb2NcbiAgICAgKi9cbiAgICBsaXN0ZW4oKSB7XG4gICAgICBpZiAoIXRoaXMuY2FsbGJhY2sgJiYgKCF0aGlzLmF1dG9MaXN0ZW4gfHwgdGhpcy5saXN0ZW5lcnMubGVuZ3RoKSkge1xuICAgICAgICB0aGlzLmNhbGxiYWNrID0gdGhpcy5fcnVuLmJpbmQodGhpcyk7XG4gICAgICAgIHRoaXMuYnBzLmZvckVhY2goYnAgPT4gYnAuYWRkTGlzdGVuZXIodGhpcy5jYWxsYmFjayksIHRoaXMpO1xuICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEBpbmhlcml0ZG9jXG4gICAgICovXG4gICAgaWdub3JlKCkge1xuICAgICAgaWYgKHRoaXMuY2FsbGJhY2spIHtcbiAgICAgICAgdGhpcy5icHMuZm9yRWFjaChicCA9PiBicC5yZW1vdmVMaXN0ZW5lcih0aGlzLmNhbGxiYWNrKSwgdGhpcyk7XG4gICAgICAgIGRlbGV0ZSB0aGlzLmNhbGxiYWNrO1xuICAgICAgfVxuICAgIH1cbiAgfTtcbn0pKERydXBhbC5Ub29sc2hlZCk7XG4iXX0=
