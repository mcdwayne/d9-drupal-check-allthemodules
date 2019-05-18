<?php

namespace Drupal\command_bus\Command;

/**
 * Interface CommandInterface.
 *
 * @package Drupal\command_bus\Command
 */
interface CommandInterface {

  /**
   * Returns the pre validators.
   *
   * @return array|\Drupal\command_bus\Validator\ValidatorInterface[]
   *   The pre validators.
   */
  public function getPreValidators();

  /**
   * Returns the post validators.
   *
   * @return array|\Drupal\command_bus\Validator\ValidatorInterface[]
   *   The post validators.
   */
  public function getPostValidators();

}
