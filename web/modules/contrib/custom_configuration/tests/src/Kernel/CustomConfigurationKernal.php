<?php

namespace Drupal\Tests\custom_configuration\kernel;

use Drupal\custom_configuration\Helper\ConfigurationHelper;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Database\Database;

/**
 * Tests generation of custom_configuration.
 *
 * @group custom_configuration
 */
class CustomConfigurationKernal extends KernelTestBase {

  /**
   * Drupal\custom_configuration\Helper\ConfigurationHelper definition.
   *
   * @var Drupal\custom_configuration\Helper\ConfigurationHelper
   */
  protected $helper;

  /**
   * Initilization.
   */
  public function setUp() {
    parent::setUp();
    $this->helper = new ConfigurationHelper(Database::getConnection());
  }

  /**
   * Test machine name.
   */
  public function testMachineName2() {
    $this->assertEquals('email_id', $this->helper->createMachineName('email id'));
    $this->assertEquals('facebook_key', $this->helper->createMachineName(' facebook key'));
    $this->assertEquals('email_id', $this->helper->createMachineName('EMAIL__ID'));
    $this->assertEquals('email_id', $this->helper->createMachineName('_EMAIL__ID'));
    $this->assertEquals('product_123', $this->helper->createMachineName('product  123 '));
    $this->assertEquals('jeetgmailcom_id', $this->helper->createMachineName('jeet@gmail.com id'));
    $this->assertEquals('website_admin_email_id', $this->helper->createMachineName('website admin email &#$  id '));
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
  public function getMachineName($name) {
    $name = preg_replace('/[^a-zA-Z0-9_ ]/', '', strtolower(trim($name)));
    $name = preg_replace('/\s+/', ' ', $name);
    $name = preg_replace('/[_]+/', '_', $name);
    $name = ltrim($name, '_');
    $name = rtrim($name, '_');
    return preg_replace('/[^a-zA-Z0-9]/', '_', $name);
  }

  /**
   * Provides data for the testSumFunctionWithData method.
   *
   * @return array
   *   $return
   */
  public function dataforSubTesting() {
    return [
      [25, 5, 15, 5],
      [40, 5, 15, 20],
      [NULL, 5, 15, 20, 'abc'],
    ];
  }

}
