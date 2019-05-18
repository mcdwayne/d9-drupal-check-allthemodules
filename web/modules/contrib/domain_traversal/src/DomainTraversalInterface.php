<?php

namespace Drupal\domain_traversal;

use Drupal\domain\Entity\Domain;
use Drupal\user\UserInterface;

interface DomainTraversalInterface {

  /**
   * Gets the domain ids the user may traverse to.
   *
   * @param \Drupal\user\UserInterface $account
   *
   * @return array
   */
  public function getAccountTraversableDomainIds(UserInterface $account);

  /**
   * Checks if the account can traverse to the domain.
   *
   * @param \Drupal\user\UserInterface $account
   * @param \Drupal\domain\Entity\Domain $domain
   *
   * @return bool
   */
  public function accountMayTraverseDomain(UserInterface $account, Domain $domain);

  /**
   * Checks if the account can traverse all domains.
   *
   * @param \Drupal\user\UserInterface $account
   *
   * @return bool
   */
  public function accountMayTraverseAllDomains(UserInterface $account);

}
