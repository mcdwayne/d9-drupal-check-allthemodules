<?php

namespace Drupal\dea;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Discover entities with attached operations related to a user account.
 */
interface GrantDiscoveryInterface {

  /**
   * Retrieve a list of related entities the use should also relate to to
   * execute a certain operation on this entity.
   *
   * @param AccountInterface $subject
   *   The the user account on.
   * @param EntityInterface $target
   *   The operations target entity.
   * @param string $operation
   *   The operation that is about to happen.
   *
   * @return EntityInterface[]
   *   List of entityies that grant operations.
   */
  public function grants(AccountInterface $subject, EntityInterface $target, $operation);
}