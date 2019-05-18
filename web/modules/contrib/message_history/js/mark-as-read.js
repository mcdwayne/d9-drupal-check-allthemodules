/**
 * @file
 * Marks the messages listed in drupalSettings.messagemessage_history.itemsToMarkAsRead
 * as read.
 *
 * Uses the Message History module JavaScript API.
 *
 * @see Drupal.message_history
 */

(function (window, Drupal, drupalSettings) {

  'use strict';

  // When the window's "load" event is triggered, mark all enumerated messages
  // as read. This still allows for Drupal behaviors (which are triggered on the
  // "DOMContentReady" event) to add "new" and "updated" indicators.
  window.addEventListener('load', function () {
    if (drupalSettings.message_history && drupalSettings.message_history.itemsToMarkAsRead) {
      Object.keys(drupalSettings.message_history.itemsToMarkAsRead).forEach(Drupal.message_history.markAsRead);
    }
  });

})(window, Drupal, drupalSettings);
