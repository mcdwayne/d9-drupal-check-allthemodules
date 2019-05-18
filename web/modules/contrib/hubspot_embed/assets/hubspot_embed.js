/**
 * @file
 * Hubspot Embed CKEditor plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add( 'hubspot_embed', {
    init: function ( editor ) {

      editor.addCommand( 'hubspot_embed', {
        modes: { wysiwyg: 1 },
        canUndo: true,
        exec: function ( editor ) {

          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function ( returnValues ) {

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');

            var embed = editor.document.createElement( 'p' );

            if ( returnValues.attributes.code !== undefined && returnValues.attributes.code !== '' ) {
              embed.setHtml( returnValues.attributes.code );
              editor.insertElement( embed );
            }

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: editor.config.hubspot_embed_dialog_title,
            dialogClass: 'hubspot-embed-dialog'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('hubspot-embed/dialog/' + editor.config.drupal.format), {}, saveCallback, dialogSettings);
        }
      });

      // CTRL + H.
      editor.setKeystroke( CKEDITOR.CTRL + 72, 'hubspot_embed' );

      // Add Hubpost Forms button
      if (editor.ui.addButton) {
        editor.ui.addButton('hubspot_embed', {
          label: Drupal.t('Hubspot Embed'),
          command: 'hubspot_embed',
          icon: this.path + 'icon.png'
        });
      }

    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
