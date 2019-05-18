<?php

namespace Drupal\developer_suite\Command;

/**
 * Interface CommandInterface.
 *
 * @package Drupal\developer_suite\Command
 */
interface CommandInterface {

  /**
   * Returns the pre validators.
   *
   * @return array|\Drupal\developer_suite\Validator\BaseValidatorInterface[]
   *   The pre validators.
   */
  public function getPreValidators();

  /**
   * Returns the post validators.
   *
   * @return array|\Drupal\developer_suite\Validator\BaseValidatorInterface[]
   *   The post validators.
   */
  public function getPostValidators();

  /**
   * Returns the command handler plugin ID.
   *
   * @return string
   *   The command handler plugin ID.
   */
  public function getCommandHandlerPluginId();

}
