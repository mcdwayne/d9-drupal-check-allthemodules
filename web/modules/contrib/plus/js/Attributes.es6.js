(function (Drupal, ArrayObject, Html, extend, isPlainObject) {

  const isArray = (a) => typeof Array.isArray === 'function' ? Array.isArray(a) : Object.prototype.toString.call(a) === '[object Array]';
  const setToObject = set => Object.assign(...[...set.values()].map((v,k) => ({[k]: v}) ));

  /**
   * @class Attributes
   *
   * Modifies attributes.
   */
  class Attributes extends ArrayObject {

    /**
     * Constructor.
     *
     * @param {Object|Attributes} attributes
     *   An object to initialize attributes with.
     */
    constructor(attributes = {}) {
      super({ 'class': new Set()});
      this.merge(attributes);
    }

    /**
     * {@inheritdoc}
     */
    convertArgument(obj) {
      // Immediately return if there is nothing to merge.
      if (!obj || !Object.keys(obj).length) {
        return;
      }

      // Get DOM element from a jQuery instance.
      if ((window.jQuery !== void 0 && obj instanceof window.jQuery) || (window.$ !== void 0 && obj instanceof window.$)) {
        obj = obj[0];
      }

      // Get attributes from a DOM node element.
      if (obj instanceof window.Node) {
        obj = [...obj.attributes].reduce((o, a) => {
          obj[a.name] = a.value;
          return o;
        }, {});
      }
      // Get data from an ArrayObject instance.
      else if (obj instanceof ArrayObject) {
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
     * Renders the attributes object as a string to inject into an HTML element.
     *
     * @return {String}
     *   A rendered string suitable for inclusion in HTML markup.
     */
    toString() {
      const data = this.value();
      let output = '';
      const checkPlain = function (str) {
        return str && str.toString().replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;') || '';
      };
      for (let name in data) {
        if (!data.hasOwnProperty(name)) continue;
        let value = data[name];
        if (typeof value === 'function') value = value();
        if (isPlainObject(value)) value = Object.values(value);
        if (isArray(value)) value = value.join(' ');
        output += ' ' + checkPlain(name) + '="' + checkPlain(value) + '"';
      }
      return output;
    }

    /**
     * Add class(es) to the array.
     *
     * @param {String|...String|Array|...Array} classes
     *   An individual class or an array of classes to add.
     *
     * @return {Attributes}
     *
     * @chainable
     */
    addClass(...classes) {
      this.data.set('class', new Set(Attributes.sanitizeClasses(this.getClasses().concat(...classes))));
      return this;
    }

    /**
     * Retrieves classes from the array.
     *
     * @param {Boolean} [set = false]
     *   Flag indicating whether to return the Set object or an array.
     *
     * @return {Array|Set}
     *   The classes array.
     */
    getClasses(set = false) {
      const set = this.get('class', new Set(), true);
      return set ? set : [...set];
    }

    /**
     * Indicates whether a class is present in the array.
     *
     * @param {string|Array} classNames
     *   The class(es) to search for.
     *
     * @return {boolean}
     *   TRUE or FALSE
     */
    hasClass(...classNames) {
      classNames = Attributes.sanitizeClasses(...classNames);
      const classes = this.getClasses(true);
      for (let i = 0, l = classNames.length; i < l; i++) {
        // If one of the classes fails, immediately return false.
        if (!classes.has(classNames[i])) {
          return false;
        }
      }
      return true;
    }

    /**
     * {@inheritdoc}
     */
    merge(...values) {
      // Convert arguments to arrays.
      const args = this.convertArguments(values, [this.value()]);

      // Handle classes differently.
      let classes = [];
      args.forEach(o => {
        if (o['class'] !== void 0) {
          classes.push(o['class']);
          delete o['class'];
        }
      });

      // Merge in the attributes.
      const data = isArray(args[0]) ? new Set([...args]) : new Map([...args.map(o => Object.entries(o))]);
      this.replace(data);


      // Merge in any classes.
      if (classes.length) {
        this.addClass(classes);
      }

      return this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated There are no "deep" multi-dimensional objects in attributes.
     *   This just proxies to the ::merge method.
     */
    mergeDeep(...values) {
      return this.merge.apply(this, values);
    }

    /**
     * Removes a class from the attributes array.
     *
     * @param {...string|Array} className
     *   An individual class or an array of classes to remove.
     *
     * @return {Attributes}
     *
     * @chainable
     */
    removeClass(className) {
      const remove = Attributes.sanitizeClasses(Array.prototype.slice.apply(arguments));
      this.data['class'] = _.without(this.getClasses(), remove);
      return this;
    }

    /**
     * Replaces a class in the attributes array.
     *
     * @param {String} oldClassName
     *   The old class to remove.
     * @param {String} newClassName
     *   The new class to add.
     * @param {Boolean} [onlyIfExists = true]
     *   Flag indicating whether to add the newClassName only if the
     *   oldClassName exists, defaults to TRUE.
     *
     * @return {Attributes}
     *
     * @chainable
     */
    replaceClass(oldClassName, newClassName, onlyIfExists = true) {
      const classes = this.getClasses(true);
      oldClassName = Attributes.sanitizeClasses(oldClassName)[0];
      if (!onlyIfExists || classes.has(oldClassName)) {
        classes.delete(oldClassName);
        classes.add(newClassName);
        this.set('class', classes);
      }
      return this;
    }

    /**
     * Ensures classes are flattened into a single is an array and sanitized.
     *
     * @param {...String|Array} classes
     *   The class or classes to sanitize.
     *
     * @return {Array}
     *   A sanitized array of classes.
     */
    static sanitizeClasses(...classes) {
      return ArrayObject.create([]).merge(...classes)
        // Flatten in case there's a mix of strings and arrays.
        .flatten()

        // Split classes added as a string using a space separator.
        .map(s => s.split(' '))

        // Flatten again since it was just split into arrays.
        .flatten()

        // Filter out empty items.
        .filter()

        // Clean each class to ensure it's a valid class identifier.
        .map(s => Html.cleanCssIdentifier(s))

        // Ensure classes are unique. This aspect of the chain is not needed
        // from its PHP counterpart since ArrayObjects here use the Set object
        // for arrays, which automatically keeps unique values.
        //.unique()

        // Retrieve the final value.
        .value();
    }

    /**
     * {@inheritdoc}
     */
    set(key, value) {
      // Handle class attribute differently.
      if (key === 'class') {
        return this.unset('class').addClass(value);
      }
      return super.set(key, value);
    }

    /**
     * Renders the Attributes object as a plain object.
     *
     * @return {Object}
     *   A plain object suitable for inclusion in DOM elements.
     */
    value() {
      const data = super.value();
      let object = {};
      for (let name in data) {
        if (!data.hasOwnProperty(name)) continue;
        let value = data[name];
        if (typeof value === 'function') value = value();
        if (isPlainObject(value)) value = Object.values(value);
        if (isArray(value)) value = value.join(' ');
        object[name] = value;
      }
      return object;
    }

  }

  Drupal.Attributes = Drupal.Attributes || Attributes;

})(window.Drupal, window.Drupal.Html, window.Drupal.ArrayObject, window.extend, window.isPlainObject);
