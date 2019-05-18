<?php

namespace Drupal\shopify\Plugin\views\argument;

use Drupal\taxonomy\Plugin\views\argument\Taxonomy;
use Drupal\views\Views;

/**
 * Filter by term id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("shopify_tags_argument")
 */
class ShopifyTagsArgument extends Taxonomy {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $join = Views::pluginManager('join')->createInstance('standard', [
      'table' => 'shopify_product__tags',
      'field' => 'entity_id',
      'left_table' => 'shopify_product',
      'left_field' => 'id',
    ]);
    $this->query->addRelationship('tag', $join, 'shopify_product__tags');

    if (!empty($this->options['break_phrase'])) {
      $break = static::breakString($this->argument, TRUE);
      $this->value = $break->value;
      $this->operator = $break->operator;
    }
    else {
      $this->value = [$this->argument];
    }

    $placeholder = $this->placeholder();
    $null_check = empty($this->options['not']) ? '' : "OR tag.$this->realField IS NULL";

    if (count($this->value) > 1) {
      $operator = empty($this->options['not']) ? 'IN' : 'NOT IN';
      $placeholder .= '[]';
      $this->query->addWhereExpression(0, "tag.$this->realField $operator($placeholder) $null_check", [$placeholder => $this->value]);
    }
    else {
      $operator = empty($this->options['not']) ? '=' : '!=';
      $this->query->addWhereExpression(0, "tag.$this->realField $operator $placeholder $null_check", [$placeholder => $this->argument]);
    }
  }

}
