<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

/**
 * Gets all Product Option Fields from BigCommerce API.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_option_field"
 * )
 */
class ProductOptionField extends ProductOption {

  /**
   * {@inheritdoc}
   */
  public function getYield(array $params) {
    $total_pages = 1;
    $options = [];
    $fields = [];
    while ($params['page'] < $total_pages) {
      $params['page']++;

      $response = $this->getSourceResponse($params);
      foreach ($response->getData() as $option) {
        $option_fields = $this->getOptionFields($option->getType());
        $option_name = $option->getName();

        // If no fields are required, skip this option.
        if (empty($option_fields) || in_array($option_name, $options, TRUE)) {
          continue;
        }

        foreach ($option_fields as $field) {
          // If this is the field storage, make sure hasn't already been
          // created.
          if ($this->configuration['import_type'] === 'storage' && in_array($field['field_name'], $fields, TRUE)) {
            continue;
          }

          $fields[] = $field['field_name'];
          $field['attribute_name'] = $option_name;
          yield $field;
        }

        $options[] = $option_name;
      }

      if ($params['page'] === 1) {
        $total_pages = $response->getMeta()->getPagination()->getTotalPages();
      }
    }
  }

}
