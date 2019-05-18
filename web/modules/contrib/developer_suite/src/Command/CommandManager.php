<?php

namespace Drupal\developer_suite\Command;

use Drupal\developer_suite\Collection\ViolationCollectionInterface;

/**
 * Class CommandManager.
 *
 * @package Drupal\developer_suite\Command
 */
class CommandManager implements CommandManagerInterface {

  /**
   * The violation collection.
   *
   * @var \Drupal\developer_suite\Collection\ViolationCollectionInterface
   */
  private $violationCollection;

  /**
   * CommandManager constructor.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violation collection.
   */
  public function __construct(ViolationCollectionInterface $violationCollection) {
    $this->violationCollection = $violationCollection;
  }

  /**
   * Runs pre validation checks on the command.
   *
   * @param \Drupal\developer_suite\Command\CommandInterface $command
   *   The command.
   *
   * @return \Drupal\developer_suite\Collection\ViolationCollectionInterface
   *   The violations.
   */
  public function preValidate(CommandInterface $command) {
    $validators = $command->getPreValidators();

    foreach ($validators as $validator) {
      $validator->validate($command, $this->violationCollection);
    }

    return $this->violationCollection;
  }

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
  public function postValidate($result, CommandInterface $command) {
    $validators = $command->getPostValidators();

    foreach ($validators as $validator) {
      $validator->validate($result, $this->violationCollection);
    }

    return $this->violationCollection;
  }

}
