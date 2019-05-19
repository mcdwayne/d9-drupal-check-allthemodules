/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, window, document, undefined) {

  "use strict";
  var TestSwarm = {
    gotoURL: function (url) {
      if (window.frames.testswarmframe) {
        window.frames.testswarmframe.location.href = url;
      }
      else {
        window.location.href = url;
      }
    },
    getURL: function () {
      if (window.frames.testswarmframe) {
        return window.frames.testswarmframe.location.href;
      }
      else {
        return window.location.href;
      }
    },
    runTests: function(testCollection) {
      if (window.frames.testswarmframe) {
        // Wait for frame to load, otherwise jQuery and Drupal aren't defined.
        // Only needed the first time, after submit/redirect we wait for load.
        // @see: waitForPageLoad
        if (!window.frames.testswarmframe.jQuery) {
          setTimeout (
            function () {
              TestSwarm.runTests (testCollection);
            },
            300
          );
          return;
        }
      }

      // Get all tests with the right jQuery and Drupal
      this.testsToRun = [];
      for (var test in testCollection) {
        if (testCollection[test].getInfo().waitForPageLoad && !window.frames.testswarmframe) {
          window.alert(Drupal.t('This test needs to be run in an iframe.'));
          return;
        }
        else {
          var testObj = {
            info: testCollection[test].getInfo(),
            tests: testCollection[test].tests
          };

          testObj.init = {};
          if (testCollection[test].hasOwnProperty('setup')) {
            testObj.init.setup = testCollection[test].setup;
          }
          if (testCollection[test].hasOwnProperty('teardown')) {
            testObj.init.teardown = testCollection[test].teardown;
          }

          this.testsToRun.push(testObj);
        }
      }
      this.numOfStops = 0;
      this.runOtherTests();
    },
    runOtherTests: function() {
      QUnit.stop();
      this.numOfStops++;

      if (this.testsToRun) {
        while (this.testsToRun.length) {
          var testSet = this.testsToRun[0];
          var info = testSet.info;
          var tests = testSet.tests;

          if (!this.hasProperties(tests)) {
            this.testsToRun.splice(0, 1);
            continue;
          }

          QUnit.module(info.group + ' - ' + info.name, testSet.init);

          // Inject simulate.js if needed
          if (info.useSimulate) {
            var doc = document;
            var add = (typeof $.fn.simulate === 'undefined');
            if (window.frames.testswarmframe) {
              add = (typeof window.frames.testswarmframe.jQuery.fn.simulate === 'undefined');
              doc = window.frames.testswarmframe.document;
            }
            if (add) {
              var newScript = doc.createElement('script');
              newScript.type = 'text/javascript';
              newScript.src = '/sites/all/modules/testswarm/libs/jquery.simulate.js';
              doc.head.appendChild(newScript);
            }
          }

          // Prepare all tests
          while (this.hasProperties(tests)) {
            var testindex = this.getFirstIndex(tests);
            // Always recalculate the objects, they change if the iframe reloads
            var testJQuery = $;
            var testDrupal = Drupal;
            var testWindow = window;
            var testDocument = document;
            if (window.frames.testswarmframe) {
              testWindow = window.frames.testswarmframe;
              testJQuery = testWindow.jQuery;
              testDrupal = testWindow.Drupal;
              testDocument = testWindow.document;
            }
            QUnit.test(testindex, tests[testindex](testJQuery, testDrupal, testWindow, testDocument, undefined));
            delete tests[testindex];
            if (info.waitForPageLoad || this.wait) {
              this.waitForPageLoad();
              this.numOfStops--;
              QUnit.start();
              break;
            }
          }
          if (info.waitForPageLoad || this.wait) {
            break;
          }
        }
      }
      if (!this.wait) {
        while (this.numOfStops) {
          this.numOfStops--;
          QUnit.start();
        }
      }
    },
    iFrameFound: function() {
      if (window.frames.testswarmframe) {
        // Wait for frame to load, otherwise jQuery and Drupal aren't defined.
        if (!window.frames.testswarmframe.jQuery) {
          setTimeout (
            function () {
              TestSwarm.iFrameFound();
            },
            100
          );
          return;
        }
      }
      TestSwarm.wait = false;
      TestSwarm.runOtherTests();
    },
    checkIFrame: function() {
      if (TestSwarm.wait) {
        var iframe = window.frames.testswarmframe;
        if (TestSwarm.currentURL !== iframe.location) {
          clearInterval(TestSwarm.intervalId);
          TestSwarm.iFrameFound();
        }
      }
      else {
        clearInterval(TestSwarm.intervalId);
        TestSwarm.iFrameFound();
      }
    },
    waitForPageLoad: function() {
      this.wait = true;
      var iframe = window.frames.testswarmframe;
      TestSwarm.currentURL = iframe.location;
      TestSwarm.intervalId = setInterval(TestSwarm.checkIFrame, 1000);
      setTimeout(function() {
        TestSwarm.wait = false;
      }, 5000);
    },
    hasProperties: function(obj) {
      var size = 0;
      for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
          size++;
        }
      }
      return size;
    },
    getFirst: function(obj) {
      var first;
      for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
          first = obj[key];
          break;
        }
      }
      return first;
    },
    getFirstIndex: function(obj) {
      var firstIndex;
      for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
          firstIndex = key;
          break;
        }
      }
      return firstIndex;
    }
  };

  /**
   * Provide a Drupal-specific wrapper for the QUnit JavaScript test framework.
   */
  Drupal.tests = Drupal.tests || {};

  var testSwarmRunOnce = false;
  Drupal.behaviors.runTests = {
    attach: function() {
      if (!testSwarmRunOnce) {
        testSwarmRunOnce = true;
        TestSwarm.runTests(Drupal.tests);
      }
    }
  };

})(jQuery, Drupal, this, this.document);
