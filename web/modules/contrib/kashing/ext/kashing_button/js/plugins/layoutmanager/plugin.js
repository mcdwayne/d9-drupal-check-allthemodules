CKEDITOR.plugins.add('kashing_button', {
  icons: 'kashing_button',
  init: function (editor) {
    CKEDITOR.dialog.add('kashingDialog', this.path + 'dialogs/dialog.js');

    editor.addCommand('kashing_dialog', new CKEDITOR.dialogCommand('kashingDialog'));

    editor.ui.addButton('kashing_button', {
      label: 'Insert Kashing form',
      command: 'kashing_dialog',
      toolbar: 'insert,100'
    });
  }
});
