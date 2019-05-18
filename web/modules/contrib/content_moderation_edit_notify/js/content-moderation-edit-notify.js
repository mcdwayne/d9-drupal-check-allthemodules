/**
 * @file
 * JavaScript API for the Content moderation edit notify module.
 *
 * May only be loaded for authenticated users on node edit form for moderated
 * content types.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  var currentUserID = parseInt(drupalSettings.user.uid, 10);
  var node = drupalSettings.content_moderation_edit_notify[currentUserID].node;
  var interval = drupalSettings.content_moderation_edit_notify.interval;
  var notified_list = [];

  /**
   * @namespace
   */
  Drupal.content_moderation_edit_notify = {

    /**
     * Fetch information on this node revision.
     *
     * @param {array} node
     *   A node array with number nid and vid.
     */
    checkLastRevision: function () {
      if (node.hasOwnProperty('nid') && node.hasOwnProperty('vid')) {
        $.ajax({
          url: Drupal.url('content_moderation_edit_notify/' + node.nid + '/' + node.vid + '/check'),
          type: 'POST',
          dataType: 'json',
          success: function (response) {
            if (response.hasOwnProperty('message') && response.hasOwnProperty('last_vid')) {
              // Check if we already set a message for this revision.
              if (!notified_list[response.last_vid]) {
                notified_list[response.last_vid] = true;
                $('form.node-form').prepend(response.message);
                $('form.node-form .form-actions').prepend(response.message);
              }
            }
          }
        });
      }
    }

  };

  /**
   * Registers behaviours related to the ajax request.
   */
  Drupal.behaviors.contentModerationEditNotify = {
    attach: function (context) {
      $(context).find('.node-form').once('editCheck').each(function () {
        setInterval("Drupal.content_moderation_edit_notify.checkLastRevision()", interval);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);

