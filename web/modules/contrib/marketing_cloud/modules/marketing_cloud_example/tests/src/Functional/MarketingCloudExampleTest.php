<?php

namespace Drupal\Tests\marketing_cloud_example\Functional;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;

/**
 * Tests the base marketing_cloud_example module.
 *
 * @group marketing_cloud
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MarketingCloudExampleTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'webform',
    'webform_ui',
    'marketing_cloud',
    'marketing_cloud_sms',
    'marketing_cloud_example',
  ];

  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->adminUser = $this->drupalCreateUser([
      'administer_marketing_cloud',
      'administer modules',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the module base functionality.
   */
  public function testModuleFunctionality() {
    /*
     * Test that the module can uninstall if the webform no longer exists.
     */
    try {
      \Drupal::entityTypeManager()
        ->getStorage('webform')
        ->load('marketing_cloud_example')
        ->delete();
    }
    catch (InvalidPluginDefinitionException $e) {
      \Drupal::logger(__FUNCTION__)->info('an error occurred trying to delete the marketing cloud example webform: %message', ['%message' => $e->getMessage()]);
    }
    catch (PluginNotFoundException $e) {
      \Drupal::logger(__FUNCTION__)->info('an error occurred trying to delete the marketing cloud example webform: %message', ['%message' => $e->getMessage()]);
    }
    catch (EntityStorageException $e) {
      \Drupal::logger(__FUNCTION__)->info('an error occurred trying to delete the marketing cloud example webform: %message', ['%message' => $e->getMessage()]);
    }
    // Uninstall marketing_cloud_example.
    \Drupal::service('module_installer')
      ->uninstall(['marketing_cloud_example']);
    \Drupal::service('module_installer')
      ->install(['marketing_cloud_example']);
  }

}
