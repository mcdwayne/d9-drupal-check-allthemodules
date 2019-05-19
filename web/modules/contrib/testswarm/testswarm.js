/*jshint strict:true, browser:true, curly:true, eqeqeq:true, expr:true, forin:true, latedef:true, newcap:true, noarg:true, trailing: true, undef:true, unused:true */
/*global Drupal: true, jQuery: true, QUnit:true*/
(function ($, Drupal, drupalSettings, QUnit) {
  "use strict";
  Drupal.behaviors.testswarm = {
    attach: function () {
      var currentTest;
      var mySettings = drupalSettings.testswarm;

      $.extend(QUnit.config, {
        reorder: false, // Never ever re-order tests!
        altertitle: false, // Don't change the title
        autostart: false
      });

      var logger = {log: {}, info: {}, tests: []};
      var currentModule = 'default';

      QUnit.moduleStart = function(module) {
        currentModule = module.name;
        if (!logger.log[currentModule]) {
          logger.log[currentModule] = {};
        }
      };

      QUnit.testStart = function(test) {
        currentTest = test.name;
      };

      QUnit.testDone = function(test) {
        logger.tests.push(test);
      };

      QUnit.done = function(data) {
        logger.info = data;
        logger.caller = mySettings.caller;
        logger.theme = mySettings.theme;
        logger.token = mySettings.token;
        logger.karma = mySettings.karma;
        logger.module = mySettings.module;
        logger.description = mySettings.description;

        // Write back to server
        var url = Drupal.url('testswarm-test-done');
        jQuery.ajax({
          type: "POST",
          url: url,
          timeout: 10000,
          data: logger,
          error: function(response) {
            Drupal.AjaxError(response, url);
          },
          success: function(){
            window.setTimeout(function() {
              if (!mySettings.debug || mySettings.debug !== 'on') {
                if (mySettings.destination) {
                  window.location = mySettings.destination;
                }
                else {
                  window.location = '/testswarm-browser-tests';
                }
              }
            }, 500);

          }
        });
      };
      QUnit.log = function(data) {
        if (!logger.log[currentModule]) {
          logger.log[currentModule] = {};
        }
        if (!logger.log[currentModule][currentTest]) {
          logger.log[currentModule][currentTest] = [];
        }
        logger.log[currentModule][currentTest].push(data);
      };
    }
  };
})(jQuery, Drupal, drupalSettings, QUnit);
