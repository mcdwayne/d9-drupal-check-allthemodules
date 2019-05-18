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

  var SaveCallback = function(data) {
    var content = data.dropcap_text;
    var str1 = content.substring(0, 1).toUpperCase() + content.substring(1);
    var first = str1.substring(0, 1);
    var other_string = content.slice(1);
    var size = data.dropcap_font_size;
    var color = data.dropcap_font_color;
    var drp_first_element = '<span style="font-size:' + size + 'px;color:' + color + ';">' + first + '</span>';
    var final_element = drp_first_element + other_string;
    insertContent(final_element);
  };

  CKEDITOR.plugins.add('dropcap_ckeditor', {
      icons: 'dropcap',
      hidpi: true,

      beforeInit: function (editor) {
        editor.addCommand( 'dropcap_ckeditor', {
          canUndo: true,
          exec: function (editor, data) {
            var existingValues = {};
            
            Drupal.ckeditor.openDialog(editor, Drupal.url('plugin/dialog/dropcap/' + editor.config.drupal.format), existingValues, SaveCallback, {});
          }
        });

        editor.ui.addButton('Dropcap', {
          label: Drupal.t('Dropcap'),
          // Note that we use the original image2 command!
          command: 'dropcap_ckeditor'
        });

        insertContent = function(html) {
          editor.insertHtml(html);
        }
      }
  });

})(jQuery, Drupal, CKEDITOR);
