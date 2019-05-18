/**
 * @file
 * JavaScript API for the Message History module, with client-side caching.
 *
 * May only be loaded for authenticated users, with the Message History module
 * enabled.
 */

(function ($, Drupal, drupalSettings, storage) {

  'use strict';

  var currentUserID = parseInt(drupalSettings.user.uid, 10);

  // Any message that is older than 30 days is automatically considered read,
  // so for these we don't need to perform a request at all!
  var thirtyDaysAgo = Math.round(new Date().getTime() / 1000) - 30 * 24 * 60 * 60;

  // Use the data embedded in the page, if available.
  var embeddedLastReadTimestamps = false;
  if (drupalSettings.history && drupalSettings.history.lastReadTimestamps) {
    embeddedLastReadTimestamps = drupalSettings.history.lastReadTimestamps;
  }

  /**
   * @namespace
   */
  Drupal.message_history = {

    /**
     * Fetch "last read" timestamps for the given messages.
     *
     * @param {Array} messageIDs
     *   An array of message IDs.
     * @param {function} callback
     *   A callback that is called after the requested timestamps were fetched.
     */
    fetchTimestamps: function (messageIDs, callback) {
      // Use the data embedded in the page, if available.
      if (embeddedLastReadTimestamps) {
        callback();
        return;
      }

      $.ajax({
        url: Drupal.url('message-history/get_read_timestamps'),
        type: 'POST',
        data: {'message_ids[]': messageIDs},
        dataType: 'json',
        success: function (results) {
          for (var messageID in results) {
            if (results.hasOwnProperty(messageID)) {
              storage.setItem('Drupal.message_history.' + currentUserID + '.' + messageID, results[messageID]);
            }
          }
          callback();
        }
      });
    },

    /**
     * Get the last read timestamp for the given message.
     *
     * @param {number|string} messageId
     *   A message ID.
     *
     * @return {number}
     *   A UNIX timestamp.
     */
    getLastRead: function (messageId) {
      // Use the data embedded in the page, if available.
      if (embeddedLastReadTimestamps && embeddedLastReadTimestamps[messageId]) {
        return parseInt(embeddedLastReadTimestamps[messageId], 10);
      }
      return parseInt(storage.getItem('Drupal.message_history.' + currentUserID + '.' + messageId) || 0, 10);
    },

    /**
     * Marks a message as read, store the last read timestamp client-side.
     *
     * @param {number|string} messageId
     *   A message ID.
     */
    markAsRead: function (messageId) {
      $.ajax({
        url: Drupal.url('message-history/' + messageId + '/read'),
        type: 'POST',
        dataType: 'json',
        success: function (timestamp) {
          // If the data is embedded in the page, don't store on the client
          // side.
          if (embeddedLastReadTimestamps && embeddedLastReadTimestamps[messageId]) {
            return;
          }

          storage.setItem('Drupal.message_history.' + currentUserID + '.' + messageId, timestamp);
        }
      });
    },

    /**
     * Determines whether a server check is necessary.
     *
     * Any message that is >30 days old never gets a "new" or "updated"
     * indicator. Any content that was published before the oldest known reading
     * also never gets a "new" or "updated" indicator, because it must've been
     * read already.
     *
     * @param {number|string} messageId
     *   A message ID.
     * @param {number} contentTimestamp
     *   The time at which some content was published.
     *
     * @return {bool}
     *   Whether a server check is necessary for the given message and its
     *   timestamp.
     */
    needsServerCheck: function (messageId, contentTimestamp) {
      // First check if the content is older than 30 days, then we can bail
      // early.
      if (contentTimestamp < thirtyDaysAgo) {
        return false;
      }

      // Use the data embedded in the page, if available.
      if (embeddedLastReadTimestamps && embeddedLastReadTimestamps[messageId]) {
        return contentTimestamp > parseInt(embeddedLastReadTimestamps[messageId], 10);
      }

      var minLastReadTimestamp = parseInt(storage.getItem('Drupal.message_history.' + currentUserID + '.' + messageId) || 0, 10);
      return contentTimestamp > minLastReadTimestamp;
    }
  };

})(jQuery, Drupal, drupalSettings, window.localStorage);
