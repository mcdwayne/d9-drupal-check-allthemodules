<?php

namespace Drupal\bibcite_entity\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the contributor entity type.
 *
 * @todo This is a temporary solution to avoid a bug with "entity_reference_autocomplete" widget.
 *
 * @EntityReferenceSelection(
 *   id = "default:bibcite_keyword",
 *   label = @Translation("Keyword selection"),
 *   entity_types = {"bibcite_keyword"},
 *   group = "default",
 *   weight = 1
 * )
 */
class KeywordSelection extends DefaultSelection {

  /**
   * Builds an EntityQuery to get referenceable entities without bundles.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $target_type = $this->configuration['target_type'];
    $handler_settings = $this->configuration['handler_settings'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    $query = $this->entityManager->getStorage($target_type)->getQuery();

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
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

}
