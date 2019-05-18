<?php

namespace Drupal\Tests\dbee\Functional;

/**
 * Enable/uninstall dbee module, Encrypting/Decrypting all existing users.
 *
 * When the dbee module is enabled or disabled or when the AES encryption key
 * changes.
 *
 * @group dbee
 */
class DbeeAllUsersTest extends DbeeWebSwitchTestBase {

  /**
   * Enabled and uninstall the dbee module.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAllUsers() {

    // Enable the dbee module.
    $this->dbeeEnablingDisablingDbeeModule(TRUE);
    // Disable the dbee module.
    $this->dbeeEnablingDisablingDbeeModule(FALSE);
    // Enable again dbee module (not the same that install module).
    $this->dbeeEnablingDisablingDbeeModule(TRUE);
  }

}
