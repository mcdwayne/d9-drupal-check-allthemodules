<?php

namespace Drupal\command_bus;

use Drupal\command_bus\Command\CommandInterface;
use Drupal\command_bus\Command\CommandManagerInterface;
use Drupal\command_bus\Validator\Violations;

/**
 * Class CommandBus.
 *
 * @package Drupal\command_bus
 */
class CommandBus implements CommandBusInterface {

  /**
   * The command manager.
   *
   * @var \Drupal\command_bus\Command\CommandManagerInterface
   */
  private $commandManager;

  /**
   * The command.
   *
   * @var \Drupal\command_bus\Command\CommandInterface
   */
  private $command;

  /**
   * The command handler.
   *
   * @var \Drupal\command_bus\Handler\CommandHandlerInterface
   */
  private $commandHandler;

  /**
   * CommandBus constructor.
   *
   * @param \Drupal\command_bus\Command\CommandManagerInterface $commandManager
   *   The command manager.
   */
  public function __construct(CommandManagerInterface $commandManager) {
    $this->commandManager = $commandManager;
  }

  /**
   * Resolves the command to a handler and handles the command handler.
   *
   * @param \Drupal\command_bus\Command\CommandInterface $command
   *   The command.
   */
  public function execute(CommandInterface $command) {
    // Get the pre validation validators.
    $preViolations = $this->commandManager->preValidate($command);

    // Get the command class and resolve to the command handler class.
    $commandClass = get_class($command);
    $commandHandler = $this->resolve($commandClass);

    // If there are no violations, execute the command.
    if ($preViolations->count() === 0 && $commandHandler) {
      // Attach the command an the command handler to the command bus.
      $this->command = $command;
      $this->commandHandler = $commandHandler;

      // Handles the command.
      $result = $this->handleCommand();

      // Run post validation checks, if violations are found run the rollback.
      $postViolations = $this->commandManager->postValidate($result, $command);
      if ($postViolations->count() > 0) {
        $this->rollbackCommand($postViolations);
      }
    }
  }

  /**
   * Resolves the command to a command handler.
   *
   * @param string $commandClass
   *   The command class string.
   *
   * @return bool|\Drupal\command_bus\Handler\CommandHandlerInterface
   *   The command handler or FALSE if no valid handler is found.
   */
  private function resolve($commandClass) {
    $commandHandlerClass = $commandClass . 'Handler';

    if (class_exists($commandHandlerClass)) {
      return new $commandHandlerClass();
    }

    return FALSE;
  }

  /**
   * Handles the command.
   *
   * @return mixed
   *   The command handle result.
   */
  private function handleCommand() {
    $this->commandHandler->setCommand($this->command);

    return $this->commandHandler->handle();
  }

  /**
   * Runs the rollback command.
   *
   * @param \Drupal\command_bus\Validator\Violations $violations
   *   The violations.
   */
  private function rollbackCommand(Violations $violations) {
    $this->commandHandler->setCommand($this->command);

    $this->commandHandler->rollback($violations);
  }

}
