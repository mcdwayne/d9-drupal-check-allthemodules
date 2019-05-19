

((Toolshed) => {
  Toolshed.EventListener = class {
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
    constructor(elem, eventName, options) {
      options = options || {}; // options can be left blank.

      this.elem = elem;
      this.event = eventName;
      this.method = options.method || `on${Toolshed.pascalCase(eventName)}`;
      this.autoListen = options.autoListen || false;
      this.listeners = [];

      // Check and properly organize the event options to be used later.
      if (options.debounce) {
        this.debounce = (typeof options.debounce === 'boolean') ? 100 : options.debounce;
      }

      // Allow for addEventListener options as described here
      // https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
      // I am also employing the https://github.com/WICG/EventListenerOptions
      // as a polyfill, but support will not be available for IE8 and earlier.
      this.eventOpts = {
        capture: options.capture || false,
        passive: options.passive || false,
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
    _run(event) {
      this.listeners.forEach(listener => listener[this.method](event), this);
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
    _callEvent(method, event) {
      this.listeners.forEach((listener) => {
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
    _validateListener(listener) {
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
    trigger(event) {
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
    listen() {
      if (!this.callback && (!this.autoListen || this.listeners.length)) {
        this.callback = (this.debounce && this.debounce > 0 && Drupal.debounce)
          ? Drupal.debounce(this._run.bind(this), this.debounce) : this._run.bind(this);

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
    ignore() {
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
    add(listener, atPos) {
      if (this._validateListener(listener)) {
        // Ensure that all existing references to this event are removed.
        // Prevents the event from being called more than once unintentionally.
        this.remove(listener);

        if (atPos !== null && atPos >= 0) this.listeners.splice(atPos - 1, 0, listener);
        else this.listeners.push(listener);

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
    addBefore(listener, before) {
      let pos = 0;
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
    addAfter(listener, after) {
      let pos = null;
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
    remove(listener) {
      let pos = this.listeners.indexOf(listener);
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
    destroy() {
      this.ignore();
    }
  };

  /**
   * Event listener for media query listeners.
   */
  Toolshed.MediaQueryListener = class extends Toolshed.EventListener {
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
    constructor(breakpoints, options) {
      super(null, 'mediaQuery', options);

      this.mode = null;
      this.bps = [];
      this.queryMap = {};

      breakpoints.forEach((bp) => {
        const mql = window.matchMedia(bp.mediaQuery);
        const event = Toolshed.pascalCase(bp.event);

        this.bps.push(mql);
        this.queryMap[bp.mediaQuery] = {
          event,
          inverted: bp.inverted || false,
        };
      }, this);
    }

    /**
     * Alter the current breakpoint mode, and trigger the related events.
     *
     * @param {string} newMode
     *   The name of the breakpoint mode to trigger.
     */
    _changeMode(newMode) {
      const oldMode = this.mode;

      // If the mode changed, trigger the appropriate action.
      if (newMode !== oldMode) {
        if (oldMode) this._callEvent(`off${oldMode}`);
        if (newMode) this._callEvent(`on${newMode}`);

        this.mode = newMode;
      }
    }

    /**
     * @inheritdoc
     */
    _run(mql) {
      const qryInfo = this.queryMap[mql.media] || { event: null, inverted: false };
      const mode = (!mql.matches !== !qryInfo.inverted) ? qryInfo.event : this.checkBreakpoints();
      this._changeMode(mode);
    }

    /**
     * @inheritdoc
     */
    _validateListener() { // eslint-disable-line class-methods-use-this
      return true;
    }

    /**
     * Check the registered breakpoints in order to see which one is active.
     *
     * @return {string|null}
     *   The query mapped event if a matching breakpoint is found, otherwise
     *   return NULL to mean no event.
     */
    checkBreakpoints() {
      for (let i = 0; i < this.bps.length; ++i) {
        const mq = this.bps[i].media;
        const qryInfo = this.queryMap[mq] || { event: null, inverted: false };

        if (!this.bps[i].matches !== !qryInfo.inverted) {
          return qryInfo.event;
        }
      }
    }

    /**
     * @inheritdoc
     */
    listen() {
      if (!this.callback && (!this.autoListen || this.listeners.length)) {
        this.callback = this._run.bind(this);
        this.bps.forEach(bp => bp.addListener(this.callback), this);
      }
    }

    /**
     * @inheritdoc
     */
    ignore() {
      if (this.callback) {
        this.bps.forEach(bp => bp.removeListener(this.callback), this);
        delete this.callback;
      }
    }
  };
})(Drupal.Toolshed);
