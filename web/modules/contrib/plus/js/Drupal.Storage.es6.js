((Drupal, JSON) => {
  'use strict';

  /**
   * Memory storage.
   *
   * This is only used as a temporary stop gap measure to prevent code that
   * relies on a valid Storage API being available.
   */
  class MemoryStorage extends Map {
    get length() {
      return this.size;
    }

    getItem(key) {
      return this.get(key);
    }

    removeItem(key) {
      this.delete(key);
    }

    setItem(key, value) {
      if (value !== undefined) {
        this.set(key, String(value));
      }
    }
  }

  // Expose global memoryStorage.
  if (window.memoryStorage === undefined) {
    window.memoryStorage = new MemoryStorage();
  }

  /**
   * @class Storage
   *
   * HTML5 Storage API for Drupal.
   *
   * @param {String|null} [prefix = "Drupal"]
   *   A prefix to use for all keys, defaults to "Drupal" (unless set as null).
   * @param {String} [type = "local"]
   *   The bin name to load, defaults to "local". Can be one of:
   *   - local
   *   - memory
   *   - session
   *
   * @see https://www.drupal.org/node/65578
   */
  class Storage {
    constructor(prefix = 'Drupal', type = 'local') {
      /**
       * The prefix to use for every key.
       *
       * @type {String}
       */
      this.prefix = prefix;

      /**
       * A Storage API based object.
       *
       * @type {localStorage|sessionStorage|MemoryStorage}
       */
      this.storage = window[`${type.toLowerCase().replace(/storage$/, '')}Storage`];

      if (!this.storage) {
        Drupal.throwError(Drupal.t('Unsupported storage: "@type". Defaulting to global "memory" storage. Warning: storage will not be persistent.', { '@type': type }));
        this.storage = window.memoryStorage;
      }
    }

    /**
     * Loads data from client-side storage.
     *
     * @param {String} key
     *   The key name to load stored data from. Automatically prefixed with
     *   "Drupal.".
     *
     * @return {*}
     *   The data stored or undefined if not set.
     *
     * @see Storage.set
     */
    get(key) {
      const item = this.storage.getItem(this.getPrefixedKey(key));
      return item === undefined ? undefined : JSON.parse(item);
    }

    /**
     * Retrieves the prefixed key.
     *
     * @param {String} key
     *   The key, un-prefixed.
     *
     * @return {String}
     */
    getPrefixedKey(key) {
      // Immediately return the key if there is no prefix.
      if (!this.prefix) {
        return key;
      }

      // Escape any characters in the prefix.
      const prefix = (`${this.prefix}`).replace(/[.?*+^$[\]\\(){}|-]/g, '\\$&');

      // Remove any prefix from the key and turn it into an array.
      const keys = key.replace(new RegExp(`^${prefix}\\.?`), '').split('.');

      // Prepend the prefix.
      keys.unshift(this.prefix);

      // Join them back together.
      return keys.join('.');
    }

    /**
     * Stores data on the client-side.
     *
     * @param key
     *   The key name to store data under. Automatically prefixed with
     *   "Drupal.". Should be further namespaced by module; e.g., for
     *   "Drupal.module.settingName" you pass "module.settingName".
     * @param value
     *   The value to store.
     *
     * @return {Storage}
     *
     * @see Storage.get
     */
    set(key, value) {
      if (value !== undefined) {
        try {
          this.storage.setItem(this.getPrefixedKey(key), JSON.stringify(value));
        }
        catch (e) {
          Drupal.throwError(e);
        }
      }
      return this;
    }

    /**
     * Delete data from client-side storage.
     *
     * Called 'remove', since 'delete' is a reserved keyword.
     *
     * @param key
     *   The key name to delete. Automatically prefixed with "Drupal.".
     *
     * @return {Storage}
     *
     * @see Storage.set
     */
    remove(key) {
      key = this.getPrefixedKey(key);
      this.storage.removeItem(key);
      return this;
    }

    /**
     * Creates a new Storage instance.
     *
     * @param {String|null} [prefix = "Drupal."]
     *   A prefix to use for all keys, defaults to "Drupal." (unless set as
     *   null).
     * @param {String} [type = "local"]
     *   The bin name to load, defaults to "local". Can be one of:
     *   - local
     *   - memory
     *   - session
     *
     * @return {Storage}
     */
    static create(prefix, type) {
      return new this(prefix, type);
    }
  }

  /**
   * Export Storage to Drupal.
   *
   * @type {Storage}
   */
  Drupal.Storage = Storage;
})(window.Drupal, window.JSON);
