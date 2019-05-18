/**
 * @file
 */

(function ($) {
 CKEDITOR.plugins.add('pagebreak', {

  init: function (editor) {

    editor.addCommand('page_break', new CKEDITOR.dialogCommand('lineBreakDialog'));

    editor.ui.addButton('PageBreakButton', {
    label: 'Add Line break', // This is the tooltip text for the button.
    command: 'page_break',
    icon: this.path + 'images/plugicon.png'
   });
   CKEDITOR.dialog.add('lineBreakDialog', this.path + 'dialogs/linebreak.js');
  }
 });
})(jQuery);
