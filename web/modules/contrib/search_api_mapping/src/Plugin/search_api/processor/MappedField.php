<?php

namespace Drupal\search_api_mapping\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_mapping\Plugin\search_api\processor\Property\MappedFieldProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\Utility;

/**
 * Adds customized mapping of existing fields to the index.
 *
 * @see \Drupal\search_api_mapping\Plugin\search_api\processor\Property\MappedFieldProperty
 *
 * @SearchApiProcessor(
 *   id = "mapped_field",
 *   label = @Translation("Mapped field"),
 *   description = @Translation("Add customized mapping of existing field to the index."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class MappedField extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Mapped field'),
        'description' => $this->t('An mapping of field values to fixed properties.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];

      $properties['mapped_field'] = new MappedFieldProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $this->index->getFields();
    $mapped_fields = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'mapped_field');
    $required_properties_by_datasource = [
      NULL => [],
      $item->getDatasourceId() => [],
    ];

    foreach ($mapped_fields as $field) {
      $configuration = $field->getConfiguration();
      list($datasource_id, $property_path) = Utility::splitCombinedId($configuration['field']);
      $required_properties_by_datasource[$datasource_id][$property_path] = $configuration['field'];
    }

    $property_values = $this->getFieldsHelper()->extractItemValues([$item], $required_properties_by_datasource)[0];

    $mapped_fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'mapped_field');
    foreach ($mapped_fields as $mapped_field) {
      $configuration = $mapped_field->getConfiguration();
      $field_values = $property_values[$configuration['field']];

      if (!is_array($field_values)) {
        $field_values = [$field_values];
      }

      $map_array = [];
      if (!empty($configuration['mapping'])) {
        $mapping = explode(PHP_EOL, $configuration['mapping']);
        foreach ($mapping as $map) {
          $tmp = explode('|', $map);
          $map_array[trim($tmp[0])] = trim($tmp[1]);
        }
      }

      $values = [];
      foreach ($field_values as $field_value) {
        if (!empty($map_array) && isset($map_array[$field_value])) {
          $values[] = $map_array[$field_value];
        }
        elseif (strlen($configuration['with_value']) && !empty($field_value)) {
          $values[] = $configuration['with_value'];
        }
        elseif (strlen($configuration['without_value']) && empty($field_value)) {
          $values[] = $configuration['without_value'];
        }
      }

      if (strlen($configuration['without_value']) && empty($field_values)) {
        $values[] = $configuration['without_value'];
      }

      if (!empty($values)) {
        $mapped_field->addValue($values);
        continue;
      }
      $mapped_field->addValue($field_values);
    }
  }

}

