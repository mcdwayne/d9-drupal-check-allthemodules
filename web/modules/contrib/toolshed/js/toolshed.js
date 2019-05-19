'use strict';

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
  escapeRegex: function escapeRegex(str) {
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
  ucFirst: function ucFirst(str) {
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
  camelCase: function camelCase(str) {
    return str.replace(/(?:[ _-]+)([a-z])/g, function (match, p1) {
      return p1.toUpperCase();
    });
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
  pascalCase: function pascalCase(str) {
    return str.replace(/(?:^|[ _-]+)([a-z])/g, function (match, p1) {
      return p1.toUpperCase();
    });
  },


  /**
   * Gets the current page Drupal URL (excluding the query or base path).
   *
   * @return {string}
   *  The current internal path for Drupal. This would be the path
   *  without the "base path", however, it can still be a path alias.
   */
  getCurrentPath: function getCurrentPath() {
    if (!Drupal.Toolshed.getCurrentPath.path) {
      Drupal.Toolshed.getCurrentPath.path = null;

      if (drupalSettings.path.baseUrl) {
        var regex = new RegExp('^' + Drupal.Toolshed.escapeRegex(drupalSettings.path.baseUrl), 'i');
        Drupal.Toolshed.getCurrentPath.path = window.location.pathname.replace(regex, '');
      } else {
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
  getUrlParams: function getUrlParams(url) {
    var params = {};
    var pStr = (url || window.location.search).split('?');

    if (pStr.length > 1) {
      pStr[1].split('&').forEach(function (param) {
        var matches = /^([^=]+)=(.*)$/.exec(param);

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
  buildUrl: function buildUrl(rawUrl, params) {
    var url = rawUrl;

    // leave absolute URL's alone.
    if (!/^([a-z]{2,5}:)?\/\//i.test(url)) {
      url = url ? url.replace(/^[/,\s]+|<front>|([/,\s]+$)/g, '') : '';
      url = (drupalSettings.path.baseUrl ? drupalSettings.path.baseUrl : '/') + url;
    }

    if (params) {
      var qry = typeof params === 'string' ? params : Object.entries(params).reduce(function (acc, entry) {
        return acc + '&' + encodeURIComponent(entry[0]) + '=' + encodeURIComponent(entry[1]);
      }, '').substr(1);

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
  createGetRequester: function createGetRequester(uri) {
    var _this = this;

    var opts = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : { method: 'GET', format: 'json' };

    var parser = opts.format === 'json' ? JSON.parse : function (resp) {
      return resp;
    };

    if (opts.method === 'POST' || opts.method === 'PUT') {
      return function (params) {
        return new Promise(function (resolve, reject) {
          var req = new XMLHttpRequest();

          req.open(opts.method, Drupal.Toolshed.buildUrl(uri), true);
          req.onreadystatechange = function () {
            if (_this.readyState === XMLHttpRequest.DONE) {
              if (_this.status === 200) resolve(parser(req.response));else reject(Error(req.status + ': ' + req.statusText));
            }
          };

          // Unable to contact the server or no response.
          req.onerror = function () {
            return reject(Error('Unable to connect'));
          };

          // Convert parameters into URL encoded values for returning.
          var body = typeof params === 'string' ? params : Object.entries(params).reduce(function (acc, entry) {
            return acc + '&' + encodeURIComponent(entry[0]) + '=' + encodeURIComponent(entry[1]);
          }, '').substr(1);

          req.send(body);
        });
      };
    }

    // Basic GET or HEAD HTTP requests.
    return function (params) {
      return new Promise(function (resolve, reject) {
        var req = new XMLHttpRequest();
        req.open(opts.method, Drupal.Toolshed.buildUrl(uri, params), true);
        req.onload = function () {
          if (req.status === 200) resolve(parser(req.response));else reject(Error(req.statusText));
        };

        // Unable to contact the server or no response.
        req.onerror = function () {
          return reject(Error('Unable to connect'));
        };
        req.send();
      });
    };
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
  sendGetRequest: function sendGetRequest(uri, params) {
    var opts = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : { method: 'GET', format: 'json' };

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
  getObject: function getObject(name) {
    if (!(name && name.split)) return null;

    var fetchObj = function fetchObj(obj, items) {
      var part = items.shift();

      if (obj[part]) return items.length ? fetchObj(obj[part], items) : obj[part];
      return null;
    };

    return fetchObj(window, name.split('.'));
  }
};
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInRvb2xzaGVkLmVzNi5qcyJdLCJuYW1lcyI6WyJEcnVwYWwiLCJUb29sc2hlZCIsImVzY2FwZVJlZ2V4Iiwic3RyIiwicmVwbGFjZSIsInVjRmlyc3QiLCJjaGFyQXQiLCJ0b1VwcGVyQ2FzZSIsInNsaWNlIiwiY2FtZWxDYXNlIiwibWF0Y2giLCJwMSIsInBhc2NhbENhc2UiLCJnZXRDdXJyZW50UGF0aCIsInBhdGgiLCJkcnVwYWxTZXR0aW5ncyIsImJhc2VVcmwiLCJyZWdleCIsIlJlZ0V4cCIsIndpbmRvdyIsImxvY2F0aW9uIiwicGF0aG5hbWUiLCJFcnJvciIsImdldFVybFBhcmFtcyIsInVybCIsInBhcmFtcyIsInBTdHIiLCJzZWFyY2giLCJzcGxpdCIsImxlbmd0aCIsImZvckVhY2giLCJwYXJhbSIsIm1hdGNoZXMiLCJleGVjIiwiZGVjb2RlVVJJQ29tcG9uZW50IiwiYnVpbGRVcmwiLCJyYXdVcmwiLCJ0ZXN0IiwicXJ5IiwiT2JqZWN0IiwiZW50cmllcyIsInJlZHVjZSIsImFjYyIsImVudHJ5IiwiZW5jb2RlVVJJQ29tcG9uZW50Iiwic3Vic3RyIiwiaW5kZXhPZiIsImNyZWF0ZUdldFJlcXVlc3RlciIsInVyaSIsIm9wdHMiLCJtZXRob2QiLCJmb3JtYXQiLCJwYXJzZXIiLCJKU09OIiwicGFyc2UiLCJyZXNwIiwiUHJvbWlzZSIsInJlc29sdmUiLCJyZWplY3QiLCJyZXEiLCJYTUxIdHRwUmVxdWVzdCIsIm9wZW4iLCJvbnJlYWR5c3RhdGVjaGFuZ2UiLCJyZWFkeVN0YXRlIiwiRE9ORSIsInN0YXR1cyIsInJlc3BvbnNlIiwic3RhdHVzVGV4dCIsIm9uZXJyb3IiLCJib2R5Iiwic2VuZCIsIm9ubG9hZCIsInNlbmRHZXRSZXF1ZXN0IiwiZ2V0T2JqZWN0IiwibmFtZSIsImZldGNoT2JqIiwib2JqIiwiaXRlbXMiLCJwYXJ0Iiwic2hpZnQiXSwibWFwcGluZ3MiOiI7O0FBQ0E7QUFDQUEsT0FBT0MsUUFBUCxHQUFrQjtBQUNoQjs7Ozs7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QUFvQkQsS0FBcEIsQ0FBZixHQUE0Q0QsSUFBSUUsSUFBSixDQUFuRDtBQUNmLGFBQU8sSUFBUDtBQUNELEtBTEQ7O0FBT0EsV0FBT0gsU0FBU3hELE1BQVQsRUFBaUJ1RCxLQUFLOUMsS0FBTCxDQUFXLEdBQVgsQ0FBakIsQ0FBUDtBQUNEO0FBeFBlLENBQWxCIiwiZmlsZSI6InRvb2xzaGVkLmpzIiwic291cmNlc0NvbnRlbnQiOlsiXG4vLyBEZWZpbmUgdGhlIFRvb2xzaGVkIG9iamVjdCBzY29wZS5cbkRydXBhbC5Ub29sc2hlZCA9IHtcbiAgLyoqXG4gICAqIFNpbXBsZSBlc2NhcGluZyBvZiBSZWdFeHAgc3RyaW5ncy5cbiAgICpcbiAgICogRG9lcyBub3QgaGFuZGxlIGFkdmFuY2VkIHJlZ3VsYXIgZXhwcmVzc2lvbnMsIGJ1dCB3aWxsIHRha2UgY2FyZSBvZlxuICAgKiBtb3N0IGNhc2VzLiBNZWFudCB0byBiZSB1c2VkIHdoZW4gY29uY2F0ZW5hdGluZyBzdHJpbmcgdG8gY3JlYXRlIGFcbiAgICogcmVndWxhciBleHByZXNzaW9ucy5cbiAgICpcbiAgICogQHBhcmFtICB7c3RyaW5nfSBzdHJcbiAgICogIFN0cmluZyB0byBlc2NhcGUuXG4gICAqXG4gICAqIEByZXR1cm4ge3N0cmluZ31cbiAgICogIFN0cmluZyB3aXRoIHRoZSByZWd1bGFyIGV4cHJlc3Npb24gc3BlY2lhbCBjaGFyYWN0ZXJzIGVzY2FwZWQuXG4gICAqL1xuICBlc2NhcGVSZWdleChzdHIpIHtcbiAgICByZXR1cm4gc3RyLnJlcGxhY2UoL1tcXF4kKyo/W1xcXXt9KClcXFxcXS9nLCAnXFxcXCQmJyk7XG4gIH0sXG5cbiAgLyoqXG4gICAqIEhlbHBlciBmdW5jdGlvbiB0byB1cHBlcmNhc2UgdGhlIGZpcnN0IGxldHRlciBvZiBhIHN0cmluZy5cbiAgICpcbiAgICogQHBhcmFtIHtzdHJpbmd9IHN0clxuICAgKiAgU3RyaW5nIHRvIHRyYW5zZm9ybS5cbiAgICpcbiAgICogQHJldHVybiB7c3RyaW5nfVxuICAgKiAgU3RyaW5nIHdoaWNoIGhhcyB0aGUgZmlyc3QgbGV0dGVyIHVwcGVyY2FzZWQuXG4gICAqL1xuICB1Y0ZpcnN0KHN0cikge1xuICAgIHJldHVybiBzdHIuY2hhckF0KDApLnRvVXBwZXJDYXNlKCkgKyBzdHIuc2xpY2UoMSk7XG4gIH0sXG5cbiAgLyoqXG4gICAqIFRyYW5zZm9ybSBhIHN0cmluZyBpbnRvIGNhbWVsIGNhc2UuIEl0IHdpbGwgcmVtb3ZlIHNwYWNlcywgdW5kZXJzY29yZXNcbiAgICogYW5kIGh5cGhlbnMsIGFuZCB1cHBlcmNhc2UgdGhlIGxldHRlciBkaXJlY3RseSBmb2xsb3dpbmcgdGhlbS5cbiAgICpcbiAgICogQHBhcmFtIHtzdHJpbmd9IHN0clxuICAgKiAgVGhlIHN0cmluZyB0byB0cnkgdG8gdHJhbnNmb3JtIGludG8gY2FtZWwgY2FzZS5cbiAgICpcbiAgICogQHJldHVybiB7c3RyaW5nfVxuICAgKiAgVGhlIHN0cmluZyB0cmFuc2Zvcm1lZCBpbnRvIGNhbWVsIGNhc2UuXG4gICAqL1xuICBjYW1lbENhc2Uoc3RyKSB7XG4gICAgcmV0dXJuIHN0ci5yZXBsYWNlKC8oPzpbIF8tXSspKFthLXpdKS9nLCAobWF0Y2gsIHAxKSA9PiBwMS50b1VwcGVyQ2FzZSgpKTtcbiAgfSxcblxuICAvKipcbiAgICogVHJhbnNmb3JtcyBhIHN0cmluZyBpbnRvIFBhc2NhbCBjYXNlLiBUaGlzIGlzIGJhc2ljYWxseSB0aGUgc2FtZSBhc1xuICAgKiBjYW1lbCBjYXNlLCBleGNlcHQgdGhhdCBpdCB3aWxsIHVwcGVyIGNhc2UgdGhlIGZpcnN0IGxldHRlciBhcyB3ZWxsLlxuICAgKlxuICAgKiBAcGFyYW0ge3N0cmluZ30gc3RyXG4gICAqICBUaGUgb3JpZ2luYWwgc3RyaW5nIHRvIHRyYW5zZm9ybSBpbnRvIFBhc2NhbCBjYXNlLlxuICAgKlxuICAgKiBAcmV0dXJuIHtzdHJpbmd9XG4gICAqICBUaGUgdHJhbnNmb3JtZWQgc3RyaW5nLlxuICAgKi9cbiAgcGFzY2FsQ2FzZShzdHIpIHtcbiAgICByZXR1cm4gc3RyLnJlcGxhY2UoLyg/Ol58WyBfLV0rKShbYS16XSkvZywgKG1hdGNoLCBwMSkgPT4gcDEudG9VcHBlckNhc2UoKSk7XG4gIH0sXG5cbiAgLyoqXG4gICAqIEdldHMgdGhlIGN1cnJlbnQgcGFnZSBEcnVwYWwgVVJMIChleGNsdWRpbmcgdGhlIHF1ZXJ5IG9yIGJhc2UgcGF0aCkuXG4gICAqXG4gICAqIEByZXR1cm4ge3N0cmluZ31cbiAgICogIFRoZSBjdXJyZW50IGludGVybmFsIHBhdGggZm9yIERydXBhbC4gVGhpcyB3b3VsZCBiZSB0aGUgcGF0aFxuICAgKiAgd2l0aG91dCB0aGUgXCJiYXNlIHBhdGhcIiwgaG93ZXZlciwgaXQgY2FuIHN0aWxsIGJlIGEgcGF0aCBhbGlhcy5cbiAgICovXG4gIGdldEN1cnJlbnRQYXRoKCkge1xuICAgIGlmICghRHJ1cGFsLlRvb2xzaGVkLmdldEN1cnJlbnRQYXRoLnBhdGgpIHtcbiAgICAgIERydXBhbC5Ub29sc2hlZC5nZXRDdXJyZW50UGF0aC5wYXRoID0gbnVsbDtcblxuICAgICAgaWYgKGRydXBhbFNldHRpbmdzLnBhdGguYmFzZVVybCkge1xuICAgICAgICBjb25zdCByZWdleCA9IG5ldyBSZWdFeHAoYF4ke0RydXBhbC5Ub29sc2hlZC5lc2NhcGVSZWdleChkcnVwYWxTZXR0aW5ncy5wYXRoLmJhc2VVcmwpfWAsICdpJyk7XG4gICAgICAgIERydXBhbC5Ub29sc2hlZC5nZXRDdXJyZW50UGF0aC5wYXRoID0gd2luZG93LmxvY2F0aW9uLnBhdGhuYW1lLnJlcGxhY2UocmVnZXgsICcnKTtcbiAgICAgIH1cbiAgICAgIGVsc2Uge1xuICAgICAgICB0aHJvdyBFcnJvcignQmFzZSBwYXRoIGlzIHVuYXZhaWxhYmxlLiBUaGlzIHVzdWFsbHkgb2NjdXJzIGlmIGdldEN1cnJlbnRQYXRoKCkgaXMgcnVuIGJlZm9yZSB0aGUgRE9NIGlzIGxvYWRlZC4nKTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICByZXR1cm4gRHJ1cGFsLlRvb2xzaGVkLmdldEN1cnJlbnRQYXRoLnBhdGg7XG4gIH0sXG5cbiAgLyoqXG4gICAqIFBhcnNlIFVSTCBxdWVyeSBwYXJhbXRlcnMgZnJvbSBhIFVSTC5cbiAgICpcbiAgICogQHBhcmFtIHtzdHJpbmd9IHVybFxuICAgKiAgRnVsbCBVUkwgaW5jbHVkaW5nIHRoZSBxdWVyeSBwYXJhbWV0ZXJzIHN0YXJ0aW5nIHdpdGggJz8nIGFuZFxuICAgKiAgc2VwYXJhdGVkIHdpdGggJyYnIGNoYXJhY3RlcnMuXG4gICAqXG4gICAqIEByZXR1cm4ge09iamVjdH1cbiAgICogIEpTT04gZm9ybWF0dGVkIG9iamVjdCB3aGljaCBoYXMgdGhlIHByb3BlcnR5IG5hbWVzIGFzIHRoZSBxdWVyeVxuICAgKiAga2V5LCBhbmQgdGhlIHByb3BlcnR5IHZhbHVlIGlzIHRoZSBxdWVyeSB2YWx1ZS5cbiAgICovXG4gIGdldFVybFBhcmFtcyh1cmwpIHtcbiAgICBjb25zdCBwYXJhbXMgPSB7fTtcbiAgICBjb25zdCBwU3RyID0gKHVybCB8fCB3aW5kb3cubG9jYXRpb24uc2VhcmNoKS5zcGxpdCgnPycpO1xuXG4gICAgaWYgKHBTdHIubGVuZ3RoID4gMSkge1xuICAgICAgcFN0clsxXS5zcGxpdCgnJicpLmZvckVhY2goKHBhcmFtKSA9PiB7XG4gICAgICAgIGNvbnN0IG1hdGNoZXMgPSAvXihbXj1dKyk9KC4qKSQvLmV4ZWMocGFyYW0pO1xuXG4gICAgICAgIGlmIChtYXRjaGVzKSB7XG4gICAgICAgICAgcGFyYW1zW2RlY29kZVVSSUNvbXBvbmVudChtYXRjaGVzWzFdKV0gPSBkZWNvZGVVUklDb21wb25lbnQobWF0Y2hlc1syXSk7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH1cblxuICAgIHJldHVybiBwYXJhbXM7XG4gIH0sXG5cbiAgLyoqXG4gICAqIEJ1aWxkIGEgVVJMIGJhc2VkIG9uIGEgRHJ1cGFsIGludGVybmFsIHBhdGguIFRoaXMgZnVuY3Rpb24gd2lsbCB0ZXN0XG4gICAqIGZvciB0aGUgYXZhaWxhYmlsaXR5IG9mIGNsZWFuIFVSTCdzIGFuZCBwcmVmZXIgdGhlbSBpZiBhdmFpbGFibGUuXG4gICAqIFRoZSBVUkwgY29tcG9uZW50cyB3aWxsIGJlIHJ1biB0aHJvdWdoIFVSTCBlbmNvZGluZy5cbiAgICpcbiAgICogQHBhcmFtIHtzdHJpbmd9IHJhd1VybFxuICAgKiAgVGhlIFVSTCB0byBhZGQgdGhlIHF1ZXJ5IHBhcmFtZXRlcnMgdG8uIFRoZSBVUkwgY2FuIHByZXZpb3VzbHkgaGF2ZVxuICAgKiAgcXVlcnkgcGFyYW1ldGVycyBhbHJlYWR5IGluY2x1ZGUuIFRoaXMgd2lsbCBhcHBlbmQgYWRkaXRpb25hbCBwYXJhbXMuXG4gICAqIEBwYXJhbSB7T2JqZWN0fHN0cmluZ30gcGFyYW1zXG4gICAqICBBbiBvYmplY3QgY29udGFpbmluZyBwYXJhbWV0ZXJzIHRvIHVzZSBhcyB0aGUgVVJMIHF1ZXJ5LiBPYmplY3RcbiAgICogIHByb3BlcnR5IGtleXMgYXJlIHRoZSBxdWVyeSB2YXJpYWJsZSBuYW1lcywgYW5kIHRoZSBvYmplY3QgcHJvcGVydHlcbiAgICogIHZhbHVlIGlzIHRoZSB2YWx1ZSB0byB1c2UgZm9yIHRoZSBxdWVyeS5cbiAgICpcbiAgICogQHJldHVybiB7c3RyaW5nfVxuICAgKiAgVGhlIHZhbGlkIERydXBhbCBVUkwgYmFzZWQgb24gdmFsdWVzIHBhc3NlZC5cbiAgICovXG4gIGJ1aWxkVXJsKHJhd1VybCwgcGFyYW1zKSB7XG4gICAgbGV0IHVybCA9IHJhd1VybDtcblxuICAgIC8vIGxlYXZlIGFic29sdXRlIFVSTCdzIGFsb25lLlxuICAgIGlmICghKC9eKFthLXpdezIsNX06KT9cXC9cXC8vaSkudGVzdCh1cmwpKSB7XG4gICAgICB1cmwgPSB1cmwgPyB1cmwucmVwbGFjZSgvXlsvLFxcc10rfDxmcm9udD58KFsvLFxcc10rJCkvZywgJycpIDogJyc7XG4gICAgICB1cmwgPSAoZHJ1cGFsU2V0dGluZ3MucGF0aC5iYXNlVXJsID8gZHJ1cGFsU2V0dGluZ3MucGF0aC5iYXNlVXJsIDogJy8nKSArIHVybDtcbiAgICB9XG5cbiAgICBpZiAocGFyYW1zKSB7XG4gICAgICBjb25zdCBxcnkgPSAodHlwZW9mIHBhcmFtcyA9PT0gJ3N0cmluZycpXG4gICAgICAgID8gcGFyYW1zIDogT2JqZWN0LmVudHJpZXMocGFyYW1zKS5yZWR1Y2UoKGFjYywgZW50cnkpID0+IGAke2FjY30mJHtlbmNvZGVVUklDb21wb25lbnQoZW50cnlbMF0pfT0ke2VuY29kZVVSSUNvbXBvbmVudChlbnRyeVsxXSl9YCwgJycpLnN1YnN0cigxKTtcblxuICAgICAgaWYgKHFyeS5sZW5ndGgpIHtcbiAgICAgICAgdXJsICs9ICh1cmwuaW5kZXhPZignPycpID09PSAtMSA/ICc/JyA6ICcmJykgKyBxcnk7XG4gICAgICB9XG4gICAgfVxuXG4gICAgcmV0dXJuIHVybDtcbiAgfSxcblxuICAvKipcbiAgICogQ3JlYXRlIGEgbGFtYmRhIHRoYXQgY2FuIG1ha2UgcmVxdWVzdHMgR0VUIHRvIGEgc3BlY2lmaWVkIFVSSS5cbiAgICpcbiAgICogVGhlIHJlc3VsdGluZyBsYW1iZGEgdGhhdCB3aGVuIGV4ZWN1dGVkIHdvdWxkIHJldHVybiBhIFByb21pc2VcbiAgICogZW5jYXBzdWxhdGluZyBhIHJlcXVlc3QgdG8gdGhlIFVSTCB3aXRoIHRoZSBwYXJhbWV0ZXJzIHBhc3NlZC5cbiAgICpcbiAgICogQHBhcmFtIHtzdHJpbmd9IHVyaVxuICAgKiAgIFVSSSB0byBjcmVhdGUgYSByZXF1ZXN0IGZ1bmN0aW9uIGZvci5cbiAgICogQHBhcmFtIHtvYmplY3R9IG9wdHNcbiAgICogICBPcHRpb25zIGZvciBob3cgdGhlIHJlcXVlc3Qgc2hvdWxkIGJlIHNlbnQgYW5kIHRoZSBleHBlY3RlZCBkYXRhXG4gICAqICAgcmVzdWx0cyBzaG91bGQgYXBwZWFyLlxuICAgKlxuICAgKiAgIFRoZSBmb2xsb3dpbmcgb3B0aW9ucyBhcmUgYXZhaWxhYmxlOlxuICAgKiAgICAtIG1ldGhvZDogVGhlIEhUVFAgbWV0aG9kIHRvIHVzZSB3aGVuIGNyZWF0aW5nIHRoZSByZXF1ZXN0LlxuICAgKiAgICAtIGZvcm1hdDogVGhlIGV4cGVjdGVkIGRhdGEgcmVzcG9uc2UgZm9ybWF0IChKU09OLCBIVE1MLCBldGMuLi4pXG4gICAqXG4gICAqIEByZXR1cm4ge2Z1bmN0aW9uKHBhcmFtczpvYmplY3QpfVxuICAgKiAgIEEgbGFtYmRhIHRoYXQgY3JlYXRlcyBhIEhUVFAgR0VUIHJlcXVlc3QgdG8gc3BlY2lmaWVkIFVSSSB3aXRoXG4gICAqICAgcGFzc2VkIGluIFVSTCBxdWVyeSBwYXJhbXMuIFRoZSBsYW1iYSByZXR1cm5zIGEgUHJvbWlzZSB3aGljaFxuICAgKiAgIGNhbiBiZSB1c2VkIHRvIHByb2Nlc3MgdGhlIHJlc3VsdHMgb3IgZXJyb3JzLlxuICAgKi9cbiAgY3JlYXRlR2V0UmVxdWVzdGVyKHVyaSwgb3B0cyA9IHsgbWV0aG9kOiAnR0VUJywgZm9ybWF0OiAnanNvbicgfSkge1xuICAgIGNvbnN0IHBhcnNlciA9IG9wdHMuZm9ybWF0ID09PSAnanNvbicgPyBKU09OLnBhcnNlIDogcmVzcCA9PiByZXNwO1xuXG4gICAgaWYgKG9wdHMubWV0aG9kID09PSAnUE9TVCcgfHwgb3B0cy5tZXRob2QgPT09ICdQVVQnKSB7XG4gICAgICByZXR1cm4gcGFyYW1zID0+IG5ldyBQcm9taXNlKChyZXNvbHZlLCByZWplY3QpID0+IHtcbiAgICAgICAgY29uc3QgcmVxID0gbmV3IFhNTEh0dHBSZXF1ZXN0KCk7XG5cbiAgICAgICAgcmVxLm9wZW4ob3B0cy5tZXRob2QsIERydXBhbC5Ub29sc2hlZC5idWlsZFVybCh1cmkpLCB0cnVlKTtcbiAgICAgICAgcmVxLm9ucmVhZHlzdGF0ZWNoYW5nZSA9ICgpID0+IHtcbiAgICAgICAgICBpZiAodGhpcy5yZWFkeVN0YXRlID09PSBYTUxIdHRwUmVxdWVzdC5ET05FKSB7XG4gICAgICAgICAgICBpZiAodGhpcy5zdGF0dXMgPT09IDIwMCkgcmVzb2x2ZShwYXJzZXIocmVxLnJlc3BvbnNlKSk7XG4gICAgICAgICAgICBlbHNlIHJlamVjdChFcnJvcihgJHtyZXEuc3RhdHVzfTogJHtyZXEuc3RhdHVzVGV4dH1gKSk7XG4gICAgICAgICAgfVxuICAgICAgICB9O1xuXG4gICAgICAgIC8vIFVuYWJsZSB0byBjb250YWN0IHRoZSBzZXJ2ZXIgb3Igbm8gcmVzcG9uc2UuXG4gICAgICAgIHJlcS5vbmVycm9yID0gKCkgPT4gcmVqZWN0KEVycm9yKCdVbmFibGUgdG8gY29ubmVjdCcpKTtcblxuICAgICAgICAvLyBDb252ZXJ0IHBhcmFtZXRlcnMgaW50byBVUkwgZW5jb2RlZCB2YWx1ZXMgZm9yIHJldHVybmluZy5cbiAgICAgICAgY29uc3QgYm9keSA9ICh0eXBlb2YgcGFyYW1zID09PSAnc3RyaW5nJylcbiAgICAgICAgICA/IHBhcmFtcyA6IE9iamVjdC5lbnRyaWVzKHBhcmFtcykucmVkdWNlKChhY2MsIGVudHJ5KSA9PiBgJHthY2N9JiR7ZW5jb2RlVVJJQ29tcG9uZW50KGVudHJ5WzBdKX09JHtlbmNvZGVVUklDb21wb25lbnQoZW50cnlbMV0pfWAsICcnKS5zdWJzdHIoMSk7XG5cbiAgICAgICAgcmVxLnNlbmQoYm9keSk7XG4gICAgICB9KTtcbiAgICB9XG5cbiAgICAvLyBCYXNpYyBHRVQgb3IgSEVBRCBIVFRQIHJlcXVlc3RzLlxuICAgIHJldHVybiBwYXJhbXMgPT4gbmV3IFByb21pc2UoKHJlc29sdmUsIHJlamVjdCkgPT4ge1xuICAgICAgY29uc3QgcmVxID0gbmV3IFhNTEh0dHBSZXF1ZXN0KCk7XG4gICAgICByZXEub3BlbihvcHRzLm1ldGhvZCwgRHJ1cGFsLlRvb2xzaGVkLmJ1aWxkVXJsKHVyaSwgcGFyYW1zKSwgdHJ1ZSk7XG4gICAgICByZXEub25sb2FkID0gKCkgPT4ge1xuICAgICAgICBpZiAocmVxLnN0YXR1cyA9PT0gMjAwKSByZXNvbHZlKHBhcnNlcihyZXEucmVzcG9uc2UpKTtcbiAgICAgICAgZWxzZSByZWplY3QoRXJyb3IocmVxLnN0YXR1c1RleHQpKTtcbiAgICAgIH07XG5cbiAgICAgIC8vIFVuYWJsZSB0byBjb250YWN0IHRoZSBzZXJ2ZXIgb3Igbm8gcmVzcG9uc2UuXG4gICAgICByZXEub25lcnJvciA9ICgpID0+IHJlamVjdChFcnJvcignVW5hYmxlIHRvIGNvbm5lY3QnKSk7XG4gICAgICByZXEuc2VuZCgpO1xuICAgIH0pO1xuICB9LFxuXG4gIC8qKlxuICAgKiBTZW5kIGEgUmVxdWVzdCB0byBVUkkgd2l0aCBwcm92aWRlZCBwYXJhbWV0ZXJzIGFuZCByZXR1cm4gUHJvbWlzZS5cbiAgICpcbiAgICogQHBhcmFtIHtzdHJpbmd9IHVyaVxuICAgKiAgIFVSSSB0byBidWlsZCB0aGUgcmVxdWVzdCBmb3IuXG4gICAqIEBwYXJhbSB7YXJyYXl8bnVsbH0gcGFyYW1zXG4gICAqICAgUGFyYW1ldGVycyB0byBpbmNsdWRlIHdoZW4gbWFraW5nIHRoZSByZXF1ZXN0LlxuICAgKiBAcGFyYW0ge29iamVjdH0gb3B0c1xuICAgKiAgU2FtZSBzZXQgb2Ygb3B0aW9ucyBhcyBmb3IgY3JlYXRlR2V0UmVxdWVzdGVyKCkgZnVuY3Rpb24uXG4gICAqXG4gICAqIEByZXR1cm4ge1Byb21pc2V9XG4gICAqICAgUHJvbWlzZSB3cmFwcGluZyB0aGUgSFRUUCByZXF1ZXN0LlxuICAgKlxuICAgKiBAc2VlIERydXBhbC5Ub29sc2hlZC5jcmVhdGVHZXRSZXF1ZXN0ZXIoKVxuICAgKi9cbiAgc2VuZEdldFJlcXVlc3QodXJpLCBwYXJhbXMsIG9wdHMgPSB7IG1ldGhvZDogJ0dFVCcsIGZvcm1hdDogJ2pzb24nIH0pIHtcbiAgICByZXR1cm4gRHJ1cGFsLlRvb2xzaGVkLmNyZWF0ZUdldFJlcXVlc3Rlcih1cmksIG9wdHMpKHBhcmFtcyk7XG4gIH0sXG5cbiAgLyoqXG4gICAqIFV0aWxpdHkgZnVuY3Rpb24gdXNlZCB0byBmaW5kIGFuIG9iamVjdCBiYXNlZCBvbiBhIHN0cmluZyBuYW1lLlxuICAgKlxuICAgKiBAcGFyYW0ge3N0cmluZ30gbmFtZVxuICAgKiAgRnVsbHkgcXVhbGlmaWVkIG5hbWUgb2YgdGhlIG9iamVjdCB0byBmZXRjaC5cbiAgICpcbiAgICogQHJldHVybiB7T2JqZWN0fVxuICAgKiAgdGhlIG9iamVjdCBtYXRjaGluZyB0aGUgbmFtZSwgb3IgTlVMTCBpZiBpdCBjYW5ub3QgYmUgZm91bmQuXG4gICAqL1xuICBnZXRPYmplY3QobmFtZSkge1xuICAgIGlmICghKG5hbWUgJiYgbmFtZS5zcGxpdCkpIHJldHVybiBudWxsO1xuXG4gICAgY29uc3QgZmV0Y2hPYmogPSAob2JqLCBpdGVtcykgPT4ge1xuICAgICAgY29uc3QgcGFydCA9IGl0ZW1zLnNoaWZ0KCk7XG5cbiAgICAgIGlmIChvYmpbcGFydF0pIHJldHVybiBpdGVtcy5sZW5ndGggPyBmZXRjaE9iaihvYmpbcGFydF0sIGl0ZW1zKSA6IG9ialtwYXJ0XTtcbiAgICAgIHJldHVybiBudWxsO1xuICAgIH07XG5cbiAgICByZXR1cm4gZmV0Y2hPYmood2luZG93LCBuYW1lLnNwbGl0KCcuJykpO1xuICB9LFxufTtcbiJdfQ==
