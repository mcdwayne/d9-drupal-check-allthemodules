/**
 * @file
 * Contains user_agent_check.js.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.userAgentClassBehavior = {
    attach: function () {
      var userAgentText = navigator.userAgent;
      var listDevicesAndBrowsers = drupalSettings.user_agent_class.ListDevicesAndBrowsers;
      var listDevices = Object.keys(listDevicesAndBrowsers[0].device_entity).map(function (e) {
        return listDevicesAndBrowsers[0].device_entity[e];
      });
      var listBrowsers = Object.keys(listDevicesAndBrowsers[1].user_agent_entity).map(function (e) {
        return listDevicesAndBrowsers[1].user_agent_entity[e];
      });
      var methodProvideBoolean = drupalSettings.user_agent_class.methodProvide;
      if (methodProvideBoolean === '0' || methodProvideBoolean === 0) {
        var classes = [];
        var item = '';
        for (item in listBrowsers) {
          if (listBrowsers[item].exclude.length > 0
            && userAgentText.search(listBrowsers[item].exclude) > 0
            && userAgentText.search(listBrowsers[item].trigger) > 0) {
            continue;
          }
          else if (userAgentText.search(listBrowsers[item].trigger) > 0) {
            classes.push(listBrowsers[item].className);
            break;
          }
        }

        for (item in listDevices) {
          if (listDevices[item].exclude.length > 0
            && userAgentText.search(listDevices[item].exclude) > 0
            && userAgentText.search(listDevices[item].trigger) > 0) {
            continue;
          }
          else if (userAgentText.search(listDevices[item].trigger) > 0) {
            classes.push(listDevices[item].className);
            break;
          }
        }
        var classesInBody = classes.join(' ');
        $('body').addClass(classesInBody);
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
