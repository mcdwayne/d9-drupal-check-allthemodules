<?php

namespace Drupal\dea;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for plugins that search for possible solutions to grant a specific
 * user access to a specific entity.
 */
interface SolutionDiscoveryInterface {
  /**
   * Search for possible resolutions of an access problem.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be accessed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account that requests access.
   * @param string $operation
   *   The operation the user wants to execute.
   *
   * @return \Drupal\dea\SolutionInterface[]
   *   An array of possible solutions.
   */
  public function solutions(EntityInterface $entity, AccountInterface $account, $operation);
}