<?php

namespace Drupal\svg_icon\Plugin\EntityReferenceSelection;

use Drupal\file\Plugin\EntityReferenceSelection\FileSelection;

/**
 * Provides specific access control for the file entity type.
 *
 * @EntityReferenceSelection(
 *   id = "svg_selection:default",
 *   label = @Translation("SVG Icon Selection"),
 *   entity_types = {"file"},
 *   group = "svg_selection",
 *   weight = 1
 * )
 */
class SvgSelection extends FileSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $query
      ->condition('filename', '%.svg', 'LIKE')
      // @todo, find out why temporary files are created.
      ->condition('status', FILE_STATUS_PERMANENT, '=');
    return $query;
  }

}
