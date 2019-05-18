<?php

namespace Drupal\aggrid\Plugin\diff\Field;

use Drupal\aggrid\Entity;
use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin to diff text with summary fields.
 *
 * @FieldDiffBuilder(
 *   id = "aggrid_builder",
 *   label = @Translation("ag-Grid Field Diff"),
 *   field_types = {
 *     "aggrid"
 *   },
 * )
 */
class AggridFieldBuilder extends FieldDiffBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = [];
    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      $values = $field_item->getValue();
      // Compare text formats.
      $aggridValue = json_decode($values['value']);

      $aggridEntity = Entity\Aggrid::load($values['aggrid_id']);
      $aggridDefault = json_decode($aggridEntity->get('aggridDefault'));

      $columns = [];
      $rowData = $aggridValue;

      // Loop through header array and dive down max 3 header rows. Squash all
      // down to single row with only the items with fields.
      // Header 1.
      foreach ($aggridDefault->columnDefs as $column) {
        // If children, then dive down for headers, otherwise establish column.
        if (isset($column->children)) {
          foreach ($column->children as $child) {
            // Header 2.
            if (isset($child->children)) {
              foreach ($child->children as $subchild) {
                if (isset($subchild->field)) {
                  // Header from row 3.
                  $columns[$column->headerName . ' - '
                    . $child->headerName . ' - '
                    . $subchild->headerName] = [];
                  $columns[$column->headerName . ' - '
                    . $child->headerName . ' - '
                    . $subchild->headerName]['field'] = $subchild->field;
                }
              }
            }
            else {
              if (isset($child->field)) {
                // Header from row 2.
                $columns[$column->headerName . ' - ' . $child->headerName] = [];
                $columns[$column->headerName . ' - ' . $child->headerName]['field'] = $child->field;
              }
            }
          }
        }
        else {
          if (isset($column->field)) {
            // Header from row 1.
            $columns[$column->headerName] = [];
            $columns[$column->headerName]['field'] = $column->field;
          }
        }
      }

      for ($i = 0; $i < count($rowData); $i++) {
        $result[$field_key][$i] = '[' . $i . '] ';
        foreach ($columns as $column => $value) {
          $colField = $columns[$column]['field'];
          if (property_exists($rowData[$i], $colField)) {
            $result[$field_key][$i] .= '[' . $column . ']: '
              . $rowData[$i]->$colField . '  ';
          }
          else {
            $result[$field_key][$i] .= '[' . $column . ']: ""  ';
          }
        }
      }
    }

    return $result;
  }

}
