/**
 * @file
 * Image popup plugin.
 *
 * Use a Drupal-native dialog (that is in fact just an alterable Drupal form
 * like any other) instead of CKEditor's own dialogs.
 *
 * @see \Drupal\editor\Form\EditorImageDialog
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {

  'use strict';

  var insertContent;

  var cantoConnectorSaveCallback = function(data) {
    insertContent(data);
  };

  CKEDITOR.plugins.add('cantoconnector', {
      icons: 'cantoconnector',
      hidpi: true,

      beforeInit: function (editor) {
        editor.addCommand( 'editcantoConnector', {
          canUndo: true,
          exec: function (editor, data) {
            var existingValues = {
              'data-align': ''
            };
            
            var edit_content = editor.getSelection().getSelectedElement();
            if (edit_content && edit_content.getAttribute('class') == 'display_image') {
                var imageData = edit_content.data('img-popup');
                imageData = imageData.split(':');
                existingValues = {
                  'data-entity-uuid': imageData[0],
                  'src': edit_content.getAttribute('src'),
                  'alt': imageData[1],
                  'image_style': imageData[2],
                  'image_style_popup': imageData[3],
                  'data-align': imageData[4]
                };
              }
            Drupal.ckeditor.openDialog(editor, Drupal.url('canto_connector/dialog/image/' + editor.config.drupal.format), existingValues, cantoConnectorSaveCallback, {});
          }
        });

        editor.ui.addButton('CantoConnector', {
          label: Drupal.t('Canto Connector'),
          command: 'editcantoConnector'
        });

        insertContent = function(html) {
          editor.insertHtml(html);
        }
      }
  });

})(jQuery, Drupal, CKEDITOR);
