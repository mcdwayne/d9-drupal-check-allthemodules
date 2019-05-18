<?php

namespace Drupal\shopify\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\Views;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("shopify_collections_filter")
 */
class ShopifyCollectionsFilter extends NumericFilter {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "coll.$this->realField";
    $join = Views::pluginManager('join')->createInstance('standard', [
      'table' => 'shopify_product__collections',
      'field' => 'entity_id',
      'left_table' => 'shopify_product',
      'left_field' => 'id',
    ]);
    $this->query->addRelationship('coll', $join, 'shopify_product__collections');

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

}
