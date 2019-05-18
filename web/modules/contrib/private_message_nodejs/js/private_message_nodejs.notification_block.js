/**
 * @file
 * Client-side script integrating private message notification block and Nodejs.
 */

/*global jQuery, Drupal, drupalSettings, io, window*/
/*jslint white:true, this, browser:true*/

(function ($, Drupal, drupalSettings, io, window) {

  "use strict";

  var initialized, notificationSocket, debug;

  function init() {

    // Only initialize once.
    if (!initialized) {
      initialized = true;

      debug = drupalSettings.privateMessageNodejs.debugEnabled;

      if (debug) {
        window.console.log("initializing Node.js notification block integration");
      }

      notificationSocket = io(drupalSettings.privateMessageNodejs.nodejsUrl + "/pm_notifications");
      if (debug) {
        window.console.log(notificationSocket);
      }

      // Listen for a connection to the remote server.
      notificationSocket.on('connect', function () {

        if (debug) {
          window.console.log("Connecting to notification block Node.js server user channel: " + drupalSettings.user.uid);
        }

         // Enter the room for this user.
         notificationSocket.emit('user', drupalSettings.user.uid, drupalSettings.privateMessageNodejs.nodejsSecret);
      });

      // Listen for an emission informing of thread update for a thread the user
      // belongs to.
      notificationSocket.on("update pm unread thread count", function () {

        if (debug) {
          window.console.log("Receiving Node.js notification to update unread thread count");
        }

        // Fetch unread thread count from the server.
        Drupal.PrivateMessageNotificationBlock.getUnreadThreadCount();
      });

      if (debug) {
        // Listen for an emission informing of a notification of an invalid
        // secret.
        notificationSocket.on("invalid secret", function (secret) {
          window.console.log("Server rejected secret as invalid: " + secret);
        });
      }

      Drupal.AjaxCommands.prototype.privateMessageNodejsTriggerUnreadThreadCountUpdateCommand = function (ajax, response) {

        // For jSlint compatibility.
        ajax = ajax;

        if (Drupal.PrivateMessageNotificationBlock) {
          $.each(response.uids, function (index) {
            if (debug) {
              window.console.log("Emitting notification to update unread thread count to user: " + response.uids[index]);
            }
            notificationSocket.emit("update pm unread thread count", response.uids[index], drupalSettings.privateMessageNodejs.nodejsSecret);
          });
        }
      };
    }
  }

  Drupal.behaviors.privateMessageNodejsNotificationBlock = {
    attach: function () {
      init();
    }
  };

}(jQuery, Drupal, drupalSettings, io, window));
