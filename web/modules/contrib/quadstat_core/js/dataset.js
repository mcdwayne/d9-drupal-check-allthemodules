/**
 * @file
 * Javascript behaviors for viewing data in the editor
 */

/* global Quadstat_Core, Drupal, jQuery, document */

(function ($, Drupal, document, Quadstat_Core) {

  'use strict';

  /**
   * Attaches behaviors for Quadstat.
   */
  Drupal.behaviors.quadstat_core = {
    attach: function (context, settings) {

      // Launch CodeMirror HTML Syntax Highlighting
      if($("#edit-body-0-value").length && $('.CodeMirror').length == 0) {
        var editor = CodeMirror.fromTextArea(document.getElementById('edit-body-0-value'), {mode: 'htmlmixed', lineNumbers: true, lineWrapping: true});
      }

      $("input[name=field_dataset_cont_table]").change(function () {
        switch($(this).val()) {
          case "0": {
            $('#edit-field-dataset-cont-table--wrapper--description').css('display', 'none')
            $('#edit-field-dataset-random-columns-wrapper').css('display', 'block')
            $('#edit-field-dataset-random-rows-wrapper').css('display', 'block')
            break;
          }
          case "1": {
            $('#edit-field-dataset-cont-table--wrapper--description').css('display', 'block')
            $('#edit-field-dataset-random-columns-wrapper').css('display', 'none')
            $('#edit-field-dataset-random-rows-wrapper').css('display', 'none')
            break;
          }
        }
      });

      // Show or hide fields when adding a dataset based on user selection
      $("input[name=field_dataset_input_method]").change(function () {
        switch($(this).val()) {
          case "random": {
            if($("input[name=field_dataset_input_method]").val() == 1) {
              $('#edit-field-dataset-cont-table--wrapper--description').css('display', 'none')
              $('#edit-field-dataset-random-columns-wrapper').css('display', 'none')
              $('#edit-field-dataset-random-rows-wrapper').css('display', 'none')
            }
            else {
              $('#edit-field-dataset-cont-table--wrapper--description').css('display', 'block')
              $('#edit-field-dataset-random-columns-wrapper').css('display', 'block')
              $('#edit-field-dataset-random-rows-wrapper').css('display', 'block')
            }
            $('#edit-field-dataset-file-wrapper').css('display', 'none')
            $('#edit-field-dataset-paste-wrapper').css('display', 'none')
            $('#edit-field-dataset-header-wrapper').css('display', 'none')
            $('#edit-field-dataset-separator-wrapper').css('display', 'none')
            break;
          }

          case "file": {
            $('#edit-field-dataset-random-columns-wrapper').css('display', 'none')
            $('#edit-field-dataset-random-rows-wrapper').css('display', 'none')
            $('#edit-field-dataset-file-wrapper').css('display', 'block')
            $('#edit-field-dataset-paste-wrapper').css('display', 'none')
            $('#edit-field-dataset-header-wrapper').css('display', 'block')
            $('#edit-field-dataset-separator-wrapper').css('display', 'block')
            break;
          }

          case "paste": {
            $('#edit-field-dataset-random-columns-wrapper').css('display', 'none')
            $('#edit-field-dataset-random-rows-wrapper').css('display', 'none')
            $('#edit-field-dataset-file-wrapper').css('display', 'none')
            $('#edit-field-dataset-paste-wrapper').css('display', 'block')
            $('#edit-field-dataset-header-wrapper').css('display', 'block')
            $('#edit-field-dataset-separator-wrapper').css('display', 'block')
            break;
          }

          case "empty": {
            $('#edit-field-dataset-random-columns-wrapper').css('display', 'none')
            $('#edit-field-dataset-random-rows-wrapper').css('display', 'none')
            $('#edit-field-dataset-file-wrapper').css('display', 'none')
            $('#edit-field-dataset-paste-wrapper').css('display', 'none')
            $('#edit-field-dataset-header-wrapper').css('display', 'none')
            $('#edit-field-dataset-separator-wrapper').css('display', 'none')
            break;
          }
        }
      });

      if($('#node-dataset-form').length > 0) {
        return;
      }
      var dataView;
      var grid;
      var options;
      var commandQueue = [];
      var columnFilters = {};
      var data = [];
 
      // Define options for grid
      var options = {
        editable: true,
        enableAddRow: false,
        enableCellNavigation: true,
        asyncEditorLoading: true,
        forceFitColumns: false,
        topPanelHeight: 25,
        enableColumnReorder: false,
        explicitInitialization: true,
        showHeaderRow: true,
        headerRowHeight: 30,
        autoedit: true,
        editCommandHandler: queueAndExecuteCommand
      };

      // Setup columns
      if (drupalSettings.quadstat_core.is_cont_table == 0) {
        // Setup columns for contingency tables
        var columns = [{id:0, name:"Rows", field:0, cssClass:"cell-title", formatter: function(row){return row+1}}];
      } else {
        var columns = [{id:0, name:"Rows", field:0, cssClass:"cell-title", editor:Slick.Editors.Text}];
      }
      if(typeof drupalSettings.quadstat_core.data[0] != 'undefined') {
        for(var i = 0; i < drupalSettings.quadstat_core.longest_row || i < 35; i++) {
          columns.push({id:i+1, name:typeof drupalSettings.quadstat_core.data[0][i] !== 'undefined' ? drupalSettings.quadstat_core.data[0][i] : '', field:i+1, editor:Slick.Editors.Text});
        }
      }

      function queueAndExecuteCommand(item, column, editCommand) {
        // Push the change to the commandQueue variable;
        commandQueue.push(editCommand);
        editCommand.execute();
      }

      function filter(item) {
        for (var columnId in columnFilters) {
          if (columnId !== undefined && columnFilters[columnId] !== "") {
            var c = grid.getColumns()[grid.getColumnIndex(columnId)];
            if (item[c.field] != columnFilters[columnId]) {
              return false;
            }
          }
        }
        return true;
      }

      function setItems(data, objectIdProperty) {
        if (objectIdProperty !== undefined) {
          idProperty = objectIdProperty;
        }
        items = data;
        refreshIdxById();
        refresh();
      }

      if (drupalSettings.quadstat_core.is_cont_table == 0) {
        // Setup rows, minimum 10x10 grid
        for (var i = 1; i < drupalSettings.quadstat_core.data.length || i < 11; i++) {
          var d = (data[i - 1] = {});
          d["id"] = i - 1;
          d[0] = typeof drupalSettings.quadstat_core.data[i] != 'undefined' && typeof drupalSettings.quadstat_core.data[i][0] !== 'undefined' ? drupalSettings.quadstat_core.data[i][0] : i;
          for(var j = 0; (typeof drupalSettings.quadstat_core.data[i] !== 'undefined' && j < drupalSettings.quadstat_core.data[i].length + 1) || j < 11; j++) {
            d[j+1]  = (typeof drupalSettings.quadstat_core.data[i] !== 'undefined' && typeof drupalSettings.quadstat_core.data[i][j] !== 'undefined') ? drupalSettings.quadstat_core.data[i][j] : '';
          }
        }
      } else {
        for (var i = 1; i < drupalSettings.quadstat_core.data.length; i++) {
          var d = (data[i - 1] = {});
          d["id"] = i - 1;
          d[0] = drupalSettings.quadstat_core.data[i][0];
          for(var j = 0; (typeof drupalSettings.quadstat_core.data[i] !== 'undefined' && j < drupalSettings.quadstat_core.data[i].length + 2); j++) {
            d[j+1]  = (typeof drupalSettings.quadstat_core.data[i] !== 'undefined' && typeof drupalSettings.quadstat_core.data[i][j+1] !== 'undefined') ? drupalSettings.quadstat_core.data[i][j+1] : '';
          }
        } 
      }
      // Create the grid
      dataView = new Slick.Data.DataView();
      grid = new Slick.Grid("#quadstat-slickgrid", dataView, columns, options);
      grid.setSelectionModel(new Slick.RowSelectionModel());

      // wire up model events to drive the grid
      dataView.onRowCountChanged.subscribe(function (e, args) {
        grid.updateRowCount();
        grid.render();
      });
  
      dataView.onRowsChanged.subscribe(function (e, args) {
        grid.invalidateRows(args.rows);
        grid.render();
      });

      $(grid.getHeaderRow()).delegate(":input", "change keyup", function (e) {
        // filter if text is entered
        var columnId = $(this).data("columnId");
        if (columnId != null) {
          columnFilters[columnId] = $.trim($(this).val());
          dataView.refresh();
        }
      });

      grid.onHeaderRowCellRendered.subscribe(function(e, args) {
        // Add filters
        $(args.node).empty();
        $("<input type='text'>")
        .data("columnId", args.column.id)
        .val(columnFilters[args.column.id])
        .appendTo(args.node);
      });

      grid.onCellChange.subscribe(function (e, args) {
        dataView.updateItem(args.item.id, args.item);
        var pop = commandQueue.pop();
        if(typeof(pop) != 'undefined') {
          $('#edit-field-dataset-history-0-value').val($('#edit-field-dataset-history-0-value').val() + "update," + (pop['row'] + 1) + ',' + pop['cell'] + ',' + pop['serializedValue'] + "\n");
        }
        commandQueue.push(pop);
      });

      $('#quadstat-slickgrid-add-col').click(function() {
        // Add new column to end of grid
        columns = grid.getColumns();
        columns.push({id:columns.length, name: columns.length, field: columns.length, editor:Slick.Editors.Text});
        grid.setColumns(columns);
        $('#edit-field-dataset-history-0-value').val($('#edit-field-dataset-history-0-value').val() + "addcol," + drupalSettings.quadstat_core.longest_row + "\n");
      });

      $('#quadstat-slickgrid-add-row').click(function() {
        // Add a new row to bottom of grid
        var item = { id:data.length+1, 0:data.length+1 };
        dataView.addItem(item);
        grid.invalidate();
        grid.updateRowCount();
        grid.render();
        grid.scrollRowIntoView(data.length)
        $('#edit-field-dataset-history-0-value').val($('#edit-field-dataset-history-0-value').val() + "addrow,\n");
      });

      grid.init();
      // initialize the model after all the events have been hooked up
      dataView.beginUpdate();
      dataView.setItems(data);
      dataView.setFilter(filter);
      dataView.endUpdate();
      // if you don't want the items that are not visible (due to being filtered out
      // or being on a different page) to stay selected, pass 'false' to the second arg
      dataView.syncGridSelection(grid, true);

      if($('body.page-node-type-dataset').length && !$('form#node-dataset-edit-form').length) {
        $('#quadstat-slickgrid div').on('click', 'div', function() {
          return false;
        });
        $('#quadstat-slickgrid div').on('dblclick', 'div', function() {
          return false;
        });
      }

      $('.slick-header-column').click(function() {
        // Don't do anything if we're not editing
        if($('body.page-node-type-dataset').length && !$('form#node-dataset-edit-form').length) {
          return false;
        }
        // Get the position of the column name to change
        var nth = $(this).index();
        if(nth == 0) {
          return false;
        }
        var $renameColumnDialog = $('<div id="quadstat-dialog-rename-col"><p>Enter the new value for this column.</p><p><input type="text" value="" id="quadstat-slickgrid-rename-column-input"></p></div>').appendTo('body');
        Drupal.dialog($renameColumnDialog, {
          title: 'Rename Column',
            buttons: [{
              text: 'Save',
              click: function() {
                $(this).dialog('close');
                updateColumnHeaders(nth);
                $('#edit-field-dataset-history-0-value').val($('#edit-field-dataset-history-0-value').val() + "rename," + nth + "," + $('#quadstat-slickgrid-rename-column-input').val() + "\n");
              }
            }]
        }).showModal();
      });

      function updateColumnHeaders(nth) {
        var cols = grid.getColumns();
        cols[nth].name = $('#quadstat-slickgrid-rename-column-input').val();
        grid.setColumns(cols);
        redo_click_action();
      }

      function redo_click_action() {
        // Rebind click header column dialog
        $('.slick-header-column').click(function() {
          // Don't do anything if we're not editing
          if($('body.page-node-type-dataset').length && !$('form#node-dataset-edit-form').length) {
            return false;
          }
          // Get the position of the column name to change
          var nth = $(this).index();

          $('#quadstat-slickgrid-rename-column-input').val('');
          var $renameColumnDialog = $('#quadstat-dialog-rename-col');
          Drupal.dialog($renameColumnDialog, {
            title: 'Rename Column',
              buttons: [{
                text: 'Save',
                click: function() {
                  $(this).dialog('close');
                  updateColumnHeaders(nth);
                  $('#edit-field-dataset-history-0-value').val($('#edit-field-dataset-history-0-value').val() + "rename," + nth + "," + $('#quadstat-slickgrid-rename-column-input').val() + "\n");
                }
              }]
          }).showModal();
        });        
      }

    }   
  };
}(jQuery, Drupal, document));
