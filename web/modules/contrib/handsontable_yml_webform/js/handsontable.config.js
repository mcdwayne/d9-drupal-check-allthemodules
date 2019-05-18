/* global Handsontable */

/**
 * @file
 * Main JavaScript file for the Handsontable module, which provides Drupal integration with the Handsontable JavaScript library.
 */

Drupal.behaviors.handsontable = {
    attach: function (context, settings) {

        function firstRowRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            td.style.fontWeight = 'bold';
            td.style.padding = '5px';
        }

        function checkIfEmpty(hot, data) {
            var noEmptyRows = false;
            jQuery.each(data, function (rowKey, object) {
                // If there's a row with data, it needs to be saved
                if (data.length > 1 && !hot.isEmptyRow(rowKey)) {
                    noEmptyRows = true;
                    return false;
                }
            });
            return noEmptyRows;
        }

        function updateTable(table, container) {
            var hot = jQuery(table).data('handsontable');
            var data = hot.getData();

            if (checkIfEmpty(hot, data)) {
                jQuery(container).val(JSON.stringify(data));
            } else {
                jQuery(container).val(null);
            }
        }

        // Initiate handsontable and add constructor options.
        jQuery.each(drupalSettings.handsontable.ids, function (i, val) {
            var userdefined_view_settings = drupalSettings.handsontable.view_settings[i];
            var container = '#' + val;
            var table = container + '-table';
            var sData = drupalSettings.handsontable.data[i];
            var sOrigData = userdefined_view_settings.initial_data ? userdefined_view_settings.initial_data : sData;
            var origData = JSON.parse(sOrigData);
            var view_settings = {
                data: JSON.parse(sData ? sData : sOrigData), // dieses Objekt wird live aktualisiert, wenn der Nutzer tippt
                minRows: 1,
                minCols: 2,
                contextMenu: ["undo", "redo"],
                colHeaders: false,
                manualColumnResize: true,
                manualRowResize: true,
                columnSorting: true,
                stretchH: 'all',
                cells: function (row, col, prop) {
                    var cellProperties = {};
                    if (row === 0) {
                        cellProperties.renderer = firstRowRenderer;
                    }
                    return cellProperties;
                },
                beforeChange: function () {
                    updateTable(table, container);
                },
                afterChange: function () {
                    updateTable(table, container);
                },
                afterCreateRow: function () {
                    updateTable(table, container);
                },
                afterCreateCol: function () {
                    updateTable(table, container);
                },
                afterRemoveRow: function () {
                    updateTable(table, container);
                },
                afterRemoveCol: function () {
                    updateTable(table, container);
                }
            };

            jQuery.extend(view_settings, userdefined_view_settings);

            var oColors = false;
            if (view_settings.background_colors) {
                oColors = JSON.parse(view_settings.background_colors);
                function webformBackgroundColorRenderer(instance, td, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer.apply(this, arguments);
                    if (oColors[row] && oColors[row][col]) {
                        td.style.background = oColors[row][col];
                    }
                    else {
                        td.style.background = '';
                    }
                }

                Handsontable.renderers.registerRenderer('webformBackgroundColorRenderer', webformBackgroundColorRenderer);

            }

            view_settings.cells = function (row, col, prop) {
                var cellProperties = {};
                if (view_settings.make_existing_data_read_only)
                    if (origData[row][col]) {
                        // die Zelle wurde vom Webform-Ersteller gefüllt
                        cellProperties.readOnly = true;
                    }

                if (oColors)
                    cellProperties.renderer = 'webformBackgroundColorRenderer';


                return cellProperties;
            };
            jQuery(table).handsontable(view_settings);
        });


        function setSize(ht, newRows, newCols, existingCols, existingRows) {
            //If the user pressed generate without changing any values, ignore
            if ((newRows == existingRows) && (newCols == existingCols)) {
                return true;
            }

            //See if rows or columns need to be inserted or removed
            var rowChange = (newRows > existingRows) ? 'insert_row' : 'remove_row';
            var colChange = (newCols > existingCols) ? 'insert_col' : 'remove_col';

            //Alter rows using the handsontable alter method
            if (Math.abs(newRows - existingRows)) {
                ht.alter(rowChange, null, Math.abs(newRows - existingRows));
            }
            //Alter columns using the handsontable alter method
            if (Math.abs(newCols - existingCols)) {
                ht.alter(colChange, null, Math.abs(newCols - existingCols));
            }
        }

        var actions = {
            addRow: function (event, ht) {
                ht.alter('insert_row');
            },
            addCol: function (event, ht) {
                ht.alter('insert_col');
            },
            alterTable: function (event, ht) {
                //Verify user input
                var newRows = document.getElementById('ht-rows').value,
                        newCols = document.getElementById('ht-cols').value;

                if ((isNaN(newRows)) || (isNaN(newCols)) || (newRows <= 0) || (newCols <= 0)) {
                    alert('Number of rows and columns must be a number greater than 0.');
                }
                else {
                    var existingRows = ht.countRows(),
                            existingCols = ht.countCols();

                    setSize(ht, newRows, newCols, existingCols, existingRows);
                    removeModal();
                }

            }
        };

        jQuery(document.body).unbind().delegate('div.handsontable-container a[data-action]', 'click', function (event) {
            var action = jQuery(this).data('action'),
                    name = jQuery(this).parents('div.handsontable-container').find('div.handsontable').attr('id'),
                    ht = jQuery('#' + name).handsontable('getInstance');

            event.preventDefault();

            // If there's an action with the given name, call it
            if (typeof actions[action] === 'function') {
                actions[action].call(this, event, ht, name);
            }
        });
    }
};