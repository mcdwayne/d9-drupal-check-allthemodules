/**
 * @file
 * Boxout plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('boxout', {
    init: function (editor) {
      editor.addCommand('boxout', {
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function (returnValues) {
            editor.fire('saveSnapshot');

            if (returnValues.attributes.body) {
              var selection = editor.getSelection();
              var range = selection.getRanges(1)[0];

              if (range.collapsed) {
                var values = returnValues.attributes;

                var container = editor.document.createElement('div');
                container.setAttribute('class', 'boxout ' + values.style);

                var header = editor.document.createElement(values.header_element_type);
                header.setAttribute('class', 'boxout-header');
                header.setHtml(values.header);

                var body = editor.document.createElement('p');
                body.setHtml(values.body);

                container.append(header);
                container.append(body);

                editor.insertElement(container);
              }
            }

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: editor.config.boxout_dialog_title_insert,
            dialogClass: 'boxout-dialog'
          };

          // Open the dialog for the edit form.
          var existingValues = {};
          Drupal.ckeditor.openDialog(editor, Drupal.url('boxout/dialog'), existingValues, saveCallback, dialogSettings);
        }
      });

      // Add button for insert.
      if (editor.ui.addButton) {
        editor.ui.addButton('Boxout', {
          label: Drupal.t('Insert Boxout'),
          command: 'boxout',
          icon: this.path + '/boxout.png'
        });
      }

      // If the "menu" plugin is loaded, register the menu items.
      if (editor.addMenuItems) {
        editor.addMenuItems({
          boxout: {
            label: Drupal.t('Boxout'),
            command: 'boxout',
            group: 'tools',
            order: 1
          }
        });
      }
    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
