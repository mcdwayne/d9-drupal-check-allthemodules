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

      // Remove quotes from input
      $('#views-exposed-form-dataset-block-1 .form-text').change(function() {
        $(this).val(function(i, v) { 
          return v.replace(/\"/g, '');
        });
      });

      $('input[data-drupal-selector="edit-application-dataset-edit"]').on('click', function(e) {
        e.stopPropagation();
        e.preventDefault();
        var nid = $('.views-field-nid').text().trim();
        window.location.href = '/node/' + nid; 
        return false;
      });

      $('#edit-application-dataset-edit').removeClass('js-form-submit form-submit').prop("type", "button");

      // Validate required inputs, the following line is hackish
      if ($('#toolbar-administration').length == 0) {
        $('#edit-submit').click(function(e) {
          if($('#edit-v').length && $('#edit-v')[0].checked) {
            // do nothing
          } else {
            if($('#edit-v1').length == 0) {
              //do nothing
            } else if (($('#edit-v1').val() == '-1' && $('#edit-v2').length == 0) || ($('#edit-v2').length == 0 && $('div.slick-header-column').hasClass('highlighted-col-2'))) {
              alert('To process your query, please select a single dataset column.');
              e.stopPropagation();
              e.preventDefault();
              return false;
            } else if ($('#edit-v2').val() == '-1' || ($('#edit-v1').val() != '-1' && $('#edit-v2').val() != '-1' && $('div.slick-header-column').hasClass('highlighted-col-3'))) {
              alert('To process your query, please select exactly two dataset columns.');
              e.stopPropagation();
              e.preventDefault();
              return false;
            }
          }
        });
      }

      // Function to highlight columns upon click
      function highlight(col) {
        if($('div.slick-header-column:eq(' + col + ')').hasClass('highlighted-col')) {
          // column already highlighted, deselect
          $('.slick-header-column').removeClass('highlighted-col');
          $('.slick-header-column').removeClass('highlighted-col-1');
          $('.slick-header-column').removeClass('highlighted-col-2');
          $('.slick-header-column').removeClass('highlighted-col-3');
          $('.slick-cell').removeClass('highlighted-col-1');
          $('.slick-cell').removeClass('highlighted-col-2');
          $('.slick-cell').removeClass('highlighted-col-3');
          $('.slick-cell').removeClass('highlighted-col');
          $('#edit-v1').val('-1');
          $('#edit-v2').val('-1');
          $('#edit-v3').val('-1');
          return true;
        }
        if($('div.slick-header-column.highlighted-col').length == 0) {
          // no highlighted column, select first one
          $('.slick-cell.r' + col).addClass('highlighted-col');
          $('.slick-cell.r' + col).addClass('highlighted-col-1');
          $('.slick-header-column:eq(' + col + ')').addClass('highlighted-col');
          $('.slick-header-column:eq(' + col + ')').addClass('highlighted-col-1');
          $('#edit-v1').val(col);
          return true;
        }
        if($('div.slick-header-column.highlighted-col').length == 1) {
            // highlight 2nd column
            $('.slick-cell.r' + col).addClass('highlighted-col-2');
            $('.slick-cell.r' + col).addClass('highlighted-col');
            $('.slick-header-column:eq(' + col + ')').addClass('highlighted-col');
            $('.slick-header-column:eq(' + col + ')').addClass('highlighted-col-2');
            $('#edit-v2').val(col);
            return true;
        }
        if($('div.slick-header-column.highlighted-col').length == 2) {
            // highlight 3rd column
            $('.slick-cell.r' + col).addClass('highlighted-col-3');
            $('.slick-cell.r' + col).addClass('highlighted-col');
            $('.slick-header-column:eq(' + col + ')').addClass('highlighted-col');
            $('.slick-header-column:eq(' + col + ')').addClass('highlighted-col-3');
            $('#edit-v3').val(col);
            return true;
        }
      }

      /*
       * The rest of the code in this file is for Slickgrid preview
       */

      var grid;
      var options;
      var data = [];
      var d = [];
      var columns = [];

      // Define options for grid
      var options = {
        editable: false,
        enableAddRow: false,
        enableCellNavigation: false,
        forceFitColumns: false,
        enableColumnReorder: false,
        showHeaderRow: false,
        autoedit: false,
      };

      // Setup columns
      if(typeof drupalSettings === 'undefined' || typeof drupalSettings.quadstat_core === 'undefined') {
        return;
      }
      for(var i = 0; i < drupalSettings.quadstat_core.data.length; i++) {
        if (drupalSettings.quadstat_core.is_cont_table[i] == 0) {
          // It's not a contingency table
          columns[i] = [{id:0, name:"Rows", field:0, cssClass:"cell-title", formatter: function(row){return row+1}}];
          for(var j = 0; j < drupalSettings.quadstat_core.data[i]['longest_row'] || j < 35; j++) { 
            columns[i].push({id: j, name: drupalSettings.quadstat_core.data[i]['val'][0][j], field:j});
          }
        } else {
          columns[i] = [{id:0, name:"Rows", field:0, cssClass:"cell-title"}];
          for(var j = 1; j < drupalSettings.quadstat_core.data[i]['longest_row'] || j < 35; j++) { 
            columns[i].push({id: j, name: drupalSettings.quadstat_core.data[i]['val'][0][j - 1], field:j});
          }
        }
      }

      function setItems(data, objectIdProperty) {
        if (objectIdProperty !== undefined) {
          idProperty = objectIdProperty;
        }
        items = data;
        refreshIdxById();
        refresh();
      }

      // Setup rows, minimum 10x10 grid
      for (var i = 0; i < drupalSettings.quadstat_core.data.length; i++ ) {
        data[i] = new Array();
        if (drupalSettings.quadstat_core.is_cont_table[i] == 0) {
          for (var j = 1; j < drupalSettings.quadstat_core.data[i]['val'].length; j++) {
            data[i][j - 1] = new Array();
            for (var k = 0; k < drupalSettings.quadstat_core.data[i].val[j].length; k++) {
              data[i][j - 1][k]  = drupalSettings.quadstat_core.data[i].val[j][k];
            }
          }
        } else {
          for (var j = 1; j < drupalSettings.quadstat_core.data[i]['val'].length; j++) {
            data[i][j - 1] = new Array();
            for (var k = 0; k < drupalSettings.quadstat_core.data[i].val[j].length; k++) {
              data[i][j - 1][k]  = drupalSettings.quadstat_core.data[i].val[j][k];
            }
          }
        }
      }

      if($('#quadstat-slickgrid').length && typeof columns[0] !== 'undefined') {
        grid = new Slick.Grid("#quadstat-slickgrid", data[0], columns[0], options);
        grid.init();  
      } else {
        return;
      }

      $('#block-views-block-dataset-block-1 table').on('dataset_changed', 'td.views-field.views-field-fid', load_new_dataset);
      function load_new_dataset() { 
        $('#edit-submit').unbind();
        var fid = Number($('td.views-field.views-field-fid').html());
        grid.invalidate();
        grid = new Slick.Grid("#quadstat-slickgrid", data[drupalSettings.quadstat_core.map[fid]], columns[drupalSettings.quadstat_core.map[fid]], options);
        grid.init();
        // Tell Quadstat which dataset to perform the application on
        $('#edit-x').val(fid);

        $('#edit-submit').click(function(e) {
          if($('#edit-v').length && $('#edit-v')[0].checked) {
            // do nothing
          } else {
            if($('#edit-v1').length == 0) {
              //do nothing
            } else if (($('#edit-v1').val() == '-1' && $('#edit-v2').length == 0) || ($('#edit-v2').length == 0 && $('div.slick-header-column').hasClass('highlighted-col-2'))) {
              alert('To process your query, please select a single dataset column.');
              e.stopPropagation();
              e.preventDefault();
              return false;
            } else if ($('#edit-v2').val() == '-1' || ($('#edit-v1').val() != '-1' && $('#edit-v2').val() != '-1' && $('div.slick-header-column').hasClass('highlighted-col-3'))) {
              alert('To process your query, please select exactly two dataset columns.');
              e.stopPropagation();
              e.preventDefault();
              return false;
            }
          }
        });

        // Clicking a header columns triggers the column highlighter and records selected dataset input column vectors
        $('.slick-header-column').click(function() {
          var col = $(this).index();
          highlight(col);
        });

        $('input[data-drupal-selector="edit-application-dataset-edit"]').removeClass('js-form-submit form-submit').prop("type", "button");
      }

      $('#block-views-block-dataset-block-1 td.views-field.views-field-fid').trigger('dataset_changed');

      if($('.no-dataset').length) {
        $('#block-views-block-dataset-block-1, #quadstat-slickgrid').css('display', 'none'); 
      }  

    }
  };
}(jQuery, Drupal, document));
