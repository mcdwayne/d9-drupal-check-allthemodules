<?php

namespace Drupal\chart_js_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of table.
 *
 * @FieldType(
 *   id = "chart_js_field_type",
 *   label = @Translation("Chart.js"),
 *   description = @Translation("Stores data for the Chart.js field type."),
 *   category = @Translation("General"),
 *   default_formatter = "chart_js_field_formatter",
 *   default_widget = "chart_js_field_widget",
 * )
 */
class ChartJsFieldType extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'type' => [
          'type' => 'text',
          'size' => 'small',
          'not null' => FALSE,
        ],
        'data' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
        'options' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $isEmpty = TRUE;
    $data = $this->get('data')->getString();

    if (!empty($data)){
      $dataObj = json_decode($data);
      if (!empty($dataObj->datasets)){
        foreach ($dataObj->datasets as $dataset) {
          if (!empty($dataset->data)){
            $isEmpty = FALSE;
          }
        }
      }
    }

    return $isEmpty;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('The type of chart the data should be displayed as.'));
    $properties['data'] = DataDefinition::create('string')
      ->setLabel(t('The data that should be displayed.'));
    $properties['options'] = DataDefinition::create('string')
      ->setLabel(t('The options that go with the chart display.'));

    return $properties;
  }

}
