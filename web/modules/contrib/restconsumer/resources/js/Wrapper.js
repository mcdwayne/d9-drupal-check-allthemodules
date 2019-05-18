/**
 * Wrapper for rest calls.
 * Takes care of authorization and retries.
 */
class Restconsumer_Wrapper {
  /**
   * Initialize wrapper, create worker.
   */
  constructor() {
    this.rest = new Restconsumer_Worker();
    this.authorized = null;
    this.openRequests = 0;
  }

  /**
   * Set the current language.
   *
   * @param {string} lang
   */
  setLang(lang) {
    this.rest.setLang(lang);
  }

  /**
   * Add format to retrieve data as json.
   *
   * @param {string} endpoint The endpoint to access.
   *
   * @returns {string} The amended endpoint.
   */
  addFormat(endpoint) {
    return this.rest.addFormat(endpoint);
  }

  /**
   * Authorize the client.
   */
  authorize() {
    if (this.authorized == null) {
      this.authorized = this.rest.authorize();
    }
    return this.authorized;
  }

  /**
   * Return the token.
   *
   * @returns {string} The rest token.
   */
  getToken() {
    return this.rest.getToken();
  }

  /**
   * Whether all requests have been resolved.
   *
   * @returns {boolean} Whether all requests have been resolved.
   */
  isFinished() {
    return this.openRequests == 0;
  }

  /**
   * Execute a rest call.
   *
   * @param {Function} fn The function to execute.
   * @param {array} args The arguments of the function.
   * @param {number} count The number of tries.
   * @param {Object} returnDeferred The deferred to resolve or reject.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  execute(fn, args, count, returnDeferred) {
    this.openRequests++;
    if (count == undefined || count == null) {
      count = 0;
    }
    if (typeof returnDeferred == 'undefined') {
      returnDeferred = jQuery.Deferred();
    }
    var executeFn = this.executeHelper(fn, args, count, returnDeferred)
    this.authorize().done(executeFn);
    return returnDeferred;
  }

  /**
   * Helper to execute a rest call.
   *
   * @param {Function} fn The function to execute.
   * @param {array} args The arguments of the function.
   * @param {number} count The number of tries.
   * @param {Object} returnDeferred The deferred to resolve or reject.
   *
   * @returns {Function} A function encapsulating the request.
   */
  executeHelper(fn, args, count, returnDeferred) {
    var self = this;
    return function () {
      var deferred = fn.apply(self.rest, args);
      deferred.done(function (data) {
        this.openRequests--;
        returnDeferred.resolve(data);
      });
      var retryFn = self.retry(fn, args, count, returnDeferred);
      deferred.fail(retryFn);
    }
  }

  /**
   * Helper to retry failed or resolve successful calls.
   *
   * @param {Function} fn The function to execute.
   * @param {array} args The arguments of the function.
   * @param {number} count The number of tries.
   * @param {Object} returnDeferred The deferred to resolve or reject.
   *
   * @returns {Function} A function encapsulating a retry.
   */
  retry(fn, args, count, returnDeferred) {
    var self = this;
    return function (info, data) {
      if (info.status == 0) {
        this.openRequests--;
        returnDeferred.reject(info);
      } else if (info.status == 201 || info.status == 200) {
        this.openRequests--;
        returnDeferred.resolve(data);
      } else if (info.status >= 400 && info.status < 500 && data.message != '') {
        this.openRequests--;
        returnDeferred.reject(info);
      } else if (count > 3) {
        this.openRequests--;
        returnDeferred.reject(info);
      } else {
        self.execute(fn, args, count + 1, returnDeferred);
      }
    }
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
    return this.execute(this.rest.get, [endpoint, isFile]);
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
    return this.execute(this.rest.post, [endpoint, data]);
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
    return this.execute(this.rest.patch, [endpoint, data]);
  }

  /**
   * Execute a delete rest call.
   *
   * @param {string} endpoint The endpoint to access.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  delete(endpoint, data) {
    return this.execute(this.rest.delete, [endpoint, data]);
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
    return this.execute(this.rest.upload, [endpoint, filename, buffer]);
  }

  /**
   * Execute an arbitrary call using Drupal.ajax.
   *
   * @param {*} settings The settings for Drupal.ajax.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  ajax(settings) {
    return this.execute(this.rest.ajax, [settings, []]);
  }

  /**
   * Submit a form using a form node in the DOM using a Drupal.ajax request.
   *
   * @param {*} form The form node.
   * @param {*} extraData Additional key=value pairs.
   * @param {*} wrapper The wrapper for the result.
   * @param {*} callbacks Object containing functions to override Drupal.ajax callbacks
   *
   * @returns {Deferred} A jQuery deferred.
   */
  submit(form, extraData, wrapper = '', callbacks = {}) {
    var data = {};
    jQuery(form).serializeArray().map(function (x) { data[x.name] = x.value; })
    for (var x in extraData) {
      data[x] = extraData[x];
    }
    var endpoint = jQuery(form).attr('action') + '?destination=' + this.rest.localizePath(jQuery(form).attr('action')) + '?_wrapper_format=drupal_ajax';

    data['form_state[redirect]'] = false;
    return this.execute(this.rest.ajax, [{ url: endpoint, wrapper: wrapper, submit: data, callbacks: callbacks }])
  }

  /**
   * Submit a form using a form node in the DOM using a jQuery post request.
   *
   * @param {*} form The form node.
   * @param {*} extraData Additional key=value pairs.
   *
   * @returns {Deferred} A jQuery deferred.
   */
  submitPlain(form, extraData) {
    var data = {};
    jQuery(form).serializeArray().map(function (x) { data[x.name] = x.value; })
    for (var x in extraData) {
      data[x] = extraData[x];
    }
    var endpoint = jQuery(form).attr('action') + '?destination=' + jQuery(form).attr('action') + '?_wrapper_format=drupal_ajax&_wrapper_format=drupal_ajax';

    data['form_state[redirect]'] = false;
    return this.execute(this.rest.post, [endpoint, data]);
  }

}
