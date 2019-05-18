/**
 * Behavior for 'aggrid_widget_type' widget.
 */
(function ($, Drupal, drupalSettings) {
  'use strict';
  var aggridFieldName = [];
  var aggridDataEdit = [];
  var aggridValidationErrors = [];
  var aggridShowError = [];
  var aggridEditStart = [];
  var aggridPasteStart = [];

  var eGridDiv = [];
  var gridOptions = [];

  // Get the license for aggrid
  const aggridLicense = drupalSettings.aggrid.settings.license_key;

  const aggridGSJSON = {
    parserTypes: {
      int: {
        regEx: '^\\d+$',
        restrictInput: 'numeric',
        errorMsg: 'Value must be a positive integer only. No decimals, commas, or other characters.'
      },
      intN: {
        regEx: '^-\\d+$',
        restrictInput: 'numeric',
        errorMsg: 'Value must be a negative integer only. No decimals, commas, or other characters.'
      },
      intPN: {
        regEx: '^-?\\d+$',
        restrictInput: 'numeric',
        errorMsg: 'Value must be a positive/negative integer only. No decimals, commas, or other characters.'
      },
      dateUSASTD: {
        regEx: '(?=\\d)^(?:(?!(?:10\\D(?:0?[5-9]|1[0-4])\\D(?:1582))|(?:0?9\\D(?:0?[3-9]|1[0-3])\\D(?:1752)))((?:0?[13578]|1[02])|(?:0?[469]|11)(?!\\/31)(?!-31)(?!\\.31)|(?:0?2(?=.?(?:(?:29.(?!000[04]|(?:(?:1[^0-6]|[2468][^048]|[3579][^26])00))(?:(?:(?:\\d\\d)(?:[02468][048]|[13579][26])(?!\x20BC))|(?:00(?:42|3[0369]|2[147]|1[258]|09)\x20BC))))))|(?:0?2(?=.(?:(?:\\d\\D)|(?:[01]\\d)|(?:2[0-8])))))([-.\\/])(0?[1-9]|[12]\\d|3[01])\\2(?!0000)((?=(?:00(?:4[0-5]|[0-3]?\\d)\x20BC)|(?:\\d{4}(?!\x20BC)))\\d{4}(?:\x20BC)?)(?:$|(?=\x20\\d)\x20))?((?:(?:0?[1-9]|1[012])(?::[0-5]\\d){0,2}(?:\x20[aApP][mM]))|(?:[01]\\d|2[0-3])(?::[0-5]\\d){1,2})?$',
        restrictInput: 'date',
        errorMsg: 'Value must be a date in the following format: mm/dd/yyyy'
      },
      dec: {
        regEx: '^\\d*\\.?\\d+$',
        restrictInput: 'numeric',
        errorMsg: 'Value must be a positive decimal only.'
      },
      decN: {
        regEx: '^-\\d*\\.?\\d+$',
        restrictInput: 'numeric',
        errorMsg: 'Value must be a negative decimal only.'
      },
      decPN: {
        regEx: '^-?\\d*\\.?\\d+$',
        restrictInput: 'numeric',
        errorMsg: 'Value must be a positive/negative decimal only.'
      },
      dropdown: {
        errorMsg: 'Value must match an item provided in the drop down options. Double click the field to view.'
      }
    },
    formatTypes: {
      numDec: {
        type: 'number',
        locale: 'en',
        options: {
          maximumFractionDigits: 8
        }
      },
      numInt: {
        type: 'number',
        locale: 'en',
        options: {
          maximumFractionDigits: 0
        }
      },
      numPer: {
        type: 'number',
        locale: 'en',
        options: {
          style: 'percent',
          maximumFractionDigits: 2
        }
      },
      numUsd: {
        type: 'number',
        locale: 'en',
        options: {
          style: 'currency',
          currency: 'USD',
          maximumFractionDigits: 2
        }
      }
    },
    restrictInput: {
      numeric: {
        regEx: '[^-\\.\\d]'
      },
      date: {
        regEx: '[^\\d\\/]'
      }
    }
  };

  // Apply the Excel Styles
  const aggridExcelStyles = [
    {
      id: 'header',
      interior: {
        color: '#f5f7f7',
        pattern: 'Solid'
      },
      font: {
        bold: true
      },
      alignment: {
        wrapText: true
      },
      borders: {
        borderBottom: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        },
        borderLeft: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        },
        borderRight: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        },
        borderTop: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        }
      }
    },
    {
      id: "aggrid-cell-section",
      interior: {
        color: '#f5f7f7',
        pattern: 'Solid'
      },
      font: {
        bold: true
      },
      alignment: {
        wrapText: true
      },
      borders: {
        borderBottom: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        },
        borderLeft: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        },
        borderRight: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        },
        borderTop: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        }
      }
    },
    {
      id: "aggrid-cell-label",
      interior: {
        color: '#fbfdfd',
        pattern: 'Solid'
      },
      font: {
        bold: true
      },
      alignment: {
        wrapText: true
      },
      borders: {
        borderBottom: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        },
        borderLeft: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        },
        borderRight: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        },
        borderTop: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 1
        }
      }
    },
    {
      id: "aggrid-cell-disabled",
      interior: {
        color: '#CCCCCC',
        pattern: 'Solid'
      }
    },
    {
      id: "aggrid-cell-indent",
      alignment: {
        indent: 1
      }
    },
    {
      id: "aggrid-cell-indent-dbl",
      alignment: {
        indent: 2
      }
    },
    {
      "id": "excelDateTime",
      "dataType": "dateTime",
      "numberFormat": {
        "format": "mm/dd/yyyy hh:mm:ss;;;"
      }
    },
    {
      "id": "excelDate",
      "dataType": "dateTime",
      "numberFormat": {
        "format": "mm/dd/yyyy;@"
      }
    },
    {
      "id": "excelCurrency",
      "dataType": "number",
      "numberFormat": {
        "format": "#,##0.00"
      }
    },
    {
      "id": "excelUSCurrency",
      "dataType": "number",
      "numberFormat": {
        "format": "$#,##0.00"
      }
    },
    {
      "id": "excelGreenBackground",
      "interior": {
        "color": "#b5e6b5",
        "pattern": "Solid"
      }
    },
    {
      id: 'excelRedFont',
      font: {
        fontName: 'Calibri Light',
        underline: 'Single',
        italic: true,
        color: '#ff0000'
      }
    },
    {
      id: 'excelDarkGreyBackground',
      interior: {
        color: '#888888',
        pattern: 'Solid'
      },
      font: {
        fontName: 'Calibri Light',
        color: '#ffffff'
      }
    },
    {
      id: 'excelBoldBorders',
      borders: {
        borderBottom: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 3
        },
        borderLeft: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 3
        },
        borderRight: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 3
        },
        borderTop: {
          color: '#000000',
          lineStyle: 'Continuous',
          weight: 3
        }
      }
    },
    {
      id: 'excelTextFormat',
      dataType: 'string'
    }
  ];

  Drupal.aggridInstances = [];

  Drupal.behaviors.aggridIntegration = {
    attach: function (context) {
      function makeJson(item) {
        var aggridOutput = {};
        aggridOutput = gridOptions[item].rowData;

        // Write back to Drupal 'Value' field
        $('#' + aggridFieldName[item] + '_rowData').val(
          JSON.stringify(aggridOutput)
            .replace(/NaN/g, 0)
            .replace(/Infinity/g, 0)
        );
      }

      function smartJSONextend(obj1, obj2) {
        // clone
        var mergedObj = JSON.parse(JSON.stringify(obj1));

        (function recurse(currMergedObj, currObj2) {
          var key;

          for (key in currObj2) {
            if (currObj2.hasOwnProperty(key)) {
              // keep path alive in mergedObj
              if (!currMergedObj[key]) {
                currMergedObj[key] = undefined;
              }

              if (
                typeof currObj2[key] === 'string' ||
                typeof currObj2[key] === 'number' ||
                typeof currObj2[key] === 'boolean'
              ) {
                // overwrite if obj2 is leaf and not nested
                currMergedObj[key] = currObj2[key];
              }
              else if (typeof currObj2[key] === 'object') {
                // obj2 is nested

                // and currMergedObj[key] is undefined, sync types
                if (!currMergedObj[key]) {
                  // obj2[key] ifArray
                  if (currObj2[key].length !== undefined) {
                    currMergedObj[key] = [];
                  }
                  else {
                    currMergedObj[key] = {};
                  }
                }
                recurse(currMergedObj[key], currObj2[key]);
              }
            }
          }
        })(mergedObj, obj2);

        return mergedObj;
      }

      // @todo Init functionality should support multiple drop zones on page.
      var selector = $('.aggrid-widget');
      var idArray = [];

      selector.each(function () {
        idArray.push(this.id);
      });

      jQuery.each(idArray, function (index, item) {
        let aggridDiv;
        let colDefsValue;
        let rowSettingsValue;
        let rowDataValue;
        let addOptValue;
        // variable used temporarily for full header name creation loop
        let tempList = [];

        let aggridJSON_colDefs;
        let aggridJSON_rowSettings;
        let aggridJSON_rowData;
        let aggridJSON_addOpt;

        // all field columns are placed in here
        let aggridFields;
        // Will contain full header name for each
        let aggridFieldFullHeaderName = [];

        // column field: parent - child - child
        // Will contain a label for each row if provided.
        let aggridRowLabels = [];

        // Set the aggrid div variable
        aggridDiv = $('#' + item);

        aggridFieldName[item] = aggridDiv.data('target');
        aggridDataEdit[item] = aggridDiv.data('edit');
        // Set the validation errors variable for this aggrid
        aggridValidationErrors[item] = {};
        aggridShowError[item] = false;
        aggridEditStart[item] = false;
        aggridPasteStart[item] = false;

        // If aggrid instance is already registered on Element. There is no
        // need to register it again.
        if (aggridDiv.once('' + aggridDiv + '').length !== aggridDiv.length) {
          return;
        }

        // Get the data
        colDefsValue = drupalSettings.aggrid.settings[aggridFieldName[item]].columnDefs;
        rowSettingsValue = drupalSettings.aggrid.settings[aggridFieldName[item]].rowSettings;
        rowDataValue = $('#' + aggridFieldName[item] + '_rowData').val();
        addOptValue = drupalSettings.aggrid.settings[aggridFieldName[item]].addOptions;

        // If it's not blank, parse the json. otherwise, null
        aggridJSON_colDefs = colDefsValue ? JSON.parse(colDefsValue) :
          null;
        aggridJSON_rowSettings = rowSettingsValue ?
          JSON.parse(rowSettingsValue) :
          null;
        aggridJSON_rowData = rowDataValue ? JSON.parse(rowDataValue) :
          null;
        aggridJSON_addOpt = addOptValue ? JSON.parse(addOptValue) :
          null;

        function cellStyleFunc(params) {
          // Check errors first and then on from there.
          if (
            aggridValidationErrors[item] !== '' &&
            typeof aggridValidationErrors[item][params.node.id] !==
              'undefined' &&
            typeof aggridValidationErrors[item][params.node.id][
              params.colDef.field
            ] !== 'undefined' &&
            Object.keys(
              aggridValidationErrors[item][params.node.id][params.colDef.field]
            ).length > 0
          ) {
            // There is an error, make sure the message shows.
            aggridShowError[item] = true;

            return {
              'background-color': 'lightcoral'
            };
          }
          else {
            // Blank it
            return {
              'background-color': ''
            };
          }
        }

        function cellClassFunc(params) {
          let cellClassVar = [];
          let rscParamsRowId;
          let rscParamsColDefField;

          if (typeof params.node.rowPinned === 'undefined') {
            rscParamsRowId = params.node.id;
          }
          else {
            rscParamsRowId =
              params.node.rowPinned.substr(0, 1) + '-' + params.node.rowIndex;
          }

          rscParamsColDefField = params.colDef.field;

          // Check for a cell setting
          cellClassVar = rowSettingsCheck(
            rscParamsRowId,
            rscParamsColDefField,
            'cellClass'
          ).split(" ");

          if (editableFunc(params)) {
            cellClassVar.push("aggrid-cell-edit-ok");
          }
          else {
            cellClassVar.push("aggrid-cell-edit-no");
          }

          return cellClassVar;
        }

        function colSpanFunc(params) {
          let colSpanVar;
          let rscParamsRowId;
          let rscParamsColDefField;

          if (typeof params.node.rowPinned === 'undefined') {
            rscParamsRowId = params.node.id;
          }
          else {
            rscParamsRowId =
              params.node.rowPinned.substr(0, 1) + '-' + params.node.rowIndex;
          }

          rscParamsColDefField = params.colDef.field;
          // Check for a cell setting
          colSpanVar = rowSettingsCheck(
            rscParamsRowId,
            rscParamsColDefField,
            'colSpan'
          );

          // If var is good, send it on. Otherwise, send default
          if (colSpanVar === '') {
            colSpanVar = 1;
          }

          return colSpanVar;
        }

        function editableFunc(params) {
          let rscParamsRowId;
          let rscParamsColDefField;

          if (typeof params.node.rowPinned === 'undefined') {
            rscParamsRowId = params.node.id;
          }
          else {
            rscParamsRowId =
              params.node.rowPinned.substr(0, 1) + '-' + params.node.rowIndex;
          }

          rscParamsColDefField = params.colDef.field;

          let editableVar;

          // Only check if editable when the grid is set to be editable.
          // Otherwise, it's false.
          if (aggridDataEdit[item]) {
            // This 'if' section will first check for an individual cell
            // setting, row, column, and then all columns. First available is
            // priority.
            editableVar = rowSettingsCheck(
              rscParamsRowId,
              rscParamsColDefField,
              'editable'
            );
            // if the variable is blank, and grid editing is on, default to
            // editable
            if (editableVar === '') {
              editableVar = true;
            }
          }
          else {
            // grid is not editable then always send false
            editableVar = false;
          }

          // output
          return editableVar;
        }

        function getHeaderParentItems(data) {
          tempList.push(data.getDefinition('headerName').headerName);
          if (data.parent !== null) {
            getHeaderParentItems(data.parent);
          }
        }

        function getRowLabels() {
          // Loop through each row and get fields assigned as isRowLabel
          $.each(gridOptions[item].rowData, function (row) {
            tempList[row] = [];
            $.each(aggridFields, function (count, field) {
              if (rowSettingsCheck(row, field.colId,
                'isRowLabel') === true) {
                tempList[row].push(gridOptions[item].rowData[
                  row][field.colId]);
              }
            });
            aggridRowLabels[row] = tempList[row]
              .filter(function (e) {
                return e;
              })
              .join(' - ');
          });
        }

        function restrictInputFunc(params) {
          // Only allow specific characters
          let ptName;
          let rscParamsRowId;
          let rscParamsColDefField;
          let inputField = 'input.ag-cell-edit-input';

          if (typeof params.node.rowPinned === 'undefined') {
            rscParamsRowId = params.node.id;
          }
          else {
            rscParamsRowId =
              params.node.rowPinned.substr(0, 1) + '-' + params.node.rowIndex;
          }

          rscParamsColDefField = params.colDef.field;

          // Check setting for cell, row, column, and then all columns. First
          // available is priority.
          ptName = rowSettingsCheck(
            rscParamsRowId,
            rscParamsColDefField,
            'parserType'
          );

          // If the ptName is set and restrictInput is available, move forward.
          // Otherwise it's an error.
          if (
            ptName !== '' &&
            typeof aggridGSJSON.parserTypes[ptName] !== 'undefined' &&
            typeof aggridGSJSON.parserTypes[ptName].restrictInput !==
              'undefined' &&
            typeof aggridGSJSON.restrictInput[
              aggridGSJSON.parserTypes[ptName].restrictInput
            ].regEx !== 'undefined'
          ) {
            let regEx = new RegExp(
              aggridGSJSON.restrictInput[
                aggridGSJSON.parserTypes[ptName].restrictInput
              ].regEx
            );

            // Put a keydown trigger on the cell input (appears when a user is
            // editing a cell)
            $(inputField)
              .on('keyup', function (e) {
                if (typeof e.keyCode !== 'undefined' && e.keyCode !== 9) { // Ignore TAB for navigation purposes
                  this.value = this.value.replace(regEx, '');
                }
              })
              .trigger('keyup');
          }
          else {
            // If the code reaches here, it's an error, so write the error to
            // console
            let errorMsg = 'D8 agGrid restrictInput Error: ';
            // ptName is available but there are issues
            if (
              ptName !== '' &&
              typeof aggridGSJSON.parserTypes[ptName] === 'undefined'
            ) {
              // parserType not found, tell the user
              console.log(
                Drupal.t(
                  errorMsg + ptName + ' parserType not found.', {}, {
                    context: 'aggrid error parserType not found'
                  }
                )
              );
            }
            else if (
              typeof aggridGSJSON.parserTypes[ptName] !== 'undefined' &&
              typeof aggridGSJSON.parserTypes[ptName].restrictInput ===
                'undefined'
            ) {
              // No restriction set on parserType. no error message.
            }
            else if (
              typeof aggridGSJSON.parserTypes[ptName] !== 'undefined' &&
              typeof aggridGSJSON.restrictInput[
                aggridGSJSON.parserTypes[ptName].restrictInput
              ].regEx !== 'undefined'
            ) {
              // Restriction set but a regEx is not defined in restrictInput
              // for item
              console.log(
                Drupal.t(
                  errorMsg +
                  ptName +
                  ' regEx missing for restrictInput "jserr_restrictInput"', {
                    jserr_restrictInput: aggridGSJSON.parserTypes[
                      ptName].restrictInput
                  }, {
                    context: 'aggrid error restrictInput missing regEx'
                  }
                )
              );
            }
          }
        }

        function rowSettingsCheck(rscParamsRowId, rscParamsColDefField, field) {
          // Check values and return a result
          if (
            aggridJSON_rowSettings !== null &&
            aggridJSON_rowSettings !== ''
          ) {
            if (
              typeof aggridJSON_rowSettings[rscParamsRowId] !==
                'undefined' &&
              typeof aggridJSON_rowSettings[rscParamsRowId][
                rscParamsColDefField
              ] !== 'undefined' &&
              typeof aggridJSON_rowSettings[rscParamsRowId][
                rscParamsColDefField
              ][field] !== 'undefined'
            ) {
              return aggridJSON_rowSettings[rscParamsRowId][
                rscParamsColDefField
              ][field];
            }
            else if (
              typeof aggridJSON_rowSettings[rscParamsRowId] !==
              'undefined' &&
              typeof aggridJSON_rowSettings[rscParamsRowId].rowDefault !==
              'undefined' &&
              typeof aggridJSON_rowSettings[rscParamsRowId].rowDefault[
                field] !== 'undefined'
            ) {
              return aggridJSON_rowSettings[rscParamsRowId].rowDefault[
                field];
            }
            else if (
              typeof aggridJSON_rowSettings.default !== 'undefined' &&
              typeof aggridJSON_rowSettings.default[
                rscParamsColDefField] !== 'undefined' &&
              typeof aggridJSON_rowSettings.default[
                rscParamsColDefField][field] !== 'undefined'
            ) {
              return aggridJSON_rowSettings.default[
                rscParamsColDefField][field];
            }
            else if (
              typeof aggridJSON_rowSettings.default !== 'undefined' &&
              typeof aggridJSON_rowSettings.default.rowDefault !==
              'undefined' &&
              typeof aggridJSON_rowSettings.default.rowDefault[field] !==
              'undefined'
            ) {
              return aggridJSON_rowSettings.default.rowDefault[field];
            }
          }

          return ''; // all else fails, send blank
        }

        function rowSpanFunc(params) {
          let rowSpanVar;
          let rscParamsRowId;
          let rscParamsColDefField;

          if (typeof params.node.rowPinned === 'undefined') {
            rscParamsRowId = params.node.id;
          }
          else {
            rscParamsRowId =
              params.node.rowPinned.substr(0, 1) + '-' + params.node.rowIndex;
          }

          rscParamsColDefField = params.colDef.field;

          // Check for a cell setting
          rowSpanVar = rowSettingsCheck(
            rscParamsRowId,
            rscParamsColDefField,
            'rowSpan'
          );

          // If var is good, send it on. Otherwise, send default
          if (rowSpanVar !== '') {
            return rowSpanVar;
          }
          else {
            return 1;
          }
        }

        function validationErrorClear(params, ptName) {
          let paramsNodeId = params.node.id;
          let paramsColumnColId = params.column.colId;

          // Clear any previous error on ptName if available
          if (
            ptName !== '' &&
            typeof aggridValidationErrors[item][paramsNodeId] !==
            'undefined' &&
            typeof aggridValidationErrors[item][paramsNodeId][
              paramsColumnColId
            ] !== 'undefined' &&
            typeof aggridValidationErrors[item][paramsNodeId][
              paramsColumnColId
            ][ptName] !== 'undefined'
          ) {
            delete aggridValidationErrors[item][paramsNodeId][
              paramsColumnColId
            ][ptName];
            // clear field if empty
            if (
              jQuery.isEmptyObject(
                aggridValidationErrors[item][paramsNodeId][
                  paramsColumnColId
                ]
              )
            ) {
              delete aggridValidationErrors[item][paramsNodeId][
                paramsColumnColId
              ];
            }
            // clear row if empty
            if (
              jQuery.isEmptyObject(aggridValidationErrors[item][
                paramsNodeId
              ])
            ) {
              delete aggridValidationErrors[item][paramsNodeId];
            }
            // Make sure validation error dialog is updated
            validationErrorUpdate();
          }
        }

        function validationErrorShow() {
          // make sure dialog html is up to date
          validationErrorUpdate();

          // show it
          $('#' + aggridFieldName[item] + '_error').dialog({
            dialogClass: 'aggrid-error-dialog',
            height: 250,
            width: 400
          });

          aggridShowError[item] = false; // reset show error on regular editing
        }

        function validationErrorUpdate() {
          let errorRow;

          // loop through and get errors for dialog
          errorRow = '<div>';
          $.each(aggridValidationErrors[item], function (rowindex) {
            errorRow += Drupal.t(
              '<h3>Row jserr_rowNum: jserr_rowLabel</h3>', {
                jserr_rowNum: Number(rowindex) + 1,
                jserr_rowLabel: aggridRowLabels[rowindex]
              }, {
                context: 'Display aggrid cell validation error list: row'
              }
            );
            errorRow += '<div>';
            $.each(aggridValidationErrors[item][rowindex],
              function (fieldname) {
                errorRow += Drupal.t(
                  '<h4>jserr_headerName</h4>', {
                    jserr_headerName: aggridFieldFullHeaderName[
                      fieldname]
                  }, {
                    context: 'Display aggrid cell validation error messages list: column'
                  }
                );
                $.each(
                  aggridValidationErrors[item][rowindex][fieldname],
                  function (errType, errItems) {
                    errorRow += Drupal.t(
                      '<p>"jserr_newValue" did not validate. jserr_message [jserr_errType]</p>',
                      {
                        jserr_newValue: errItems.newValue,
                        jserr_errType: errType,
                        jserr_message: errItems.message
                      },
                      {
                        context: 'Display aggrid cell validation error messages to end users'
                      }
                    );
                  }
                );
              });
            errorRow += '</div>';
          });
          errorRow += '</div>';

          // if no errors, tell em
          if (errorRow === '') {
            // reset show error on regular editing
            errorRow = 'No validation errors';
          }

          // change dialog html
          $('#' + aggridFieldName[item] + '_error').html(errorRow);
        }


        function valueConstraintFunc(params) {
          // valueConstraintFunc is executed from valueParserFunc after
          // positive return.
          let ctJSON;
          let paramsNodeId = params.node.id;
          let paramsColumnColId = params.column.colId;
          let constraintType_errorJSON = {};

          let rscParamsRowId;
          let rscParamsColDefField;

          if (typeof params.node.rowPinned === 'undefined') {
            rscParamsRowId = params.node.id;
          }
          else {
            rscParamsRowId =
              params.node.rowPinned.substr(0, 1) + '-' + params.node.rowIndex;
          }

          rscParamsColDefField = params.colDef.field;

          // Make sure the user is typing the correct info.
          // Default to ptName provided if available. Otherwise, get the setting
          if (typeof ctJSON !== 'undefined' || ctJSON !== '') {
            // Check setting for cell, row, column, and then all columns. First
            // available is priority.
            ctJSON = rowSettingsCheck(
              rscParamsRowId,
              rscParamsColDefField,
              'constraintType'
            );
          }

          // If the ctName is set and constraint/errorMsg is available, move
          // forward. Otherwise it's an error.
          if (
            ctJSON !== '' &&
            typeof ctJSON.constraint !== 'undefined' &&
            typeof ctJSON.errorMsg !== 'undefined'
          ) {
            let constIf = ctJSON.constraint;

            // Clear the current error for this item, if there is one
            validationErrorClear(params, 'constraint');

            // Check the value against the constraint. if it's good, apply.
            // Otherwise tell the user and flip back.
            if (eval(constIf)) {
              // We're good, let the new value go into place
              return params.newValue;
            }
            else {
              // New value doesn't meet the constraint. Process
              if (params.newValue === '') {
                // If delete, just change to zero.
                // (This needs adjustment for number vs any other field)
                return 0;
              }
              else {
                // Has a newValue, though it's not meeting requirements.
                // Process it Make sure our variable is ready for data
                constraintType_errorJSON[paramsNodeId] = {};
                constraintType_errorJSON[paramsNodeId][
                  paramsColumnColId
                ] = {};

                // Set the error values. old, new, and the message
                constraintType_errorJSON[paramsNodeId][
                  paramsColumnColId
                ]['constraint'] = {
                  oldValue: params.oldValue,
                  newValue: params.newValue,
                  message: ctJSON.errorMsg
                };

                // Merge the JSON - This smartJSONextend function will merge
                // without writing over objects
                aggridValidationErrors[item] = smartJSONextend(
                  aggridValidationErrors[item],
                  constraintType_errorJSON
                );

                // Make sure validation error dialog is updated
                validationErrorUpdate();

                // Place the value and refresh the cells, this fixed an issue
                // with multi copy and paste
                gridOptions[item].rowData[paramsNodeId][
                  paramsColumnColId
                ] = params.oldValue;
                gridOptions[item].api.refreshCells();

                // Return it
                return params.oldValue;
              }
            }
          }
          else {
            // If the code reaches here, it's an error, so write the error to
            // console
            let errorMsg = 'D8 agGrid constraintType Error: ';
            //  is available but there are issues
            if (
              ctJSON === '' &&
              typeof ctJSON === 'undefined' &&
              typeof ctJSON.constraint === 'undefined' &&
              typeof ctJSON.errorMsg === 'undefined'
            ) {
              // found in aggridGSJSON, but missing the necessary objects
              console.log(
                Drupal.t(
                  errorMsg + ' missing constraint or errorMsg', {}, {
                    context: 'aggrid error constraintType missing constraint or errorMsg'
                  }
                )
              );
            }

            // Even though it is an error and missing, treat the change as a
            // good one.
            return params.newValue;
          }
        }

        function valueFormatterFunc(params) {
          let ftName;

          let rscParamsRowId;
          let rscParamsColDefField;

          if (params.node === null) {
            rscParamsRowId = 0;
          }
          else if (typeof params.node.rowPinned === 'undefined') {
            rscParamsRowId = params.node.id;
          }
          else {
            rscParamsRowId =
              params.node.rowPinned.substr(0, 1) + '-' + params.node.rowIndex;
          }

          rscParamsColDefField = params.colDef.field;

          // Change the look of the cells based on settings
          // Default to ftName provided if available. Otherwise, get the setting
          if (typeof ftName !== 'undefined' || ftName !== '') {
            // Check setting for cell, row, column, and then all columns. First
            // available is priority.
            ftName = rowSettingsCheck(
              rscParamsRowId,
              rscParamsColDefField,
              'formatType'
            );
          }

          // If there is no ftName at this point, it is possibly an error or
          // perhaps no settings for item. Just allow any value. Otherwise, if
          // type is available and solution provided, move forward. Also,
          // exclude pinned header and footer row (isnumeric)
          if (
            ftName !== '' &&
            typeof aggridGSJSON.formatTypes[ftName] !== 'undefined' &&
            aggridGSJSON.formatTypes[ftName].type === 'number'
          ) {
            // Set variables for formatNumber
            let locale = 'en'; // Default to English (USA)
            let options = {};
            let ftItem = aggridGSJSON.formatTypes[ftName];

            // Optional settings for NumberFormat for locale and options
            if (typeof ftItem.locale !== 'undefined') {
              locale = ftItem.locale;
            }
            if (typeof ftItem.options !== 'undefined') {
              options = ftItem.options;
            }

            // if it is a number, format it. otherwise, dont.
            if ($.isNumeric(params.value)) {
              // Format based on locale and extra options
              return Intl.NumberFormat(locale, options).format(params
                .value);
            }
            else {
              // Return without format
              return params.value;
            }
          }
          else {
            if (ftName !== '' && $.isNumeric(params.node.id)) {
              console.log(
                Drupal.t(
                  'D8 agGrid formatType Error: jserr_ftName or "type" not found in function',
                  {
                    jserr_ftName: ftName
                  }
                )
              );
            }

            // Return without format
            return params.value;
          }
        }

        function valueGetterFunc(params) {
          let valueGetterItems;
          let valueGot;
          // Auto value change when needed
          let rscParamsRowId;
          let rscParamsColDefField;

          if (typeof params.node.rowPinned === 'undefined') {
            rscParamsRowId = params.node.id;
          }
          else {
            rscParamsRowId =
              params.node.rowPinned.substr(0, 1) + '-' + params.node.rowIndex;
          }

          rscParamsColDefField = params.colDef.field;

          // Check setting for cell, row, column, and then all columns. First
          // available is priority.
          valueGetterItems = rowSettingsCheck(
            rscParamsRowId,
            rscParamsColDefField,
            'valueGetter'
          );

          // Exclude if getter is empty and exclude pinned row header and footer
          if (
            valueGetterItems !== '' &&
            typeof gridOptions[item].rowData[params.node.id] !==
            'undefined' &&
            typeof gridOptions[item].rowData[params.node.id][
              params.column.colId
            ] !== 'undefined'
          ) {
            // Return valueGetter
            valueGot = eval(valueGetterItems);

            // Update the cell value
            gridOptions[item].rowData[params.node.id][
              params.column.colId
            ] = valueGot;
            makeJson(item); // Make sure the rowData output is updated
            // Return it
            return valueGot;
          }
          else {
            // else, just return the current value
            return params.data[params.column.colId];
          }
        }

        function valueParserFunc(params) {
          let ptName;
          let paramsNodeId = params.node.id;
          let paramsColumnColId = params.column.colId;
          let parserType_errorJSON = {};

          let rscParamsRowId;
          let rscParamsColDefField;

          let newValue = params.newValue.valueOf();

          if (typeof params.node.rowPinned === 'undefined') {
            rscParamsRowId = params.node.id;
          }
          else {
            rscParamsRowId =
              params.node.rowPinned.substr(0, 1) + '-' + params.node.rowIndex;
          }

          rscParamsColDefField = params.colDef.field;

          // Make sure the user is typing the correct info.
          // Default to ptName provided if available. Otherwise, get the setting
          if (typeof ptName !== 'undefined' || ptName !== '') {
            // Check setting for cell, row, column, and then all columns. First
            // available is priority.
            ptName = rowSettingsCheck(
              rscParamsRowId,
              rscParamsColDefField,
              'parserType'
            );
          }

          // If the ptName is set and regEx/errorMsg is available, move
          // forward. Otherwise it is an error.
          if (
            ptName !== '' &&
            ptName !== 'dropdown' &&
            typeof aggridGSJSON.parserTypes[ptName] !== 'undefined' &&
            typeof aggridGSJSON.parserTypes[ptName].regEx !==
            'undefined' &&
            typeof aggridGSJSON.parserTypes[ptName].errorMsg !==
            'undefined'
          ) {
            let regEx = new RegExp(aggridGSJSON.parserTypes[ptName].regEx);
            let restrictInput = '';

            if (typeof aggridGSJSON.parserTypes[ptName].restrictInput !== 'undefined') {
              restrictInput = aggridGSJSON.parserTypes[ptName].restrictInput;
            }

            // Clear the current error for this item, if there is one
            validationErrorClear(params, ptName);

            // If number, do clean-up
            if (restrictInput === 'numeric' && newValue !== '') {
              // ==== Numbers
              // Remove dollar signs and commas
              newValue = newValue.replace('$', '').replace(',', '');
              // ==== Percent
              // If user is pasting a percent (73.34%), change to .7334
              if (newValue.substr(newValue.length - 1) === '%'
                && !isNaN(newValue.substr(0, newValue.length-1))) {
                // Change percent to decimal
                newValue = newValue.substr(0, newValue.length-1) / 100;
              }
            }

            // Check the value against the regEx. if it is good, apply.
            // Otherwise tell the user and flip back.
            if (regEx.test(newValue)) {
              // Update the cell value
              gridOptions[item].rowData[params.node.id][
                params.column.colId
              ] = newValue;
              params.newValue = newValue;
              gridOptions[item].api.refreshCells();
              // Value is potentially correct for the type, now check any
              // constraints.
              return valueConstraintFunc(params);
            }
            else {
              // New value does not meet the requirements. Process
              if (newValue === '') {
                // If delete, just change to zero.
                // (This needs adjustment for number vs any other field)
                return 0;
              }
              else {
                // Has a newValue, though it is not meeting requirements.
                // Process it Make sure our variable is ready for data
                parserType_errorJSON[paramsNodeId] = {};
                parserType_errorJSON[paramsNodeId][paramsColumnColId] = {};
                parserType_errorJSON[paramsNodeId][paramsColumnColId][ptName] =
                  {};

                // Set the error values. old, new, and the message
                parserType_errorJSON[paramsNodeId][paramsColumnColId][ptName] =
                {
                  oldValue: params.oldValue,
                  newValue: newValue,
                  message: aggridGSJSON.parserTypes[ptName].errorMsg
                };

                // Merge the JSON - This smartJSONextend function will merge
                // without writing over objects
                aggridValidationErrors[item] = smartJSONextend(
                  aggridValidationErrors[item],
                  parserType_errorJSON
                );

                // Make sure validation error dialog is updated
                validationErrorUpdate();

                // Place the value and refresh the cells, this fixed an issue
                // with multi copy and paste
                gridOptions[item].rowData[paramsNodeId][
                  paramsColumnColId
                ] = params.oldValue;
                gridOptions[item].api.refreshCells();

                // Return it
                return params.oldValue;
              }
            }
          }
          else if (
            ptName === 'dropdown' &&
            typeof aggridGSJSON.parserTypes[ptName] !== 'undefined' &&
            aggridGSJSON.parserTypes[ptName].errorMsg !== 'undefined'
          ) {
            // Process a dropdown check. if it is not in the values for
            // cellEditorParams, then err

            // Clear the current error for this item, if there is one
            validationErrorClear(params, ptName);

            if (
              typeof params.colDef.cellEditorParams.values !==
              'undefined' &&
              params.colDef.cellEditorParams.values.indexOf(newValue) !==
              -1
            ) {
              // Update the cell value
              gridOptions[item].rowData[params.node.id][
                params.column.colId
              ] = newValue;
              // Value is potentially correct for the type, now check any
              // constraints.
              return valueConstraintFunc(params);
            }
            else {
              // Has a newValue, though it is not meeting requirements. Process
              // it
              /*  -- Had to comment out adding an error right now. dropdown does not fire off valueParser.

              // Make sure our variable is ready for data
              parserType_errorJSON[paramsNodeId] = {};
              parserType_errorJSON[paramsNodeId][paramsColumnColId] = {};
              parserType_errorJSON[paramsNodeId][paramsColumnColId][ptName] = {};

              // Set the error values. old, new, and the message
              parserType_errorJSON[paramsNodeId][paramsColumnColId][ptName] = {
                'oldValue': params.oldValue,
                'newValue': params.newValue,
                'message': aggridGSJSON.parserTypes[ptName].errorMsg
              };

              // Merge the JSON - This smartJSONextend function will merge without writing over objects
              aggridValidationErrors[item] = smartJSONextend(aggridValidationErrors[item], parserType_errorJSON);
              validationErrorUpdate(); // Make sure validation error dialog is updated
              */
              gridOptions[item].rowData[paramsNodeId][paramsColumnColId] = params.oldValue;
              gridOptions[item].api.refreshCells();

              return params.oldValue;
            }
          }
          else {
            // If the code reaches here, it is an error, so write the error to
            // console
            let errorMsg = 'D8 agGrid parserType Error: ';
            // ptName is available but there are issues
            if (
              ptName !== '' ||
              (ptName !== '' &&
                typeof aggridGSJSON.parserTypes[ptName] === 'undefined')
            ) {
              // not found in aggridGSJSON at all, tell the user
              console.log(
                Drupal.t(
                  errorMsg + ptName + ' not found.', {}, {
                    context: 'aggrid error parserType not found'
                  }
                )
              );
            }
            else if (
              ptName === '' &&
              typeof aggridGSJSON.parserTypes === 'undefined' &&
              typeof aggridGSJSON.parserTypes[ptName] === 'undefined' &&
              typeof aggridGSJSON.parserTypes[ptName].regEx ===
              'undefined' &&
              typeof aggridGSJSON.parserTypes[ptName].errorMsg ===
              'undefined'
            ) {
              // found in aggridGSJSON, but missing the necessary objects
              console.log(
                Drupal.t(
                  errorMsg + ptName + ' missing regEx or errorMsg', {}, {
                    context: 'aggrid error parserType missing regEx or errorMsg'
                  }
                )
              );
            }

            // Update the cell value
            gridOptions[item].rowData[params.node.id][
              params.column.colId
            ] = newValue;
            // Value is potentially correct for the type, now check any
            // constraints.
            return valueConstraintFunc(params);
          }
        }

        // #######
        // #######  Random functions
        // #######

        function columnTotal(field, idFrom, idTo) {
          // Used to help sum a spanning column total
          let rowCount;
          let colCount;
          let valColumnTotal = 0;

          // Check if field is an array... if not, make it one.
          if (!Array.isArray(field)) {
            field = field.split(',');
          }

          // Check idFrom and idTo to make sure they are defined and correctly
          // span vs an incorrect crossover
          if (
            typeof idFrom !== 'undefined' &&
            typeof idTo !== 'undefined' &&
            idFrom >= 0 &&
            idTo >= 0 &&
            idFrom <= idTo
          ) {
            // Loop through the span and sum the amounts
            for (rowCount = idFrom; rowCount <= idTo; rowCount++) {
              for (colCount = 0; colCount <= field.length; colCount++) {
                if ($.isNumeric(gridOptions[item].rowData[rowCount][
                  field[colCount]
                  ])) {
                  valColumnTotal += Number(
                    gridOptions[item].rowData[rowCount][field[colCount]]
                  );
                }
              }
            }
            // return the sum
            return valColumnTotal;
          }
          else {
            // Issue with the idFrom and idTo, tell the user and return zero
            console.log(
              Drupal.t(
                'D8 agGrid columnTotal Error: jserr_field - Check idFrom & idTo for issues.',
                {
                  jserr_field: field
                },
                {
                  context: 'aggrid error columnTotal idFrom & idTo not correct'
                }
              )
            );
            return 0;
          }
        }

        // For adding/removing a focus class
        function colFocusClass(params) {
          if (params.column !== null) {
            let colId = params.column.colId;

            // Remove any current focus class
            $("div.aggrid-col-focus").removeClass("aggrid-col-focus");
            // Add to current
            $("div[col-id='" + colId + "']").addClass("aggrid-col-focus");
          }
        }

        // #######
        // ####### Context Menu Items
        // #######

        function getContextMenuItems(params) {
          var result = [
            // built in copy item
            'copy',
            {
              // custom item
              name: 'Excel Export',
              action: function() {
                onBtExport(params);
              }
            }
          ];

          return result;
        }

        function onBtExport(params) {
          // Export Excel
          gridOptions[item].api.exportDataAsExcel(params);
        }

        // #######
        // ####### Build ag-Grid
        // #######

        // Build JSON for ag-Grid

        var aggridJSON = {
          columnDefs: aggridJSON_colDefs,
          rowData: aggridJSON_rowData
        };

        // Merge the grid
        aggridJSON = $.extend(aggridJSON, aggridJSON_addOpt);

        // Default Options for all ag-Grid
        // ensureDomOrder: true is added by default for accessibility
        // Though it is added, enableRangeSelection: true only works with
        // Enterprise
        var default_gridOptions = {
          enableRangeSelection: true,
          ensureDomOrder: true,
          domLayout: 'autoHeight',
          stopEditingWhenGridLosesFocus: true,
          onCellFocused: function (params) {
            colFocusClass(params);
          },
          onCellEditingStarted: function (params) {
            restrictInputFunc(params);
          },
          onCellEditingStopped: function (params) {
            aggridEditStart[item] = false;
            if (aggridShowError[item]) {
              validationErrorShow();
            }
          },
          onCellValueChanged: function (params) {
            if (aggridPasteStart[item]) {
              valueParserFunc(params);
            }
            if (aggridShowError[item]) {
              validationErrorShow();
            }

            makeJson(item);
          },
          onPasteStart: function () {
            aggridPasteStart[item] = true;
          },
          onPasteEnd: function () {
            aggridShowError[item] = false;
            aggridPasteStart[item] = false;
          },
          getContextMenuItems: getContextMenuItems,
          excelStyles: aggridExcelStyles
        };

        // Add the Default Options
        gridOptions[item] = aggridJSON;
        gridOptions[item] = $.extend(aggridJSON, default_gridOptions);

        let default_gridOptions_rowSettingsOptions = {};
        default_gridOptions_rowSettingsOptions['defaultColDef'] = {};

        // If rowSettings are available, add other functions
        if (aggridJSON_rowSettings !== null) {
          // rowSettings is there, so get the setting
          default_gridOptions_rowSettingsOptions['defaultColDef'] = {
            suppressMovable: true,
            suppressMenu: true,
            cellClass: cellClassFunc,
            cellStyle: cellStyleFunc,
            colSpan: colSpanFunc,
            rowSpan: rowSpanFunc,
            valueFormatter: valueFormatterFunc
          };
          // Only add editable, valueGetter, and valueParser on Edit = true
          if (aggridDataEdit[item]) {
            let default_gridOptions_rowSettingsOptions_edit = {
              editable: editableFunc,
              valueGetter: valueGetterFunc,
              valueParser: valueParserFunc
            };
            default_gridOptions_rowSettingsOptions['defaultColDef'] =
              $.extend(
                default_gridOptions_rowSettingsOptions[
                  'defaultColDef'],
                default_gridOptions_rowSettingsOptions_edit
              );
          }
        }
        else if (aggridDataEdit[item]) {
          // No rowSettings and edit = true, so add some defaults
          default_gridOptions_rowSettingsOptions['defaultColDef'] = {
            editable: true
          };
        }

        // Merge grid options together
        gridOptions[item] = $.extend(
          gridOptions[item],
          default_gridOptions_rowSettingsOptions
        );

        // Apply the license if it is available
        if (aggridLicense !== '' && agGrid.LicenseManager) {
          agGrid.LicenseManager.setLicenseKey(aggridLicense);
        }

        // Get the ag-Grid Div and start it up
        eGridDiv[item] = document.querySelector('#' + item);

        // create the grid passing in the div to use together with the columns
        // & data we want to use
        new agGrid.Grid(eGridDiv[item], gridOptions[item]);

        // Make sure the grid columns fit
        gridOptions[item].api.sizeColumnsToFit();

        // Apply all columns to this variable
        aggridFields = gridOptions[item].columnApi.getAllGridColumns();

        // Loop through columns and get the FULL name for each field. Just run
        // once for ag-Grid
        $.each(aggridFields, function (rowIndex) {
          aggridFieldFullHeaderName[aggridFields[rowIndex].colId] = [];
          tempList = [];
          getHeaderParentItems(aggridFields[rowIndex]);
          // Take collected headers, reverse them, separate by dashes and
          // clear out blanks on up to a header with 3 rows.
          aggridFieldFullHeaderName[aggridFields[rowIndex].colId] =
            tempList
              .reverse()
              .filter(function (e) {
                return e;
              })
              .join(' - ');
        });

        getRowLabels();
      });
    }
  };

  Drupal.behaviors.HtmlIntegration = {
    attach: function (context) {

      // @todo Init functionality should support multiple drop zones on page.
      var selector = $('.aggrid-html-widget');
      var idArray = [];

      selector.each(function () {
        idArray.push(this.id);
      });

      jQuery.each(idArray, function (index, item) {

        // Set the aggrid Table variable
        let aggridTable = $('#' + item);

        // Only run this once
        if (aggridTable.once('' + aggridTable + '').length !== aggridTable.length) {
          return;
        }

        // Set variables for formatNumber
        let locale = 'en'; // Default to English (USA)
        let options = {};

        aggridTable.each(function () {
          $('td', this).each(function () {
            let td_value = $(this).text();
            let td_class = "";

            if ($(this).attr('class')) {
              td_class = $(this).attr('class').split(' ');
            }

            if (td_value !== '') {
              for (let i in td_class) {
                if (td_class[i].substring(0, 17) === 'aggrid-html-ftype') {

                  let ftName = td_class[i].substring(18);
                  let ftItem = aggridGSJSON.formatTypes[ftName];

                  // Optional settings for NumberFormat for locale and options
                  if (typeof ftItem.locale !== 'undefined') {
                    locale = ftItem.locale;
                  }
                  if (typeof ftItem.options !== 'undefined') {
                    options = ftItem.options;
                  }

                  let formatter = new Intl.NumberFormat(locale, options);

                  $(this).text(formatter.format(td_value));
                }
              }
            }

          });

        });

      });
    }
  }
})(jQuery, Drupal, drupalSettings);
