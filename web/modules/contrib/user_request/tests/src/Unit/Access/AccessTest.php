<?php

namespace Drupal\Tests\user_request\Unit\Access;

use Drupal\Tests\user_request\Unit\UnitTestCase;

/**
 * Base class for access related tests.
 *
 * @group user_request
 */
abstract class AccessTest extends UnitTestCase {

  /**
   * Permissions returned by account's hasPermission() method.
   *
   * @var array
   */
  protected $permissions;

  public function checkPermission($permission) {
    return !empty($this->permissions[$permission]);
  }

  protected function addPermission($permission) {
    $this->permissions[$permission] = TRUE;
  }

  protected function mockAccount($id = NULL) {
    $account = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $account
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue($id ?: rand()));
    $account
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnCallback([$this, 'checkPermission']));
    return $account;
  }

}
