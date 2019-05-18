<?php

namespace Drupal\aggrid\Plugin\Field\FieldFormatter;

use Drupal\aggrid\Entity;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'aggrid_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "html_formatter_type",
 *   label = @Translation("HTML grid view mode"),
 *   field_types = {
 *     "aggrid"
 *   }
 * )
 */
class HtmlFormatterType extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Implement default settings.
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
        // Implement settings form.
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  public function getRowSettings($aggridRowSettings, $headers, $rowData, $rowPrefix) {
    $rowSettings[][] = [];
    for ($i = 0; $i < count($rowData); $i++) {
      foreach ($headers as $field) {
        $rowSettings[$i][$field] = [];
        if (isset($aggridRowSettings['default']['rowDefault'])) {
          $rowSettings[$i][$field] = array_merge($rowSettings[$i][$field]
            , $aggridRowSettings['default']['rowDefault']);
        }
        if (isset($aggridRowSettings['default'][$field])) {
          $rowSettings[$i][$field] = array_merge($rowSettings[$i][$field]
            , $aggridRowSettings['default'][$field]);
        }
        if (isset($aggridRowSettings[$rowPrefix . $i]['rowDefault'])) {
          $rowSettings[$i][$field] = array_merge($rowSettings[$i][$field]
            , $aggridRowSettings[$rowPrefix . $i]['rowDefault']);
        }
        if (isset($aggridRowSettings[$rowPrefix . $i][$field])) {
          $rowSettings[$i][$field] = array_merge($rowSettings[$i][$field]
            , $aggridRowSettings[$rowPrefix . $i][$field]);
        }
      }
    }

    return $rowSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function createAggridRowData($rowSettings, $headers, $rowData) {
    $table_render = '';
    $spanSkip[][] = 0;
    for ($i = 0; $i < count($rowData); $i++) {
      // Each row... then each cell in each row.
      $table_render .= '<tr>';
      $colCount = -1;
      foreach ($headers as $field) {
        $colCount++;
        // Loop and look for cell data.
        $colSpan = 1;
        $rowSpan = 1;
        $cellClass = "";
        // Check if a spanCount exists for item. If not, create it.
        if (!isset($spanSkip[$i][$colCount])) {
          $spanSkip[$i][$colCount] = 0;
        }

        // If it exists, do it
        if (($spanSkip[$i][$colCount] == 0
            || $spanSkip[$i][$colCount] == '')) {
          // Has data, put it to cell.
          // Get the colspan and rowspan.
          if (isset($rowSettings[$i][$field]['colSpan'])) {
            $colSpan = $rowSettings[$i][$field]['colSpan'];
          }
          if (isset($rowSettings[$i][$field]['rowSpan'])) {
            $rowSpan = $rowSettings[$i][$field]['rowSpan'];
          }
          if ($rowSpan == '' || $rowSpan == NULL) {
            $rowSpan = 1;
          }
          if ($colSpan == '' || $colSpan == NULL) {
            $colSpan = 1;
          }

          // Loop span and set skips.
          for ($si = 0; $si < $rowSpan; $si++) {
            $rowNum = $i+$si;
            for ($sc = 0; $sc < $colSpan; $sc++) {
              $colNum = $colCount + $sc;
              $spanSkip[$rowNum][$colNum] = 1;
            }
          }

          $cellClass = '';

          // Get the class, switch the name from just aggrid to aggrid-html.
          if (isset($rowSettings[$i][$field]['cellClass'])) {
            $cellClass = str_replace('aggrid-', 'aggrid-html', $rowSettings[$i][$field]['cellClass']);
          }

          // Get the class, switch the name from just aggrid to aggrid-html.
          if (isset($rowSettings[$i][$field]['formatType'])
            && $rowSettings[$i][$field]['formatType'] != '') {
            $cellClass = $cellClass . ' aggrid-html-ftype-' . $rowSettings[$i][$field]['formatType'];
          }

          // Check if this cell item is actually a label. If so, define as a row for scope (accessibility).
          if (strpos($cellClass, 'aggrid-htmlcell-label') !== false) {
            $cellScope = 'scope="row"';
          }
          else {
            $cellScope = '';
          }

          // Check if there is data. If not, send blank variable.
          if (isset($rowData[$i]->$field)) {
            $fieldValue = $rowData[$i]->$field;
          }
          else {
            $fieldValue = '';
          }

          // Finally, display the cell.
          $table_render .= '<td '. $cellScope .' rowspan="' . $rowSpan . '" colspan="' . $colSpan . '" class="'. $cellClass .'">' . $fieldValue . '</td>';
        }
        elseif ($spanSkip[$i][$colCount] > 0) {
          // No need to render the cell.
        }
        else {
          // No data, just a blank cell.
          $table_render .= '<td></td>';
        }
      }
      // Close up the row.
      $table_render .= '</tr>';
    }

    return $table_render;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $field_name = $this->fieldDefinition->getName();

      $item_id = Html::getUniqueId("ht-$field_name-$delta");

      $aggridEntity = Entity\Aggrid::load($items[$delta]->aggrid_id);

      if ($aggridEntity == '') {

        $elements[$delta]['container'] = [
          '#plain_text' => $this->t('Missing ag-Grid Config Entity'),
          '#prefix' => '<div class="aggrid-widget-missing">',
          '#suffix' => '</div>',
        ];

      }
      else {

        $aggridDefault = json_decode($aggridEntity->get('aggridDefault'));

        if ($items[$delta]->value == '' || $items[$delta]->value == '{}') {
          $aggridValue = $aggridDefault->rowData;
        }
        else {
          $aggridValue = json_decode($items[$delta]->value);
        }

        $pinnedTopRowData = @json_decode($aggridEntity->get('addOptions'))->pinnedTopRowData;
        $pinnedBottomRowData = @json_decode($aggridEntity->get('addOptions'))->pinnedBottomRowData;

        $aggridRowSettings = @json_decode(json_encode($aggridDefault->rowSettings), true);

        $columns[][][][][] = "";
        $columnFields = "";
        $rowData = $aggridValue;

        // Build table.
        $rowIndex = 0;
        $colIndex = 0;

        foreach ($aggridDefault->columnDefs as $column) {
          $rowIndex = $rowIndex > 1 ? $rowIndex : 1;
          $colIndex++;

          $columns[1][$colIndex][$colIndex][$column->headerName] = [];
          $columns[1][$colIndex][$colIndex][$column->headerName]['headerName'] = $column->headerName;
          $columns[1][$colIndex][$colIndex][$column->headerName]['field'] = isset($column->field) ? $column->field : NULL;

          // Set the field if available.
          if (isset($column->field)) {
            $columnFields .= $column->field . ",";
          }

          // If children, then dive down for headers, otherwise establish column.
          if (isset($column->children)) {
            $columns[1][$colIndex][$colIndex][$column->headerName]['colspan'] = count($column->children);
            $count2 = 0;
            foreach ($column->children as $child) {
              $rowIndex = $rowIndex > 2 ? $rowIndex : 2;
              $count2++;

              $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName] = [];
              $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName]['headerName'] = $child->headerName;
              $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName]['field'] = isset($child->field) ? $child->field : NULL;

              // Set the field if available.
              if (isset($child->field)) {
                $columnFields .= $child->field . ",";
              }

              if (isset($child->children)) {
                $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $column->headerName]['colspan'] = count($child->children);
                $count3 = 0;
                foreach ($child->children as $subchild) {
                  $rowIndex = $rowIndex > 3 ? $rowIndex : 3;
                  $count3++;

                  $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName] = [];
                  $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName]['headerName'] = $subchild->headerName;
                  $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName]['field'] = isset($subchild->field) ? $subchild->field : NULL;
                  $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName]['colspan'] = 1;

                  // Set the field if available.
                  if (isset($subchild->field)) {
                    $columnFields .= $subchild->field . ",";
                  }
                }
              }
              else {
                // Just one for colspan.
                $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName]['colspan'] = 1;
              }
            }
          }
          else {
            // Just one for colspan.
            $columns[1][$colIndex][$colIndex][$column->headerName]['colspan'] = 1;
          }
        }

        // Put columnFields to headers, trim comma, and put to array.
        $headers = $columnFields;
        $headers = substr($headers, 0, strlen($headers) - 1);
        $headers = str_getcsv($headers);

        // Build table.
        $table_render = '';
        $table_render .= '<table id="' . $item_id . '-table" class="aggrid-html-widget"><thead>';

        $count = 0;

        // Get the header rows.
        for ($y = 1; $y <= $rowIndex; $y++) {
          // Each header row and each column cell with spanning.
          $table_render .= '<tr>';
          for ($x = 1; $x <= $colIndex; $x++) {
            if (!array_key_exists($x, $columns[$y])) {
              $table_render .= '<th id="' . $x . '"></th>';
            }
            else {
              foreach ($columns[$y][$x] as $count => $value) {
                foreach ($columns[$y][$x][$count] as $column => $value) {
                  $table_render .= '<th scope="col" id="' .
                    $x .
                    '"  colspan="' .
                    $columns[$y][$x][$count][$column]['colspan'] .
                    '">' .
                    $columns[$y][$x][$count][$column]['headerName'] .
                    '</th>';
                }
              }
            }
          }
          $table_render .= '</tr>';
        }
        // Close up the headers and start on data rows.
        $table_render .= '</thead><tbody>';

        // Pinned Top Row Settings.
        $pinnedTopRowSettings[][][] = "";
        $pinnedTopRowSettings = $this->getRowSettings($aggridRowSettings, $headers, $pinnedTopRowData, 't-');

        // Pinned Top Rows.
        $table_render .= $this->createAggridRowData($pinnedTopRowSettings, $headers, $pinnedTopRowData);

        // (Data) Row Settings.
        $rowSettings[][][] = "";
        $rowSettings = $this->getRowSettings($aggridRowSettings, $headers, $rowData, '');

        // Data rows.
        $table_render .= $this->createAggridRowData($rowSettings, $headers, $rowData);

        // Pinned Bottom Row Settings.
        $pinnedBottomRowSettings[][][] = "";
        $pinnedBottomRowSettings = $this->getRowSettings($aggridRowSettings, $headers, $pinnedBottomRowData, 'b-');

        // Pinned Bottom Rows.
        $table_render .= $this->createAggridRowData($pinnedBottomRowSettings, $headers, $pinnedBottomRowData);

        // Close up the table.
        $table_render .= '</tbody></table>';

        $elements[$delta]['container'] = [
          '#title' => $this->fieldDefinition->label(),
          '#description' => $this->fieldDefinition->getDescription(),
          '#suffix' => $table_render,
          '#attached' => [
            'library' => [
              'aggrid/widget',
            ],
          ],
        ];


        /*
         * Putting this code to the side for now. They're currently working on multiple headers
         * for the '#type' => 'table'
         *
         *

        // Loop through header array and dive down max 3 header rows. Squash all down to single row with only the items with fields.
        // Header 1
        foreach($aggridDefault->columnDefs as $column) {
          if (isset($column->children)) { // If children, then dive down for headers, otherwise establish column
            foreach ($column->children as $child) {
              // Header 2
              if (isset($child->children)) {
                foreach ($child->children as $subchild) {
                  if (isset($subchild->field)) {
                    // Header from row 3
                    $columns[$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName] = [];
                    $columns[$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName]['field'] = $subchild->field;
                  }
                }
              } else {
                if (isset($child->field)) {
                  // Header from row 2
                  $columns[$column->headerName . ' - ' . $child->headerName] = [];
                  $columns[$column->headerName . ' - ' . $child->headerName]['field'] = $child->field;
                }
              }
            }
          } else {
            if (isset($column->field)) {
              // Header from row 1
              $columns[$column->headerName] = [];
              $columns[$column->headerName]['field'] = $column->field;
            }
          }
        }

        // Headers
        foreach($columns as $column => $value) {
          array_push($headers, $column);
        }

        // Row Data
        for ($i = 0; $i < count($rowData); $i++) {
          foreach($columns as $column => $value) {
            $colField = $columns[$column]['field'];
            $tabledata[$i][$columns[$column]['field']] = [
              'data' => $rowData[$i]->$colField,
              'class' => ['row_' . $columns[$column]['field'], 'col_' . $columns[$column]['field']],
            ];
          }
        }

        $elements[$delta]['tablefield'] = [
          '#type' => 'table',
          '#headers' => $headers,
          '#rows' => $tabledata,
          '#attributes' => [
            'id' => [$item_id . '-table'],
            'class' => ['aggrid-html-widget'],
          ],
          '#prefix' => '<div id="tablefield-wrapper-' . $delta . '" class="tablefield-wrapper">',
          '#suffix' => '</div>',
        ];
        */

      }

    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    /*
     * The text value has no text format assigned to it, so the user input
     * should equal the output, including newlines.
     */
    return nl2br(Html::escape($item->value));
  }

}
