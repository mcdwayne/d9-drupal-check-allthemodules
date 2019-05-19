/**
 * @file
 * UEditor implementation of {@link Drupal.editors} API.
 */

(function (Drupal, debounce, UE, $) {

  'use strict';

  /**
   * @namespace
   */
  Drupal.editors.ueditor = {

    /**
     * Editor attach callback.
     *
     * @param {HTMLElement} element
     *   The element to attach the editor to.
     * @param {string} format
     *   The text format for the editor.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.replace()` created an editor or not.
     */
    attach: function (element, format) {
      var editorOption = {
        lang: format.editorSettings.language,
        zIndex: format.editorSettings.zindex,
        initialFrameHeight: format.editorSettings.initialFrameHeight,
        initialFrameWidth: '100%',
        initialContent: format.editorSettings.initial_content,
        serverUrl: format.editorSettings.serverUrl,
        toolbars: format.editorSettings.toolbars,
        autoHeightEnabled: format.editorSettings.auto_height ? true : false,
        autoFloatEnabled: format.editorSettings.auto_float ? true : false,
        allowDivTransToP: format.editorSettings.allowdivtop ? true : false,
        elementPathEnabled: format.editorSettings.show_elementpath ? true : false,
        wordCount: format.editorSettings.show_wordcount ? true : false,
        UEDITOR_HOME_URL:  format.editorSettings.editorPath,
      };
      var editor = UE.ui.Editor(editorOption);
      var elementId = element.getAttribute('id');
      Drupal.ueditor.instances = editor;
      return editor.render(element);
    },

    /**
     * Editor detach callback.
     *
     * @param {HTMLElement} element
     *   The element to detach the editor from.
     * @param {string} format
     *   The text format used for the editor.
     * @param {string} trigger
     *   The event trigger for the detach.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.dom.element.get(element).getEditor()`
     *   found an editor or not.
     */
    detach: function (element, format, trigger) {
      var editor = UE.dom.element.get(element).getEditor();
      if (editor) {
        if (trigger === 'serialize') {
          element.defaultValue = editor.getContent();
          element.dataset.editorValueOriginal = editor.getContent();
        }
        else {
          editor.destroy();
        }
      }
      return !!editor;
    },

    /**
     * Reacts on a change in the editor element.
     *
     * @param {HTMLElement} element
     *   The element where the change occured.
     * @param {function} callback
     *   Callback called with the value of the editor.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.dom.element.get(element).getEditor()`
     *   found an editor or not.
     */
    onChange: function (element, callback) {
    },

    /**
     * Attaches an inline editor to a DOM element.
     *
     * @param {HTMLElement} element
     *   The element to attach the editor to.
     * @param {object} format
     *   The text format used in the editor.
     * @param {string} [mainToolbarId]
     *   The id attribute for the main editor toolbar, if any.
     * @param {string} [floatedToolbarId]
     *   The id attribute for the floated editor toolbar, if any.
     *
     * @return {bool}
     *   Whether the call to `CKEDITOR.replace()` created an editor or not.
     */
    attachInlineEditor: function (element, format, mainToolbarId, floatedToolbarId) {
    },

  };

  Drupal.ueditor = {
    instances: null
  };

})(Drupal, Drupal.debounce, UE, jQuery);
