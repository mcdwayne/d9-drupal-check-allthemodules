/**
 * @file
 * Functionality to enable the Frame insertion functionality in CKEditor.
 */

(function () {
  'use strict';

  // Register plugin.
  CKEDITOR.plugins.add('frame', {
    hidpi: true,
    icons: 'icon',
    init: function (editor) {
      // Add single button.
      editor.ui.addButton('Frame', {
        command: 'addFrameCmd',
        icon: this.path + 'icons/icon.png',
        label: Drupal.t('Insert frame')
      });

      // Add CSS for edition state.
      var cssPath = this.path + 'frame.css';
      editor.on('mode', function () {
        if (editor.mode === 'wysiwyg') {
          this.document.appendStyleSheet(cssPath);
        }
      });

      // Command to insert initial structure.
      editor.addCommand('addFrameCmd', {
        exec: function (editor) {
          var div = new CKEDITOR.dom.element.createFromHtml(
            '<div class="frameContainer"></div>'
          );
          editor.insertElement(div);
        }
      });
    }
  });
})();
