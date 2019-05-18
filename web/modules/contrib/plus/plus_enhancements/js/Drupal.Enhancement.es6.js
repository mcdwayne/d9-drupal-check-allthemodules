/**
 * @file
 * Drupal+ Enhancement base class.
 */
(($, Drupal) => {
  'use strict';

  // Private properties.
  const _$element = new WeakMap();
  const _$wrapper = new WeakMap();
  const _attachments = new WeakMap();
  const _deferred = new WeakMap();
  const _detachments = new WeakMap();
  const _debug = new WeakMap();
  const _id = new WeakMap();
  const _initHandlers = new WeakMap();
  const _parent = new WeakMap();
  const _settings = new WeakMap();
  const _initialized = new WeakMap();
  const _storage = new WeakMap();

  /**
   * @class Enhancement
   */
  class Enhancement {

    /**
     * Constructor.
     *
     * @param {String} id
     * @param {Object} [settings = {}]
     * @param {Enhancement} [parent = null]
     */
    constructor(id, settings = {}, parent = null) {
      // Set private properties.
      _debug.set(this, false);
      _id.set(this, id);
      _initialized.set(this, false);
      _parent.set(this, parent);
      _settings.set(this, settings);
      _storage.set(this, Drupal.Storage.create('Drupal.Enhancement.' + id));

      /**
       * The element for this enhancement.
       *
       * @type {jQuery}
       */
      this.$element = Drupal.$noop;

      /**
       * The wrapper around the element for this enhancement.
       *
       * @type {jQuery}
       */
      this.$wrapper = Drupal.$noop;

      /**
       * The attachments object for this enhancement.
       *
       * @type {Object}
       */
      this.attachments = {};

      /**
       * Stores any deferred events.
       *
       * @type {Object}
       */
      this.deferred = {};

      /**
       * The detachments object for this enhancement.
       *
       * @type {Object}
       */
      this.detachments = {};

      // Reference custom parent properties that this instance doesn't have.
      if (parent) {
        this.extend(parent);
      }
    }

    get $element() {
      return _$element.get(this);
    }

    set $element(value) {
      if (!(value instanceof $)) {
        return Drupal.fatal(Drupal.t('A user enhancement $element must be a jQuery object.'));
      }
      return _$element.set(this, value);
    }

    get $wrapper() {
      return _$wrapper.get(this);
    }

    set $wrapper(value) {
      if (!(value instanceof $)) {
        return Drupal.fatal(Drupal.t('A user enhancement $wrapper must be a jQuery object.'));
      }
      return _$wrapper.set(this, value);
    }

    get attachments() {
      return _attachments.get(this);
    }

    set attachments(value) {
      return _attachments.set(this, value);
    }

    get deferred() {
      return _deferred.get(this);
    }

    set deferred(value) {
      return _deferred.set(this, value);
    }

    get detachments() {
      return _detachments.get(this);
    }

    set detachments(value) {
      return _detachments.set(this, value);
    }

    /**
     * Indicates whether debug mode is enabled on the user enhancement.
     *
     * @return {Boolean}
     */
    get debug() {
      return _debug.get(this);
    }

    /**
     * Setter for debug property.
     *
     * @param {Boolean} [value = true]
     *   Flag indicating whether to enable or disable debug mode.
     */
    set debug(value) {
      _debug.set(this, Boolean(value));
    }

    /**
     * The default settings for the user enhancement.
     *
     * @returns {Object}
     */
    get defaultSettings() {
      return {};
    }

    /**
     * The machine name identifier of the user enhancement.
     *
     * @return {String}
     */
    get id() {
      return _id.get(this);
    }

    /**
     * Indicates whether the user enhancement has been initialized.
     *
     * @return {Boolean}
     */
    get initialized() {
      return _initialized.get(this);
    }

    get parent() {
      return _parent.get(this);
    }

    /**
     * This settings for this user enhancement.
     *
     * @return {Object}
     */
    get settings() {
      const parent = this.parent;
      return $.extend(true, parent && parent.settings, this.defaultSettings, _settings.get(this));
    }

    /**
     * The Storage object for this user enhancement.
     *
     * @return {Drupal.Storage}
     */
    get storage() {
      return _storage.get(this);
    }

    /**
     * Creates an attachment behavior for the user enhancement.
     *
     * @param {string} selectors
     *   The selector(s) on which to bind this behavior.
     * @param {Function} [callback]
     *   Optional. A callback function to implement when the behavior is
     *   attached to the element as defined by the selector.
     *
     * @see Drupal.attachBehaviors
     *
     * @chainable
     *
     * @return {Enhancement}
     */
    attach(selectors, callback) {
      this.attachments[selectors] = (context, settings) => {
        // Only attach behaviors that are not already attached (similar to
        // $.once).
        const $selectors = $(context).find(selectors).filter(() => {
          return !$(this).data('Drupal.Enhancement.' + this.id);
        });
        if ($selectors[0]) {
          this.$selectors = $selectors;
          this.__args__ = arguments;
          this.$selectors.data('Drupal.Enhancement.' + this.id, this);
          callback.apply(this, [this.$selectors, this.settings]);
          delete this.__args__;
        }
      };
      return this;
    }

    /**
     * Attaches defined elements to the DOM and creates an attachment
     * behavior.
     *
     * @param {String} method
     *   The method on which to invoke on the selectors to attach the
     *   elements.
     * @param {string} selectors
     *   The selector(s) on which to bind this behavior.
     * @param {Function} [callback]
     *   Optional. A callback function to implement when the behavior is
     *   attached to the element as defined by the selector.
     *
     * @chainable
     *
     * @return {Enhancement}
     */
    attachElements(method, selectors, callback) {
      this.attach(selectors, ($selectors) => {
        const parts = (method + ':*').split(':');
        const filter = parts[1] === '*' ? parts[1] : ':' + parts[1];
        method = parts[0];
        if (this.$wrapper[0]) {
          this.$wrapper.append(this.$element);
          $selectors.filter(filter)[method](this.$wrapper);
        }
        else {
          $selectors.filter(filter)[method](this.$element);
        }
        if (callback) {
          callback.apply(this, arguments);
        }
      });
      return this;
    }

    /**
     * Creates an detachment behavior for the user enhancement.
     *
     * @param {string} selectors
     *   The selector(s) on which to bind this behavior.
     * @param {Function} [callback]
     *   Optional. A callback function to implement when the behavior is
     *   attached to the element as defined by the selector.
     *
     * @see Drupal.detachBehaviors
     *
     * @chainable
     *
     * @return {Enhancement}
     */
    detach(selectors, callback) {
      this.detachments[selectors] = (context, settings, trigger) => {
        const $selectors = $(context).find(selectors);
        if ($selectors[0]) {
          this.__args__ = arguments;
          this.$selectors = $selectors;
          callback.apply(this, [this.$selectors, this.settings]);
          this.$selectors.removeData('Drupal.Enhancement.' + this.id);
          delete this.__args__;
        }
      };
      return this;
    }

    /**
     * Detaches defined elements from the DOM and creates a detachment
     * behavior.
     *
     * @param {string} selectors
     *   The selector(s) on which to bind this behavior.
     * @param {Function} [callback]
     *   Optional. A callback function to implement when the behavior is
     *   attached to the element as defined by the selector.
     *
     * @chainable
     *
     * @return {Enhancement}
     */
    detachElements(selectors, callback) {
      this.detach(selectors, () => {
        this.$element.remove();
        this.$wrapper.remove();
        if (callback) {
          callback.apply(this, arguments);
        }
      });
      return this;
    }

    /**
     * Displays an error message, if debugging is enabled.
     *
     * @param {String} message
     *   The message to display.
     * @param {Object} [args]
     *   An arguments to use in message.
     */
    error(message, args) {
      Drupal.error(message, args);
    }

    /**
     * Extends a user enhancement.
     *
     * @param {...Object} args
     *   The object(s) to extend from. If the first parameter is a boolean, it
     *   will be treated as a flag that determines whether only shallow
     *   properties are extended from any passed objects.
     *
     * @return {Enhancement}
     */
    extend(...args) {
      const deep = args[0] === true || args[0] === false ? args.shift() : false;
      args.forEach(obj => {
        Object.keys(obj).forEach(key => {
          if (!deep && !obj.hasOwnProperty(key)) {
            return;
          }
          this[key] = obj[key];
        });
      });
      return this;
    }

    /**
     * Provide a helper method for displaying when something is went wrong.
     *
     * @param {String} message
     *   The message to display.
     * @param {Object} [args]
     *   An arguments to use in message.
     *
     * @return {Boolean<false>}
     *   Always returns FALSE.
     */
    fatal(message, args) {
      return Drupal.fatal(message, args);
    }

    /**
     * Retrieves a setting value for this user enhancement.
     *
     * @param {String} name
     *   The machine name of a setting to retrieve.
     * @param {*|Function} [defaultValue]
     *   The default value to use if the setting does not exist. A function
     *   can also be passed and it will be invoked in the even complex logic
     *   needs to occur to determine the value (e.g. parsing the DOM).
     *
     * @return {*}
     *   The setting value, if any.
     */
    getSetting(name, defaultValue) {
      const settings = this.settings;
      if (settings[name] === undefined || settings[name] === null) {
        return typeof defaultValue === 'function' ? defaultValue.call(this) : defaultValue;
      }
      return settings[name];
    }

    /**
     * Displays an informative message, if debugging is enabled.
     *
     * @param {String} message
     *   The message to display.
     * @param {Object} [args]
     *   An arguments to use in message.
     */
    info(message, args) {
      if (this.debug) {
        Drupal.info(message, args);
      }
    }

    /**
     * Invoked upon the initial creation of the user enhancement.
     *
     * This is for non DOM related setup tasks. If you need to traverse DOM
     * elements, use the "ready" method instead.
     *
     * @param {Function} [callback = null]
     *   Optional. A callback function to invoke when the user enhancement is
     *   initially created. If not callback is provided, then all currently
     *   registered initialization handlers will be invoked and the user
     *   enhancement will be flagged as "initialized".
     *
     * @chainable
     *
     * @return {Enhancement}
     *
     * @see Enhancement.ready
     */
    init(callback = null) {
      // Immediate return if already initialized.
      if (_initialized.get(this)) {
        return this;
      }
      let handlers = _initHandlers.get(this) || new Set();
      if (callback) {
        handlers.add(callback);
      }
      else {
        handlers.forEach(handler => {
          handler.call(this);
        });
        handlers.clear();
        _initialized.set(this, true);
      }
      _initHandlers.set(this, handlers);
      return this;
    }

    /**
     * Namespaces an Event type by appending the name of the user enhancement.
     *
     * @param {String} [type]
     *   The event type.
     *
     * @return {Array}
     *   The namespaced events.
     */
    namespaceEventType(type) {
      type = type || '';
      const types = type.split(' ');
      types.forEach((type, i) => {
        let namespaced = type.split('.');
        namespaced.push(this.id);
        types[i] = namespaced.join('.');
      });
      return types;
    }

    /**
     * Unbinds any events from the Enhancement.$element object.
     *
     * @param {String} type
     *   The event type to unbind.
     *
     * @chainable
     *
     * @return {Enhancement}
     */
    off(type) {
      const namespaced = this.namespaceEventType(type);

      // Remove any attributes from the element.
      if (this.$element[0]) {
        const attributes = this.$element[0].attributes;
        for (let i in attributes) {
          if (!attributes.hasOwnProperty(i)) {
            continue;
          }
          let name = attributes[i].name;

          // Ignore any attributes that don't start with the following.
          if (!/^data-user-enhancement-/.test(name)) {
            continue;
          }

          // Remove either a specific event type namespaced data attribute, if
          // one was specified, or all namespaced types.
          if (new RegExp(type ? namespaced.join('-').replace(/\./g, '-') : this.id.replace(/\./g, '-')).test(name)) {
            attributes.removeNamedItem(name);
          }
        }
      }

      // Remove the DOM handler.
      Drupal.$document.off(namespaced.join(' '));
      return this;
    }

    /**
     * Binds and event handler on the Enhancement.$element object.
     *
     * @param {String} type
     *   The event type to bind.
     * @param {Function} handler
     *   The event handler callback.
     *
     * @chainable
     *
     * @return {Enhancement}
     */
    on(type, handler) {
      const namespaced = this.namespaceEventType(type);
      const dataAttribute = 'data-user-enhancement-' + namespaced.join('-').replace(/\./g, '-');
      this.$element.attr(dataAttribute, 'true');
      Drupal.$document.on(namespaced.join(' '), '[' + dataAttribute + ']', handler.bind(this));
      return this;
    }

    /**
     * Parses a function's arguments into a proper array.
     *
     * @param {Array} args
     *   The function's arguments.
     * @param {Boolean} [bind=true]
     *   Toggle determining whether or not any functions passed should be
     *   properly bound to this enhancement. Defaults to true.
     *
     * @return {Array}
     *   The parsed arguments.
     */
    parseArguments(args, bind) {
      args = [...args];
      if (bind === void 0 || bind) {
        args.forEach((arg, i) => {
          if (typeof arg === 'function') {
            args[i] = arg.bind(this);
          }
        });
      }
      return args;
    }

    /**
     * Prepend a special "DOM ready" attachment.
     *
     * @param {Function} callback
     *   The callback to invoke when the DOM is ready.
     *
     * @chainable
     *
     * @return {Enhancement}
     */
    ready(callback) {
      let o = {};
      o[this.random('__ready__')] = callback;
      this.attachments = {...o, ...this.attachments};
      return this;
    }

    /**
     * Sets a setting for this user enhancement.
     *
     * @param {String|Object} name
     *   The machine name of a setting to set. Optionally, an object can be
     *   passed instead to set multiple key/value settings.
     * @param {*} [value]
     *   The value to set.
     *
     * @chainable
     *
     * @return {Enhancement}
     */
    setSetting(name, value) {
      const settings = _settings.get(this);

      let obj = {...typeof name === 'object' ? name : {}};
      if (typeof name === 'string') {
        obj[name] = value;
      }

      Object.keys(obj).forEach(key => {
        settings[key] = Drupal.typeCast(this.getSetting(key), obj[key]);
      });

      _settings.set(this, settings);

      return this;
    }

    /**
     * Triggers an event on a defined user enhancement $element.
     *
     * @chainable
     *
     * @return {Enhancement}
     */
    trigger(...args) {
      this.$element.trigger.apply(this.$element, this.parseArguments(args));
      return this;
    }

    /**
     * Displays a warning message, if debugging is enabled.
     *
     * @param {String} message
     *   The message to display.
     * @param {Object} [args]
     *   An arguments to use in message.
     */
    warning(message, args) {
      if (this.debug) {
        Drupal.warn(message, args);
      }
    }

  }

  // Export the Enhancement class to the Drupal namespace.
  Drupal.Enhancement = Enhancement;

})(jQuery, Drupal);
