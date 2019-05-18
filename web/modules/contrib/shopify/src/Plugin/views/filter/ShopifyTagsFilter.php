<?php

namespace Drupal\shopify\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\Views;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("shopify_tags_filter")
 */
class ShopifyTagsFilter extends NumericFilter {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "tag.$this->realField";
    $join = Views::pluginManager('join')->createInstance('standard', [
      'table' => 'shopify_product__tags',
      'field' => 'entity_id',
      'left_table' => 'shopify_product',
      'left_field' => 'id',
    ]);
    $this->query->addRelationship('tag', $join, 'shopify_product__tags');

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

}
