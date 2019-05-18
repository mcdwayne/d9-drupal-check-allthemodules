<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Gets all Product Options from BigCommerce API.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_option_value"
 * )
 */
class ProductOptionValue extends ProductOption {

  /**
   * {@inheritdoc}
   */
  protected $trackChanges = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getYield(array $params) {
    $total_pages = 1;
    $values = [];
    while ($params['page'] < $total_pages) {
      $params['page']++;

      $response = $this->getSourceResponse($params);
      foreach ($response->getData() as $option) {
        foreach ($option->getOptionValues() as $value) {
          $data = $value->get();
          if (!isset($values[$option->getName()][$data['id']])) {
            $data['attribute_name'] = $option->getName();
            $data['attribute_type'] = $option->getType();
            $values[$option->getName()][$data['id']] = $data['id'];
            yield $data;
          }
        }
      }

      if ($params['page'] === 1) {
        $total_pages = $response->getMeta()->getPagination()->getTotalPages();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $migration = $this->migration;
    $process = $migration->get('process');

    // Clear fields process so we do not map fields which aren't available.
    foreach (array_keys($process) as $field_name) {
      if (strpos($field_name, "field_product_attribute") !== FALSE) {
        unset($process[$field_name]);
      }
    }

    // Look up if this row has any fields to fill out.
    $fields = $this->getOptionFields($row->getSourceProperty('attribute_type'));
    if (!empty($fields)) {
      foreach ($fields as $field) {
        if ($row->hasSourceProperty($field['source'])) {
          if (!empty($field['process'])) {
            $process[$field['field_name']] = $field['process'];
          }
          else {
            $process[$field['field_name']] = $field['source'];
          }
        }
      }
      $migration->set('process', $process);
    }
    return parent::prepareRow($row);
  }

}
