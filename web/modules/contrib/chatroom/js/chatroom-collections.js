/**
 * @file
 * Backbone collections for chatroom.
 */

(function ($, Backbone) {

  'use strict';

  /**
   * Collection for chatroom messages.
   */
  Drupal.chatroom.ChatroomMessageCollection = Backbone.Collection.extend({
    model: Drupal.chatroom.ChatroomMessageModel,
    cid: null,

    /**
     * Comparator callback for sorting.
     */
    comparator: function (model) {
      // Order items by cmid. This function is needed so that cmid is compared as int instead of string.
      return parseInt(model.get('cmid'));
    },

    /**
     * Initializer.
     */
    initialize: function (models, options) {
      this.cid = options.cid;
    },

    /**
     * Generates the url to use for retrieving messages.
     */
    url: function () {
      return drupalSettings.chatroom.chatroomBasePath + '/' + this.cid + '/previous';
    },

    /**
     * Retrieves messages older that the first messages that's already loaded.
     */
    loadPreviousMessages: function () {
      var firstMessage = this.at(0);
      var query = {
        cmid: (firstMessage ? firstMessage.get('cmid') : 0),
        limit: 20
      };

      this.fetch({
        data: query,
        remove: false,
        merge: false
      });
    }
  });

  /**
   * Collection for online users.
   */
  Drupal.chatroom.ChatroomUserCollection = Backbone.Collection.extend({
    model: Drupal.chatroom.ChatroomUserModel
  });

})(jQuery, Backbone);
