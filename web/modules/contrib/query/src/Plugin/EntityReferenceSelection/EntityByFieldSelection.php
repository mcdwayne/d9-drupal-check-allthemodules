<?php

namespace Drupal\query\Plugin\EntityReferenceSelection;

use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;

/**
 * Helps build queries for entities.
 * @see https://hamrant.com/post/entity-autocomplete-customization
 *
 * @EntityReferenceSelection(
 *   id = "default:entity_by_field",
 *   label = @Translation("Entity by field selection"),
 *   entity_types = {"user", "node"},
 *   group = "default",
 *   weight = 3
 * )
 */
class EntityByFieldSelection extends NodeSelection {
  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $handler_settings = $this->configuration['handler_settings'];
    if (!isset($handler_settings['filter'])) {
      return $query;
    }
    $filter_settings = $handler_settings['filter'];
    foreach ($filter_settings as $field_name => $filter_setting) {
      if (is_array($filter_setting)) {
        $operator = isset($filter_setting['operator']) ? $filter_setting['operator'] : '=';
        $value = isset($filter_setting['value']) ? $filter_setting['value'] : $filter_setting;
      }
      else {
        $operator = '=';
        $value = $filter_setting;
      }
      $query->condition($field_name, $value, $operator);
    }
    return $query;
  }
}
