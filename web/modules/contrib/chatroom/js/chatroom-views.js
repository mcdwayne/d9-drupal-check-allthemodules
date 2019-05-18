/**
 * @file
 * Backbone views for chatroom.
 */

(function ($, Backbone, Drupal, drupalSettings) {

  'use strict';

  /**
   * Chat view.
   */
  Drupal.chatroom.ChatView = Backbone.View.extend({
    cid: null,
    userListView: null,
    messages: null,
    token: null,

    /**
     * Initializer.
     */
    initialize: function (options) {
      this.cid = options.cid;
      this.userListView = options.userListView;
      this.messages = new Drupal.chatroom.ChatroomMessageCollection([], {cid: this.cid});
      this.token = this.$el.find('.chatroom-irc-buttons').attr('data-token');

      var thisView = this;

      // Find messages that were pre-rendered on the backend and create models for them.
      this.$el.find('.chatroom-board .chatroom-message').each(function () {
        var cmid = $(this).attr('data-cmid');
        var messageModel = new Drupal.chatroom.ChatroomMessageModel({
          cid: thisView.cid,
          cmid: cmid,
          rendered: $(this).html()
        });

        thisView.messages.add(messageModel);
      });

      // Scroll to the last messages.
      this.scrollToLatestMessage();

      // Add event listeners for the input field.
      this.$el.find('.chatroom-irc-buttons textarea').keyup(function(e) {
        if (e.keyCode == 13 && !e.shiftKey && !e.ctrlKey) {
          thisView.postMessage();
        }
        else {
          return true;
        }
      });

      // Add event listeners for the submit button.
      this.$el.find('.chatroom-irc-buttons input[type=submit]').click(function (e) {
        e.preventDefault();
        thisView.postMessage();
      });

      // Trigger loading previous messages when the view is scrolled to the top.
      this.$el.find('.chatroom-board').scroll(function() {
        var yPos = thisView.$el.find('.chatroom-board').scrollTop();
        if (yPos === 0) {
          // @TODO: Prevent sending if there are no more messages?
          thisView.messages.loadPreviousMessages();
        }
      });

      // Update view when messages are added/removed.
      this.listenTo(this.messages, 'update', this.render);

      this.listenTo(Backbone.Events, 'chatroomMessageReceived', this.addMessageToBoard);
      this.listenTo(Backbone.Events, 'chatroomAuthenticated', this.joinChat);
    },

    /**
     * Submits the entered message to the backend.
     */
    postMessage: function () {
      var inputField = this.$el.find('.chatroom-irc-buttons .chatroom-message-entry');
      var messageText = inputField.val().replace(/^\s+|\s+$/g, '');
      var anonNameText = this.$el.find('.chatroom-irc-buttons .chatroom-anon-name').val();

      if (!messageText) {
        return;
      }

      inputField.val('').focus();

      var chatroomMessage = new Drupal.chatroom.ChatroomMessageModel({
        cid: this.cid,
        message: messageText,
        anonName: anonNameText
      });

      // Save message to backend. No need to render here because this should trigger a nodejs message that will add
      // the new message.
      chatroomMessage.save({token: this.token});
    },

    /**
     * Joins the user to the chatroom.
     * Generates the channel token that can be used to join the channel.
     */
    joinChat: function () {
      var thisView = this;

      // Parameters needed for Backbone.sync().
      var options = {
        url: drupalSettings.chatroom.chatroomBasePath + '/' + this.cid + '/join',
        attrs: {}
      };

      // Initiate ajax call. (the 'create' method translates to a POST request)
      Backbone.sync('create', this, options)
        .done(function (data, textStatus, jqXHR) {
          Drupal.Nodejs.joinTokenChannel(data.channel, data.token);

          // Set the initial list of online users in the user list.
          thisView.setOnlineUsers(data.users);
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
          console.log('Unable to join chat: ' + errorThrown);
        });
    },

    /**
     * Notifies the user list view of the currently joined users.
     * @param users
     *   Array of user data.
     */
    setOnlineUsers: function (users) {
      if (!this.userListView) {
        return;
      }

      for (var i = 0; i < users.length; i++) {
        var data = {
          cid: this.cid,
          uid: users[i].uid,
          rendered: users[i].rendered,
          name: users[i].name
        };
        this.userListView.addUser(data);
      }
    },

    /**
     * Adds a message to the chat view.
     * @param message
     *   A nodejs notification object containing the following keys:
     *     cid: Chatroom id,
     *     cmid: Chatroom message id,
     *     timestamp: Timestamp of the message,
     *     rendered: The rendered message,
     */
    addMessageToBoard: function (message) {
      if (message.cid != this.cid) {
        return;
      }

      var chatroomMessage = new Drupal.chatroom.ChatroomMessageModel({
        cid: message.cid,
        cmid: message.cmid,
        rendered: message.rendered
      });

      this.messages.add(chatroomMessage);
    },

    /**
     * Scrolls the view to the last message.
     */
    scrollToLatestMessage: function () {
      if (this.$el.find('.chatroom-board .chatroom-message').length > 0) {
        var boardOffset = this.$el.find('.chatroom-board').offset().top;
        var targetOffset = this.$el.find('.chatroom-board .chatroom-message:last').offset().top;
        var scrollAmount = targetOffset - boardOffset;
        this.$el.find('.chatroom-board').animate({scrollTop: '+='+ scrollAmount +'px'}, 250);
      }
    },

    /**
     * Renders the chat messages.
     */
    render: function () {
      var thisView = this;
      var renderedMessages = this.$el.find('.chatroom-board .chatroom-message');
      var currentTop = renderedMessages.first();
      var previousAdded = false;

      // Collect the ids of messages that are already rendered.
      var renderedCmids = [];
      renderedMessages.each(function () {
        renderedCmids.push($(this).attr('data-cmid'));
      });

      // Render the messages that have not been rendered yet.
      this.messages.each(function (chatroomMessage) {
        if (renderedCmids.indexOf(chatroomMessage.get('cmid')) === -1) {
          // Determine if this messages goes to the top or bottom of the list (order determined by cmid).
          if (renderedCmids.length && renderedCmids[0] > chatroomMessage.get('cmid')) {
            currentTop.before(chatroomMessage.get('rendered'));
            previousAdded = true;
          }
          else {
            thisView.$el.find('.chatroom-board').append(chatroomMessage.get('rendered'));
          }
        }
      });

      // If we have added anything to the top, we will keep the first item at the top (this happens when old messages
      // are loaded.) Otherwise, we will scroll to the last message.
      if (previousAdded) {
        // Scroll back to the original position.
        // Logic borrowed from:
        // http://stackoverflow.com/questions/5688362/how-to-prevent-scrolling-on-prepend
        var previousHeight = 0;
        currentTop.prevAll().each(function () {
          previousHeight += $(this).outerHeight();
        });
        this.$el.find('.chatroom-board').scrollTop(previousHeight);
      }
      else {
        this.scrollToLatestMessage();
      }
    }
  });

  /**
   * User list view.
   */
  Drupal.chatroom.UserListView = Backbone.View.extend({
    cid: null,
    users: null,

    /**
     * Initializer.
     */
    initialize: function (options) {
      this.cid = options.cid;
      this.users = new Drupal.chatroom.ChatroomUserCollection();

      this.listenTo(Backbone.Events, 'chatroomUserOnline', this.addUser);
      this.listenTo(Backbone.Events, 'chatroomUserOffline', this.removeUser);

      // Rerender if user list changes.
      this.listenTo(this.users, 'update', this.render);
    },

    /**
     * Adds a user to the list view.
     * @param message
     *   A nodejs message object containing the following keys:
     *    cid: The id of the chatroom that the user connected to.
     *    uid: The user id.
     *    rendered: The rendered username.
     *    name: The user's name.
     */
    addUser: function (message) {
      if (message.cid != this.cid) {
        return;
      }

      var user = new Drupal.chatroom.ChatroomUserModel({
        uid: message.uid,
        rendered: message.rendered,
        name: message.name
      });

      this.users.add(user);
    },

    /**
     * Removes a user form the list view.
     * @param message
     *   A nodejs message object containing the following keys:
     *    cid: The id of the chatroom that the user connected to.
     *    uid: The user id.
     */
    removeUser: function (message) {
      if (message.cid != this.cid) {
        return;
      }

      this.users.remove(message.uid);
    },

    /**
     * Renders the user list.
     */
    render: function () {
      var thisView = this;
      var renderedUsers = this.$el.find('.chatroom-user-list div');

      // Collect the ids of users that are already rendered.
      var renderedUids = [];
      renderedUsers.each(function () {
        var uid = $(this).attr('data-uid');

        if (thisView.users.get(uid)) {
          renderedUids.push(uid);
        }
        else {
          // User not online anymore - remove from the list.
          $(this).remove();
        }
      });

      // Render the users that have not been rendered yet.
      this.users.each(function (user) {
        if (renderedUids.indexOf(user.get('uid')) === -1) {
          thisView.$el.find('.chatroom-user-list').append(user.get('rendered'));
        }
      });
    }
  });

}(jQuery, Backbone, Drupal, drupalSettings));
