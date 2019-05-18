(function($, Drupal) {
  'use strict';

  /**
   * Handles inserting content into text areas and editors.
   * @constructor
   *
   * @param {HTMLElement} insertContainer
   * @param {Drupal.insert.FocusManager} focusManager
   * @param {Drupal.insert.EditorInterface} [editorInterface]
   */
  Drupal.insert.Inserter = Drupal.insert.Inserter || (function() {

    /**
     * @constructor
     *
     * @param {HTMLElement} insertContainer
     * @param {Drupal.insert.FocusManager} focusManager
     * @param {Drupal.insert.EditorInterface|undefined} [editorInterface]
     */
    function Inserter(insertContainer, focusManager, editorInterface) {
      var self = this;

      if (typeof insertContainer === 'undefined') {
        throw new Error('insertContainer needs to be specified.');
      }
      if (typeof focusManager === 'undefined') {
        throw new Error('focusManager needs to be specified.')
      }
      if (editorInterface && typeof editorInterface !== 'object') {
        throw new Error('editorInterface needs to be an instance of Drupal.insert.EditorInterface.');
      }

      this._focusManager = focusManager;
      this._editorInterface = editorInterface;

      this.$container = $(insertContainer);
      this.$insertStyle = this.$container.find('.insert-style');
      this.$button = this.$container.find('.insert-button');
      this._type = this.$container.data('insert-type');

      this.$button.on('click.insert', function() {
        self._insert();
      });
    }

    $.extend(Inserter.prototype, {

      /**
       * @type {Drupal.insert.FocusManager}
       */
      _focusManager: undefined,

      /**
       * @type {Drupal.insert.EditorInterface|undefined}
       */
      _editorInterface: undefined,

      /**
       * @type {jQuery}
       */
      $container: undefined,

      /**
       * The Insert style select box or the hidden style input, if just one
       * style is enabled.
       * @type {jQuery}
       */
      $insertStyle: undefined,

      /**
       * @type {jQuery}
       */
      $button: undefined,

      /**
       * The widget type or type of the field, the Inserter interacts with, i.e.
       * "file" or "image".
       * @type {string|undefined}
       */
      _type: undefined,

      /**
       * @return {Drupal.insert.FocusManager}
       */
      getFocusManager: function() {
        return this._focusManager;
      },

      /**
       * @return {Drupal.insert.EditorInterface|undefined}
       */
      getEditorInterface: function() {
        return this._editorInterface;
      },

      /**
       * Returns the insert type.
       *
       * @return {string}
       */
      getType: function() {
        return this._type;
      },

      /**
       * Returns the template for the currently selected insert style.
       *
       * @return {string}
       */
      getTemplate: function() {
        var style = this.$insertStyle.val();
        return $('input.insert-template[name$="[' + style + ']"]', this.$container).val();
      },

      /**
       * Inserts content into the current (or last active) editor/textarea on
       * the page.
       *
       * @return {HTMLElement|undefined}
       *
       * @triggers insert
       */
      _insert: function() {
        var active = this._focusManager.getActive();
        var activeElement;
        var content = $(this).triggerHandler('insert');

        if (active && active.insertHtml && this._editorInterface) {
          active.insertHtml(content);
          activeElement = this._editorInterface.getElement(active);
        }
        else if (active) {
          this._insertAtCursor(active, content);
          activeElement = active;
        }

        return activeElement;
      },

      /**
       * Insert content into a textarea at the current cursor position.
       *
       * @param {HTMLElement} textarea
       *   The DOM object of the textarea that will receive the text.
       * @param {string} content
       *   The string to be inserted.
       */
      _insertAtCursor: function(textarea, content) {
        // Record the current scroll position.
        var scroll = textarea.scrollTop;

        // IE support.
        if (document.selection) {
          textarea.focus();
          var sel = document.selection.createRange();
          sel.text = content;
        }

        // Mozilla/Firefox/Netscape 7+ support.
        else if (textarea.selectionStart || textarea.selectionStart == '0') {
          var startPos = textarea.selectionStart;
          var endPos = textarea.selectionEnd;
          textarea.value = textarea.value.substring(0, startPos)
            + content
            + textarea.value.substring(endPos, textarea.value.length);
          textarea.selectionStart = textarea.selectionEnd = startPos + content.length;
        }

        // Fallback, just add to the end of the content.
        else {
          textarea.value += content;
        }

        // Ensure the textarea does not scroll unexpectedly.
        textarea.scrollTop = scroll;
      }

    });

    return Inserter;

  })();

})(jQuery, Drupal);
