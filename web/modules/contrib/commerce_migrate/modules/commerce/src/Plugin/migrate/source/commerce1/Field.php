<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\field\Plugin\migrate\source\d7\Field as CoreField;

/**
 * The field source class.
 *
 * Gets all the fields taking into account that nodes can be product displays.
 *
 * @MigrateSource(
 *   id = "commerce1_field",
 *   source_module = "field_sql_storage"
 * )
 */
class Field extends CoreField {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = $this->prepareQuery()->execute()->fetchAll();

    // Gets a list of product display node types.
    $query = $this->select('commerce_product_type', 'pt')
      ->fields('pt', ['type']);
    $product_node_types = $query->execute()->fetchCol();

    $new_rows = [];
    foreach ($results as &$result) {
      $result['commerce1_entity_type'] = $result['entity_type'];
      if ($result['entity_type'] === 'node') {
        $instances = $this->select('field_config_instance', 'fci')
          ->fields('fci')
          ->condition('field_name', $result['field_name'])
          ->condition('entity_type', $result['entity_type'])
          ->execute()
          ->fetchAll();
        $i = 0;
        foreach ($instances as $instance) {
          if (in_array($instance['bundle'], $product_node_types)) {
            $i++;
          }
        }
        if ($i > 0) {
          if ($i == count($instances)) {
            // If all bundles for this field are product types, then set the
            // commerce1_entity_type to 'product_display'. This is used in the
            // CommerceFieldEntityType process plugin to determine the
            // destination entity type.
            $result['commerce1_entity_type'] = 'product_display';
          }
          else {
            // This field, such as the body field and title_field, is used on
            // both nodes and product displays. Add a new row setting the source
            // entity_type to 'product_display'. This is used in the
            // CommerceFieldEntityType process plugin to determine the
            // destination entity type.
            $new_row = $result;
            $new_row['commerce1_entity_type'] = 'product_display';
            $new_row['entity_type'] = 'product_display';
            $new_rows[] = $new_row;
          }
        }
      }
    }

    foreach ($new_rows as $new_row) {
      array_push($results, $new_row);
    }
    return new \ArrayIterator($results);
  }

}
