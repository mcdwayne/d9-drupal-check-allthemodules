/**
 * @file
 * Backbone models for chatroom.
 */

(function ($, Backbone, drupalSettings) {

  'use strict';

  /**
   * Model for chatroom messages.
   */
  Drupal.chatroom.ChatroomMessageModel = Backbone.Model.extend({
    idAttribute: 'cmid',

    /**
     * Generates the url to use for saving new messages.
     * @returns {string}
     */
    url: function () {
      return drupalSettings.chatroom.chatroomBasePath + '/' + this.get('cid') + '/post';
    },

    /**
     * Saves the messages to the backend.
     */
    save: function(options) {
      options = options || {};
      options.headers = options.headers || {};

      options.type = 'POST';
      // Token is checked by the post message callback.
      options.headers['X-Csrf-Token'] = options.token;

      var xhr = Backbone.sync.call(this, 'update', this, options);

      return xhr
        .fail(function (jqXHR, textStatus, errorThrown) {
          console.log('Error when saving message (' + errorThrown + ')');
        });
    }
  });

  /**
   * Model for chatroom users.
   */
  Drupal.chatroom.ChatroomUserModel = Backbone.Model.extend({
    idAttribute: 'uid'
  });


})(jQuery, Backbone, drupalSettings);
