/**
 * @file
 * Outputs connection information for the Private Message Node.js status report.
 */

/*global jQuery, Drupal, io, window*/
/*jslint white:true, this, browser:true*/

(function ($, Drupal, io, window) {

  "use strict";

  function loadStatusReport(context, settings) {
    $(context).find("#private_message_nodejs_server_status").once("loadStatusReport").each(function () {

      window.console.log("Initiating connection to the Node.js server");
      var statusReportSocket = io(settings.privateMessageNodejs.nodejsUrl + "/status_report");
      window.console.log(statusReportSocket);

      // Listen for a connection to the remote server.
      statusReportSocket.on('connect', function () {
        window.console.log("Connected to the Node.js server");
        $("#private_message_nodejs_server_status").text(Drupal.t("Checking secret"));

        // Check the provided secret.
        statusReportSocket.emit('check secret', settings.privateMessageNodejs.nodejsSecret);
      });

      // Listen for an emission of the validation of the given secret.
      statusReportSocket.on("check secret", function (message) {

      $("#private_message_nodejs_server_status").text(message);
      });

      // Listen for a disconnection from the remote server.
      statusReportSocket.on('disconnect', function () {
        $("#private_message_nodejs_server_status").text(Drupal.t("Running server not found at @url", {"@url":settings.privateMessageNodejs.nodejsUrl}));
      });
    });
  }

  Drupal.behaviors.privateMessageNodejsStatusReport = {
    attach: function (context, settings) {
      loadStatusReport(context, settings);
    }
  };

}(jQuery, Drupal, io, window));
