<?php

namespace Drupal\quick_code\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the quick_code entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:quick_code",
 *   label = @Translation("Quick code selection"),
 *   entity_types = {"quick_code"},
 *   group = "default",
 *   weight = 1
 * )
 */
class QuickCodeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $target_type = $this->configuration['target_type'];
    $handler_settings = $this->configuration['handler_settings'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    $query = $this->entityManager->getStorage($target_type)->getQuery();

    // If 'target_bundles' is NULL, all bundles are referenceable, no further
    // conditions are needed.
    if (isset($handler_settings['target_bundles']) && is_array($handler_settings['target_bundles'])) {
      // If 'target_bundles' is an empty array, no bundle is referenceable,
      // force the query to never return anything and bail out early.
      if ($handler_settings['target_bundles'] === []) {
        $query->condition($entity_type->getKey('id'), NULL, '=');
        return $query;
      }
      else {
        $query->condition($entity_type->getKey('bundle'), $handler_settings['target_bundles'], 'IN');
      }
    }

    if (isset($match)) {
      $today = new DrupalDateTime('now', DATETIME_STORAGE_TIMEZONE);
      $today = $today->format(DATETIME_DATE_STORAGE_FORMAT);
      $or1 = $query->orConditionGroup()
        ->condition('effective_dates__value', NULL, 'IS NULL')
        ->condition('effective_dates__value', $today, '<=');
      $or2 = $query->orConditionGroup()
        ->condition('effective_dates__end_value', NULL, 'IS NULL')
        ->condition('effective_dates__end_value', '')
        ->condition('effective_dates__end_value', $today, '>');
      $and = $query->andConditionGroup()
        ->condition($or1)
        ->condition($or2);
      $query->condition($and);

      $or = $query->orConditionGroup()
        ->condition('code', $match, $match_operator)
        ->condition('label', $match, $match_operator)
        ->condition('description', $match, $match_operator)
        ->condition('quick_code', $match, $match_operator);
      $query->condition($or);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if (!empty($handler_settings['sort'])) {
      $sort_settings = $handler_settings['sort'];
      if ($sort_settings['field'] != '_none') {
        $query->sort($sort_settings['field'], $sort_settings['direction']);
      }
    }

    return $query;
  }

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
      // TODO: code-label + description
      $label = $entity->label();
      $type = $entity->type->entity;
      if ($type->getCode()) {
        if (!empty($code = $entity->code->value)) {
          $label = '<span class="code">' . $entity->code->value . '</span> ' . $label;
        }
      }
      if (!empty($description = $entity->description->value)) {
        $label = $label . '<span class="description">' . $description . '</span>';
      }
      $options[$bundle][$entity_id] = $label;
    }

    return $options;
  }

}
