/**
 * @file
 * Client-side script to integrate private message with browser notifications.
 */

/*global Drupal, drupalSettings, io, Notification, window*/
/*jslint white:true, this, browser:true*/

(function (Drupal, drupalSettings, io, Notification, window) {

  "use strict";

  var initialized, browserNotificationSocket, debug;

  function checkPermission() {

    if (debug) {
      window.console.log("Checking browser permissions for sending notifications");
    }

    if (window.hasOwnProperty("Notification")) {
      var permission = Notification.permission;

      if (permission === "granted") {
        if (debug) {
          window.console.log("User has provided access to send browser notifications");
        }

        return true;
      }

      if (permission !== "denied") {
        Notification.requestPermission().then(function () {

          if (debug) {
            window.console.log("User has provided access to send browser notifications");
          }
          return true;
        });
      }
    }
  }

  function showBrowserNotification(body, icon, title, link, duration) {

    if (checkPermission()) {

      // Default duration is 5 seconds.
      duration = duration || 5000;

      var options = {
        body: body,
        icon: icon
      };

      // Create the notification.
      var n = new Notification(title, options);

      // Set a click listener if a link was passed.
      if (link) {
        n.onclick = function () {
          window.open(link);
        };
      }

      // Set the notification to close after the duration.
      setTimeout(n.close.bind(n), duration);
    }
  }

  function notifyBrowserOfNewMessage(message) {

    showBrowserNotification(message.body, message.icon, message.title, message.link);
  }

  function init() {

    // Only initialize once.
    if (!initialized) {
      initialized = true;

      debug = drupalSettings.privateMessageNodejs.debugEnabled;

      if (debug) {
        window.console.log("Initializing Node.js browser notification integration");
      }

      browserNotificationSocket = io(drupalSettings.privateMessageNodejs.nodejsUrl + "/pm_browser_notification");
      if (debug) {
        window.console.log(browserNotificationSocket);
      }

      // Listen for a connection to the remote server.
      browserNotificationSocket.on("connect", function () {
         // Enter the room for this user.
         if (debug) {
           window.console.log("Connected to browser notification Nodejs server");
         }

         if (debug) {
           window.console.log("Connecting to browser notification Nodejs server user channel: " + drupalSettings.user.uid);
         }

         browserNotificationSocket.emit("user", drupalSettings.user.uid, drupalSettings.privateMessageNodejs.nodejsSecret);
      });

      // Listen for an emission informing of a notification of a new message for
      // the current user.
      browserNotificationSocket.on("notify browser new message", function (message) {

        if (debug) {
          window.console.log("Received notification of new browser message from Node.js server");
        }

        // Send a browser notification of the new message.
        notifyBrowserOfNewMessage(message);
      });

      if (debug) {
        // Listen for an emission informing of a notification of an invalid
        // secret.
        browserNotificationSocket.on("invalid secret", function (secret) {
          window.console.log("Server rejected secret as invalid: " + secret);
        });
      }
    }
  }

  Drupal.behaviors.privateMessageNodejsBrowserNotify = {
    attach: function () {
      init();
    }
  };

}(Drupal, drupalSettings, io, Notification, window));
