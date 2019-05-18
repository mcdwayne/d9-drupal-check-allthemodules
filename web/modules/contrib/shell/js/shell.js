/**
 * @file
 * Shell JQuery scripts.
 */

(function ($) {

  'use strict';

  /**
   * Shell base utility functions.
   */
  Drupal.shell = Drupal.shell || {

    /**
     * An array of commands entered in Shell.
     *
     * @type {array}
     */
    history: [],

    /**
     * The pointer to the current command in the history.
     *
     * @type {number}
     */
    historyPointer: 0,

    /**
     * Indicates AJAX being executed.
     *
     * @type {boolean}
     */
    executing: false,

    /**
     * Processes a command entered in the Shell screen.
     *
     * An Ajax POST is sent to the backend.
     *
     * @param {string} sendData
     *   The command to be executed.
     *
     * @return {boolean}
     *   FALSE if another command is being executed, TRUE otherwise.
     */
    sendCommand: function (sendData) {
      // Avoid re-entrance.
      if (this.executing === true) {
        return false;
      }

      // Add what we typed to the history.
      if (sendData !== '__pressed_tab') {
        this.history.push($('#shell-input-field').val());
      }

      // Some servers have a problem sending certain commands through the POST,
      // like 'cd ..' or 'wget.'  To get around this, we will encode the string
      // the user is trying to send.
      if (sendData === '') {
        sendData = $('#shell-input-field').val();
        sendData = 'command=' + this.encode(sendData);
        // Insert a history item of what the player typed.
        this.insertHistory('<div class="user-command">&gt; ' + $('#shell-input-field').val() + '</div>', true);
        // Clear out the message box.
        $('#shell-input-field').val('');
      }
      if (sendData === '__pressed_tab') {
        sendData = $('#shell-input-field').val();
        sendData = 'command=' + this.encode(sendData);
        sendData = sendData + '&pressed_tab=yes';
      }

      // Add in extra information so we can validate this submission in PHP
      // (to prevent CSRF).
      sendData = sendData + '&form_token=' + $('#shell-display-form input[name=form_token]').val();

      // Add the current working directory (cwd) to the sendData, but encode
      // it just like the command.
      sendData = sendData + '&shell_cwd=' + this.encode($('#shell-display-form input[name=shell-cwd]').val());

      // Show the throbber.
      $('.shell-command-input-suffix').addClass('throbber');

      // Send our sendData to the Shell module via Ajax.
      this.executing = true;
      $.post(Drupal.url('shell/ajax-send-command'), sendData, function (data) {
        // Remove the throbber.
        $('.shell-command-input-suffix').removeClass('throbber');
        // Capture the returned shell_cwd so we can keep track of what the
        // user's current working directory is.
        $('#shell-display-form input[name=shell-cwd]').val(data.shell_cwd);
        // Cleanup screen if required.
        if (data.action === 'clear') {
          Drupal.shell.clear();
        }
        // Text was sent back to go on the screen.
        if (data.text !== '') {
          Drupal.shell.insertHistory(data.text, true);
        }
        // A change to the input field was sent back (probably because the
        // user pressed TAB)
        if (data.input_field !== '') {
          $('#shell-input-field').val(data.input_field);
        }
        Drupal.shell.executing = false;
      }, 'json');
      $('#shell-input-field').focus();
      return true;
    },

    /**
     * Insert in the history the results of the processed command.
     *
     * @param {string} str
     *   The string to add to the history.
     * @param {bool} scroll_bottom
     *   If TRUE, sets the history screen to the last item entered.
     */
    insertHistory: function (str, scroll_bottom) {
      var temp = $('#shell-screen-history').html() + str;
      // Append to the bottom.
      $('#shell-screen-history').html(temp);
      // Make it scroll to bottom.
      if (scroll_bottom) {
        $('#shell-screen-history')[0].scrollTop = $('#shell-screen-history')[0].scrollHeight;
      }
    },

    /**
     * Clear the output screen.
     */
    clear: function () {
      $('#shell-screen-history').html('');
    },

    /**
     * Set the input field to the current this.historyPointer value.
     */
    displayInputHistory: function () {
      $('#shell-input-field').val(this.history[this.historyPointer]);
    },

    /**
     * Encode a string with base64 algorithm.
     *
     * This is a function to provide base64 encoding as well as URI encoding,
     * which we will use to encode the commands we are sending through the
     * POST.
     * This function is based off of a similar public domain function by Tyler
     * Akins, @see http://rumkin.com
     *
     * @param {string} input
     *   The string to be encoded.
     *
     * @return {string}
     *   The base64 encoded string.
     */
    encode: function (input) {
      var keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
      var output = '';
      var chr1;
      var chr2;
      var chr3;
      var enc1;
      var enc2;
      var enc3;
      var enc4;
      var i = 0;

      while (i < input.length) {
        chr1 = input.charCodeAt(i++);
        chr2 = input.charCodeAt(i++);
        chr3 = input.charCodeAt(i++);
        enc1 = chr1 >> 2;
        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
        enc4 = chr3 & 63;

        if (isNaN(chr2)) {
          enc3 = enc4 = 64;
        }
        else if (isNaN(chr3)) {
          enc4 = 64;
        }

        output = output + keyStr.charAt(enc1) + keyStr.charAt(enc2) + keyStr.charAt(enc3) + keyStr.charAt(enc4);
      }

      return encodeURIComponent(output);
    }
  };

})(jQuery);
