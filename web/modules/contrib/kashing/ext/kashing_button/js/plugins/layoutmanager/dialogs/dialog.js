CKEDITOR.dialog.add('kashingDialog', function (editor) {
  return {
    title: 'Kashing shortcode',
    minWidth: 100,
    minHeight: 70,
    contents: [
      {
        id: 'mainKashingShortcodeWindow',
        label: 'Kashing Shortcode',
        validate: CKEDITOR.dialog.validate.notEmpty('Field cannot be empty.'),
        elements: [
          {
            type: 'select',
            id: 'kashingSelectForm',
            label: 'Form name',
            items: editor.config.kashing_block_ids
          }
        ]
      }
    ],
    onOk: function () {
      var dialog = this;

      var attribute = dialog.getValueOf('mainKashingShortcodeWindow', 'kashingSelectForm');

      editor.insertHtml('[kashing id=' + attribute + ' /]');
    },
    onShow: function () {
    }
  };
});
