(function (performance, drupal, settings) {
  // This is for YUI compressor not to rename "callbacks" for contrib use.
  "callbacks:nomunge, prevent:nomunge";

  "use strict";

  // Exit early if old browser.
  if (!performance) { return; }

  var
    // Magic key used to inject settings from PHP.
    localConfig = "$config$",
    // Magic key used to inject settings from PHP.
    localData = "$data$",
    // Declare variables here to make them available in this scope.
    data, extra,
    // Array of contrib callbacks.
    callbacks = {
      add: function (func) { this.cache.push(func); },
      cache: []
    },
    // Variable for injected scripts to prevent logging.
    prevent = localData.logPercent <= Math.random() * 100,
    // For minification
    keys = Object.keys,
    stringify = JSON.stringify;

  /**
   * Fires the Ajax call.
   *
   * @param {Object} data
   * @param {Object} extra
   */
  function logData () {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', localConfig.url, true);
    xhr.onreadystatechange = handleReadystatechange;
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    // Stringify here, the PHP serialization has weird parameters.
    data.extra = stringify(extra);
    xhr.send('nt=' + stringify(data));
  }

  /**
   * Handles the AJAX reply.
   *
   * @param {Object} data
   * @param {Array} callbacks
   */
  function handleReadystatechange () {
    var xhr = this;
    if (xhr.readyState === 4 && xhr.status === 200) {
      var responseJSON = {};
      try { responseJSON = JSON.parse(xhr.responseText); } catch (e) {}
      if (localConfig.debug) { callbacks.add(function () {console.log(arguments);}); }
      callbacks.cache.forEach(function (callback) {
        callback.apply(xhr, [responseJSON, data, extra, localConfig]);
      });
    }
  }

  /**
   * Gathers navigation timing data and logs it to the server.
   *
   * @param {Object} data
   * @param {Object} extra
   * @param {Object} localData
   * @param {Object} localConfig
   */
  function navigationTimingLog () {
    var
      undefined,
      pageState,
      body = document.documentElement,
      queryString = location.search,
      no_js = /no_js=1/.test(queryString),
      no_css = /no_css=1/.test(queryString);

    // Drupal specific values.
    data = {
      ua: navigator.userAgent,
      type: performance.navigation.type,
      js: no_js ? 0 : 1,
      css: no_css ? 0 : 1,
      clientWidth: body.clientWidth,
      clientHeight: body.clientHeight
    };
    populateTimingData(data, performance.timing, localData);

    // Add extra information from the Drupal page.
    if (settings) {
      pageState = settings.ajaxPageState;
      extra = {
        js: pageState.js ? keys(pageState.js) : undefined,
        css: pageState.css ?  keys(pageState.css) : undefined,
        behaviors: drupal ? keys(drupal.behaviors) : undefined
      };
    }
    logData();
  }

  /**
   * Fills our data object with navigation timing and drupal-related data.
   *
   * @param {Object} data
   * @param {Object} timing
   * @param {Object} localData
   */
  function populateTimingData (data, timing, localData) {
    // Don't filter the loop, NT values are in the prototype chain.
    for (var time in timing) {
      if (time === 'navigationStart') {
        data[time] = timing[time];
      }
      else {
        // Transform other timestamps to relative duration.
        data[time] = timing[time] > 0 ? timing[time] - timing.navigationStart : 0;
      }
    }
    // Copy Drupal values in the data sent.
     keys(localData).forEach(function (value) {
      data[value] = localData[value];
    });
  }

  /**
   * Handles window load event.
   */
  function handleLoadEvent () {
    // This will remove navigationTimingLog processing time from NT measurements
    // Il will also make sure timing.loadEventEnd is populated.
    setTimeout(navigationTimingLog, 1000);
  }

  // Allow contrib to inject JS replacing functions or data.
  "$inject_js$";

  // 'prevent' can be changed by injected scripts to avoid logging a particular request.
  if (!prevent && window.addEventListener) {
    window.addEventListener('load', handleLoadEvent, true);
  }

}(window['performance'], window['Drupal'], window['drupalSettings']));
