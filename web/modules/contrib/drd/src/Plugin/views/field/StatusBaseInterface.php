<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\drd\Entity\BaseInterface;

/**
 * Interface for status fields.
 */
interface StatusBaseInterface {

  /**
   * Get all domains of a remote entity.
   *
   * For a domain entity that is the entity itself, for host and core entities
   * this contains a list of all domains attached to them.
   *
   * @param \Drupal\drd\Entity\BaseInterface $remote
   *   The remote entity.
   *
   * @return \Drupal\drd\Entity\DomainInterface[]
   *   List of domains.
   */
  public function getDomains(BaseInterface $remote);

}
