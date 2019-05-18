<?php

namespace Drupal\adva\Plugin\adva;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines access OverridingAccessConsumerInterface.
 *
 * Defines interface for extending upon the AccessConsumer to define the need
 * for overriding the access control handler class for an entity type and to
 * carryout the required modifications to the entity type info.
 *
 * @see adva_entity_type_build().
 */
interface OverridingAccessConsumerInterface extends AccessConsumerInterface {

  /**
   * Modify Entity Type definition to apply access control override.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   Entity type definition.
   */
  public function overrideAccessControlHandler(EntityTypeInterface $entityType);

  /**
   * Rebuild Cached Access Storage records for this entity type.
   */
  public function rebuildCache($batch_mode = FALSE);

  /**
   * Set or get Access Rebuild state.
   *
   * @param bool $rebuild
   *   (Optional) If provided, set the rebuild state.
   *
   * @return bool
   *   Returns the rebuild state. Does not return if $rebuild is provided.
   */
  public function rebuildRequired($rebuild = NULL);

}
