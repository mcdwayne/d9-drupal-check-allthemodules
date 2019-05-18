/**
 * @file
 * Marketo Forms CKEditor plugin.
 *
 * @ignore
 */

(function($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('marketo_forms', {
    init: function(editor) {

      editor.addCommand('marketo_forms', {
        modes: {
          wysiwyg: 1
        },
        canUndo: true,
        exec: function(editor) {

          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function(returnValues) {

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');

            var embed = editor.document.createElement('p');

            if (returnValues.attributes.code !== undefined &&
              returnValues.attributes.code !== '') {
              embed.setHtml(returnValues.attributes.code);
              editor.insertElement(embed);
            }

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: editor.config.marketo_forms_dialog_title,
            dialogClass: 'marketo-forms-dialog'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url(
            'marketo-forms/dialog/' + editor.config.drupal.format
          ), {}, saveCallback, dialogSettings);
        }
      });

      // CTRL + H.
      editor.setKeystroke(CKEDITOR.CTRL + 72, 'marketo_forms');

      // Add Marketo Forms button
      if (editor.ui.addButton) {
        editor.ui.addButton('marketo_forms', {
          label: Drupal.t('Marketo Forms'),
          command: 'marketo_forms',
          icon: this.path + 'icon.png'
        });
      }

    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
