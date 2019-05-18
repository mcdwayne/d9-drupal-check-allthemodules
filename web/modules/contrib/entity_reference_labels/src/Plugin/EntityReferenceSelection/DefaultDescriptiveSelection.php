<?php

namespace Drupal\entity_reference_labels\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Enhance the entity reference selection with additional details.
 *
 * @EntityReferenceSelection(
 *   id = "default_descriptive",
 *   label = @Translation("Default (Descriptive)"),
 *   group = "default_descriptive",
 *   weight = 5,
 *   deriver = "Drupal\entity_reference_labels\Plugin\Derivative\DefaultDescriptiveSelectionDeriver"
 * )
 */
class DefaultDescriptiveSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */ 
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();

      $translated_entity = $this->entityManager->getTranslationFromContext($entity);
      $options[$bundle][$entity_id] = Html::escape($this->entityManager->getTranslationFromContext($entity)->label() . ' [' . $entity->id() . ']');
    }

    return $options;
  }

}