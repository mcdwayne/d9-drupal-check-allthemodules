/**
 * Worker for rest calls.
 * Executes requests.
 */
class Restconsumer_Worker {

  /**
   * Initialize worker.
   */
  constructor() {
    this.lang = null;
    this.token = "";
  }

  /**
   * Set the language.
   *
   * @param {string} lang
   */
  setLang(lang = null) {
    this.lang = lang;
  }

  /**
   * Add format to retrieve data as json.
   *
   * @param {string} endpoint The endpoint to access.
   *
   * @returns {string} The amended endpoint.
   */
  addFormat(endpoint) {
    if (endpoint.indexOf('_format=hal_json') == -1) {
      if (endpoint.indexOf('?') == -1) {
        endpoint += "?_format=hal_json";
      } else {
        endpoint += "&_format=hal_json";
      }
    }
    return endpoint;
  }

  /**
   * Localize the endpoint with the given language.
   *
   * @param {string} endpoint The endpoint to access.
   *
   * @returns {string} The localized endpoint.
   */
  localizePath(endpoint) {
    if (this.lang !== null) {
      if (endpoint.startsWith('/' + this.lang)) {
        return endpoint;
      }
      endpoint = '/' + this.lang + endpoint;
    }
    return endpoint;
  }

  /**
   * Return the token.
   *
   * @returns {string} The rest token.
   */
  getToken() {
    return this.token;
  }

  /**
   * Authorize the consumer with backend.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  authorize() {
    var endpoint = this.localizePath('/rest/session/token');
    return jQuery.ajax(endpoint).done(
      (function (self) {
        return function (data) {
          self.token = data;
        }
      })(this)
    );
  }

  /**
   * Execute a get rest call.
   *
   * @param {string} endpoint The endpoint to access.
   * @param {string} isFile Whether we expect a file.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  get(endpoint, isFile) {
    if (!isFile) {
      endpoint = this.addFormat(this.localizePath(endpoint));
      return jQuery.ajax(endpoint,
        {
          method: "GET",
          headers: { "Content-Type": "application/hal+json", 'X-CSRF-Token': self.token }
        }
      );
    } else {
      return jQuery.ajax(endpoint,
        {
          method: "GET",
          headers: { "Content-Type": "application/hal+json", 'X-CSRF-Token': self.token }
        }
      );
    }
  }


  /**
   * Execute a post rest call.
   *
   * @param {string} endpoint The endpoint to access.
   * @param {*} data The data to send.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  post(endpoint, data) {
    endpoint = this.addFormat(this.localizePath(endpoint));
    return jQuery.ajax(endpoint,
      {
        method: "POST",
        headers: { "Content-Type": "application/hal+json", 'X-CSRF-Token': this.token },
        data: JSON.stringify(data),
      });
  }

  /**
   * Execute a patch rest call.
   *
   * @param {string} endpoint The endpoint to access.
   * @param {*} data The data to send.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  patch(endpoint, data) {
    endpoint = this.addFormat(this.localizePath(endpoint));
    return jQuery.ajax(endpoint,
      {
        method: "PATCH",
        headers: { "Content-Type": "application/hal+json", 'X-CSRF-Token': this.token },
        data: JSON.stringify(data),
      });
  }

  /**
   * Execute a delete rest call.
   *
   * @param {string} endpoint The endpoint to access.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  delete(endpoint, data) {
    endpoint = this.addFormat(this.localizePath(endpoint));
    return jQuery.ajax(endpoint,
      {
        method: "DELETE",
        headers: { "Content-Type": "application/hal+json", 'X-CSRF-Token': this.token },
        data: JSON.stringify(data),
      });
  }

  /**
   * Upload a file.
   *
   * @param {string} endpoint The endpoint to access.
   * @param {string} filename The filename to set.
   * @param {*} buffer The file buffer containing the file.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  upload(endpoint, filename, buffer) {
    endpoint = this.addFormat(this.localizePath(endpoint));
    return jQuery.ajax(endpoint,
      {
        method: 'POST',
        data: buffer,
        cache: false,
        contentType: false,
        processData: false,
        // contentType: "application/octet-stream;charset=UTF-8",
        mimeType: "application/octet-stream",
        headers: {
          'Content-Disposition': 'file; filename="' + encodeURIComponent(filename) + '"',
          'Content-Type': 'application/octet-stream',
          'X-CSRF-Token': this.token
        },
      });
  }

  /**
   * Execute an arbitrary call using Drupal.ajax.
   *
   * @param {*} settings The settings for Drupal.ajax.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  ajax(settings) {
    settings.url = this.localizePath(settings.url);
    var DrupalAjax = Drupal.ajax(settings);

    for (var callback in settings.callbacks) {
      DrupalAjax[callback] = settings.callbacks[callback];
    }

    return DrupalAjax.execute();
  }
}
