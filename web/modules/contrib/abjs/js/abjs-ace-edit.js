/**
 * @file
 * Adds ACE code editor to replace and sync with javascript textareas.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.abjs = {
    attach: function (context, settings) {
      if (typeof window.ace !== 'undefined') {
        jQuery('.form-item-script .form-textarea-wrapper').hide();
        jQuery('.form-item-script .form-textarea-wrapper').after('<div id="editor" style="witdh:100%; height:200px;"></div>');
        var editor = window.ace.edit('editor');
        editor.setTheme('ace/theme/monokai');
        editor.getSession().setMode('ace/mode/javascript');
        editor.getSession().setValue(jQuery('#edit-script').val());
        editor.getSession().on('change', function () {
          jQuery('#edit-script').val(editor.getSession().getValue());
        });
      }
    }
  };
})(jQuery);
