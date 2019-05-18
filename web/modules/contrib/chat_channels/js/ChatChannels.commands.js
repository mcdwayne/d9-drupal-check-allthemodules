/**
 * @file
 * Refresh functionality chats.
 */

(function ($, Drupal) {
  /**
   * Refresh the messages of a chat channel.
   *
   * @param ajax
   * @param response
   * @param status
   */
  Drupal.AjaxCommands.prototype.chatChannelRefreshMessage = function (ajax, response, status) {
    // Get new messages for the channel.
    Drupal.behaviors.chatChannels.refreshMessages(response.channelId, false);

    var $form = $('form[data-channel-id="' + response.channelId + '"].js-chatChannelForm');
    var editor = $form.find('textarea.js-chatChannelFormMessage').emojioneArea();

    editor[0].emojioneArea.setText('');

    var outer_height = editor.parent().outerHeight();
    $form.siblings('.chat-channel').parent().css('padding-bottom', outer_height);
  }
})(jQuery, Drupal);