/* global CKEDITOR*/
(function () {
  'use strict';
  CKEDITOR.plugins.add('tweetthis', {
    icons: 'tweetthis',
    hidpi: true,
    init: function (editor) {
      var pluginName = 'tweetthis';
      editor.addCommand(pluginName, {
        exec: function (editor) {
          var selectedText = editor.getSelection().getSelectedText();
          var lnk = 'http://twitter.com/intent/tweet?text=' + selectedText;
          var replacetext = '<a class="tweetthis" href="' + lnk + '" target="_blank">' + selectedText + '</a>';
          editor.insertHtml(replacetext);
        }
      });
      editor.ui.addButton('TweetThis', {
        label: 'Tweet the selected text',
        command: pluginName,
        toolbar: 'links'
      });
    }
  });
}());
