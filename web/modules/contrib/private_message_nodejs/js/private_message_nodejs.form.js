/**
 * @file
 * Client-side script to integrate private message with browser notifications.
 */

/*global jQuery, Drupal, drupalSettings, io, window*/
/*jslint white:true, this, browser:true*/

(function ($, Drupal, drupalSettings, io, window) {

  "use strict";

  var initialized, browserNotificationSocket, debug;

  function init() {

    // Only initialize once.
    if (!initialized) {
      initialized = true;

      debug = drupalSettings.privateMessageNodejs.debugEnabled;

      if (debug) {
        window.console.log("Initializing Node.js private message form integration");
      }

      browserNotificationSocket = io(drupalSettings.privateMessageNodejs.nodejsUrl + "/pm_browser_notification");
      if (debug) {
        window.console.log(browserNotificationSocket);
      }

      Drupal.AjaxCommands.prototype.privateMessageNodejsNotifyBrowserOfNewMessage = function (ajax, response) {

        // For jSlint compatibility.
        ajax = ajax;

        $.each(response.uids, function (index) {
          if (debug) {
            window.console.log("Emitting notification of new browser message to user: " + response.uids[index]);
          }

          browserNotificationSocket.emit("notify browser new message", response.uids[index], response.message, drupalSettings.privateMessageNodejs.nodejsSecret);
        });
      };
    }
  }

  Drupal.behaviors.privateMessageNodejsForm = {
    attach: function () {
      init();
    }
  };

}(jQuery, Drupal, drupalSettings, io, window));
