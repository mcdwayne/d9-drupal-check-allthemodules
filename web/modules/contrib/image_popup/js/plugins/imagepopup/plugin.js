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

  var imagePopupSaveCallback = function(data) {
    var content = data.image_render;
    insertContent(content);
  };

  CKEDITOR.plugins.add('imagepopup', {
      icons: 'imagepopup',
      hidpi: true,

      beforeInit: function (editor) {
        editor.addCommand( 'editimagepopup', {
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
            Drupal.ckeditor.openDialog(editor, Drupal.url('image_popup/dialog/image/' + editor.config.drupal.format), existingValues, imagePopupSaveCallback, {});
          }
        });

        editor.ui.addButton('ImagePopup', {
          label: Drupal.t('Image popup'),
          // Note that we use the original image2 command!
          command: 'editimagepopup'
        });

        insertContent = function(html) {
          editor.insertHtml(html);
        }
      }
  });

})(jQuery, Drupal, CKEDITOR);
