/**
 * @file
 * Editor for PHP form.
 */

(function ($) {
  // @FIXME: Sometimes Drupal attaches the JS more than once, so we should make
  // sure it happens only once.
  var attached = false;

  Drupal.behaviors.dbgEditor = {
    attach: function () {
      if (!attached) {
        attached = true;

        var phpForm = $('#dbg-php-form');
        phpForm.find('textarea').css('display', 'none');

        var textareaWrapper = phpForm.find('.form-content-wrapper');
        textareaWrapper.prepend('<div id="editor"></div>');
        var textarea = textareaWrapper.find('textarea');

        // Setup editor.
        ace.require("ace/ext/language_tools");
        var editor = ace.edit('editor');
        editor.$blockScrolling = Infinity;
        editor.setOptions({
          enableBasicAutocompletion: true,
          enableSnippets: true,
          maxLines: Infinity
        });

        // Setup editor session.
        var session = editor.getSession();
        session.setMode('ace/mode/php');
        session.setTabSize(2);
        session.setUseSoftTabs(true);
        session.setUseWrapMode(true);
        session.setValue(textarea.val());
        editor.gotoLine(session.getLength(), 0);

        // Update textarea as we type.
        session.on('change', function () {
          textarea.val(session.getValue());
        });
      }
    }
  };
})(jQuery);
