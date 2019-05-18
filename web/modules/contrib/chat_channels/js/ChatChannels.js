(function ($, Drupal) {

  /**
   * Automaticly refresh all channels every 3 seconds.
   */
  Drupal.behaviors.chatChannels = {
    /**
     *  Attach the interval to the document.
     */
    attach: function (context, settings) {
      var $channel = $('.js-chatChannel');
      $channel.once('refreshChannel').each(function () {
        var channelId = $(this).attr('data-channel-id');
        var $scrollDownButton = $('.js-scrollDownButton');

        setInterval(Drupal.behaviors.chatChannels.refreshMessages, 10000, channelId, false);
        setTimeout(function () {
          Drupal.behaviors.chatChannels.scrollToLastMessage(channelId)
        }, 200);

        var $jsChatCahnnelMessage = $('.js-chatChannelMessage');
        $jsChatCahnnelMessage.once('chatChannelMessage').each(Drupal.behaviors.chatChannels.convertToEmojione);

        $scrollDownButton.once('scrollDownButton').each(function () {
          $scrollDownButton.click(function () {
            Drupal.behaviors.chatChannels.scrollToLastMessage(channelId);
          });

          $channel.scroll(function () {
            if (Drupal.behaviors.chatChannels.channelScrolledBottom(channelId)) {
              $scrollDownButton.hide();
              Drupal.behaviors.chatChannels.refreshNewMessagesIndicator(0);
            } else {
              $scrollDownButton.show();
            }

            var $messages = $("div[data-channel-id='" + channelId + "'].js-chatChannel").find('div[data-channel-message-new]');
            if ($messages.length > 0) {
              $first_new_message = $messages.first();
              if ($first_new_message.visible()) {
                $first_new_message.removeAttr('data-channel-message-new');
                var count = $messages.length - 1;
                Drupal.behaviors.chatChannels.refreshNewMessagesIndicator(count);
              }
            }
          });
        });
      });


      $('.js-chatChannelForm').once('chatChannelMessageForm').each(function () {
        element = $('textarea.js-chatChannelFormMessage', $(this)).emojioneArea({
          saveEmojisAs: "shortname", // unicode | shortname | image
          imageType: "png",
          events: {
            keydown: function (editor, event) {
              // keyCode 13 is the Enter key.
              if (event.keyCode === 13 && !event.shiftKey && ($('ul.textcomplete-dropdown[data-strategy="emojionearea"]').length === 0 || $('ul.textcomplete-dropdown[data-strategy="emojionearea"]:hidden').length > 0)) {
                // Submit the form through 'clicking' the submit button.
                var content = this.getText();
                this.setText(content);

                $form = editor.closest('form');
                $form.find('.js-chatChannelFormSubmit').click();

                // We don't want a new line after the submit.
                event.preventDefault();
              }
            },
            keyup: function (editor, event) {
              var outer_height = editor.parent().outerHeight() + 'px';
              var $form = editor.closest('form');
              var channel_padding = $form.siblings('.chat-channel').parent().css('padding-bottom');

              if (outer_height != channel_padding) {
                $form.siblings('.chat-channel').parent().css('padding-bottom', outer_height);
                Drupal.behaviors.chatChannels.scrollToLastMessage($form.attr('data-channel-id'));
              }
            }
          }
        });
      });
    },

    /**
     * Get the new messages for each channel.
     */
    refreshMessages: function (channelId, scrollToLastMessage) {
      $('.js-chatChannel').each(function () {
        var lastSeenMessageid = $("div[data-channel-id='" + channelId + "'].js-chatChannel").find("div[data-channel-message-seen='true'].js-chatChannelMessage").last().attr('data-channel-message-id');

        Drupal.behaviors.chatChannels.getNewMessages(lastSeenMessageid, channelId, scrollToLastMessage);
      });
    },
    appendMessages: function (messages, channelId) {
      $("div[data-channel-id='" + channelId + "'].js-chatChannel").append(messages);
      $("div[data-channel-id='" + channelId + "'].js-chatChannel .js-chatChannelMessage").once('chatChannelMessage').each(Drupal.behaviors.chatChannels.convertToEmojione);
    },

    getNewMessages: function (lastSeenMessageId, channelId, scrollToLastMessage) {
      var url = location.protocol + '//' + location.host + Drupal.url('chat_channel/ajax');
      $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: {messageid: lastSeenMessageId, channelid: channelId},
        dataType: 'json',
        success: function (data) {
          var messages = data.messages;
          var scrollOffset = Drupal.behaviors.chatChannels.scrollOffset(channelId);
          if (data.message_count > 0) {
            Drupal.behaviors.chatChannels.refreshNewMessagesIndicator(data.message_count)
          }
          Drupal.behaviors.chatChannels.appendMessages(messages, channelId);
          var count = $("div[data-channel-id='" + channelId + "'].js-chatChannel").find('div[data-channel-message-new]').length;
          Drupal.behaviors.chatChannels.refreshNewMessagesIndicator(count);

          if (scrollOffset == 0 || scrollToLastMessage) {
            Drupal.behaviors.chatChannels.scrollToLastMessage(channelId);
          }
        }
      });
    },

    /**
     * Animates the scroll to last message.
     *
     * @param channelId
     */
    scrollToLastMessage: function (channelId) {
      var $chatChannel = $("div[data-channel-id='" + channelId + "'].js-chatChannel");
      var chatChannelScrollHeight = $chatChannel.prop("scrollHeight");
      var chatChannelScrollTop = $chatChannel.prop("scrollTop");
      if (chatChannelScrollHeight != chatChannelScrollTop) {
        $chatChannel.animate({scrollTop: $chatChannel.prop("scrollHeight")}, 600);
        $('.js-scrollDownButton').hide();
        Drupal.behaviors.chatChannels.refreshNewMessagesIndicator(0);
      }
    },

    /**
     * Determines the scroll offset of the loaded channel.
     *
     * @param channelId
     * @returns {number}
     */
    scrollOffset: function (channelId) {
      var $chatChannel = $("div[data-channel-id='" + channelId + "'].js-chatChannel");
      var chatChannelScrollHeight = $chatChannel.prop("scrollHeight");
      var chatChannelScrollTop = $chatChannel.prop("scrollTop");
      var chatChannelDivHeight = $chatChannel.prop("offsetHeight");
      return ((chatChannelScrollHeight - chatChannelScrollTop) - chatChannelDivHeight);
    },

    /**
     * Convert a emojione to a image.
     */
    convertToEmojione: function () {
      var element = $(this).find('.field--name-message');
      if (element.length > 0) {
        var message = element.html();
        var output = window.emojione.toImage(message);
        element.html(output);
      }
    },

    /**
     * Check weather a channel is scrolled to the bottom
     *
     * @param channelId
     * @returns {boolean}
     */
    channelScrolledBottom: function (channelId) {
      var scrollOffset = Drupal.behaviors.chatChannels.scrollOffset(channelId);
      return (scrollOffset == 0);
    },

    /**
     * Clear the new message indicators.
     */
    refreshNewMessagesIndicator: function (string) {
      var $jsNewMessageIndicator = $('.js-newMessageIndicator');
      if (string == null || string == 0) {
        $jsNewMessageIndicator.attr('empty');
        $jsNewMessageIndicator.html(string);
      }
      else {
        $jsNewMessageIndicator.removeAttr('empty');
        $jsNewMessageIndicator.html(string);
      }
    },
  }
})
(jQuery, Drupal);