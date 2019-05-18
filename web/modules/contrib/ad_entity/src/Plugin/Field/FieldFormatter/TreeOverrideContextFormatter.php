<?php

namespace Drupal\ad_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'tree_override_context' formatter.
 *
 * @FieldFormatter(
 *   id = "tree_override_context",
 *   label = @Translation("Context with tree override"),
 *   field_types = {
 *     "ad_entity_context"
 *   }
 * )
 */
class TreeOverrideContextFormatter extends TaxonomyContextFormatterBase {

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
    $aggregated_items = [$items];
    // ::loadAllParents() already includes the term itself.
    foreach ($this->termStorage->loadAllParents($term->id()) as $parent) {
      $this->renderer->addCacheableDependency($element, $parent);
      $this->contextManager->addInvolvedEntity($parent);
    }
    if ($items->isEmpty()) {
      $override_items = $this->getOverrideItems($items);
      if (!$override_items->isEmpty()) {
        $aggregated_items[] = $override_items;
      }
    }

    foreach ($aggregated_items as $to_include) {
      $element = array_merge($element, $this->includeForAppliance($to_include));
    }

    return $element;
  }

}
