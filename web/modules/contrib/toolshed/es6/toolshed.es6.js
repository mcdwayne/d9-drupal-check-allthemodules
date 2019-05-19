
// Define the Toolshed object scope.
Drupal.Toolshed = {
  /**
   * Simple escaping of RegExp strings.
   *
   * Does not handle advanced regular expressions, but will take care of
   * most cases. Meant to be used when concatenating string to create a
   * regular expressions.
   *
   * @param  {string} str
   *  String to escape.
   *
   * @return {string}
   *  String with the regular expression special characters escaped.
   */
  escapeRegex(str) {
    return str.replace(/[\^$+*?[\]{}()\\]/g, '\\$&');
  },

  /**
   * Helper function to uppercase the first letter of a string.
   *
   * @param {string} str
   *  String to transform.
   *
   * @return {string}
   *  String which has the first letter uppercased.
   */
  ucFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  },

  /**
   * Transform a string into camel case. It will remove spaces, underscores
   * and hyphens, and uppercase the letter directly following them.
   *
   * @param {string} str
   *  The string to try to transform into camel case.
   *
   * @return {string}
   *  The string transformed into camel case.
   */
  camelCase(str) {
    return str.replace(/(?:[ _-]+)([a-z])/g, (match, p1) => p1.toUpperCase());
  },

  /**
   * Transforms a string into Pascal case. This is basically the same as
   * camel case, except that it will upper case the first letter as well.
   *
   * @param {string} str
   *  The original string to transform into Pascal case.
   *
   * @return {string}
   *  The transformed string.
   */
  pascalCase(str) {
    return str.replace(/(?:^|[ _-]+)([a-z])/g, (match, p1) => p1.toUpperCase());
  },

  /**
   * Gets the current page Drupal URL (excluding the query or base path).
   *
   * @return {string}
   *  The current internal path for Drupal. This would be the path
   *  without the "base path", however, it can still be a path alias.
   */
  getCurrentPath() {
    if (!Drupal.Toolshed.getCurrentPath.path) {
      Drupal.Toolshed.getCurrentPath.path = null;

      if (drupalSettings.path.baseUrl) {
        const regex = new RegExp(`^${Drupal.Toolshed.escapeRegex(drupalSettings.path.baseUrl)}`, 'i');
        Drupal.Toolshed.getCurrentPath.path = window.location.pathname.replace(regex, '');
      }
      else {
        throw Error('Base path is unavailable. This usually occurs if getCurrentPath() is run before the DOM is loaded.');
      }
    }

    return Drupal.Toolshed.getCurrentPath.path;
  },

  /**
   * Parse URL query paramters from a URL.
   *
   * @param {string} url
   *  Full URL including the query parameters starting with '?' and
   *  separated with '&' characters.
   *
   * @return {Object}
   *  JSON formatted object which has the property names as the query
   *  key, and the property value is the query value.
   */
  getUrlParams(url) {
    const params = {};
    const pStr = (url || window.location.search).split('?');

    if (pStr.length > 1) {
      pStr[1].split('&').forEach((param) => {
        const matches = /^([^=]+)=(.*)$/.exec(param);

        if (matches) {
          params[decodeURIComponent(matches[1])] = decodeURIComponent(matches[2]);
        }
      });
    }

    return params;
  },

  /**
   * Build a URL based on a Drupal internal path. This function will test
   * for the availability of clean URL's and prefer them if available.
   * The URL components will be run through URL encoding.
   *
   * @param {string} rawUrl
   *  The URL to add the query parameters to. The URL can previously have
   *  query parameters already include. This will append additional params.
   * @param {Object|string} params
   *  An object containing parameters to use as the URL query. Object
   *  property keys are the query variable names, and the object property
   *  value is the value to use for the query.
   *
   * @return {string}
   *  The valid Drupal URL based on values passed.
   */
  buildUrl(rawUrl, params) {
    let url = rawUrl;

    // leave absolute URL's alone.
    if (!(/^([a-z]{2,5}:)?\/\//i).test(url)) {
      url = url ? url.replace(/^[/,\s]+|<front>|([/,\s]+$)/g, '') : '';
      url = (drupalSettings.path.baseUrl ? drupalSettings.path.baseUrl : '/') + url;
    }

    if (params) {
      const qry = (typeof params === 'string')
        ? params : Object.entries(params).reduce((acc, entry) => `${acc}&${encodeURIComponent(entry[0])}=${encodeURIComponent(entry[1])}`, '').substr(1);

      if (qry.length) {
        url += (url.indexOf('?') === -1 ? '?' : '&') + qry;
      }
    }

    return url;
  },

  /**
   * Create a lambda that can make requests GET to a specified URI.
   *
   * The resulting lambda that when executed would return a Promise
   * encapsulating a request to the URL with the parameters passed.
   *
   * @param {string} uri
   *   URI to create a request function for.
   * @param {object} opts
   *   Options for how the request should be sent and the expected data
   *   results should appear.
   *
   *   The following options are available:
   *    - method: The HTTP method to use when creating the request.
   *    - format: The expected data response format (JSON, HTML, etc...)
   *
   * @return {function(params:object)}
   *   A lambda that creates a HTTP GET request to specified URI with
   *   passed in URL query params. The lamba returns a Promise which
   *   can be used to process the results or errors.
   */
  createGetRequester(uri, opts = { method: 'GET', format: 'json' }) {
    const parser = opts.format === 'json' ? JSON.parse : resp => resp;

    if (opts.method === 'POST' || opts.method === 'PUT') {
      return params => new Promise((resolve, reject) => {
        const req = new XMLHttpRequest();

        req.open(opts.method, Drupal.Toolshed.buildUrl(uri), true);
        req.onreadystatechange = () => {
          if (this.readyState === XMLHttpRequest.DONE) {
            if (this.status === 200) resolve(parser(req.response));
            else reject(Error(`${req.status}: ${req.statusText}`));
          }
        };

        // Unable to contact the server or no response.
        req.onerror = () => reject(Error('Unable to connect'));

        // Convert parameters into URL encoded values for returning.
        const body = (typeof params === 'string')
          ? params : Object.entries(params).reduce((acc, entry) => `${acc}&${encodeURIComponent(entry[0])}=${encodeURIComponent(entry[1])}`, '').substr(1);

        req.send(body);
      });
    }

    // Basic GET or HEAD HTTP requests.
    return params => new Promise((resolve, reject) => {
      const req = new XMLHttpRequest();
      req.open(opts.method, Drupal.Toolshed.buildUrl(uri, params), true);
      req.onload = () => {
        if (req.status === 200) resolve(parser(req.response));
        else reject(Error(req.statusText));
      };

      // Unable to contact the server or no response.
      req.onerror = () => reject(Error('Unable to connect'));
      req.send();
    });
  },

  /**
   * Send a Request to URI with provided parameters and return Promise.
   *
   * @param {string} uri
   *   URI to build the request for.
   * @param {array|null} params
   *   Parameters to include when making the request.
   * @param {object} opts
   *  Same set of options as for createGetRequester() function.
   *
   * @return {Promise}
   *   Promise wrapping the HTTP request.
   *
   * @see Drupal.Toolshed.createGetRequester()
   */
  sendGetRequest(uri, params, opts = { method: 'GET', format: 'json' }) {
    return Drupal.Toolshed.createGetRequester(uri, opts)(params);
  },

  /**
   * Utility function used to find an object based on a string name.
   *
   * @param {string} name
   *  Fully qualified name of the object to fetch.
   *
   * @return {Object}
   *  the object matching the name, or NULL if it cannot be found.
   */
  getObject(name) {
    if (!(name && name.split)) return null;

    const fetchObj = (obj, items) => {
      const part = items.shift();

      if (obj[part]) return items.length ? fetchObj(obj[part], items) : obj[part];
      return null;
    };

    return fetchObj(window, name.split('.'));
  },
};
