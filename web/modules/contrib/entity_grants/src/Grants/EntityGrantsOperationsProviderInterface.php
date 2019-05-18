<?php

namespace Drupal\entity_grants\Grants;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Allows entity types to provide operations for grants.
 */
interface EntityGrantsOperationsProviderInterface {

  /**
   * Builds operations for the given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   The operations.
   */
  public function getOperations(EntityTypeInterface $entity_type);

}
