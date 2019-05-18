/**
 * @file
 * Client-side script to integrate the private message inbox block with Node.js.
 */

/*global jQuery, Drupal, drupalSettings, io, window*/
/*jslint white:true, this, browser:true*/

(function ($, Drupal, drupalSettings, io, window) {

  "use strict";

  var initialized, inboxSocket, debug;

  function init() {

    // Only initialize once.
    if (!initialized) {
      initialized = true;

      debug = drupalSettings.privateMessageNodejs.debugEnabled;

      if (debug) {
        window.console.log("Initializing Node.js private message inbox integration");
      }

      inboxSocket = io(drupalSettings.privateMessageNodejs.nodejsUrl + "/pm_inbox");
      if (debug) {
        window.console.log(inboxSocket);
      }

      // Listen for a connection to the remote server.
      inboxSocket.on('connect', function () {

        if (debug) {
          window.console.log("Connecting to pm inbox Node.js server user channel: " + drupalSettings.user.uid);
        }

         // Enter the room for this user.
         inboxSocket.emit('user', drupalSettings.user.uid, drupalSettings.privateMessageNodejs.nodejsSecret);
      });

      // Listen for an emission informing of thread update for a thread the user
      // belongs to.
      inboxSocket.on("update pm inbox", function () {

        if (debug) {
          window.console.log("Received notification to update PM inbox");
        }

        // Fetch new messages from the server.
        Drupal.PrivateMessageInbox.updateInbox();
      });

      if (debug) {
        // Listen for an emission informing of a notification of an invalid
        // secret.
        inboxSocket.on("invalid secret", function (secret) {
          window.console.log("Server rejected secret as invalid: " + secret);
        });
      }

      Drupal.AjaxCommands.prototype.privateMessageNodejsTriggerInboxUpdateCommand = function (ajax, response) {

        // For jSlint compatibility.
        ajax = ajax;

        if (Drupal.PrivateMessageInbox) {
          $.each(response.uids, function (index) {

            if (debug) {
              window.console.log("Emitting notification of necessity for user " + response.uids[index] + " to update their inbox");
            }

            inboxSocket.emit("update pm inbox", response.uids[index], drupalSettings.privateMessageNodejs.nodejsSecret);
          });
        }
      };
    }
  }

  Drupal.behaviors.privateMessageNodejsInbox = {
    attach: function () {
      init();
    }
  };

}(jQuery, Drupal, drupalSettings, io, window));
