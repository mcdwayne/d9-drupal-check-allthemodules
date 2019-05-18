<?php

namespace Drupal\commander_test\Plugin\CommandHandler;

use Drupal\commander\Plugin\CommandHandlerBase;

/**
 * Class RegisterUserHandler.
 *
 * @CommandHandler(
 *   id = "register_user_handler",
 *   label = @Translation("Register user handler"),
 * )
 */
class RegisterUserHandler extends CommandHandlerBase {

  /**
   * Handled registering the user in a test.
   *
   * @param \Drupal\commander_test\Commands\RegisterUser $command
   *   Command object.
   *
   * @return \Drupal\commander_test\Commands\RegisterUser
   *   Executed command.
   */
  public function execute($command) {
    $command->registered = TRUE;

    return $command;
  }

}
