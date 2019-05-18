<?php

namespace Drupal\ad_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'tree_aggregation_context' formatter.
 *
 * @FieldFormatter(
 *   id = "tree_aggregation_context",
 *   label = @Translation("Context with tree aggregation"),
 *   field_types = {
 *     "ad_entity_context"
 *   }
 * )
 */
class TreeAggregationContextFormatter extends TaxonomyContextFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    switch ($field_definition->getTargetEntityTypeId()) {
      case 'taxonomy_term':
        return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $term = $items->getEntity();
    $term_id = $term->id();
    $field_name = $items->getFieldDefinition()->get('field_name');
    $aggregated_items = [$items];
    // ::loadAllParents() already includes the term itself.
    $parents = $this->termStorage->loadAllParents($term_id);
    unset($parents[$term_id]);
    foreach ($parents as $parent) {
      $this->renderer->addCacheableDependency($element, $parent);
      $parent_items = $parent->get($field_name);
      if (!$parent_items->isEmpty()) {
        $aggregated_items[] = $parent_items;
      }
    }

    foreach ($aggregated_items as $to_include) {
      $element = array_merge($element, $this->includeForAppliance($to_include));
    }

    return $element;
  }

}
