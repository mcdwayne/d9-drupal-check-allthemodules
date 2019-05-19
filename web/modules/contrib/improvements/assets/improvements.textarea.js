(function ($, Drupal) {
  // Safe leading spaces in textareas
  Drupal.behaviors.improvementsTextareaLeadingSpaces = {
    attach: function attach(context, settings) {
      $('.form-textarea', context).on('keyup.improvementsTextarea', function (event) {
        if (event.keyCode == 13) {
          var textarea = this;
          var textareaValue = textarea.value;
          var textareaCursorPosition = textarea.selectionStart;
          var spacesCount = 0;
          var currentChar;

          for (var i = textareaCursorPosition - 2; i >= 0; i--) {
            currentChar = textareaValue.charAt(i);
            if (currentChar == "\n") {
              break;
            }
            if (currentChar == ' ') {
              spacesCount++;
            }
            else {
              spacesCount = 0;
            }
          }

          if (spacesCount > 0) {
            var spaces = ' '.repeat(spacesCount);
            textarea.value = textareaValue.substring(0, textareaCursorPosition) + spaces + textareaValue.substring(textareaCursorPosition);
            textarea.selectionStart = textarea.selectionEnd = textareaCursorPosition + spacesCount;
          }
        }
      });
    },
    detach: function detach(context, settings, trigger) {
      $('.form-textarea', context).off('keyup.improvementsTextarea');
    }
  };
})(jQuery, Drupal);
