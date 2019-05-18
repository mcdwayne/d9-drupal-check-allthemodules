(function (Drupal, extend) {

  const isArray = a => typeof Array.isArray === 'function' ? Array.isArray(a) : Object.prototype.toString.call(a) === '[object Array]';
  const mapToObject = map => Object.assign(...[...map.entries()].map(([k, v]) => ({ [k]: v })));
  const setToObject = set => Object.assign(...[...set.values()].map((v,k) => ({[k]: v}) ));
  const throwError = message => setTimeout(() => {
    throw new Error(message);
  }, 0);

  /**
   * @class ArrayObject
   *
   * Since, in JavaScript, associative arrays are really just Objects, the
   * concept of an "array" with this class is not a 1:1 parity with its PHP
   * counterpart.
   *
   * This class primarily exists to provide an approximate replica of the
   * extended functionality that \Drupal\plus\Utility\ArrayObject provides
   * and to assist with other classes that may rely upon its functionality.
   *
   * This is not a 1:1 replica of the SPL \ArrayObject class. In fact, the only
   * methods that are copied from it are "count" and the various "offset*"
   * methods, minus the "offset" prefix (e.g. offsetGet => get,
   * offsetUnset => unset, etc).
   */
  class ArrayObject {

    /**
     * Constructs an ArrayObject class.
     *
     * @param {...Object|...Array} values
     *   (optional) The values used construct the ArrayObject instance with.
     *   The first object will set the "type" of object (e.g. Array or Object).
     */
    constructor(...values) {
      /**
       * The internal data Map object.
       *
       * @type {Map|Set}
       */
      this.data = isArray(values[0]) ? new Set() : new Map();

      // Merge in the values.
      this.merge(...values);
    }

    /**
     * Base flatten.
     *
     * @param {Array} array
     *   The array to iterate over.
     * @param {Boolean} shallow
     *   Flag indicating whether the array will only flatten a single level.
     * @param {Boolean} strict
     *   Flag indicating whether to only flatten arrays.
     *
     * @return array
     *   The flattened array.
     *
     * @see https://github.com/tajawal/lodash-php/blob/master/src/arrays/flatten.php
     */
    baseFlatten(array = [], shallow = false, strict = true) {
      let output = [];
      let k = 0;
      array.forEach(v => {
        if (isArray(v)) {
          if (!shallow) {
            v = this.baseFlatten(v, shallow, strict);
          }
          let i = 0;
          let len = v.length;
          while (i < len) {
            output[k++] = v[i++];
          }
        }
        else {
          if (!strict) {
            output[k++] = v;
          }
        }
      });
      return output;
    }

    /**
     * Converts an argument into a simple array or plain object.
     *
     * @param {Array|Object|ArrayObject} obj
     *   The object to convert.
     *
     * @return {Array|Object|null}
     *   An array or plain object clone, converted from other Object types. If
     *   object is not a valid type, NULL will be returned instead.
     */
    convertArgument(obj) {
      // Immediately return if there is nothing to merge.
      if (!obj || !Object.keys(obj).length) {
        return;
      }

      // Get data from an ArrayObject instance.
      if (obj instanceof ArrayObject) {
        obj = obj.value();
      }

      // Convert object to array.
      if (this.isArray() && !isArray(obj)) {
        obj = Object.values(obj);
      }
      // Convert array to object.
      else if (this.isObject() && isArray(obj)) {
        obj = setToObject(new Set(obj));
      }

      // At this point, the passed object should be valid to merge.
      if ((this.isArray() && !isArray(obj)) || (this.isObject() && typeof obj !== 'object')) {
        throwError('Passed argument must be either an array, an object or an instance of ArrayObject: ' + typeof obj);
        return null;
      }

      // Otherwise, return a clone of the object.
      return isArray(obj) ? [...obj] : {...obj};
    }

    /**
     * Converts multiple arguments into an array of simple objects.
     *
     * @param {Array|arguments} args
     *   An array of arguments, usually passed as func_get_args().
     * @param {Array} array
     *   An array to add the converted arguments to.
     *
     * @return array
     *   The converted array.
     */
    convertArguments(args = [], array = []) {
      return array.concat(args.forEach(this.convertArgument.bind(this)).filter(Boolean));
    }

    /**
     * Get the number of properties in the ArrayObject data.
     *
     * @return {Number}
     *   The number of properties in the ArrayObject data.
     */
    count() {
      return this.data.size;
    }

    /**
     * Creates a new ArrayObject instance.
     *
     * @param {...Array|...Object} args
     *   (optional) The values used construct the ArrayObject instance with.
     *   The first object will set the "type" of object (e.g. Array or Object).
     *
     * @return ArrayObject
     *   A new ArrayObject instance.
     */
    static create(...args) {
      return new this(...args);
    }

    /**
     * Returns whether the requested key exists.
     *
     * @param {String} key
     *   The key to check.
     *
     * @return {Boolean}
     *   TRUE or FALSE
     */
    exists(key) {
      return this.data.has(key)
    }

    /**
     * Flattens a multidimensional array.
     *
     * @param {Boolean} shallow
     *   Flag indicating whether the array will only flatten a single level.
     *
     * @return ArrayObject
     */
    flatten(shallow = false) {
      return this.replace(this.baseFlatten(Object.values(this.value()), shallow, false));
    }

    /**
     * Retrieves the value for a specified key.
     *
     * @param {String} key
     *   The key to retrieve.
     * @param {*} [defaultValue = null]
     *   The default value to return if key is not set, defaults to NULL.
     * @param {Boolean} [setIfNotExists = true]
     *   Flag indicating whether to set the value to the default value if it
     *   doesn't yet exist. If FALSE, it will only return defaultValue, not set
     *   it.
     *
     * @return {*}
     *   The value for the specified key or defaultValue if not set.
     */
    get(key, defaultValue = null, setIfNotExists = true) {
      if (!this.exists(key)) {
        if (!setIfNotExists) {
          return defaultValue;
        }
        this.set(key, defaultValue);
      }
      return this.exists(key) ? this.data.get(key) : defaultValue;
    }

    /**
     * Indicates whether the underlying data is an array.
     *
     * @return {Boolean}
     *   TRUE or FALSE
     */
    isArray() {
      return this.data instanceof Set;
    }

    /**
     * Indicates whether the underlying data is a plain object.
     *
     * @return {Boolean}
     *   TRUE or FALSE
     */
    isObject() {
      return !this.isArray();
    }

    /**
     * Applies the callback to the elements of the array.
     *
     * @param {Function} callback
     *   Callback function to run for each item in the ArrayObject data.
     *
     * @return ArrayObject
     */
    map(callback) {
      this.data.forEach(callback);
      return this;
    }

    /**
     * Merges in objects.
     *
     * @param {...Object|...Array} values
     *   (optional) The values to merge in.
     *
     * @return ArrayObject
     */
    merge(...values) {
      const args = this.convertArguments(values, [this.value()]);
      const data = isArray(args[0]) ? new Set([...args]) : new Map([...args.map(o => Object.entries(o))]);
      return this.replace(data);
    }

    /**
     * Merge in objects, recursively.
     *
     * @param {...Object|...Array} values
     *   (optional) The values to merge in.
     *
     * @return ArrayObject
     */
    mergeDeep(...values) {
      let value = this.value();
      const args = this.convertArguments(values);
      args.forEach(o => extend(true, value, o));
      const data = isArray(value[0]) ? new Set([...value]) : new Map([...value.map(o => Object.entries(o))]);
      return this.replace(data);
    }

    /**
     * Replaces the ArrayObject data with new value.
     *
     * @param {Array|Object} value
     *   The value used to replace data.
     * @param {Object} [previous]
     *   (optional) A parameter, passed by reference, used to capture any
     *   previously set data. Note: this is not quite like the companion PHP
     *   method since JavaScript doesn't "pass-by-reference". You will need to
     *   pass an empty object and then the previous data will be set to the
     *   "data" property on it.
     *
     * @return ArrayObject
     */
    replace(value, previous = {}) {
      previous.data = this.data;

      // If value is already a Map or Set object, just use it.
      if (value instanceof Map || value instanceof Set) {
        this.data = value;
      }
      // Otherwise, convert the value and store the new data.
      else {
        value = this.convertArgument(value);
        this.data = isArray(value) ? new Set(value || []) : new Map(Object.entries(value || {}));
      }

      return this;
    }

    /**
     * Sets the value at the specified index to newval.
     *
     * @param {*} key
     *   The index being set.
     * @param {*} value
     *   The new value for key.
     *
     * @return ArrayObject
     */
    set(key, value) {
      if (this.isObject()) {
        this.data.set(key, value);
      }
      return this;
    }

    /**
     * Unsets the value for the specified key.
     *
     * @param {*} key
     *   The key to unset.
     *
     * @return ArrayObject
     */
    unset(key) {
      this.data.delete(key);
      return this;
    }

    /**
     * Creates a copy of the ArrayObject data.
     *
     * Note: this is the equivalent of the getArrayCopy method on the PHP side.
     *
     * @return {Object}
     *   A copy of the array object data.
     */
    value() {
      if (this.isArray()) {
        return [...this.data];
      }
      return mapToObject(this.data);
    }

  }

  Drupal.ArrayObject = Drupal.ArrayObject || ArrayObject;

})(window.Drupal, window.extend);
