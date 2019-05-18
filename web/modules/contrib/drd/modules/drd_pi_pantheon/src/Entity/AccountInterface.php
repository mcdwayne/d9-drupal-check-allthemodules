<?php

namespace Drupal\drd_pi_pantheon\Entity;

/**
 * Provides an interface for defining Account entities.
 */
interface AccountInterface {

  /**
   * Machine token of this account.
   *
   * @return string
   *   Machine token.
   */
  public function getMachineToken();

  /**
   * Set the machine token of this account.
   *
   * @param string $machineToken
   *   Machine token.
   *
   * @return $this
   */
  public function setMachineToken($machineToken);

}
