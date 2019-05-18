<?php

namespace Drupal\Tests\php_password\Unit\Password;

use Drupal\php_password\Password\PhpPassword;
use Drupal\Tests\UnitTestCase;

/**
 * Class PhpPasswordTest
 *
 * @coversDefaultClass \Drupal\php_password\Password\PhpPassword
 * @group php_password
 */
class PhpPasswordTest extends UnitTestCase {

  /**
   *
   * TODO: Generalize for no bcrypt implementations.
   * @covers ::hash
   * @dataProvider providePasswordOptions
   */
  public function testHash($cost, $algo) {
    $password_implementation = new PhpPassword($cost, $algo);
    $hash = $password_implementation->hash('mock_password');
    // Each hash is unique with a salt so we verify it validates.
    $this->assertTrue(password_verify('mock_password', $hash));
    $info = password_get_info($hash);
    $this->assertEquals($info['algo'], $algo);
    $this->assertEquals($info['options'], [
      'cost' => $cost,
    ]);
  }

  /**
   *
   * TODO: Generalize for no bcrypt implementations.
   * @covers ::hash
   * @covers ::getOptions
   * @dataProvider providePasswordOptions
   */
  public function testHashDefault($cost) {
    $password_implementation = new PhpPassword($cost);
    $hash = $password_implementation->hash('mock_password');
    // Each hash is unique with a salt so we verify it validates.
    $this->assertTrue(password_verify('mock_password', $hash));
    $info = password_get_info($hash);
    $this->assertEquals($info['algo'], PASSWORD_DEFAULT);
    $this->assertEquals($info['options'], [
      'cost' => $cost,
    ]);
  }

  /**
   * @covers ::needsRehash
   */
  public function testNeedsRehash() {
    $this->markTestIncomplete('needs testing.');
  }

  /**
   * @covers ::check
   */
  public function testCheck() {
    $this->markTestIncomplete('needs testing.');
  }

  public function providePasswordOptions() {
    return [
      [5, PASSWORD_DEFAULT,],
      [10, PASSWORD_DEFAULT,],
    ];
  }
}
