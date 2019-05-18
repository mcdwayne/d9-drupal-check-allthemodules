<?php

namespace Drupal\Tests\marketing_cloud_address\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the base marketing_cloud_address module.
 *
 * @group marketing_cloud
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MarketingCloudAddressTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['marketing_cloud', 'marketing_cloud_address'];

  protected $adminUser;
  protected $service;
  protected $moduleConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->adminUser = $this->drupalCreateUser(['administer_marketing_cloud']);
    $this->drupalLogin($this->adminUser);
    // Set module config.
    $this->config('marketing_cloud.settings')
      ->set('client_id', 'testingid')
      ->set('client_secret', 'testingsecret')
      ->set('validate_json', TRUE)
      ->set('do_not_send', TRUE)
      ->save();
    // Create service.
    $this->service = \Drupal::service('marketing_cloud_address.service');
    // Get marketing_cloud_assets config object.
    $this->moduleConfig = \Drupal::config('marketing_cloud_address.settings');
  }

  /**
   * Tests the services and schemas for marketing_cloud_address.
   */
  public function testDefinitions() {
    // Test schema.
    $this->validateDefinition('validate_email');
    // Test validateEmail against expected inputs.
    $data = $this->validateEmailData();
    $result = $this->service
      ->validateEmail($data);
    $this->assertNotFalse($result, 'Valid json failed against the schema in validate_email()');
    $this->assertEquals(
      [
        'url' => 'https://www.exacttargetapis.com/address/v1/validateEmail',
        'data' => json_encode($data),
        'method' => 'post',
      ],
      $result
    );
    // Test validateEmail against invalid extra index.
    $data['foo'] = 'bar';
    $result = $this->service
      ->validateEmail($data);
    $this->assertFalse($result, 'Failed to detect invalid json against the schema in validate_email()');
    // Test validateEmail against invalid type.
    $data['validators'] = 'foobar';
    $result = $this->service
      ->validateEmail($data);
    $this->assertFalse($result, 'Failed to detect invalid json against the schema in validate_email()');
  }

  /**
   * Test that the Json-Schema is valid, and that the API method id correct.
   *
   * @param string $machineName
   *   The machine name for the api call definition.
   */
  protected function validateDefinition($machineName) {
    // Validate schema.
    $schema = $this->moduleConfig->get("definitions.$machineName.schema");
    $this->assertNotEmpty($schema, "json schema for $machineName definition is empty.");
    $schema = json_decode($schema);
    $this->assertNotEmpty($schema, "json schema for $machineName definition is invalid json.");
  }

  /**
   * JSON payload for the validateEmail() service call.
   *
   * @return array
   *   Sample JSON payload data for tests.
   */
  private function validateEmailData() {
    return [
      'email' => 'help@example.com',
      'validators' => [
        'SyntaxValidator',
        'MXValidator',
        'ListDetectiveValidator',
      ],
    ];
  }

}
