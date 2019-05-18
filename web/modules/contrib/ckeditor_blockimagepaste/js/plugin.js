/**
 * @file
 * Contains plugin.js.
 */

'use strict';

(function () {
  CKEDITOR.plugins.add('blockimagepaste', {
    init: function (editor) {
      function replaceImgText(html) {
        var ret = html.replace(/<img .*?>/gi, function (img) {
          alert(Drupal.t('Direct image paste is not allowed.'));
          return '';
        });
        return ret;
      }

      function chkImg() {
        // Don't execute code if the editor is readOnly.
        if (editor.readOnly) {
          return;
        }
        setTimeout(function () {
          editor.document.$.body.innerHTML = replaceImgText(editor.document.$.body.innerHTML);
        },100);
      }

      editor.on('contentDom', function () {
        // For Firefox.
        editor.document.on('drop', chkImg);
        // For IE.
        editor.document.getBody().on('drop', chkImg);
      });

      editor.on('paste', function (e) {
        var html = e.data.dataValue;
        if (!html) {
          return;
        }
        e.data.dataValue = replaceImgText(html);
      });
    },
  });

})();
