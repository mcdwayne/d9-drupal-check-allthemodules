/**
 * @file
 * Drupal+ Enhancements core functionality.
 */
(($, Drupal) => {
  'use strict';

  // Load jQuery On/Off Shim.
  if ($ && $.fn && !$.fn.on && $.fn.delegate) {
    Drupal.require('@plus/jquery.on.off.shim');
  }

  /**
   * @class Enhancements
   */
  class Enhancements extends Map {
    /**
     * Behavior attachment handler for enhancements.
     *
     * @see Drupal.attachBehaviors
     */
    attach() {
      this.forEach(/** @type {Enhancement} */ (enhancement) => {
        for (const s in enhancement.attachments) {
          if (enhancement.attachments.hasOwnProperty(s)) {
            enhancement.attachments[s].apply(enhancement, arguments);
          }
        }
      });
    }

    /**
     * Creates a user enhancement.
     *
     * @param {String} id
     *   The machine name of the user enhancement to create. If an existing
     *   namespaced (using dot notation) user enhancement exists, it will be
     *   used as the "parent" object that this new object is created from.
     *   This effectively allows enhancements to automatically inherit their
     *   parent's object, e.g. a user enhancement with the machine name:
     *   "parent.child.grandchild" would automatically inherit properties and
     *   methods from the "parent.child" and "parent" user enhancement objects,
     *   if they existed.
     * @param {Enhancement} [enhancement]
     *   The enhancement class.
     *
     * @return {Drupal.Enhancements.|Boolean<false>}
     *   A user enhancement instance or FALSE if unable to create the
     *   enhancement.
     */
    create(id, enhancement) {
      if (typeof id !== 'string') {
        return Drupal.fatal(Drupal.t('The first argument in Drupal.Enhancements.create() must be a string indicating the machine identifier of the user enhancement to create.'));
      }

      if (this.has(id)) {
        Drupal.warning(Drupal.t('An instance for the "@id" user enhancement already exists and cannot be recreated, returning existing instance. Use the Drupal.Enhancements.get() method to retrieve or use the Drupal.Enhancements.extend() methods to extend an existing instance.'), {
          '@id': id,
        });
        return this.get(id);
      }

      // Immediately return if ctor isn't a valid constructor function.
      if (!Drupal.isClass(enhancement)) {
        return Drupal.fatal(Drupal.t('You must provide an ES6 Class to create the user enhancement "@id": @ctor'), {
          '@id': id,
          '@ctor': enhancement,
        });
      }

      // Find any namespaced parent objects.
      const namespaces = id.split('.');
      let parent;
      while (namespaces.length) {
        parent = this.get(namespaces.join('.'), true);
        if (parent) {
          break;
        }
        namespaces.pop();
      }

      const instance = new enhancement(id, Drupal.settings && Drupal.settings.Enhancements && Drupal.settings.Enhancements[id] || {}, parent);

      // Ensure/override the following properties after any of the above
      // objects are merged in.
      instance.debug = this.debug;

      // Initialize the instance.
      instance.init();

      this.set(id, instance);

      return instance;
    }

    /**
     * Getter for debug property.
     *
     * @return {Boolean}
     *   TRUE or FALSE
     */
    get debug() {
      return !!Drupal.Storage.create('Drupal.Enhancements').get('debug');
    }

    /**
     * Setter for debug property.
     *
     * @param {Boolean} [value = true]
     *   Flag indicating whether to enable or disable debug mode.
     *
     * @return {Enhancements}
     */
    set debug(value) {
      if (value) {
        Drupal.Storage.create('Drupal.Enhancements').set('debug', true);
      }
      else {
        Drupal.Storage.create('Drupal.Enhancements').remove('debug');
      }
      this.forEach(/** @type {Enhancement} */ (enhancement) => {
        enhancement.debug = value;
      });
      return this;
    }

    /**
     * Behavior detachment handler for enhancements.
     *
     * @see Drupal.detachBehaviors
     */
    detach() {
      this.forEach(/** @type {Enhancement} */ (enhancement) => {
        for (const s in enhancement.detachments) {
          if (enhancement.detachments.hasOwnProperty(s)) {
            enhancement.detachments[s].apply(enhancement, arguments);
          }
        }
      });
    }

    /**
     * Retrieves a user enhancement instance.
     *
     * @param {String} id
     *   The machine name identifier of the user enhancement to retrieve.
     * @param {Boolean} [suppressError]
     *   Toggle determining whether or not to suppress any errors if the user
     *   enhancement does not exist.
     *
     * @return {Drupal.Enhancements.}
     */
    get(id, suppressError) {
      const enhancement = super.get(id) || undefined;
      if (!enhancement && !suppressError) {
        Drupal.error('The user enhancement "@id" does not exist. You must first create the user enhancement before you can retrieve it.', {
          '@id': id,
        });
      }
      return enhancement;
    }
  }

  // Create a new instance of Enhancements.
  Drupal.Enhancements = Drupal.behaviors.Enhancements = new Enhancements();
})(jQuery, Drupal);
