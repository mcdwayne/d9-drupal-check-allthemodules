<?php

namespace Drupal\linky\Plugin\Linkit\Matcher;

use Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher;

/**
 * Provides specific linkit matchers for the linky entity type.
 *
 * @Matcher(
 *   id = "entity:linky",
 *   label = @Translation("Linky"),
 *   target_entity = "linky",
 *   provider = "linky"
 * )
 */
class LinkyMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   *
   * Overridden in order to make the condition an OR on label or uri.
   */
  protected function buildEntityQuery($search_string) {
    $search_string = $this->database->escapeLike($search_string);

    $entity_type = $this->entityTypeManager->getDefinition($this->targetType);
    $query = $this->entityTypeManager->getStorage($this->targetType)->getQuery();
    $label_key = $entity_type->getKey('label');

    $or = $query->orConditionGroup();
    $or->condition($label_key, '%' . $search_string . '%', 'LIKE');
    $or->condition('link__uri', '%' . $search_string . '%', 'LIKE');
    $query->condition($or);

    $query->sort($label_key, 'ASC');

    // Add tags to let other modules alter the query.
    $query->addTag('linkit_entity_autocomplete');
    $query->addTag('linkit_entity_' . $this->targetType . '_autocomplete');

    // Add access tag for the query.
    $query->addTag('entity_access');
    $query->addTag($this->targetType . '_access');

    return $query;
  }

}
