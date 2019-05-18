<?php

namespace Drupal\developer_suite\Command;

use Drupal\developer_suite\Collection\ViolationCollectionInterface;

/**
 * Interface CommandHandlerInterface.
 *
 * @package Drupal\developer_suite\Command
 */
interface CommandHandlerInterface {

  /**
   * Sets the attached command.
   *
   * @param \Drupal\developer_suite\Command\CommandInterface $command
   *   The attached command.
   */
  public function setCommand(CommandInterface $command);

  /**
   * Returns the attached command.
   *
   * @return \Drupal\developer_suite\Command\CommandInterface
   *   The attached command.
   */
  public function getCommand();

  /**
   * Handles the command.
   */
  public function handle();

  /**
   * Invoked if any of the attached post validation handlers fails.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  public function postValidationFailed(ViolationCollectionInterface $violationCollection);

  /**
   * Invoked if any of the attached post validation handlers fails.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  public function preValidationFailed(ViolationCollectionInterface $violationCollection);

}
