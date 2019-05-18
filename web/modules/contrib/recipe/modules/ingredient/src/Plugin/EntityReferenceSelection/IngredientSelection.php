<?php

namespace Drupal\ingredient\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the ingredient entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:ingredient",
 *   label = @Translation("Ingredient selection"),
 *   entity_types = {"ingredient"},
 *   group = "default",
 *   weight = 1
 * )
 */
class IngredientSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    return $query;
  }

}
