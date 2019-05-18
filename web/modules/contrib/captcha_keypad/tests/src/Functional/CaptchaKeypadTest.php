<?php

namespace Drupal\Tests\captcha_keypad\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Captcha Keypad tests.
 *
 * @group captcha_keypad
 *
 * Class CaptchaKeypadTest
 * @package Drupal\Tests\captcha_keypad\Functional
 */
class CaptchaKeypadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['captcha_keypad', 'node'];


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

  }
  /**
   * Basic test setup.
   */
  public function testExample() {

  }

}
