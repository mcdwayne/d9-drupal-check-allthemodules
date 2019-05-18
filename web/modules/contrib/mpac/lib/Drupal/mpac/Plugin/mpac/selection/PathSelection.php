<?php

/**
 * @file
 * Contains \Drupal\mpac\Plugin\mpac\selection\PathSelection.
 */

namespace Drupal\mpac\Plugin\mpac\selection;

use Drupal\mpac\Annotation\MpacSelection;
use Drupal\Core\Annotation\Translation;
use Drupal\mpac\Plugin\mpac\selection\SelectionBase;

/**
 * Provides specific selection functions for nodes.
 *
 * @MpacSelection(
 *   id = "path",
 *   module = "mpac",
 *   label = @Translation("Path selection"),
 *   types = {"path"},
 *   group = "default",
 *   weight = 1
 * )
 */
class PathSelection extends SelectionBase {

  public function countMatchingItems($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->buildQuery($match, $match_operator);
    return $query
      ->count()
      ->execute();
  }

  public function getMatchingItems($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if (!isset($match)) {
      return array();
    }

    $query = $this->buildQuery($match, $match_operator);
    $result = $query
            ->fields('url_alias')
            ->execute();

    if (empty($result)) {
      return array();
    }

    $matches = array();
    foreach ($result as $data) {
      $matches[$data->source] = sprintf('%s *', $data->alias);
    }

    return $matches;
  }

  private function buildQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = db_select('url_alias');
    if (isset($match)) {
      $query->condition('alias', '%' . $match . '%', 'LIKE');
    }
    return $query;
  }

}
