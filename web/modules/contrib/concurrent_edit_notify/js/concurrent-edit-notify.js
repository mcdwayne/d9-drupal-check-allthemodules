/**
 * @file
 * JavaScript API for the Concurrent edit notify module.
 *
 * May only be loaded for authenticated users on node edit form for
 * content types.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  var currentUserID = parseInt(drupalSettings.user.uid, 10);
  var node = drupalSettings.concurrent_edit_notify[currentUserID].node;
  var interval = drupalSettings.concurrent_edit_notify.interval;
  var firstUserId = drupalSettings.concurrent_edit_notify.first_uid;
  var notified_list = [];

  /**
   * @namespace
   */
  Drupal.concurrent_edit_notify = {

    /**
     * Fetch information on this node revision.
     *
     * @param {array} node
     *   A node array with number nid and vid.
     */
    check: function () {
      if (node.hasOwnProperty('nid') && node.hasOwnProperty('vid')) {
        $.ajax({
          url: Drupal.url('concurrent_edit_notify/' + node.nid + '/' + node.vid + '/check'),
          type: 'POST',
          dataType: 'json',
          success: function (response) {
            if (response.hasOwnProperty('message')) {
                $('form.node-form').prepend(response.message);
            }
          }
        });
      }
    }

  };

  /**
   * @namespace
   */
  Drupal.concurrent_token = {

    /**
     * Fetch information on this node revision.
     *
     * @param {array} node
     *   A node array with number nid and vid.
     */
    reset: function () {
      if (node.hasOwnProperty('nid') && node.hasOwnProperty('vid')) {
        $.ajax({
          url: Drupal.url('concurrent_edit_notify/' + node.nid + '/' + node.vid + '/reset'),
          type: 'POST',
          dataType: 'json'
        });
      }
    }

  };

  /**
   * Registers behaviours related to the ajax request.
   */
  Drupal.behaviors.concurrentEditNotify = {
    attach: function (context) {
      $(context).find('.node-form').once('editCheck').each(function () {
        Drupal.concurrent_edit_notify.check();
      });
      if (firstUserId == currentUserID) {
        $(window).bind('beforeunload', function() {
          Drupal.concurrent_token.reset();
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
