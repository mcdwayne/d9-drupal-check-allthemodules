/**
 * Helper methods for navigating through a Drupal site.
 *
 * This file is included automatically by the casperjs Drush command.
 */
var utils = require('utils');
var f     = utils.format;

// Set the default timeout to 2 minutes, since the backend experience can be
// quite slow. If you change this value in a test suite, please set it back.
casper.options.waitTimeout = 120000;

/**
 * Run a test suite, ending all sessions when done.
 */
casper.drupalRun = function(time) {
  casper.run(function(self) {
    casper.drupalEndSession();
    self.test.done();
  }, time);
};

/**
 * Checks if we have a FQDN path.
 *
 * @return bool
 *   true if a FQDN path was passed.
 */
casper.urlIsFQDN = function(path) {
  var FQDNregexp = new RegExp('^(([A-Z]|[a-z])+:)?\/\/.*');
  return FQDNregexp.test(path);
};

/**
 * Listen to the open.location event in order to prepend the hostname.
 *
 * This filter will automatically prepend the full hostname that you are running
 * tests against to a given path. For example, if you run
 * casper.thenOpen('node/1'), it will convert it to
 * http://drupal.local/node/1 unless it is a FQDN already.
 */
casper.setFilter('open.location', function(location) {
  if (utils.isUndefined(location)) {
    location = "";
  }
  if (!casper.urlIsFQDN(location)) {
    var cleanPath = location.replace(/^\//, '');
    return casper.cli.get('url') + '/' + cleanPath;
  }
  return location;
});

/**
 * Set the viewport to a different breakpoint.
 *
 * @param string $size
 *   A breakpoint name. One of mobile, tablet, tablet-landscape or desktop.
 */
casper.thenChangeViewport = function (size) {
  this.then(function () {
    if (size === 'mobile') {
      this.viewport(320, 400);
    } else if (size === 'tablet') {
      this.viewport(768, 1024);
    } else if (size === 'tablet-landscape') {
      this.viewport(1020, 1020);
    } else if (size === 'desktop') {
      this.viewport(1280, 1280);
    } else {
      test.fail('Responsive Check Not Properly Defined')
    }
  });
};

/**
 * Save page markup to a file. Respect an existing savePageContent function, if
 * casper.js core introduces one.
 *
 * @param String targetFile
 *   A target filename.
 * @return Casper
 */
casper.savePageContent = casper.savePageContent || function(targetFile) {
  var fs = require('fs');

  // Get the absolute path.
  targetFile = fs.absolute(targetFile);
  // Let other code modify the path.
  targetFile = this.filter('page.target_filename', targetFile) || targetFile;
  this.log(f("Saving page html to %s", targetFile), "debug");
  // Try saving the file.
  try {
    fs.write(targetFile, this.getPageContent(), 'w');
  } catch(err) {
    this.log(f("Failed to save page html to %s; please check permissions", targetFile), "error");
    this.log(err, "debug");
    return this;
  }

  this.log(f("Page html saved to %s", targetFile), "info");
  // Trigger the page.saved event.
  this.emit('page.saved', targetFile);

  return this;
};

/**
 * Capture the markup and screenshot of the page. NOTE: Capturing will only
 * occur in one of two ways: either you pass true as the second argument to this
 * function (not recommended, except for testing purposes) or if you have set
 * the CASPERJS_TEST_CAPTURE environment variable to true, like so:
 *
 * $ export CASPERJS_TEST_CAPTURE=true
 *
 * @param string filename
 *   The name of the file to save, without the extension.
 * @param boolean force
 *   Force capturing of screenshots and markup.
 */
casper.drupalCapture = function(filename, force) {
  // If we are not capturing, simply return.
  if (!this.drupalVariableGet('CASPERJS_TEST_CAPTURE', false) && !force) {
    return;
  }
  // If we didn't get a filename, use an empty string.
  if (utils.isFalsy(filename)) {
    filename = '';
  }
  // Otherwise, add a dash delimiter to the end.
  else {
    filename += '-';
  }
  // Make the filename unique with a timestamp.
  filename += new Date().getTime();
  var screenshot = 'screenshots/' + filename + '.jpg',
      markup = 'pages/' + filename + '.html',
      prefix = '',
      screenshot_url = screenshot,
      markup_url = markup;
  // If we have a Drupal files directory available, use it.
  if (casper.drupalVariableGet('DRUPAL_FILES_DIRECTORY')) {
    prefix = casper.drupalVariableGet('DRUPAL_FILES_DIRECTORY') + '/testing/';
    screenshot_url = casper.drupalVariableGet('DRUPAL_FILES_URL') + '/' + screenshot;
    markup_url = casper.drupalVariableGet('DRUPAL_FILES_URL') + '/' + markup;
  }
  this.capture(prefix + screenshot);
  this.test.comment(f('Saved screenshot to %s.', casper.cli.get('url') + '/' + screenshot_url));
  this.savePageContent(prefix + markup);
  this.test.comment(f('Saved markup to %s.', casper.cli.get('url') + '/' + markup_url));
};

/**
 * Some event listeners to log errors to drupalCapture.
 */
casper.on('error', function() {
  casper.drupalCapture('error');
});
casper.on('step.error', function() {
  casper.drupalCapture('step-error');
});
casper.on('step.timeout', function() {
  casper.drupalCapture('step-timeout');
});
casper.on('waitFor.timeout', function() {
  casper.drupalCapture('wait-for-timeout');
});
casper.on('started', function() {
  // If we have http authentication credentials, use them.
  if (casper.drupalVariableGet('CASPERJS_TEST_HTTP_USERNAME') && casper.drupalVariableGet('CASPERJS_TEST_HTTP_PASSWORD')) {
    this.log("Using HTTP Authentication.");
    casper.setHttpAuth(casper.drupalVariableGet('CASPERJS_TEST_HTTP_USERNAME'), casper.drupalVariableGet('CASPERJS_TEST_HTTP_PASSWORD'));
  }
});

/**
 * Retrieves an environment variable.
 *
 * @param String key
 *   The name of the variable to retrieve.
 * @param defaultValue
 *   The default value to return if no environment variable exists.
 * @return
 *   The value from the environment variable, or the default if none was found,
 *   or undefined if neither are found.
 */
casper.drupalVariableGet = function(key, defaultValue) {
  var variables = require('system').env;
  return utils.isUndefined(variables[key]) ? defaultValue : variables[key];
};

// Comment about the status of the CASPERJS_TEST_CAPTURE variable.
if (casper.drupalVariableGet('CASPERJS_TEST_CAPTURE')) {
  casper.test.comment('Capturing of screenshots and markup is enabled.');
}

/**
 * Fill in a Drupal autocomplete field.
 *
 * Fills an autocomplete field with the provided value, then waits for the
 * AJAX operation to complete and clicks the element. Simulating filling the
 * field from the suggestions list.
 *
 * If you're using this on a form where you also want to fill in other fields
 * you will need to do so using something like jQuery inside of an evaluate()
 * call. Using casper.fill() after casper.drupalFillAutocomplete() will
 * essentially negate the values in the autocomplete field.
 *
 * Example:
 *
 * @code
 * casper.drupalFillAutocomplete('#edit-uid', 'admin');
 * casper.thenEvaluate(function() {
 *   jQuery('#edit-user-limit').val(4);
 * });
 * casper.thenClick('#edit-submit');
 * @endcode
 *
 * @param String field
 *   Selector for the input field to fill in.
 * @param String value
 *   Value to fill in for the autocomplete field.
 *
 * @return boolean
 *   True of the autocomplete was able to be filled.
 */
casper.drupalFillAutocomplete = function(field, value) {
  casper.thenEvaluate(function(js_field, js_value) {
    // First set some value in the field.
    jQuery(js_field).val(js_value);
    // Then type something to trigger the autocomplete handler.
    jQuery(js_field).trigger('keyup');
  }, field, value);

  // Wait for the autocomplete suggestions to load and choose the first one.
  // This isn't perfect, but it should suffice as long as values are unique
  // enough.
  casper.waitForSelector('#autocomplete li');

  casper.thenEvaluate(function(js_field) {
    jQuery('#autocomplete li:first').trigger('mouseover');
    jQuery('#autocomplete li:first').trigger('mousedown');
    jQuery(js_field).blur();
  }, field);

  return true;
};

/**
 * Generates a random string of ASCII characters of codes 32 to 126.
 *
 * The generated string includes alpha-numeric characters and common
 * miscellaneous characters. Use this method when testing general input where
 * the content is not restricted.
 *
 * Do not use this method when special characters are not possible (e.g., in
 * machine or file names that have already been validated); instead, use
 * casper.randomName().
 *
 * @param Number length
 *   Length of random string to generate.
 *
 * @return String
 *   Randomly generated string.
 */
casper.randomString = casper.randomString || function(length) {
  length = length || 8;
  var str = '';
  for (i = 0; i < length; i++) {
    str += String.fromCharCode(32 + Math.floor((Math.random() * 95)));
  }
  return str;
};

/**
 * Generates a random string containing letters and numbers.
 *
 * The string will always start with a letter. The letters may be upper or
 * lower case. This method is better for restricted inputs that do not accept
 * certain characters. For example, when testing input fields that require
 * machine readable values (i.e. without spaces and non-standard characters)
 * this method is best.
 *
 * Do not use this method when testing unvalidated user input. Instead,
 * use casper.randomString().
 *
 * @param Number length
 *   Length of random string to generate.
 *
 * @return String
 *   Randomly generated string.
 */
casper.randomName = casper.randomName || function(length) {
  length = length || 8;
  var str = String.fromCharCode(97 + Math.floor((Math.random() * 26)));
  var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  for (i = 1; i < length; i++) {
    str += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return str;
};
