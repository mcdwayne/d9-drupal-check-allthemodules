/**
 * @file
 * Client-side script to integrate private message threads with Nodejs.
 */

/*global Drupal, drupalSettings, io, window*/
/*jslint white:true, this, browser:true*/

(function (Drupal, drupalSettings, io, window) {

  "use strict";

  var initialized, threadSocket, debug;

  function init() {

    // Only initialize once.
    if (!initialized) {
      initialized = true;

      debug = drupalSettings.privateMessageNodejs.debugEnabled;

      if (debug) {
        window.console.log("Initializing Node.js thread integration");
      }

      threadSocket = io(drupalSettings.privateMessageNodejs.nodejsUrl + "/pm_thread");
      if (debug) {
        window.console.log(threadSocket);
      }

      threadSocket.on('connect', function () {

        if (debug) {
          window.console.log("Connecting to thread Node.js server thread channel: " + drupalSettings.privateMessageThread.threadId);
        }

         // Enter for the room for this thread.
         threadSocket.emit('thread', drupalSettings.privateMessageThread.threadId, drupalSettings.privateMessageNodejs.nodejsSecret);
      });

      // Listen for an emission informing of a new private message in the
      // thread.
      threadSocket.on("new private message", function () {

        if (debug) {
          window.console.log("Receiving notification from Node.js server of new private message");
        }

        // Fetch new messages from the server.
        Drupal.PrivateMessages.getNewMessages();
      });

      if (debug) {
        // Listen for an emission informing of a notification of an invalid
        // secret.
        threadSocket.on("invalid secret", function (secret) {
          window.console.log("Server rejected secret as invalid: " + secret);
        });
      }

      // Set up a callback for when the user has changed threads.
      Drupal.PrivateMessages.threadChange.privateMessageNodejs = {
        threadLoaded: function (threadId) {

          if (debug) {
            window.console.log("Connecting to thread Node.js server thread channel: " + threadId);
          }

          // Join the room for the new thread.
          threadSocket.emit('thread', threadId, drupalSettings.privateMessageNodejs.nodejsSecret);
        }
      };

      Drupal.AjaxCommands.prototype.privateMessageNodejsTriggerNewMessages = function () {

        if (debug) {
          window.console.log("Emitting notification to Node.js server of new message in thread: " + drupalSettings.privateMessageThread.threadId);
        }
        threadSocket.emit("new private message", drupalSettings.privateMessageThread.threadId, drupalSettings.privateMessageNodejs.nodejsSecret);
      };
    }
  }

  Drupal.behaviors.privateMessageNodejsThread = {
    attach: function () {
      init();
    }
  };

}(Drupal, drupalSettings, io, window));
