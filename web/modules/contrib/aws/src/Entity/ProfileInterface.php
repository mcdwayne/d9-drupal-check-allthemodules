<?php

namespace Drupal\aws\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Profile entity.
 */
interface ProfileInterface extends ConfigEntityInterface {

  /**
   * Returns the arguments required to instantiate an AWS service client.
   *
   * @return array
   *   The client arguments expected to be passed to a class implementing AWs\WHATEVER.
   */
  public function getClientArgs();

}
