<?php

namespace Drupal\simple_access;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface SimpleAccessProfileInterface.
 *
 * @package Drupal\simple_access
 */
interface SimpleAccessProfileInterface extends ConfigEntityInterface {

  /**
   * Build list of grants to be as part of the content selection.
   *
   * @param \Drupal\user\UserInterface|null $account
   *   Account of the user to generate access for.
   * @param string $op
   *   Operation which is being queried.
   *
   * @return array
   *   A list of all the grants to be used.
   */
  public function buildGrant(AccountInterface $accoun, $op);

}
