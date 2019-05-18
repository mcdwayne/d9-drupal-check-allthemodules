/**
 * @file
 * Bootstrap Grid plugin.
 */

(function ($, Drupal, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('bs_grid', {
      lang: 'en',
      requires: 'widget,dialog',
      icons: 'bs_grid',

      init: function (editor) {
        var maxGridColumns = 12;
        var lang = editor.lang.bs_grid;

        CKEDITOR.dialog.add('bs_grid', this.path + 'dialogs/bs_grid.js');
        editor.addContentsCss(this.path + 'css/editor.css');

        editor.ui.addButton('bs_grid', {
          label: lang.createBsGrid,
          command: 'bs_grid',
          icon: this.path + 'icons/bs_grid.png'
        });

        editor.widgets.add('bs_grid',
          {
            allowedContent: 'div(!bs_grid);div(!row,!row-*);div(!col-*-*);div(!content)',
            requiredContent: 'div(bs_grid)',
            parts: {
              bs_grid: 'div.bs_grid',
            },
            editables: {
              content: '',
            },
            template:
            '<div class="bs_grid container">' +
            '</div>',
            dialog: 'bs_grid',
            defaults: {
              //  colCount: 2,
              // rowCount: 1
            },
            // Before init.
            upcast: function (element) {
              return element.name === 'div' && element.hasClass('bs_grid');
            },
            // initialize
            // Init function is useful after copy paste rebuild.
            init: function () {
              var rowNumber = 1;
              var rowCount = this.element.getChildCount();
              for (rowNumber; rowNumber <= rowCount; rowNumber++) {
                this.createEditable(maxGridColumns, rowNumber);
              }
            },
            // Prepare data
            data: function () {
              if (this.data.colCount && this.element.getChildCount() < 1) {
                var colCount = this.data.colCount;
                var rowCount = this.data.rowCount;
                var breakpoint = this.data.breakpoint;
                var row = this.parts['bs_grid'];
                for (var i = 1; i <= rowCount; i++) {
                  this.createGrid(colCount, row, i, breakpoint);
                }
              }
            },
            //Helper functions.
            // Create grid
            createGrid: function (colCount, row, rowNumber, breakpoint) {
              var content = '<div class="row row-' + rowNumber + '">';
              for (var i = 1; i <= colCount; i++) {
                content = content + '<div class="' + breakpoint + maxGridColumns / colCount + '">' +
                  '  <div class="content">' +
                  '    <p>Col ' + i + ' content area</p>' +
                  '  </div>' +
                  '</div>';
              }
              content = content + '</div>';
              row.appendHtml(content);
              this.createEditable(colCount, rowNumber);
            },
            // Create editable.
            createEditable: function (colCount, rowNumber) {
              for (var i = 1; i <= colCount; i++) {
                this.initEditable('content' + rowNumber + i, {
                  selector: '.row-' + rowNumber + ' > div:nth-child(' + i + ') div.content'
                });
              }
            }
          }
        );
      }
    }
  );

})(jQuery, Drupal, CKEDITOR);
