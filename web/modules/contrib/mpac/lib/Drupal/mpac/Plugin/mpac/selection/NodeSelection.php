<?php

/**
 * @file
 * Contains \Drupal\mpac\Plugin\mpac\selection\NodeSelection.
 */

namespace Drupal\mpac\Plugin\mpac\selection;

use Drupal\mpac\Annotation\MpacSelection;
use Drupal\Core\Annotation\Translation;
use Drupal\mpac\Plugin\mpac\selection\SelectionBase;

/**
 * Provides specific selection functions for nodes.
 *
 * @MpacSelection(
 *   id = "node",
 *   module = "mpac",
 *   label = @Translation("Node selection"),
 *   types = {"*"},
 *   group = "default",
 *   weight = 10
 * )
 */
class NodeSelection extends SelectionBase {

  public function countMatchingItems($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->buildEntityQuery($match, $match_operator);
    return $query
      ->count()
      ->execute();
  }

  public function getMatchingItems($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if (!isset($match)) {
      return array();
    }

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return array();
    }

    $matches = array();
    // Load entites.
    $entities = entity_load_multiple('node', $result);
    foreach ($entities as $entity_id => $entity) {
      $matches["node/{$entity_id}"] = check_plain($entity->label());
    }

    return $matches;
  }

  /**
   * Builds an EntityQuery to get matching nodes.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions applied to it.
   */
  private function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $target_type = 'node';
    $entity_info = entity_get_info($target_type);

    $query = \Drupal::entityQuery($target_type);

    if (isset($match) && isset($entity_info['entity_keys']['label'])) {
      $query->condition($entity_info['entity_keys']['label'], $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag('node_access');

    // Adding the 'node_access' tag is sadly insufficient for nodes: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'. We need to do that as long as there are no access control
    // modules in use on the site. As long as one access control module is there,
    // it is supposed to handle this check.
    if (!user_access('bypass node access') && !count(module_implements('node_grants'))) {
      $query->condition('status', NODE_PUBLISHED);
    }

    return $query;
  }

}
