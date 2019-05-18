<?php

namespace Drupal\developer_suite;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\developer_suite\Collection\ViolationCollectionInterface;
use Drupal\developer_suite\Command\CommandInterface;
use Drupal\developer_suite\Command\CommandManagerInterface;
use Exception;

/**
 * Class CommandBus.
 *
 * @package Drupal\developer_suite
 */
class CommandBus implements CommandBusInterface {

  use StringTranslationTrait;

  /**
   * The log channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * The command manager.
   *
   * @var \Drupal\developer_suite\Command\CommandManagerInterface
   */
  private $commandManager;

  /**
   * The command.
   *
   * @var \Drupal\developer_suite\Command\CommandInterface
   */
  private $command;

  /**
   * The command handler.
   *
   * @var \Drupal\developer_suite\Command\CommandHandlerInterface
   */
  private $commandHandler;

  /**
   * The command handler manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $commandHandlerManager;

  /**
   * CommandBus constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   * @param \Drupal\developer_suite\Command\CommandManagerInterface $commandManager
   *   The command manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $commandHandlerManager
   *   The command handler manager.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, TranslationInterface $stringTranslation, CommandManagerInterface $commandManager, PluginManagerInterface $commandHandlerManager) {
    $this->logger = $logger;
    $this->stringTranslation = $stringTranslation;
    $this->commandManager = $commandManager;
    $this->commandHandlerManager = $commandHandlerManager;
  }

  /**
   * Resolves the command to a handler and handles the command handler.
   *
   * @param \Drupal\developer_suite\Command\CommandInterface $command
   *   The command.
   */
  public function execute(CommandInterface $command) {
    // Get the pre validation validators.
    $preViolations = $this->commandManager->preValidate($command);

    // Resolve the command to the command handler class.
    $commandHandler = $this->resolveHandler($command);

    // If the command handler is not NULL continue processing.
    if ($commandHandler) {
      // Attach the command and the command handler to the command bus.
      $this->command = $command;
      $this->commandHandler = $commandHandler;

      // If there are no violations execute the command.
      if ($preViolations->count() === 0) {
        // Handle the command.
        $result = $this->handleCommand();

        // Run post validation checks.
        $postViolations = $this->commandManager->postValidate($result, $command);

        // If post violations are found invoke the post validation failed
        // command in the command handler.
        if ($postViolations->count() > 0) {
          $this->postValidationFailedCommand($postViolations);
        }
      }
      // Pre violations found, invoke the pre validation failed command in the
      // command handler.
      else {
        $this->preValidationFailedCommand($preViolations);
      }
    }
  }

  /**
   * Resolves the command to a command handler.
   *
   * @param \Drupal\developer_suite\Command\CommandInterface $command
   *   The command.
   *
   * @return bool|\Drupal\developer_suite\Command\CommandHandlerInterface
   *   The command handler or FALSE if no valid handler is found.
   */
  private function resolveHandler(CommandInterface $command) {
    $commandHandlerPlugin = $command->getCommandHandlerPluginId();

    try {
      $pluginDefinition = $this->commandHandlerManager->getDefinition($commandHandlerPlugin);
      $commandHandlerClass = $pluginDefinition['class'];
    }
    catch (Exception $exception) {
      // Set the command handler class to FALSE.
      $commandHandlerClass = FALSE;

      // Log the exception message to the database log.
      $this->log(
        $this->t(
          "An exception was thrown while trying to resolve command '@command' to its handler: '@exceptionMessage'",
          [
            '@command' => get_class($command),
            '@exceptionMessage' => $exception->getMessage(),
          ]
        ), 'error'
      );
    }

    // If the command handler class exists return it.
    if (class_exists($commandHandlerClass)) {
      return new $commandHandlerClass();
    }

    return FALSE;
  }

  /**
   * Logs a message to the database log.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The log message.
   * @param string $severity
   *   The log severity.
   */
  private function log($message, $severity) {
    $this->logger->get('developer_suite')->$severity($message);
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
   * Runs the post validation failed command.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violations
   *   The violations.
   */
  private function postValidationFailedCommand(ViolationCollectionInterface $violations) {
    $this->commandHandler->setCommand($this->command);
    $this->commandHandler->postValidationFailed($violations);
  }

  /**
   * Runs the pre validation failed command.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violations
   *   The violations.
   */
  private function preValidationFailedCommand(ViolationCollectionInterface $violations) {
    $this->commandHandler->setCommand($this->command);
    $this->commandHandler->preValidationFailed($violations);
  }

}
