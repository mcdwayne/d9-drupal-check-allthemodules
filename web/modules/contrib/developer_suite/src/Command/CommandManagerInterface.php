<?php

namespace Drupal\developer_suite\Command;

/**
 * Interface CommandManager.
 *
 * @package Drupal\developer_suite\Command
 */
interface CommandManagerInterface {

  /**
   * Runs pre validation checks before the command is run.
   *
   * @param \Drupal\developer_suite\Command\CommandInterface $command
   *   The command.
   *
   * @return \Drupal\developer_suite\Collection\ViolationCollectionInterface
   *   The violations.
   */
  public function preValidate(CommandInterface $command);

  /**
   * Runs post validation checks on the command result.
   *
   * @param mixed $result
   *   The command result.
   * @param \Drupal\developer_suite\Command\CommandInterface $command
   *   The command.
   *
   * @return \Drupal\developer_suite\Collection\ViolationCollectionInterface
   *   The violations.
   */
  public function postValidate($result, CommandInterface $command);

}
