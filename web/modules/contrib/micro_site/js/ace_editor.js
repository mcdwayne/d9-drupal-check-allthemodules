/**
 * @file
 * Asset Injector applies Ace Editor to simplify work.
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.assetInjector = {
    attach: function (context, settings) {
      $('.ace-editor').each(function () {
        var textarea = $(this).parent().siblings().find('textarea');
        var mode = $(textarea).attr('data-ace-mode');

        if (mode) {
          $(textarea).hide();
          var editor = ace.edit(this);
          editor.getSession().setMode('ace/mode/' + mode);
          editor.getSession().setTabSize(2);

          editor.getSession().on('change', function () {
            console.log('value: ' + editor.getSession().getValue());
            textarea.val(editor.getSession().getValue());
          });

          $('.resizable').resizable({
            resize: function (event, ui) {
              editor.resize();
            }
          });

          editor.setValue(textarea.val());
          editor.resize();
        }
      });
    }
  };
})(jQuery, Drupal);
