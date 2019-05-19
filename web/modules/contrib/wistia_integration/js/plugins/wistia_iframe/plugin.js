/**
 * @file
 * CKEditorWistiaIframeButton plugin.
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR, settings) {
  'use strict';
  CKEDITOR.plugins.add('wistia_iframe', {
    icons: 'wistia_iframe_button',

    init: function (editor) {
      editor.addCommand('wistia_iframe_command', {
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          var existingValues = {};
          var dialogSettings = {
            title: Drupal.t('Add Video'),
            dialogClass: 'editor-video-dialog'
          };

          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function (returnValues) {
            editor.fire('saveSnapshot');
            var selection = editor.getSelection();
            var range = selection.getRanges(1)[0];

            if (range.collapsed) {
              var text = new CKEDITOR.dom.text(returnValues.text, editor.document);
              range.insertNode(text);
              range.selectNodeContents(text);
            }

            // Create iframe by applying a style to the new text.
            var style = new CKEDITOR.style({
              element: 'iframe',
              attributes: {
                src: '//fast.wistia.net/embed/iframe/' + returnValues.text,
                allowtransparency: 'true',
                frameborder: '0',
                scrolling: 'no',
                class: 'wistia_embed',
                name: 'wistia_embed',
                allowfullscreen: '',
                mozallowfullscreen: '',
                webkitallowfullscreen: '',
                oallowfullscreen: '',
                msallowfullscreen: '',
                width: '620',
                height: '349'
              }
            });
            style.type = CKEDITOR.STYLE_INLINE;
            style.applyToRange(range);
            range.select();

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('wistia_integration/dialog/wistia_video_dialog/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });

      editor.ui.addButton('wistia_iframe_button', {
        label: Drupal.t('Wistia iframe button'),
        command: 'wistia_iframe_command'
      });
    }
  });
})(jQuery, Drupal, CKEDITOR);
