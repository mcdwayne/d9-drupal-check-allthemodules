<?php

namespace Drupal\Tests\dbee\Functional;

/**
 * Enable/uninstall dbee module, Encrypting/Decrypting using batch.
 *
 * When the dbee module is enabled or disabled.
 *
 * @group dbee
 */
class DbeeAllUsersBatchTest extends DbeeAllUsersTest {

  /**
   * Nomber of basic user to create.
   *
   * @var array
   */
  protected $nUsers = 20;

}
