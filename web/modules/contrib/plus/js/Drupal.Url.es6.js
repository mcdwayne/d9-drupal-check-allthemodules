/**
 * @file
 * Drupal+ Url.
 */
((Drupal) => {
  'use strict';

  const elementUrlMap = new Map(Object.entries({
    code: new Set([window.HTMLAppletElement].filter(Boolean)),
    data: new Set([HTMLObjectElement].filter(Boolean)),
    href: new Set([HTMLAnchorElement, HTMLAreaElement, HTMLBaseElement, HTMLLinkElement].filter(Boolean)),
    src: new Set([HTMLAudioElement, HTMLEmbedElement, HTMLIFrameElement, HTMLImageElement, HTMLInputElement, HTMLScriptElement, HTMLSourceElement, HTMLTrackElement, HTMLVideoElement].filter(Boolean)),
  }));

  const getElementUrl = (element) => {
    let url = null;
    elementUrlMap.forEach((elements, prop) => {
      elements.forEach((value) => {
        if (url === null && element instanceof value) {
          url = element[prop];
        }
      });
    });
    return url;
  };

  /**
   * Retrieves the file mime type from an element.
   *
   * @param {HTMLElement} element
   *   An element.
   *
   * @return {Array}
   *   The mime type.
   */
  const getElementMimeType = (element) => {
    let mimeType = null;
    if (element instanceof HTMLAnchorElement) {
      mimeType = element.type;
    }
    else if (element instanceof HTMLFormElement) {
      mimeType = element.enctype;
    }
    return (mimeType && mimeType.split(/;\s*/).filter(Boolean)) || [];
  };

  /**
   * Private properties.
   *
   * @type {Object<String, WeakMap>}
   *
   * @private
   */
  const _ = {
    absolute: new WeakMap(),
    basename: new WeakMap(),
    element: new WeakMap(),
    extension: new WeakMap(),
    filename: new WeakMap(),
    hash: new WeakMap(),
    langcode: new WeakMap(),
    mimeType: new WeakMap(),
    origin: new WeakMap(),
    path: new WeakMap(),
    protocol: new WeakMap(),
    query: new WeakMap(),
    relation: new WeakMap(),
    relative: new WeakMap(),
    size: new WeakMap(),
    title: new WeakMap(),
    uid: new WeakMap(),
    url: new WeakMap(),
  };

  /**
   * @class Url
   */
  class Url {
    constructor() {
      _.absolute.set(this, null);
      _.basename.set(this, null);
      _.element.set(this, null);
      _.extension.set(this, null);
      _.filename.set(this, null);
      _.hash.set(this, null);
      _.langcode.set(this, null);
      _.mimeType.set(this, null);
      _.origin.set(this, null);
      _.path.set(this, null);
      _.protocol.set(this, null);
      _.query.set(this, null);
      _.relation.set(this, null);
      _.relative.set(this, null);
      _.size.set(this, null);
      _.title.set(this, null);
      _.uid.set(this, null);
      _.url.set(this, null);
    }

    /**
     * The absolute URL.
     *
     * @return {String}
     *   The value.
     */
    get absolute() {
      return _.absolute.get(this) || '';
    }

    /**
     * Sets the absolute URL.
     *
     * @param {String} value
     *   The value to set.
     */
    set absolute(value) {
      const origin = _.origin.get(this) || window.location.origin;
      let url;

      try {
        url = new URL(value, origin);
      }
      catch (e) {
        return Drupal.fatal(Drupal.t('Only absolute URLs (or protocol relative URLs) can be set on the "absolute" property: @value'), { '@value': value });
      }

      _.url.set(this, url);
      _.absolute.set(this, url.href);

      // Reset other properties so they can be reevaluated.
      _.basename.set(this, null);
      _.extension.set(this, null);
      _.filename.set(this, null);
      _.hash.set(this, null);
      _.origin.set(this, null);
      _.path.set(this, null);
      _.protocol.set(this, null);
      _.query.set(this, null);
      _.relative.set(this, null);
    }

    /**
     * The base filename, without the extension.
     *
     * @return {String}
     *   The value.
     *
     * @see http://locutus.io/php/filesystem/basename/
     */
    get basename() {
      return this.getPrivateProperty(_, 'basename', () => {
        let url = this.path;
        let basename = '';

        const last = url && url.charAt(url.length - 1);
        if (last === '/' || last === '\\') {
          url = url.slice(0, -1);
        }
        url = url && url.replace(/^.*[/\\]/g, '');

        const extension = `.${this.extension}`;
        if (url.substr(url.length - extension.length) === extension) {
          basename = url.substr(0, url.length - extension.length);
        }

        return basename;
      });
    }

    /**
     * The known element that the values were extract from, if any.
     *
     * @return {HTMLElement}
     *   The value.
     */
    get element() {
      return _.element.get(this);
    }

    /**
     * Sets the element property.
     *
     * @param {HTMLElement} value
     *   The value to set.
     */
    set element(value) {
      if (!getElementUrl(value)) {
        return Drupal.fatal(Drupal.t('Only HTMLElement objects where a URL value can be extracted are allowed to be set on the "element" property: @value'), { '@value': value });
      }

      _.element.set(this, value);

      // Reset other properties so they can be reevaluated.
      _.absolute.set(this, null);
      _.basename.set(this, null);
      _.extension.set(this, null);
      _.filename.set(this, null);
      _.hash.set(this, null);
      _.langcode.set(this, null);
      _.mimeType.set(this, null);
      _.origin.set(this, null);
      _.path.set(this, null);
      _.protocol.set(this, null);
      _.query.set(this, null);
      _.relation.set(this, null);
      _.relative.set(this, null);
      _.size.set(this, null);
      _.title.set(this, null);
      _.uid.set(this, null);
      _.url.set(this, null);
    }

    /**
     * The extension of the file.
     *
     * @return {String}
     *   The value.
     *
     * @see http://stackoverflow.com/a/12900504
     */
    get extension() {
      return this.getPrivateProperty(_, 'extension', () => {
        const path = this.path;
        /* eslint no-bitwise: ["error", { "allow": [">>>"] }] */
        return path && /tar\.gz$/.test(path) ? 'tar.gz' : path.slice((path.lastIndexOf('.') - 1 >>> 0) + 2) || '';
      });
    }

    /**
     * The known filename of the file.
     *
     * @return {String}
     *   The value.
     */
    get filename() {
      return this.getPrivateProperty(_, 'filename', () => [this.basename, this.extension].filter(Boolean).join('.'));
    }

    /**
     * The known hash of the file.
     *
     * @return {String}
     *   The value.
     */
    get hash() {
      return this.getPrivateProperty(_, 'hash', () => {
        const url = _.url.get(this);
        return (url && url.hash) || '';
      });
    }

    /**
     * The known language code of the file, if any.
     *
     * @return {String}
     *   The value.
     */
    get langcode() {
      return this.getPrivateProperty(_, 'langcode', element => (element && (element.hreflang || element.lang)) || '');
    }

    /**
     * The known mime type of the file, if any.
     *
     * @return {String}
     *   The value.
     */
    get mimeType() {
      return this.getPrivateProperty(_, 'mimeType', element => getElementMimeType(element).shift() || '');
    }

    /**
     * The origin of the file, if any.
     *
     * @return {String}
     *   The value.
     */
    get origin() {
      return this.getPrivateProperty(_, 'origin', (element) => {
        const url = _.url.get(this);
        return (element && element.origin) || (url && url.origin) || '';
      });
    }

    /**
     * Setter for the origin of the file.
     *
     * @param {String} value
     *   The value to set.
     */
    set origin(value) {
      _.origin.set(this, value);
    }

    /**
     * The path of the file (without origin), if any.
     *
     * @return {String}
     *   The value.
     */
    get path() {
      return this.getPrivateProperty(_, 'path', () => {
        const url = _.url.get(this);
        return (url && url.pathname) || '';
      });
    }

    /**
     * The protocol of the file.
     *
     * @return {String}
     *   The value.
     */
    get protocol() {
      return this.getPrivateProperty(_, 'protocol', () => {
        const url = _.url.get(this);
        return (url && url.protocol.replace(/:$/, '')) || '';
      });
    }

    /**
     * The known query of the file, if any.
     *
     * @return {String}
     *   The value.
     */
    get query() {
      return this.getPrivateProperty(_, 'query', () => {
        const url = _.url.get(this);
        return (url && url.search) || '';
      });
    }

    /**
     * The known relationship of the file, if any.
     *
     * @return {String}
     *   The value.
     */
    get relation() {
      return this.getPrivateProperty(_, 'relation', element => (element && element.rel) || '');
    }

    /**
     * The file relative URL (minus the origin).
     *
     * @return {String}
     *   The value.
     */
    get relative() {
      return this.getPrivateProperty(_, 'relation', () => {
        const url = this.absolute;
        return (url && url.replace(this.origin, '')) || '';
      });
    }

    /**
     * The known size of the file, if any.
     *
     * @return {Number}
     *   The value.
     */
    get size() {
      return this.getPrivateProperty(_, 'size', (element) => {
        let size = 0;

        // Extract the file size from the mime type, if provided.
        const mimeType = getElementMimeType(element).slice(1);
        for (let i = 0, l = mimeType.slice(0).length; i < l; i++) {
          const parts = mimeType[i].split('=');
          if (parts && parts[0] === 'length') {
            size = parts[1];
            break;
          }
        }
        return size;
      }, ['fileSize', 'size']);
    }

    /**
     * The known title of the file, if any.
     *
     * @return {String}
     *   The value.
     */
    get title() {
      return this.getPrivateProperty(_, 'title', element => (element && element.title) || '');
    }

    /**
     * The identifier of the user who is associated with this file, if any.
     *
     * @return {Number}
     *   The value.
     */
    get uid() {
      return this.getPrivateProperty(_, 'uid', 0);
    }

    /**
     * The absolute URL.
     *
     * An alias for DrupalUrl.absolute.
     *
     * @type {String}
     *
     * @see Url.absolute
     */
    get url() {
      return this.absolute;
    }

    /**
     * Sets the absolute URL.
     *
     * @param {String} value
     *   The value to set.
     */
    set url(value) {
      this.absolute = value;
    }

    /**
     * Sets a specific property.
     *
     * @param {String|Object|HTMLElement} name
     *   The name of a supported property to set. Can also be an object of
     *   key/value properties to set or an HTMLElement object.
     * @param {*} [value]
     *   The value to set if the "name" provided was a string of the property
     *   to set.
     *
     * @return {Url}
     *   The instance.
     */
    set(name, value) {
      let values = {};

      if (typeof name === 'string') {
        values[name] = value;
      }
      else if (name instanceof HTMLElement) {
        values.element = name;
      }
      else if (typeof name === 'object') {
        values = { ...name };
      }

      // Convert "url" alias to "absolute".
      if (values.url && values.absolute === undefined) {
        values.absolute = values.url;
        delete values.url;
      }

      // Only merge in supported properties, typecasting as needed.
      const keys = Object.keys(values);
      for (let i = 0, l = keys.length; i < l; i++) {
        const prop = keys[i];
        if (_.hasOwnProperty(prop) || this.hasOwnProperty(prop)) {
          this[prop] = values[prop];
        }
      }

      return this;
    }

    getPrivateProperty(properties, name, defaultValue = '', dataAttributes = null) {
      const prop = properties[name];
      let value = prop.get(this);

      // Immediately return the value if it's set.
      if (value !== null) {
        return value;
      }

      const element = properties.element ? properties.element.get(this) : _.element.get(this);

      // Attempt to get a data attribute value from an element.
      if (element) {
        if (dataAttributes === null) {
          dataAttributes = [name];
        }
        if (dataAttributes) {
          for (let i = 0, l = dataAttributes.length; i < l; i++) {
            if (element.dataset[dataAttributes[i]] !== undefined) {
              value = element.dataset[dataAttributes[i]];
              break;
            }
          }
        }
      }

      // Handle defaultValue as a callback by invoking it and then setting
      // the return value as the defaultValue.
      if (value === null && typeof defaultValue === 'function') {
        defaultValue = defaultValue(element);
        if (defaultValue === undefined || defaultValue === null) {
          defaultValue = '';
        }
      }

      // Typecast the new value from the default value.
      value = Drupal.typeCast(defaultValue, value);

      // Set the property.
      prop.set(this, value);

      // Return it.
      return value;
    }

    /**
     * Constructs a new file object from values.
     *
     * @param {Object|HTMLElement|Url} [obj]
     *   The values to assign to the new DrupalUrl. If an HTMLElement is
     *   provided, all relevant data will be extracted from it.
     * @param {String} [origin = window.location.origin]
     *   A fallback origin for the URL. By default, the origin will
     *   automatically be determined from the URL, but if it is a relative URL
     *   this will be used to make an absolute URL. It defaults to the the
     *   origin of the current page.
     *
     * @return {Url}
     *   The new DrupalUrl instance.
     */
    static create(obj, origin = null) {
      if (obj instanceof this) {
        return obj;
      }
      const instance = new this();
      if (obj) {
        instance.set('origin', origin || window.location.origin + Drupal.settings.basePath + Drupal.settings.pathPrefix);
        instance.set(typeof obj === 'string' ? { url: obj } : obj);
      }
      return instance;
    }
  }

  /**
   * Export to Drupal.
   *
   * @type {Url}
   */
  Drupal.Url = Url;
})(window.Drupal);
