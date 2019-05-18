<?php

namespace Drupal\Tests\custom_configuration\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests generation of custom_configuration.
 *
 * @group custom_configuration
 */
class CustomConfigurationTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // parent::setup();
  }

  /**
   * Test machine name.
   */
  public function testMachineName() {
    $this->assertEquals('email_id', $this->createMachineName('email id'));
    $this->assertEquals('facebook_key', $this->createMachineName(' facebook key'));
    $this->assertEquals('email_id', $this->createMachineName('EMAIL__ID'));
    $this->assertEquals('email_id', $this->createMachineName('_EMAIL__ID'));
    $this->assertEquals('product_123', $this->createMachineName('product  123 '));
    $this->assertEquals('jeetgmailcom_id', $this->createMachineName('jeet@gmail.com id'));
  }

  /**
   * Create machine name. Replace all characters except alpha & number.
   *
   * @param string $name
   *   Name will check and replace the string.
   *
   * @return string
   *   It will return the machine name.
   */
  public function createMachineName($name) {
    $name = preg_replace('/[^a-zA-Z0-9_ ]/', '', strtolower(trim($name)));
    $name = preg_replace('/\s+/', ' ', $name);
    $name = preg_replace('/[_]+/', '_', $name);
    $name = ltrim($name, '_');
    $name = rtrim($name, '_');
    return preg_replace('/[^a-zA-Z0-9]/', '_', $name);
  }

}
