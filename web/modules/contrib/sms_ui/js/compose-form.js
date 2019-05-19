/**
 * compose-form.js
 */

/* jQuery closure */
(function($, Drupal) {

  "use strict";

  Drupal.behaviors.smsUiComposeForm = {
    attach: function (context, settings) {
      var _attached = this;

      // Events for the compose form input fields.
      $(context).find('#edit-message').once('compose-form')
        .on('keyup', function () {
          _attached.updateCharacterCount(context);
        });

      $(context).find('#edit-recipients').once('compose-form')
        .on('keyup', function () {
          _attached.updateRecipientCount(context);
        })
        .on('change', function () {
          _attached.cleanupRecipients(context);
        });

      _attached.updateCharacterCount(context);
      _attached.updateRecipientCount(context);
      _attached.cleanupRecipients(context);

      /**
       * Inserts a selected token in a particular point in the message.
       *
       * @todo
       */
      var $edit = $('#edit-message');
      // Create this hack for browsers that don't have selectionStart and
      // selectionEnd. Pre-save a selection.
      if (typeof($edit.get(0).selectionStart) == 'undefined' && document.selection) {
        $(context).find('#edit-message').once('sms-ui-token')
          .on('keyup mouseup', function () {
            this.focus();
            this.selection = document.selection.createRange().duplicate();
          });
      }
      $(context).find('.sms_ui_token_help span').once('sms-ui-token')
        .css('cursor', 'pointer')
        .on('click', function () {
          $edit.insertAtCaret(this.innerHTML);
          $edit.keyup();
        });

    },

    updateCharacterCount: function (context) {
      var $element = $(context).find('#edit-message');
      $('.js-statistic-character-count').html($element.val().length);
    },

    updateRecipientCount: function (context) {
      var $element = $(context).find('#edit-recipients');
      $('.js-statistic-recipient-count').html(Drupal.smsUi.countNumbers($element.val()));
    },

    cleanupRecipients: function (context) {
      var $element = $(context).find('#edit-recipients');
      var numbers = Drupal.smsUi.splitNumbers($element.val());
      numbers = Drupal.smsUi.filterDuplicates(numbers);
      $element.val(numbers.join('\n'));
      this.updateRecipientCount(context);
    }
  };

  /**
   * Namespace for the smsUi
   */
  Drupal.smsUi = {
    /**
     * Splits the text provided into an array of recipient numbers.
     *
     * This uses a standard regular expression to achieve this.
     *
     * @param text
     *
     * @return Array
     */
    splitNumbers: function(text) {
      text = text.replace(/([\n\r, ]+)/g, '\n').replace(/(^,+)|(,+$)/g, '');
      if (text.length > 1) {
        return text.split('\n').filter(function (number, index) {
          return number.replace(/\b0+/, '') != '';
        });
      }
      else {
        return [];
      }
    },

    /**
     * Counts the mobile numbers in the supplied text.
     *
     * The mobile numbers are assumed comma- or whitespace-delimited.
     */
    countNumbers: function (text) {
      return this.splitNumbers(text).length;
    },

    /**
     * Cleans mobile numbers and harmonizes the separators.
     *
     * This removes the spaces, commas and replaces them with the appropriate
     * delimiter which could be either commas or newlines.
     *
     * @param text string
     *   The text to be processed.
     * @param delimiter string
     *   The standard delimiter to use to replace all other delimiters.
     *
     * @return string
     *   The processed string.
     */
    cleanNumbers: function (text, delimiter) {
      return text.trim().replace(/(^,+)|(,+$)/g, '').replace(/([\n\r, ]+)/g, delimiter);
    },

    /**
     * Calculates the number of pages into which a long message would be divided.
     *
     * This assumes the 7-septet UDH headers would be used to concatenate longer
     * messages.
     *
     * @param message string
     *   The message to be processed.
     *
     * @return int
     *   The number of message parts.
     *
     * @see \Drupal\sms_ui\SmsUi::calculatePages()
     */
    calculatePages: function (message) {
      var msglen = this.utf8String.utf8ByteCount(message);
      if (msglen < 161) {
        return 1;
      }
      else {
        return parseInt((message.length / 152) + 1);
      }
    },

    /**
     * Filter duplicate numbers from the array of provided numbers.
     *
     * @param array
     * 
     * @returns {*}
     */
    filterDuplicates: function (array) {
      var hash = {};
      return array.filter(function(item) {
        return hash.hasOwnProperty(item) ? false : (hash[item] = true);
      });
    }

  };

  Drupal.smsUi.utf8String = {
    /**
     * codePoint - an integer containing a Unicode code point
     * return - the number of bytes required to store the code point in UTF-8
     */
    utf8Len: function (codePoint) {
      if (codePoint >= 0xD800 && codePoint <= 0xDFFF)
        throw new Error("Illegal argument: " + codePoint);
      if (codePoint < 0) throw new Error("Illegal argument: " + codePoint);
      if (codePoint <= 0x7F) return 1;
      if (codePoint <= 0x7FF) return 2;
      if (codePoint <= 0xFFFF) return 3;
      if (codePoint <= 0x1FFFFF) return 4;
      if (codePoint <= 0x3FFFFFF) return 5;
      if (codePoint <= 0x7FFFFFFF) return 6;
      throw new Error("Illegal argument: " + codePoint);
    },

    isHighSurrogate: function (codeUnit) {
      return codeUnit >= 0xD800 && codeUnit <= 0xDBFF;
    },

    isLowSurrogate: function (codeUnit) {
      return codeUnit >= 0xDC00 && codeUnit <= 0xDFFF;
    },

    /**
     * Transforms UTF-16 surrogate pairs to a code point.
     * See RFC2781
     */
    toCodePoint: function (highCodeUnit, lowCodeUnit) {
      if (!this.isHighSurrogate(highCodeUnit)) throw new Error("Illegal argument: " + highCodeUnit);
      if (!this.isLowSurrogate(lowCodeUnit)) throw new Error("Illegal argument: " + lowCodeUnit);
      highCodeUnit = (0x3FF & highCodeUnit) << 10;
      var u = highCodeUnit | (0x3FF & lowCodeUnit);
      return u + 0x10000;
    },

    /**
     * Counts the length in bytes of a string when encoded as UTF-8.
     * str - a string
     * return - the length as an integer
     */
    utf8ByteCount: function (str) {
      var count = 0;
      for (var i = 0; i < str.length; i++) {
        var ch = str.charCodeAt(i);
        if (this.isHighSurrogate(ch)) {
          var high = ch;
          var low = str.charCodeAt(++i);
          count += this.utf8Len(this.toCodePoint(high, low));
        } else {
          count += this.utf8Len(ch);
        }
      }
      return count;
    }
  };

})(jQuery, Drupal);
