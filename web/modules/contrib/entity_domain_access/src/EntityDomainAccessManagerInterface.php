<?php

namespace Drupal\entity_domain_access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Checks the access status of entities based on domain settings.
 */
interface EntityDomainAccessManagerInterface extends DomainAccessManagerInterface {

  /**
   * Check that entity has domain access for domains from the list.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param array $domains
   *   Domain ID list.
   *
   * @return bool
   *   Returns TRUE if the entity access field contains the domains from
   *   the list.
   */
  public function checkEntityHasDomains(EntityInterface $entity, array $domains);

  /**
   * Check that entity has domain access for current domain.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   *
   * @return bool
   *   Returns TRUE if the entity access field contains the current domain..
   */
  public function checkEntityHasCurrentDomain(EntityInterface $entity);

}
