<?php

namespace Drupal\inmail\Tests;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\inmail_test\Plugin\inmail\Deliverer\TestDelivererTrait;
use Drupal\inmail_test\Plugin\inmail\Deliverer\TestFetcher;
use Drupal\simpletest\WebTestBase;

/**
 * Tests Inmail sensor plugins.
 *
 * @group inmail
 */
class InmailSensorPluginTest extends WebTestBase {

  use DelivererTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'inmail',
    'inmail_test',
    'monitoring',
    'node'
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests incoming mails sensor.
   */
  public function testIncomingMailsSensorPlugin() {
    // Create a user with permissions to create deliverers and run sensors.
    $test_user = $this->drupalCreateUser([
      'monitoring reports',
      'administer monitoring',
      'access administration pages',
      'administer inmail',
    ]);
    $this->drupalLogin($test_user);

    // Add test fetcher.
    $this->drupalGet('admin/config/system/inmail/deliverers/add');
    $edit = [
      'label' => 'Test Test Fetcher',
      'id' => 'test_fetcher',
      'plugin' => 'test_fetcher',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'plugin');
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Check fetcher status to get the unprocessed mails.
    $this->drupalPostForm(NULL, [], 'Check fetcher status');

    // Run the sensor and test the result. Test fetcher has 100 unprocessed
    // messages by default.
    $this->drupalGet('/admin/reports/monitoring/sensors/inmail_incoming_mails');
    $this->drupalPostForm(NULL, [], t('Run again'));
    $this->assertText(t('@unprocessed unprocessed incoming mails', ['@unprocessed' => 100]));

    // Process fetcher.
    $this->drupalGet('admin/config/system/inmail/deliverers');
    $this->drupalPostForm(NULL, [], 'Process fetchers');

    // Run the sensor again and assert result for unprocessed messages.
    $result = monitoring_sensor_run('inmail_incoming_mails', TRUE, TRUE);
    $this->assertEqual($result->getValue(), 99);

    // Set sensor to track processed messages.
    $this->drupalGet('/admin/config/system/monitoring/sensors/inmail_incoming_mails');
    $edit = [
      'settings[count_type]' => 'processed',
      'settings[deliverers][test_fetcher]' => 'test_fetcher',
      'value_label' => 'processed incoming mails',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Run sensor again and assert result for processed messages.
    $result = monitoring_sensor_run('inmail_incoming_mails', TRUE, TRUE);
    $this->assertEqual($result->getValue(), 1);

    // Add a Drush deliverer.
    $this->drupalGet('admin/config/system/inmail/deliverers/add');
    $edit = [
      'label' => 'Test Drush Deliverer',
      'id' => 'test_drush',
      'plugin' => 'drush',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'plugin');
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Edit configuration settings and make sure that deliverers are updating.
    $this->drupalGet('/admin/config/system/monitoring/sensors/inmail_incoming_mails');
    // Assert both deliverers are there for processed messages.
    $this->assertText('Test Test Fetcher');
    $this->assertText('Test Drush Deliverer');
    // Select unprocessed mails to track.
    $this->drupalPostAjaxForm(NULL, ['settings[count_type]' => 'unprocessed'], 'settings[count_type]');
    // Assert that ajax callback has updated deliverers.
    $this->assertNoText('Test Drush Deliverer');
    $this->assertText('Test Test Fetcher');
  }
}
