<?php

namespace Drupal\shopify\Plugin\views\argument;

use Drupal\taxonomy\Plugin\views\argument\Taxonomy;
use Drupal\views\Views;

/**
 * Filter by term id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("shopify_collections_argument")
 */
class ShopifyCollectionsArgument extends Taxonomy {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $join = Views::pluginManager('join')->createInstance('standard', [
      'table' => 'shopify_product__collections',
      'field' => 'entity_id',
      'left_table' => 'shopify_product',
      'left_field' => 'id',
    ]);
    $this->query->addRelationship('coll', $join, 'shopify_product__collections');

    if (!empty($this->options['break_phrase'])) {
      $break = static::breakString($this->argument, TRUE);
      $this->value = $break->value;
      $this->operator = $break->operator;
    }
    else {
      $this->value = [$this->argument];
    }

    $placeholder = $this->placeholder();
    $null_check = empty($this->options['not']) ? '' : "OR coll.$this->realField IS NULL";

    if (count($this->value) > 1) {
      $operator = empty($this->options['not']) ? 'IN' : 'NOT IN';
      $placeholder .= '[]';
      $this->query->addWhereExpression(0, "coll.$this->realField $operator($placeholder) $null_check", [$placeholder => $this->value]);
    }
    else {
      $operator = empty($this->options['not']) ? '=' : '!=';
      $this->query->addWhereExpression(0, "coll.$this->realField $operator $placeholder $null_check", [$placeholder => $this->argument]);
    }
  }

}
