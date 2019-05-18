<?php

namespace Drupal\business_rules\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the Business Rules item entity type.
 *
 * This plugin is used to allow entity_autocomplete implements filters on
 * Business Rules items.
 *
 * @EntityReferenceSelection(
 *   id = "default:business_rules_item_by_field",
 *   label = @Translation("Business Rules item by field selection"),
 *   entity_types = {"business_rules_item"},
 *   group = "default",
 *   weight = 3
 * )
 */
class BusinessRulesItemSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $query = parent::buildEntityQuery($match, $match_operator);

    // Here is the magic.
    $handler_settings = $this->configuration['handler_settings'];
    if (isset($handler_settings['filter'])) {
      $filter_settings = $handler_settings['filter'];
      foreach ($filter_settings as $field_name => $value) {
        $query->condition($field_name, $value, '=');
      }
    }

    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityManager->getStorage($target_type)
      ->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle                       = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityManager->getTranslationFromContext($entity)
        ->label());
    }

    return $options;
  }

}
