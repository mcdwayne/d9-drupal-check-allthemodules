<?php

namespace Drupal\plotly_js\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of Plotly.js Graph.
 *
 * @FieldType(
 *   id = "plotly_js_graph",
 *   label = @Translation("Plotly.js Graph"),
 *   module = "plotly_js",
 *   category = @Translation("Graphs"),
 *   description = @Translation("A graph of data points using one of the Plotly.js graphing methods"),
 *   default_formatter = "plotly_js_graph_formatter",
 *   default_widget = "plotly_js_graph_widget",
 * )
 */
class PlotlyJsGraph extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Columns contains the values that the field will store.
      'columns' => [
        'graph_name' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => TRUE,
        ],
        'number_of_series' => [
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
        ],
        'series_data' => [
          'type' => 'blob',
          'size' => 'big',
          'not null' => TRUE,
        ],
        'layout' => [
          'type' => 'blob',
          'size' => 'big',
          'not null' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['graph_name'] = DataDefinition::create('string')
      ->setLabel(t('Graph Name'))
      ->setDescription(t('The name of this graph'));
    $properties['number_of_series'] = DataDefinition::create('integer')
      ->setLabel(t('Number of Series'))
      ->setDescription(t('The number of series entries this graph contains'));
    $properties['series_data'] = DataDefinition::create('string')
      ->setLabel(t('Series Data'))
      ->setDescription(t('The serialized data for the graph series'));
    $properties['layout'] = DataDefinition::create('string')
      ->setLabel(t('Layout Data'))
      ->setDescription(t('The serialized data for the graph layout'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $graph_name = $this->get('graph_name')->getValue();
    return $graph_name === NULL || $graph_name === '';
  }

}
