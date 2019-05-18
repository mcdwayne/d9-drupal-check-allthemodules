/**
 * @file
 * Image synbox plugin.
 */

(function ($, Drupal, CKEDITOR) {
  'use strict';

  var insertContent;

  var synimageSaveCallback = function (data) {
    var content = data.image_render;
    insertContent(content);
  };

  CKEDITOR.plugins.add('synimage', {
    icons: 'synimage',
    hidpi: true,
    beforeInit: function (editor) {

      editor.addCommand('editsynimage', {
        exec: function (editor, data) {
          var imageElement = getSelectedImage(editor);
          var existingValues = {'data-align': ''};
          if (imageElement) {
            var json_data = decodeURIComponent(imageElement.data('cke-widget-data'));
            var existingValues = jQuery.parseJSON(json_data);
            existingValues.synimage = imageElement.data('synimage');
          }
          Drupal.currentEditor = editor;
          Drupal.ckeditor.openDialog(
            editor, Drupal.url('synimage/dialog/image/' + editor.config.drupal.format),
            existingValues,
            synimageSaveCallback,
            {}
          );
        }
      });

      editor.ui.addButton('SynImage', {
        label: Drupal.t('Synapse Image'),
        command: 'editsynimage'
      });

      insertContent = function (html) {
        Drupal.currentEditor.insertHtml(html);
      }
    }
  });

  /**
   * Get Image from CKEDITOR.span!
   */
  function getSelectedImage(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    if (selectedElement && selectedElement.is('div')) {
      // Element API http://docs.ckeditor.com/#!/api/CKEDITOR.dom.element !
      var selectedElement = selectedElement.getChildren().getItem(0);
    }
    if (selectedElement && selectedElement.is('p')) {
      var selectedElement = selectedElement.getChildren().getItem(0);
    }
    if (selectedElement && selectedElement.is('span')) {
      var selectedElement = selectedElement.getChildren().getItem(0);
    }
    if (selectedElement && selectedElement.is('img')) {
      return selectedElement;
    }
  }

})(jQuery, Drupal, CKEDITOR);
