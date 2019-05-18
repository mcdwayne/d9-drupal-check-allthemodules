/**
 * @file
 * Shell form behaviors.
 */

(function ($, shell) {

  'use strict';

  Drupal.behaviors.shellForm = {

    attach: function (context, settings) {

      // Send data when clicking the button.
      $('#shell-send').on('click', function (event, ui) {
        shell.sendCommand('');
        return false;
      });

      // Give focus to the input field.
      $('#shell-input-field').focus();

      // Bind pressing keys in the input field.
      $('#shell-input-field').bind('keydown', function (event) {
        switch (event.keyCode) {
          case 13:
            // Enter pressed.
            shell.sendCommand('');
            shell.historyPointer = shell.history.length;
            return false;

          case 38:
            // Pressed up arrow. Move up through the history.
            shell.historyPointer--;
            if (shell.historyPointer < 0) {
              shell.historyPointer = 0;
            }
            shell.displayInputHistory();
            return false;

          case 40:
            // Pressed down arrow. Move down through the history.
            shell.historyPointer++;
            if (shell.historyPointer >= shell.history.length) {
              shell.historyPointer = shell.history.length;
              // If we reached the bottom, then clear it out.
              $('#shell-input-field').val('');
            }
            else {
              shell.displayInputHistory();
            }
            return false;

          case 9:
            // Tab pressed.
            shell.sendCommand('__pressed_tab');
            shell.historyPointer = shell.history.length;
            return false;

        }
      });
    }

  };

})(jQuery, Drupal.shell);
