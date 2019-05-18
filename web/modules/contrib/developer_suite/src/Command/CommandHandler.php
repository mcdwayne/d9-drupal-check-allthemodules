<?php

namespace Drupal\developer_suite\Command;

use Drupal\developer_suite\Collection\ViolationCollectionInterface;

/**
 * Class CommandHandler.
 *
 * @package Drupal\developer_suite\Command
 */
abstract class CommandHandler implements CommandHandlerInterface {

  /**
   * The attached command.
   *
   * @var \Drupal\developer_suite\Command\CommandInterface
   */
  private $command;

  /**
   * Returns the attached command.
   *
   * @return \Drupal\developer_suite\Command\CommandInterface
   *   The attached command.
   */
  public function getCommand() {
    return $this->command;
  }

  /**
   * Sets the attached command.
   *
   * @param \Drupal\developer_suite\Command\CommandInterface $command
   *   The attached command.
   */
  public function setCommand(CommandInterface $command) {
    $this->command = $command;
  }

  /**
   * Handles the command.
   */
  abstract public function handle();

  /**
   * Invoked if any of the attached post validation handlers fails.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  abstract public function postValidationFailed(ViolationCollectionInterface $violationCollection);

  /**
   * Invoked if any of the attached post validation handlers fails.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  abstract public function preValidationFailed(ViolationCollectionInterface $violationCollection);

}
