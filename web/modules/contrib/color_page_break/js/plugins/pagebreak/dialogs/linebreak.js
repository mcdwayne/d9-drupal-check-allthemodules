/**
 * @file
 */

CKEDITOR.dialog.add('lineBreakDialog', function (editor) {
    return {
        title: 'Enter color name',
        minWidth: 400,
        minHeight: 200,
        contents: [
            {
                id: 'color-setting',
                label: 'Color setting',
                elements: [
                    {
                        type: 'text',
                        id: 'linecolor',
                        label: 'Color',
                        validate: CKEDITOR.dialog.validate.notEmpty("Color field cannot be empty.")
                    },
                ]
            },
        ],
        onOk: function () {
            var dialog = this;
            var linecolor = dialog.getValueOf('color-setting', 'linecolor');
            var line = '<hr color="' + linecolor + '" size="4">';
            editor.insertHtml(line);
        }
    };
});
