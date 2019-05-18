<?php

namespace Drupal\Tests\monitoring\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\Tests\monitoring\Functional\MonitoringTestTrait;

/**
 * Tests for the Monitoring UI.
 *
 * @group monitoring
 */
class MonitoringUiJavascriptTest extends JavascriptTestBase {

  use MonitoringTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'monitoring', 'monitoring_test', 'dblog', 'node', 'views', 'file', 'automated_cron'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create the content type page in the setup as it is used by several tests.
    $this->drupalCreateContentType(array('type' => 'page'));

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }


  /**
   * Tests creation of sensor through UI.
   */
  public function testSensorCreation() {
    $account = $this->drupalCreateUser([
      'administer monitoring',
      'monitoring reports',
    ]);
    $this->drupalLogin($account);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Create a node to test verbose fields.
    $node = $this->drupalCreateNode([
      'type' => 'article',
    ]);
    $this->drupalGet('admin/config/system/monitoring/sensors/add');

    $assert_session->checkboxChecked('status');

    // Test creation of Node entity aggregator sensor.
    $page->fillField('Label', 'Node Entity Aggregator sensor');
    // Give the page time to load the machine name suggestion.
    sleep(1);
    $assert_session->pageTextContains('Machine name: node_entity_aggregator_sensor');

    $page->selectFieldOption('Sensor Plugin', 'entity_aggregator');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextContains('Sensor plugin settings');
    $page->pressButton('Add another condition');
    $assert_session->assertWaitOnAjaxRequest();

    $edit = [
      'caching_time' => 100,
      'description' => 'Sensor created to test UI',
      'value_label' => 'Test Value',
      'settings[aggregation][time_interval_field]' => 'created',
      'settings[aggregation][time_interval_value]' => 86400,
      'settings[entity_type]' => 'node',
      'conditions[0][field]' => 'type',
      'conditions[0][value]' => 'article',
      'conditions[1][field]' => 'sticky',
      'conditions[1][value]' => 0,
    ];

    // Available fields for the entity type node.
    $node_fields = ['langcode', 'sticky', 'status', 'uuid', 'created', 'changed', 'uid'];

    // Add verbose fields based on node fields.
    foreach ($node_fields as $i => $field) {
      $page->fillField('settings[verbose_fields][' . ($i + 2) . ']', $field);
      $page->pressButton('Add another field');
      $assert_session->assertWaitOnAjaxRequest();
    }
    $this->drupalPostForm(NULL, $edit, 'Save');

    $assert_session->pageTextContains(new FormattableMarkup('Sensor @label saved.', ['@label' => 'Node Entity Aggregator sensor']));

    // Test details page by clicking the link in confirmation message.
    $this->clickLink('Node Entity Aggregator sensor');
    $assert_session->pageTextContains('Result');
    $assert_session->responseContains('<th>id</th>');
    $assert_session->responseContains('<th>label</th>');
    $assert_session->responseContains('<th>langcode');
    $assert_session->responseContains('<th>status</th>');
    $assert_session->responseContains('<th>sticky</th>');

    // Assert that the output is correct.
    $assert_session->linkExists($node->getTitle());
    $assert_session->linkExists($node->getOwner()->getDisplayName());
    $this->assertFalse($node->isSticky());
    $assert_session->pageTextContains($node->uuid());
    $assert_session->pageTextContains(\Drupal::service('date.formatter')->format($node->getCreatedTime(), 'short'));
    $assert_session->pageTextContains(\Drupal::service('date.formatter')->format($node->getChangedTime(), 'short'));

    $this->drupalGet('admin/config/system/monitoring/sensors/node_entity_aggregator_sensor');
    $this->createScreenshot("/tmp/edit.jpg");
    $assert_session->fieldValueEquals('caching_time', 100);
    $assert_session->fieldValueEquals('conditions[0][field]', 'type');
    $assert_session->fieldValueEquals('conditions[0][value]', 'article');
    $assert_session->fieldValueEquals('conditions[1][field]', 'sticky');
    $assert_session->fieldValueEquals('conditions[1][value]', '0');
    $i = 2;
    foreach ($node_fields as $field) {
      $assert_session->fieldValueEquals('settings[verbose_fields][' . $i++ . ']', $field);
    }

    // Create a file to test.
    $file_path = file_default_scheme() . '://test';
    $contents = "some content here!!.";
    file_put_contents($file_path, $contents);

    // Test if the file exist.
    $this->assertTrue(is_file($file_path));

    // Create a file entity.
    $file = File::create([
      'uri' => $file_path,
      'uid' => 1,
    ]);
    $file->save();

    // Test if the entity was created.
    $this->assertTrue($file->id());

    // Test creation of File entity aggregator sensor.
    $this->drupalGet('admin/config/system/monitoring/sensors/add');
    $page->fillField('Label', 'File Entity Aggregator sensor');
    // Give the page time to load the machine name suggestion.
    sleep(1);
    $assert_session->pageTextContains('Machine name: file_entity_aggregator_sensor');

    $page->selectFieldOption('Sensor Plugin', 'entity_aggregator');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextContains('Sensor plugin settings');
    $page->selectFieldOption('Entity Type', 'file');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add another condition');
    $assert_session->assertWaitOnAjaxRequest();

    // Available fields for entity type file.
    $file_fields = [
      'uuid',
      'filename',
      'uri',
      'filemime',
      'filesize',
      'status',
      'created',
    ];
    $edit = [];

    // Add verbose fields based on file fields.
    foreach ($file_fields as $i => $field) {
      $page->fillField('settings[verbose_fields][' . ($i + 2) . ']', $field);
      $page->pressButton('Add another field');
      $assert_session->assertWaitOnAjaxRequest();
    }
    $this->drupalPostForm(NULL, $edit, 'Save');

    $assert_session->pageTextContains(new FormattableMarkup('Sensor @label saved.', ['@label' => 'File Entity Aggregator sensor']));

    // Test details page by clicking the link in confirmation message.
    $this->clickLink('File Entity Aggregator sensor');
    $assert_session->pageTextContains('Result');
    $assert_session->responseContains('<th>label</th>');
    $assert_session->responseContains('<th>uuid</th>');
    $assert_session->responseContains('<th>filename</th>');
    $assert_session->responseContains('<th>filesize</th>');
    $assert_session->responseContains('<th>uri</th>');
    $assert_session->responseContains('<th>created</th>');

    // Assert that the output is correct.
    $assert_session->pageTextContains($file->getFilename());
    $assert_session->pageTextContains($file->uuid());
    $assert_session->pageTextContains($file->getSize());
    $assert_session->pageTextContains($file->getMimeType());
    $assert_session->pageTextContains(\Drupal::service('date.formatter')
      ->format($file->getCreatedTime(), 'short'));

    $this->drupalGet('admin/config/system/monitoring/sensors/file_entity_aggregator_sensor');
    $i = 2;
    foreach ($file_fields as $field) {
      $assert_session->fieldValueEquals('settings[verbose_fields][' . $i++ . ']', $field);
    }

    $this->drupalGet('admin/config/system/monitoring/sensors/node_entity_aggregator_sensor/delete');
    $assert_session->pageTextContains('This action cannot be undone.');
    $this->drupalPostForm(NULL, [], 'Delete');
    $assert_session->pageTextContains('Node Entity Aggregator sensor has been deleted.');

    // Configuration sensor.
    $this->drupalGet('admin/config/system/monitoring/sensors/add');
    $page->fillField('Label', 'UI created Sensor config');
    // Give the page time to load the machine name suggestion.
    sleep(1);
    $assert_session->pageTextContains('Machine name: ui_created_sensor_config');

    $page->selectFieldOption('Sensor Plugin', 'config_value');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextContains('Expected value');

    $assert_session->pageTextContains('Sensor plugin settings');

    // Test if the expected value type is no_value, the value label is hidden.
    $page->selectFieldOption('Expected value type', 'no_value');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('The value label represents the units of the sensor value.');

    $page->selectFieldOption('Expected value type', 'bool');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('The value label represents the units of the sensor value.');

    $this->drupalPostForm(NULL, [
      'description' => 'Sensor created to test UI',
      'value_label' => 'Test Value',
      'caching_time' => 100,
      'value_type' => 'bool',
      'settings[key]' => 'interval',
      'settings[config]' => 'automated_cron.settings',
    ], 'Save');
    $assert_session->pageTextContains(new FormattableMarkup('Sensor @label saved.', ['@label' => 'UI created Sensor config']));

    // Go back to the sensor edit page,
    // Check the value type is properly selected.
    $this->drupalGet('admin/config/system/monitoring/sensors/ui_created_sensor_config');
    $option = $assert_session->optionExists('Expected value type', 'bool');
    $this->assertTrue($option->hasAttribute('selected'));

    // Update sensor with a config entity.
    $this->drupalPostForm(NULL, [
      'settings[key]' => 'id',
      'settings[config]' => 'views.view.content',
    ], 'Save');

    // Make sure the config dependencies are set.
    $sensor_config = SensorConfig::load('ui_created_sensor_config');
    $dependencies = $sensor_config->get('dependencies');
    $this->assertEquals(['views.view.content'], $dependencies['config']);

    // Try to enable a sensor which is disabled by default and vice versa.
    // Check the default status of cron safe threshold and new users sensors.
    $sensor_cron = SensorConfig::load('core_cron_safe_threshold');
    $this->assertTrue($sensor_cron->status());
    $sensor_theme = SensorConfig::load('core_theme_default');
    $this->assertFalse($sensor_theme->status());

    // Change the status of these sensors.
    $this->drupalPostForm('admin/config/system/monitoring/sensors', [
      'sensors[core_cron_safe_threshold]' => FALSE,
      'sensors[core_theme_default]' => TRUE,
    ], 'Update enabled sensors');

    // Make sure the changes have been made.
    $sensor_cron = SensorConfig::load('core_cron_safe_threshold');
    $this->assertFalse($sensor_cron->status());
    $sensor_theme = SensorConfig::load('core_theme_default');
    $this->assertTrue($sensor_theme->status());

    // Test the creation of a Watchdog sensor with default configuration.
    $this->drupalGet('admin/config/system/monitoring/sensors/add');
    $page->fillField('Label', 'Watchdog sensor');
    // Give the page time to load the machine name suggestion.
    sleep(1);
    $assert_session->pageTextContains('Machine name: watchdog_sensor');

    $page->selectFieldOption('Sensor Plugin', 'watchdog_aggregator');
    $assert_session->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], 'Save');
    $assert_session->pageTextContains('Sensor Watchdog Sensor saved');

    // Edit sensor with invalid fields.
    $this->drupalPostForm('admin/config/system/monitoring/sensors/watchdog_sensor', [
      'conditions[0][field]' => 'condition_invalid',
      'verbose_fields[0][field_key]' => 'verbose_invalid',
    ], 'Save');

    $assert_session->pageTextContains('The field condition_invalid does not exist in the table "watchdog".');
    $assert_session->pageTextContains('The field verbose_invalid does not exist in the table "watchdog".');

    // Load the created sensor and assert the default configuration.
    $sensor_config = SensorConfig::load('watchdog_sensor');
    $settings = $sensor_config->getSettings();
    $this->assertEquals('number', $sensor_config->getValueType());
    $this->assertEquals('watchdog', $settings['table']);
    $this->assertEquals('timestamp', $settings['time_interval_field']);

    // Test that the entity id is set after selecting a watchdog sensor.
    $this->drupalGet('admin/config/system/monitoring/sensors/add');
    $page->fillField('Label', 'Test entity id');
    // Give the page time to load the machine name suggestion.
    sleep(1);
    $assert_session->pageTextContains('Machine name: test_entity_id');

    $page->selectFieldOption('Sensor Plugin', 'watchdog_aggregator');
    $assert_session->assertWaitOnAjaxRequest();

    $page->selectFieldOption('Sensor Plugin', 'entity_aggregator');
    $assert_session->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], 'Save');
    $assert_session->pageTextContains('Sensor Test entity id saved.');

    // Test that the description of the verbose output changes.
    $this->drupalGet('admin/config/system/monitoring/sensors/add');
    $page->fillField('Label', 'Test entity id');
    // Give the page time to load the machine name suggestion.
    sleep(1);
    $assert_session->pageTextContains('Machine name: test_entity_id');

    $page->selectFieldOption('Sensor Plugin', 'entity_aggregator');
    $assert_session->assertWaitOnAjaxRequest();

    // Change entity type to File.
    $page->selectFieldOption('Entity Type', 'file');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Available Fields for entity type File:');
    $assert_session->pageTextContains('changed, created, fid, filemime, filename, filesize, id, label, langcode, status, uid, uri, uuid');

    // Change entity type to User.
    $page->selectFieldOption('Entity Type', 'user');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Available Fields for entity type User:');
    $assert_session->pageTextContains('access, changed, created, default_langcode, id, init, label, langcode, login, mail, name, pass, preferred_admin_langcode, preferred_langcode, roles, status, timezone, uid, uuid');
  }


  /**
   * Tests the UI/settings of the installed modules sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\EnabledModulesSensorPlugin
   */
  public function testSensorInstalledModulesUI() {
    $account = $this->drupalCreateUser(['administer monitoring']);
    $this->drupalLogin($account);

    $page = $this->getSession()->getPage();

    // Visit settings page of the disabled sensor. We run the sensor to check
    // for deltas. This led to fatal errors with a disabled sensor.
    $this->drupalGet('admin/config/system/monitoring/sensors/monitoring_installed_modules');

    // Enable the sensor.
    monitoring_sensor_manager()->enableSensor('monitoring_installed_modules');

    // Test submitting the defaults and enabling the sensor.
    $this->drupalPostForm('admin/config/system/monitoring/sensors/monitoring_installed_modules', [
      'status' => TRUE,
    ], 'Save');
    // Reset the sensor config so that it reflects changes done via POST.
    monitoring_sensor_manager()->resetCache();
    // The sensor should now be OK.
    $result = monitoring_sensor_run('monitoring_installed_modules');
    $this->assertTrue($result->isOk());

    // Expect the contact and book modules to be installed.
    $this->drupalPostForm('admin/config/system/monitoring/sensors/monitoring_installed_modules', [
      'settings[modules][contact]' => TRUE,
      'settings[modules][book]' => TRUE,
    ], 'Save');
    // Reset the sensor config so that it reflects changes done via POST.
    monitoring_sensor_manager()->resetCache();
    // Make sure the extended / hidden_modules form submit cleanup worked and
    // they are not stored as a duplicate in settings.
    $sensor_config = SensorConfig::load('monitoring_installed_modules');
    $this->assertTrue(!array_key_exists('extended', $sensor_config->settings), 'Do not persist extended module hidden selections separately.');
    // The sensor should escalate to CRITICAL.
    $result = $this->runSensor('monitoring_installed_modules');
    $this->assertTrue($result->isCritical());
    $this->assertEquals( '2 modules delta, expected 0, Following modules are expected to be installed: Book (book), Contact (contact)', $result->getMessage());
    $this->assertEquals(2, $result->getValue());

    // Reset modules selection with the update selection (ajax) button.
    $this->drupalGet('admin/config/system/monitoring/sensors/monitoring_installed_modules');
    $page->pressButton('Update module selection');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], 'Save');
    $result = $this->runSensor('monitoring_installed_modules');
    $this->assertTrue($result->isOk());
    $this->assertEquals('0 modules delta', $result->getMessage());

    // The default setting is not to allow additional modules. Enable comment
    // and the sensor should escalate to CRITICAL.
    $this->installModules(['help']);
    $result = $this->runSensor('monitoring_installed_modules');
    $this->assertTrue($result->isCritical());
    $this->assertEquals('1 modules delta, expected 0, Following modules are NOT expected to be installed: Help (help)', $result->getMessage());
    $this->assertEquals(1, $result->getValue());
    // Allow additional, the sensor should not escalate anymore.
    $this->drupalPostForm('admin/config/system/monitoring/sensors/monitoring_installed_modules', [
      'settings[allow_additional]' => 1,
    ], 'Save');
    $result = $this->runSensor('monitoring_installed_modules');
    $this->assertTrue($result->isOk());
    $this->assertEquals( '0 modules delta', $result->getMessage());
  }

}
