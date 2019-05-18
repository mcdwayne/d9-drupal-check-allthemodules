<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\commerce_product\Entity\ProductType as CommerceProductType;

/**
 * Gets Commerce 1 commerce_product_type data from database.
 *
 * @MigrateSource(
 *   id = "commerce1_product_display_type",
 *   source_module = "commerce_product"
 * )
 */
class ProductDisplayType extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => t('Product reference field name'),
      'type' => t('Type'),
      'name' => t('Name'),
      'description' => t('Description'),
      'help' => t('Help'),
      'data' => t('Product reference field instance data'),
      'variation_type' => t('Product variation type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    $ids['type']['alias'] = 'nt';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $instance_config = unserialize($row->getSourceProperty('data'));
    $product_variation_type = array_filter($instance_config['settings']['referenceable_types']);

    if (count($product_variation_type) > 1) {
      $product_variation_type = $this->resolveTargetVariationType($row, $product_variation_type);
    }
    else {
      $product_variation_type = reset($product_variation_type);
    }

    $row->setSourceProperty('variation_type', $product_variation_type);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config', 'fc');
    $query->leftJoin('field_config_instance', 'fci', '(fci.field_id = fc.id)');
    $query->leftJoin('node_type', 'nt', '(nt.type = fci.bundle)');
    $query->condition('fc.type', 'commerce_product_reference')
      ->condition('fc.active', 1)
      ->condition('fci.entity_type', 'node')
      ->condition('nt.disabled', 0);
    $query->fields('fc', ['field_name'])
      ->fields('fci', ['data'])
      ->fields('nt', ['type', 'name', 'description', 'help']);
    return $query;
  }

  /**
   * Tries to determine a single target variation type.
   *
   * In 2.x, products can only be mapped to a single product variation type,
   * whereas in 1.x one product display node can be mapped to multiple product
   * types via the product reference field's settings.
   *
   * This function can be overwritten by custom migration classes if you need
   * different logic for determining the target variation type.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row.
   * @param array $product_variation_types
   *   An array of product variation types.
   *
   * @return bool|string
   *   The product variation type matching the product, of FALSE if not found.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function resolveTargetVariationType(Row $row, array $product_variation_types) {
    $product_variation_type = FALSE;

    if (isset($this->configuration['variations']['matching'])) {
      // Try to find a variation type that matches the product type.
      $key = array_search($row->getSourceProperty('type'), $product_variation_types);

      if ($key !== FALSE) {
        $product_variation_type = $product_variation_types[$key];
      }
    }

    if ($product_variation_type === FALSE) {
      // Make sure the default product type exists.
      if (!empty($this->configuration['variations']['default']) && CommerceProductType::load($this->configuration['variations']['default'])) {
        $product_variation_type = $this->configuration['variations']['default'];
      }
      else {
        $product_type = $row->getSourceProperty('type');
        throw new MigrateException("A product variation type could not be determined for the product type: $product_type");
      }
    }

    return $product_variation_type;
  }

}
