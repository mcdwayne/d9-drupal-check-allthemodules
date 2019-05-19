/**
 * @file
 * Custom CKEditor plugin for Widen Collective module.
 */

/**
 * Registers the plugin within the editor.
 */
(function ($, Drupal, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('widencollective', {
    requires: ['iframedialog'],
    icons: 'widencollective',

    init: function (editor) {

      // Attach button to toolbar.
      editor.ui.addButton('Widencollective', {
        label: Drupal.t('Widen Collective'),
        command: 'widencollectiveDialog'
      });

      // Fetch widen search UI url.
      $.get(Drupal.url('admin/widen/search_url'), function (data) {
        if (data.status_code === 200) {
          // Create new search dialog.
          editor.addCommand('widencollectiveDialog', new CKEDITOR.dialogCommand('widenSearchDialog'));

          // Create an iframe object inside dialog.
          CKEDITOR.dialog.addIframe('widenSearchDialog', Drupal.t('Widen Collective'), data.url, 1024, 640, null, {
            buttons: [CKEDITOR.dialog.cancelButton]
          });

          // Listen to widen collective postMessage.
          window.addEventListener('message', function (event) {
            var embedCode = event.data.items[0].embed_code;
            editor.insertHtml(embedCode);
            // Close the dialog.
            CKEDITOR.dialog.getCurrent().hide();
          });

        }
        else {
          var msg = Drupal.t('Cannot connect to Widen Collective server. Please try again.');
          if (data.error) {
            msg = msg + '\n' + data.error;
          }
          alert(msg);
        }
      });
    }
  });

})(jQuery, Drupal, CKEDITOR);
