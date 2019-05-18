<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Random;
use Drupal\Tests\aws_cloud\Traits\AwsCloudTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\aws_cloud\Service\GoogleSpreadsheetService;

/**
 * Base Test Case class for AWS cloud.
 */
abstract class AwsCloudTestCase extends BrowserTestBase {

  use AwsCloudTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'cloud', 'aws_cloud',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'minimal';

  protected $random;

  protected $cloudContext;

  protected $cloudRegion;

  protected $latestTemplateVars;

  /**
   * Set up test.
   */
  protected function setUp() {
    parent::setUp();

    if (!$this->random) {
      $this->random = new Random();
    }

    $config = \Drupal::configFactory()->getEditable('aws_cloud.settings');
    $config->set('aws_cloud_test_mode', TRUE)
      ->save();

    $this->initMockInstanceTypes();
    $this->initMockGoogleSpreadsheetService();

    $this->createCloudContext();
    $this->initMockData();

    $perms = $this->getPermissions();
    $perms[] = 'view ' . $this->cloudContext;

    $web_user = $this->drupalCreateUser($perms);
    $this->drupalLogin($web_user);
  }

  /**
   * Get permissions of login user.
   *
   * @return array
   *   permissions of login user.
   */
  abstract protected function getPermissions();

  /**
   * Get mock data.
   *
   * @return array
   *   mock data.
   */
  protected function getMockData() {
    return [];
  }

  /**
   * Get mock data from configuration.
   *
   * @return array
   *   mock data.
   */
  protected function getMockDataFromConfig() {
    $config = \Drupal::configFactory()->getEditable('aws_cloud.settings');
    return json_decode($config->get('aws_cloud_mock_data'), TRUE);
  }

  /**
   * Update mock data in configuration.
   */
  protected function updateMockDataToConfig($mock_data) {
    $config = \Drupal::configFactory()->getEditable('aws_cloud.settings');
    $config->set('aws_cloud_mock_data', json_encode($mock_data))
      ->save();
  }

  /**
   * Create cloud context.
   */
  private function createCloudContext() {
    $random = $this->random;
    $this->cloudContext = $random->name(8);

    $num = 1;
    $data = [
      'cloud_context'      => $this->cloudContext,
      'type'               => 'aws_ec2',
      'label'              => "Amazon EC2 US West ($num) - " . $random->name(8, TRUE),
    ];

    $cloud = entity_create('cloud_config', $data);
    $cloud->field_cloud_type->value = 'amazon_ec2';
    $cloud->field_description->value = "#$num: " . date('Y/m/d H:i:s - D M j G:i:s T Y') . $random->string(64, TRUE);
    $cloud->field_api_version->value = 'latest';
    $cloud->field_region->value = "us-west-$num";
    $cloud->field_api_endpoint_uri->value = "https://ec2.us-west-$num.amazonaws.com";
    $cloud->field_access_key->value = $random->name(20, TRUE);
    $cloud->field_secret_key->value = $random->name(40, TRUE);
    $cloud->field_account_id->value = $random->name(16, TRUE);
    $cloud->field_image_upload_url->value = "https://ec2.us-west-$num.amazonaws.com";
    $cloud->field_x_509_certificate->value = $random->string(255, TRUE);

    // Set the cloud region so it is available.
    $this->cloudRegion = "us-west-$num";

    // Save.
    $cloud->save();
  }

  /**
   * Mock the GoogleSpreadsheetService.
   *
   * The mock up will return an spreadsheet url.
   */
  private function initMockGoogleSpreadsheetService() {
    $mock_spreadsheet_service = $this
      ->getMockBuilder(GoogleSpreadsheetService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $spreadsheet_id = $this->random->name(44, TRUE);
    $spreadsheet_url = "https://docs.google.com/spreadsheets/d/${spreadsheet_id}/edit";
    $mock_spreadsheet_service->expects($this->any())
      ->method('createOrUpdate')
      ->willReturn($spreadsheet_url);

    // Provide a mock service container, for the services our module uses.
    $container = \Drupal::getContainer();
    $container->set('aws_cloud.google_spreadsheet', $mock_spreadsheet_service);
  }

  /**
   * Init mock data.
   */
  private function initMockData() {
    $mock_data = [];
    $this->latestTemplateVars = $this->getMockDataTemplateVars();
    foreach ([__CLASS__, get_class($this)] as $class_name) {
      $content = $this->getMockDataFileContent($class_name, $this->latestTemplateVars);
      if (!empty($content)) {
        $mock_data = array_merge($mock_data, Yaml::decode($content));
      }
    }

    $config = \Drupal::configFactory()->getEditable('aws_cloud.settings');
    $config->set('aws_cloud_mock_data', json_encode($mock_data))
      ->save();
  }

  /**
   * Get the content of mock data file.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The content of mock data file.
   */
  protected function getMockDataFileContent($class_name, $vars, $suffix = '') {
    $path = drupal_realpath(drupal_get_path('module', 'aws_cloud')) . '/tests/mock_data';
    $pos = strpos($class_name, 'aws_cloud') + strlen('aws_cloud');
    $path .= str_replace('\\', '/', substr($class_name, $pos)) . $suffix . '.yml';

    if (!file_exists($path)) {
      return '';
    }

    $twig = \Drupal::service('twig');
    return $twig->renderInline(file_get_contents($path), $vars);
  }

  /**
   * Get variables in mock data template file.
   *
   * @return array
   *   variables in mock data template file.
   */
  protected function getMockDataTemplateVars() {
    return [];
  }

  /**
   * Reload mock data in configuration.
   */
  protected function reloadMockData() {
    $mock_data = $this->getMockDataFromConfig();
    $this->latestTemplateVars = $this->getMockDataTemplateVars();
    $file_content = $this->getMockDataFileContent(get_class($this), $this->latestTemplateVars);
    if (!empty($file_content)) {
      $mock_data = array_merge($mock_data, Yaml::decode($file_content));
    }
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Create random IAM roles.
   *
   * @return array
   *   Random IAM roles.
   */
  protected function createRandomIamRoles() {
    $random = $this->random;
    $iam_roles = [];
    $count = rand(1, 10);
    for ($i = 0; $i < $count; $i++) {
      $arn_num = sprintf('%012s', rand(1, 999999999999));
      $arn_name = $random->name(16, TRUE);
      $name = $random->name(10, TRUE);
      $iam_roles[] = [
        'InstanceProfileName' => $name,
        'Arn' => "arn:aws:iam::$arn_num:instance-profile/$arn_name",
        'Roles' => [
          ['RoleName' => $name],
        ],
      ];

    }

    return $iam_roles;
  }

  /**
   * Update IAM roles to mock data.
   *
   * @param array $iam_roles
   *   The IAM roles.
   */
  protected function updateIamRolesToMockData(array $iam_roles) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['ListInstanceProfiles']['InstanceProfiles'] = $iam_roles;
    $this->updateMockDataToConfig($mock_data);
  }

}
