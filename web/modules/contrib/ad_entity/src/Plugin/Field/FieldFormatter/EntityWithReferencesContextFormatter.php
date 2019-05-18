<?php

namespace Drupal\ad_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Plugin implementation of the 'ad_entity_reference_context' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_with_references_context",
 *   label = @Translation("Context from entity with references"),
 *   field_types = {
 *     "ad_entity_context"
 *   }
 * )
 */
class EntityWithReferencesContextFormatter extends ContextFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $aggregated_items = [$items];
    foreach ($items->getEntity()->referencedEntities() as $reference) {
      if ($reference instanceof FieldableEntityInterface) {
        $field_definitions = $reference->getFieldDefinitions();
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $definition */
        foreach ($field_definitions as $definition) {
          if ($definition->getType() == 'ad_entity_context') {
            $this->renderer->addCacheableDependency($element, $reference);
            $field_name = $definition->getName();
            $items_from_reference = $reference->get($field_name);
            $aggregated_items[] = $items_from_reference;
          }
        }
      }
    }

    foreach ($aggregated_items as $to_include) {
      $element = array_merge($element, $this->includeForAppliance($to_include));
    }

    return $element;
  }

}
