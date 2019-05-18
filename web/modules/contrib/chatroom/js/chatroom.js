(function ($, Backbone, Drupal, drupalSettings) {

  Drupal.chatroom = {};
  Drupal.chatroom.chats = {};

  /**
   * Initializes chatroom.
   */
  Drupal.behaviors.chatroom = {
    attach: function (context) {
      // Find all chatrooms and initialize Backbone views for them.
      $(context).find('.chatroom-container').once('chatroom-init').each(function () {
        var cid = $(this).attr('data-cid');

        // Find user list view corresponding to this chat.
        var userListElement = $(context).find('.chatroom-user-list-container[data-cid=' + cid +']');
        var userListView = null;
        if (userListElement.length) {
          userListView = new Drupal.chatroom.UserListView({
            el: userListElement[0],
            cid: cid
          });
        }

        var chatView = new Drupal.chatroom.ChatView({
          el: this,
          userListView: userListView,
          cid: cid
        });

        Drupal.chatroom.chats[cid] = chatView;
      });
    }
  };

  /**
   * Triggered when nodejs successfully connects.
   */
  Drupal.Nodejs.connectionSetupHandlers.chatroom = {
    connect: function () {
      // @TODO: Make call to request channel token, send online notification.
      Backbone.Events.trigger('chatroomConnected');
    }
  };

  /**
   * Triggered when a new message is received.
   */
  Drupal.Nodejs.callbacks.chatroomMessageHandler = {
    callback: function (message) {
      Backbone.Events.trigger('chatroomMessageReceived', message.data);
    }
  };

  /**
   * Triggered when a user connects to a channel.
   */
  Drupal.Nodejs.callbacks.clientJoinedTokenChannel = {
    callback: function (message) {
      if (!message.data.userData) {
        // User data not available. This channel may not belong to a chatroom.
        return;
      }
      Backbone.Events.trigger('chatroomUserOnline', message.data.userData);
    }
  };

  /**
   * Triggered when the user has been successfully authenticated by nodejs.
   */
  Drupal.Nodejs.callbacks.clientAuthenticated = {
    callback: function (message) {
      Backbone.Events.trigger('chatroomAuthenticated', message.data);
    }
  };

  /**
   * Triggered when a user leaves the chat.
   */
  Drupal.Nodejs.contentChannelNotificationCallbacks.chatroom = {
    callback: function (message) {
      if (message.data.type == 'disconnect') {
        var cid = message.channel.replace(/^chatroom_/, '');

        Backbone.Events.trigger('chatroomUserOffline', {
          cid: cid,
          uid: message.data.uid
        });
      }
    }
  };

})(jQuery, Backbone, Drupal, drupalSettings);

