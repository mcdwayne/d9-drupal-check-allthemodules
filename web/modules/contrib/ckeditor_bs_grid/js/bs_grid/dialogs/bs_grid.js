CKEDITOR.dialog.add('bs_grid', function (editor) {
  var lang = editor.lang.bs_grid;

  // Whole-positive-integer validator.
  function validatorNum(msg) {
    return function () {
      var value = this.getValue(),
        pass = !!(CKEDITOR.dialog.validate.integer()(value) && value > 0);

      if (!pass) {
        alert(msg);
      }

      return pass;
    };
  }

  return {
    title: lang.editBsGrid,
    minWidth: 600,
    minHeight: 300,
    onShow: function () {
      // Detect if there's a selected table.
      var selection = editor.getSelection(),
        ranges = selection.getRanges();
      var command = this.getName();

      var rowsInput = this.getContentElement('info', 'rowCount'),
        colsInput = this.getContentElement('info', 'colCount'),
        breakpoinInput = this.getContentElement('info', 'breakpoint');

      if (command === 'bs_grid') {
        var grid = selection.getSelectedElement();
        // Enable or disable row and cols.
        if (grid) {
          this.setupContent(grid);
          if (rowsInput) { rowsInput.disable(); }
          if (colsInput) { colsInput.disable(); }
          if (breakpoinInput) { breakpoinInput.disable(); }
        }
      }
    },
    contents: [
      {
        id: 'info',
        label: lang.infoTab,
        accessKey: 'I',
        elements: [
          {
            id: 'rowCount',
            type: 'text',
            width: '50px',
            required: true,
            label: lang.numRows,
            validate: validatorNum(lang.numRowsError),
            setup: function (widget) {
              this.setValue(widget.data.rowCount);
            },
            commit: function (widget) {
              widget.setData('rowCount', this.getValue());
            }
          },
          {
            id: 'colCount',
            type: 'select',
            required: true,
            label: lang.numCols,
            items: [
              ['1', 1],
              ['2', 2],
              ['3', 3],
              ['4', 4],
              ['5', 5],
              ['6', 6],
              ['6', 6],
              ['7', 7],
              ['8', 8],
              ['9', 9],
              ['10', 10],
              ['11', 11],
              ['12', 12]
            ],
            validate: validatorNum(lang.numColsError),
            setup: function (widget) {
              this.setValue(widget.data.colCount);
            },
            commit: function (widget) {
              widget.setData('colCount', this.getValue());
            }
          },
          {
            id: 'breakpoint',
            type: 'radio',
            required: true,
            label: lang.breakpoint,
            items: [
              [lang.xs, 'col-'],
              [lang.sm, 'col-sm-'],
              [lang.md, 'col-md-'],
              [lang.lg, 'col-lg-'],
              [lang.xl, 'col-xl-']
            ],
            setup: function (widget) {
              this.setValue(widget.data.breakpoint);
            },
            commit: function (widget) {
              widget.setData('breakpoint', this.getValue());
            }
          }
        ]
      }
    ]
  };
});
