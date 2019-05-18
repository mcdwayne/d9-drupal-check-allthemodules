<?php

namespace Drupal\locker\Commands;

/**
 *
 */
class LockerCommands extends LockerCommandsBase {

  /**
   * Lock your Drupal site.
   *
   * @param $passphrase
   *   Enter your desired passphrase to unlock the site.
   * @param $user
   *   Enter your desired username to unlock the site.
   * @param $pass
   *   Enter your desired password to unlock the site.
   *
   * @usage drush lock u username password
   *   Lock your Drupal site with username = username & password = password.
   * @usage drush lock passphrase
   *   Lock your Drupal site with passphrase = passphrase.
   *
   * @command locker:lock
   * @aliases lock
   */
  public function drush_locker_lock($passphrase = NULL, $user = NULL, $pass = NULL) {
    $this->cliService->drush_locker_lock($passphrase, $user, $pass);
  }

  /**
   * Unlock your Drupal site.
   *
   * @command locker:unlock
   * @usage drush unlock
   * @aliases unlock
   */
  public function drush_locker_unlock() {
    $this->cliService->drush_locker_unlock();
  }

}
