/**
 * @file
 * devel_php_ace.js
 */

(function($, Drupal) {
  Drupal.behaviors.develPHPAce = {
    attach: function(context, settings) {
      $('.ace-enabled').each(function(i) {
        var textarea = $(this);
        id = textarea.attr('id') + '-ace';
        textarea.before('<div id="' + id + '"></div>');
        var editor = ace.edit(id);
        if (theme = settings.develPHPAce.theme) {
          editor.setTheme("ace/theme/" + theme + "");
        }
        editor.session.setMode({path:"ace/mode/php", inline:true});
        editor.setValue(textarea.val());
        editor.on('change', function(){
          textarea.val(editor.getValue());
        });
        textarea.hide();
      });
    }
  };
})(jQuery, Drupal);
