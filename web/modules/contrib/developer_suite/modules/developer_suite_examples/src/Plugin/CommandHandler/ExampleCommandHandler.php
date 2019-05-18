<?php

namespace Drupal\developer_suite_examples\Plugin\CommandHandler;

use Drupal\developer_suite\Collection\ViolationCollectionInterface;
use Drupal\developer_suite\Command\CommandHandler;

/**
 * Class ExampleCommandHandler.
 *
 * The ID in the CommandHandler annotation should be exactly the same as the
 * return value of the getCommandHandlerPluginId() method in your command
 * handler.
 *
 * @see \Drupal\developer_suite_examples\Command\ExampleCommand
 *
 * @CommandHandler(
 *   id = "example_command_handler",
 *   label = @Translation("Example command handler"),
 * )
 *
 * @package Drupal\developer_suite_examples\Command
 */
class ExampleCommandHandler extends CommandHandler {

  /**
   * Handles the command.
   *
   * @return bool
   *   The command result. The result gets passed as the $value parameter in
   *   your command post validators.
   */
  public function handle() {
    // Retrieve the command class via the getCommand() method.
    /** @var \Drupal\developer_suite_examples\Command\ExampleCommand $command */
    $command = $this->getCommand();
    $command->getSomething();

    // Add your execution logic and return the command result. The command
    // result gets validated by your attached post validators.
    return TRUE;
  }

  /**
   * Invoked if any of the attached post validation handlers fails.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  public function postValidationFailed(ViolationCollectionInterface $violationCollection) {
    // Handle the violations, for example by displaying them to your users.
    foreach ($violationCollection->getViolations() as $violation) {
      drupal_set_message($violation->getMessage(), 'error');
    }
  }

  /**
   * Invoked if any of the attached post validation handlers fails.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  public function preValidationFailed(ViolationCollectionInterface $violationCollection) {
    // Handle the violations, for example by displaying them to your users.
    foreach ($violationCollection->getViolations() as $violation) {
      drupal_set_message($violation->getMessage(), 'warning');
    }
  }

}
