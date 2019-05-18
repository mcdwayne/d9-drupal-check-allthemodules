/**
 * @file
 * Drupal+ Asynchronous Queue.
 */
((Drupal) => {
  'use strict';

  /**
   * @class AsyncQueue
   *
   * @param {Object|Function[]|Function} obj
   *   The object to process. May be one of the following:
   *   - {Object} An object that contains additional objects where a method on
   *     each of the objects will be invoked. This means you must provide the
   *     method name to use each time .process() is invoked.
   *   - {Array} An array of functions to invoke.
   *   - {Function} A callback function that will be invoked every time an item
   *     from the queue is to be retrieved. This is particularly useful if you
   *     have a dynamic need to populate the queue.
   * @param {Function} [callback]
   *   A callback used to process the result returned from the item that was
   *   just processed. This is primarily useful as a way to intercept when an
   *   item's processing has failed and to return an error message instead of
   *   the original result.
   */
  class AsyncQueue {
    constructor(obj, callback) {
      this.callback = callback;
      this.errors = [];
      this.errorCallbacks = [];
      this.object = obj;
      this.processed = [];
    }

    /**
     * Create a new asynchronous queue.
     *
     * @param {Object|Function[]|Function} obj
     *   The object to process. May be one of the following:
     *   - {Object} An object that contains additional objects where a method
     *   on
     *     each of the objects will be invoked. This means you must provide the
     *     method name to use each time .process() is invoked.
     *   - {Array} An array of functions to invoke.
     *   - {Function} A callback function that will be invoked every time an
     *   item from the queue is to be retrieved. This is particularly useful if
     *   you have a dynamic need to populate the queue.
     * @param {Function} [callback]
     *   A callback used to process the result returned from the item that was
     *   just processed. This is primarily useful as a way to intercept when an
     *   item's processing has failed and to return an error message instead of
     *   the original result.
     *
     * @return {AsyncQueue}
     *   The current instance.
     */
    static create(obj, callback) {
      return new this(obj, callback);
    }

    /**
     * Destroys the queue.
     *
     * @return {AsyncQueue}
     *   The current instance.
     */
    destroy() {
      this.callback = null;
      this.errors = [];
      this.errorCallbacks = [];
      this.object = null;
      this.processed = [];
      return this;
    }

    /**
     * Provides a method for adding a callback handler for error messages.
     *
     * @param {Function} callback
     *   The callback handler to add.
     *
     * @return {AsyncQueue}
     *   The current instance.
     */
    error(callback) {
      this.errorCallbacks.push(callback);
      return this;
    }

    /**
     * Retrieves the current errors.
     *
     * @param {Boolean} [reset = true]
     *   Flag indicating whether to reset the errors after retrieving them.
     *
     * @return {Error[]}
     *   The errors.
     */
    getErrors(reset) {
      const errors = [...this.errors];
      if (reset === undefined || reset) {
        this.errors = [];
      }
      return errors;
    }

    /**
     * Retrieves the items from the stored object.
     *
     * @return {Object|Function}
     *   An object or function.
     */
    getItems() {
      let obj = this.object;

      // Handle callbacks used to provide dynamic items.
      if (typeof obj === 'function') {
        obj = obj();
      }

      // Convert arrays into an object.
      if (Array.isArray(obj)) {
        return obj.reduce((o, v, k) => {
          o[k] = v;
          return o;
        }, {});
      }

      return obj;
    }

    /**
     * Retrieves the next item.
     *
     * @return {Object|Function}
     *   An object or function.
     */
    getNextItem() {
      const items = this.getItems();

      // Immediately return if items is a function.
      if (typeof items === 'function') {
        items.__asyncQueueId__ = items.name || items.constructor.name || 'anonymous function';
        return items;
      }

      // Otherwise, return the first behavior not yet processed.
      const itemKeys = Object.keys(items);
      for (let i = 0, l = itemKeys.length; i < l; i++) {
        const key = itemKeys[i];
        if (this.processed.indexOf(key) === -1) {
          const item = items[key];
          item.__asyncQueueId__ = key;
          return item;
        }
      }
    }

    /**
     * Processes the queue.
     *
     * @return {AsyncQueue}
     *   The current instance.
     */
    process(...args) {
      const item = this.getNextItem();

      // Done handler.
      const done = () => {
        this.processed = [];
        const errors = this.getErrors();
        if (errors[0]) {
          this.errorCallbacks.forEach(callback => callback(errors));
        }
        return this;
      };

      // Return if there are no more items.
      if (!item) {
        return done();
      }

      let fn;
      let method;
      const originalArgs = [...args];
      if (typeof item === 'function') {
        fn = item;
      }
      else if (typeof item === 'object') {
        method = args.shift();
        if (typeof method !== 'string') {
          this.errors.push(new Error(Drupal.t('The item being processed is an object. The first argument passed to AsyncQueue.process() must be a stringed method name to invoke on the item: @method', {
            '@method': method,
          })));
          return done();
        }
        fn = item[method];
      }

      let async = false;

      // Update the internal status object and run the next behavior.
      const complete = (success) => {
        let error = null;

        // Allow a callback to process the result.
        if (typeof this.callback === 'function') {
          success = this.callback.call(item, success);
        }

        // False was passed, behavior failed generically.
        if (success === false) {
          error = new Error(Drupal.t('The processed item "@id" failed.', {
            '@id': item.__asyncQueueId__,
          }));
        }
        // An error object was passed, so the behavior failed specifically.
        else if (success instanceof Error || {}.toString.call(success) === '[object Error]') {
          error = success;
          success = false;
        }
        // Behavior succeeded.
        else {
          success = true;
        }

        // If behavior failed, call error handler.
        if (!success && error) {
          this.errors.push(error);
        }

        // Remove the custom async method this queue generated.
        delete item.async;

        // Restore any previously saved async property/method.
        if (item.__asyncQueueAsync__ !== undefined) {
          item.async = item.__asyncQueueAsync__;
        }

        // Indicate that the behavior has been executed, regardless of success.
        this.processed.push(item.__asyncQueueId__);

        // Remove unnecessary properties that were added to the behavior.
        delete item.__asyncQueueAsync__;
        delete item.__asyncQueueId__;
        delete item.__asyncQueueMethod__;
        delete item.__asyncQueueArguments__;

        // Invoke the next behavior.
        Drupal.tick(() => this.process(...originalArgs));
      };

      // Save any existing async property/method.
      item.__asyncQueueAsync__ = item.async;
      item.__asyncQueueMethod__ = method;
      item.__asyncQueueArguments__ = args;

      item.async = () => {
        async = true;
        let once = 0;
        return (success) => {
          if (once === 0) {
            once = 1;
            Drupal.tick(() => complete(success));
          }
        };
      };

      try {
        let success;
        if (typeof fn === 'function') {
          success = fn.apply(item, args);
        }
        if (!async) {
          complete(success);
        }
      }
      catch (err) {
        complete(err);
      }
    }
  }

  /**
   * Export to Drupal.
   *
   * @type {AsyncQueue}
   */
  Drupal.AsyncQueue = AsyncQueue;
})(window.Drupal);
