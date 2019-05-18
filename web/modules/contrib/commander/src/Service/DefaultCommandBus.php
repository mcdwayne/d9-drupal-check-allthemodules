<?php

namespace Drupal\commander\Service;

use Drupal\commander\Contracts\CommandBusInterface;
use Drupal\commander\Contracts\CommandInterface;
use Drupal\commander\Plugin\CommandHandlerManager;

/**
 * Class DefaultCommandBus.
 */
class DefaultCommandBus implements CommandBusInterface {

  /**
   * Command handler manager.
   *
   * @var \Drupal\commander\Plugin\CommandHandlerManager
   */
  protected $commandHandlerManager;

  /**
   * DefaultCommandBus constructor.
   *
   * @param \Drupal\commander\Plugin\CommandHandlerManager $commandHandlerManager
   *   Command handler manager.
   */
  public function __construct(CommandHandlerManager $commandHandlerManager) {
    $this->commandHandlerManager = $commandHandlerManager;
  }

  /**
   * Gets the command handler to execute the command.
   *
   * @param \Drupal\commander\Contracts\CommandInterface $command
   *   Command object.
   *
   * @return mixed
   *   Command execution result.
   */
  public function execute(CommandInterface $command) {
    $id = $command->handlerPluginId();

    /** @var \Drupal\commander\Plugin\CommandHandlerInterface $handler */
    $handler = $this->commandHandlerManager->createInstance($id);

    return $handler->execute($command);
  }

}
