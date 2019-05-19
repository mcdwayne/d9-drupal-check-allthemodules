;(function(window) {
  'use strict';
  var Twig = window.Twig;
  var Drupal = window.Drupal;
  var settings = window.drupalSettings;
  Drupal.behaviors.twigJsTestSimple = {
    attach: function(context) {
      var testCases = {
        simpleController: {
          selector: 'twigjs_test_js',
          template: 'testTemplate',
          data: settings.twigjsTest.variables
        },
        fileController: {
          selector: 'twigjs-test-file-js',
          template: 'fileTemplate',
          data: {
            text: 'test_time'
          }
        },
        inlineController: {
          selector: 'twigjs-test-controller-wrapper-js',
          template: 'inlineTemplate',
          data: {
            users: [
              'testUser'
            ]
          }
        }
      };

      for (var prop in testCases) {
        var item = testCases[prop];
        var controllerWrapper = document.getElementById(item.selector);
        if (controllerWrapper) {
          var fcTemplate = Twig.twig({
            id: prop,
            data: settings.twigjsTest[item.template]
          });
          controllerWrapper.innerHTML = fcTemplate.render(item.data);
        }
      }
      var lightWrapper = document.getElementById('twigjs-test-light-wrapper-js');
      if (lightWrapper) {
        var template = TwigLight.twig({
          id: 'light',
          data: settings.twigjsTest.lightTemplate
        });
        lightWrapper.innerHTML = template.render({
          name: 'testName',
          number: 1337
        });
      }
    }
  };
})(window);
