<?php

namespace Drupal\rng_contact\Plugin\EntityReferenceSelection;

use Drupal\rng\Plugin\EntityReferenceSelection\RNGSelectionBase;

/**
 * Provides selection for contact entities when registering.
 *
 * @EntityReferenceSelection(
 *   id = "rng:register:rng_contact",
 *   label = @Translation("Contact selection"),
 *   entity_types = {"rng_contact"},
 *   group = "rng_register",
 *   provider = "rng",
 *   weight = 10
 * )
 */
class RngContactRngSelection extends RNGSelectionBase {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Select contacts owned by the user.
    if ($this->currentUser->isAuthenticated()) {
      $query->condition('owner', $this->currentUser->id(), '=');
    }
    else {
      // Cancel the query.
      $query->condition($this->entityType->getKey('id'), NULL, 'IS NULL');
      return $query;
    }

    return $query;
  }

}
