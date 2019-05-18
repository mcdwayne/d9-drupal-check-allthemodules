<?php

namespace Drupal\locker\Commands;

use Drush\Commands\DrushCommands;

/**
 *
 */
interface LockerCliServiceInterface {

  /**
   * Set the Drush 9.x command.
   *
   * @param \Drush\Commands\DrushCommands $command
   *   A Drush 9.x command.
   */
  public function setCommand(DrushCommands $command);

  /**
   * Implements hook_drush_command().
   */
  public function locker_drush_command();

  /**
   * Implements drush_hook_COMMAND_lock().
   */
  public function drush_locker_lock($passphrase = NULL, $user = NULL, $pass = NULL);

  /**
   * Implements drush_hook_COMMAND_unlock().
   */
  public function drush_locker_unlock();

}
