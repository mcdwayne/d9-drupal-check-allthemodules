(function($) {

/**
 * Provide a Drupal-specific wrapper for the QUnit JavaScript test framework.
 */
Drupal.tests = Drupal.tests || {};

Drupal.behaviors.toggleIframe = {
  attach: function(context, settings) {
    $('#qunit-test-iframe', context).before('<input id="qunit-iframe-toggle" type="button" value="' + Drupal.t('Show/hide test frame') + '" />');
    $('#qunit-iframe-toggle').click(function() {
      $('#qunit-test-iframe').toggleClass('visible');
      return false;
    });
  }
};

Drupal.behaviors.runTests = {
  attach: function(context, settings) {
    var index;
    var loaded = 0;
    for (index in Drupal.tests) {
      var test = new Drupal.tests[index];
      test.run();
    }
  }
};

Drupal.Test = function() {
}
Drupal.Test.prototype.run = function() {
  module(this.getInfo().group, this);
  test(this.getInfo().name, this.test);
;}

/**
 * Stub class for unit tests.
 */
Drupal.UnitTest = function() {
}
Drupal.UnitTest.prototype = new Drupal.Test;

/**
 * Stub class for web tests.
 */
Drupal.WebTest = function() {
  this.Browser = new Drupal.Browser();
}
Drupal.WebTest.prototype = new Drupal.Test;

/**
 * Set up a test database on the server and initialize the iframe.
 */
Drupal.WebTest.prototype.setup = function() {
  this.className = this.getInfo().serverClass;
  if (!this.className) {
    throw Drupal.t('Subclasses of Drupal.WebTest must define the serverClass property in their getInfo() method.');
  }
  // Give the database 5 minutes to set up, then give up.
  // This takes about 1 minute for me, but slower computers might have more trouble.
  // Also gives a very reasonable amount of time to debug the script.
  stop(600000);
  // Set up the iframe and run qunit/ajax/MachineNameTestCase/setUp.
  this.Browser.init(this.className, jQuery.proxy(this.loadTestContent, this));
};

/**
 * Load the HTML and JS into the iframe for testing.
 */
Drupal.WebTest.prototype.loadTestContent = function() {
  this.Browser.get('qunit/test', jQuery.proxy(this.start, this));
};

Drupal.WebTest.prototype.start = function (child$) {
  if (child$) {
    this.child$ = child$;
    start();
  }
  else {
    throw Drupal.t('No jQuery object was returned from the browser. Make sure the serverClass is valid.');
  }
};

Drupal.WebTest.prototype.teardown = function () {
  this.Browser.exit(this.className);
};

/**
 * Reusable Browser class, currently used by Drupal.WebTest.
 */
Drupal.Browser = function() {
  this.Lock = new Drupal.Lock;
};

Drupal.Browser.prototype.init = function(classname, fn) {
  var iframe = $('#qunit-test-iframe').get(0);
  iframe = (iframe.contentWindow || iframe.contentDocument);
  if (iframe.document) {
    iframe = iframe.document;
  }
  this.testFrame = iframe.body;
  this.Lock.acquire(function() {
    result = $.post(Drupal.settings.basePath + '?q=qunit/ajax/' + classname + '/setUp', {'token': Drupal.settings.formToken}, function(data) {
      document.cookie = 'simpletest_db_prefix=' + data.dbPrefix;
      fn();
    });
  });
};

Drupal.Browser.prototype.get = function(dest, fn) {
  $('#qunit-test-iframe').attr('src', Drupal.settings.basePath + '?q=' + dest);
  $('#qunit-test-iframe').load(function() {
    var child$ = jQuery = $(this).get(0).contentWindow.jQuery;
    if (fn) {
      fn(child$);
    }
  });
};

Drupal.Browser.prototype.exit = function(classname, fn) {
  document.cookie = 'simpletest_db_prefix=';
  $.post(Drupal.settings.basePath + '?q=qunit/ajax/' + classname + '/tearDown', {'token': Drupal.settings.formToken}, function(data) {
    if (fn) {
      fn();
    }
  });
  this.Lock.release();
};

/**
 * Reusable Drupal Lock class.
 */
Drupal.Lock = function(waitTime) {
  this.locked = false;
  this.waitTime = waitTime || 1000;
};

Drupal.Lock.prototype.acquire = function(fn) {
  if (this.locked == false) {
    this.locked = true;
    if (fn) {
      fn();
    }
  }
  else {
    var self = this;
    setTimeout(function() {
      self.acquire(fn);
    }, this.waitTime);
  }
};

Drupal.Lock.prototype.release = function(fn) {
  this.locked = false;
  if (fn) {
    fn();
  }
};

})(jQuery);
