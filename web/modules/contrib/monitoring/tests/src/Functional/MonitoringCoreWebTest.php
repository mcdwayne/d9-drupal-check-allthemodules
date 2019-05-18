<?php

namespace Drupal\Tests\monitoring\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Integration tests for the core pieces of monitoring.
 *
 * @group monitoring
 */
class MonitoringCoreWebTest extends MonitoringTestBase {

  public static $modules = array('dblog', 'image', 'node', 'taxonomy', 'file');

  /**
   * Tests individual sensors.
   */
  public function testSensors() {
    $this->doTestUserIntegritySensorPlugin();
    $this->doTestDatabaseAggregatorSensorPluginActiveSessions();
    $this->doTestTwigDebugSensor();
    $this->doTestWatchdogAggregatorSensorPlugin();
    $this->doTestPhpNoticesSensor();
    $this->doTestQueueSizeSensor();
  }

  /**
   * Tests creation of sensor through UI.
   */
  public function doTestQueueSizeSensor() {
    $account = $this->drupalCreateUser(['administer monitoring', 'monitoring reports']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/system/monitoring/sensors/add');
    $this->assertFieldByName('status', TRUE);
    // Test creation of Node entity aggregator sensor.
    $this->drupalPostForm('admin/config/system/monitoring/sensors/add', [
      'label' => 'QueueTest',
      'id' => 'queue_size_test',
      'plugin_id' => 'queue_size',
    ], 'Select sensor');

    $this->assertOption('edit-settings-queue', 'monitoring_test');
    $this->assertOptionByText('edit-settings-queue', 'Test Worker');

    $edit = [
      'settings[queue]' => 'monitoring_test',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Sensor QueueTest saved.');
  }

  /**
   * Tests that requirements sensors are properly updated when installing and
   * uninstalling a module.
   */
  public function testUpdateRequirementsSensors() {
    // Create and login user with permission to view monitoring reports.
    $test_user = $this->drupalCreateUser([
      'monitoring reports',
      'administer monitoring',
    ]);
    $this->drupalLogin($test_user);

    // Assert updates when installing and uninstalling the past module.
    $this->drupalGet('admin/reports/monitoring');
    $this->assertNoRaw('<span title="Requirements of the past module">Module past</span>');
    $this->installModules(['past']);
    $this->drupalGet('admin/reports/monitoring');
    $this->assertRaw('<span title="Requirements of the past module">Module past</span>');
    $this->uninstallModules(['past']);
    $this->drupalGet('admin/reports/monitoring');
    $this->assertNoRaw('<span title="Requirements of the past module">Module past</span>');

    // Assert the rebuild update changes.
    $this->drupalGet('/admin/config/system/monitoring/sensors/rebuild');
    $this->assertText('No changes were made.');

  }

  /**
   * Tests successful user logins through watchdog sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\DatabaseAggregatorSensorPlugin
   */
  protected function doTestWatchdogAggregatorSensorPlugin() {
    // Create and login user with permission to edit sensors and view reports.
    $test_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer monitoring',
      'monitoring reports',
      'access site reports',
      'monitoring verbose',
    ]);
    $this->drupalLogin($test_user);
    // Test output and default message replacement.
    $this->drupalGet('admin/reports/monitoring/sensors/user_successful_logins');

    $rows = $this->getSession()->getPage()->findAll('css', '#unaggregated_result tbody tr');
    $message = $rows[0]->find('css', 'td:nth-child(2)')->getText();
    $this->assertEquals(5, count($rows), 'There are 5 results in the table.');
    $this->assertTrue(!empty($rows[0]->find('css', 'a')->getText()), 'Found WID in verbose output');
    $this->assertEquals("Session opened for {$test_user->getDisplayName()}.", $message, 'Found replaced message in output.');
    $this->assertText('Session opened for ' . $test_user->label());

    // Remove variables from the fields and assert message has no replacements.
    $this->drupalPostForm('admin/config/system/monitoring/sensors/user_successful_logins', ['verbose_fields[variables][field_key]' => ''], t('Save'));
    $this->drupalGet('admin/reports/monitoring/sensors/user_successful_logins');
    $rows = $this->getSession()->getPage()->findAll('css', '#unaggregated_result tbody tr');
    $message = $rows[0]->find('css', 'td:nth-child(2)')->getText();
    $this->assertTrue(!empty($rows[0]->find('css', 'td:nth-child(1)')->getText()), 'Found WID in verbose output');
    $this->assertEquals('Session opened for %name.', $message, 'Found unreplaced message in output.');
  }

  /**
   * Tests active session count through db aggregator sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\DatabaseAggregatorSensorPlugin
   */
  protected function doTestDatabaseAggregatorSensorPluginActiveSessions() {
    // Create and login a user to have data in the sessions table.
    $test_user = $this->drupalCreateUser([
      'monitoring reports',
      'access site reports',
      'monitoring verbose',
    ]);
    $this->drupalLogin($test_user);

    $result = $this->runSensor('user_sessions_authenticated');
    $this->assertEqual($result->getValue(), 1);
    $result = $this->runSensor('user_sessions_all');
    $this->assertEqual($result->getValue(), 1);
    // Logout the user to see if sensors responded to the change.
    $this->drupalLogout();

    $result = $this->runSensor('user_sessions_authenticated');
    $this->assertEqual($result->getValue(), 0);
    $result = $this->runSensor('user_sessions_all');
    $this->assertEqual($result->getValue(), 0);

    // Check verbose output.
    $this->drupalLogin($test_user);
    /** @var User $test_user */
    $test_user = User::load($test_user->id());
    $this->drupalGet('/admin/reports/monitoring/sensors/user_sessions_authenticated');

    $query = "SELECT sessions.uid AS uid, sessions.hostname AS hostname, sessions.timestamp AS timestamp FROM {$this->databasePrefix}sessions sessions WHERE (uid != :db_condition_placeholder_0) AND (timestamp > :db_condition_placeholder_1) ORDER BY timestamp DESC LIMIT 10 OFFSET 0";
    $this->assertSession()->elementTextContains('css', '#unaggregated_result details pre', $query);

    // 3 fields are expected to be displayed.
    $columns = $this->getSession()->getPage()->findAll('css', '#unaggregated_result tbody tr:nth-child(1) td');
    $this->assertTrue(count($columns) == 3, '3 fields have been found in the verbose result.');

    // Test DatabaseAggregator history table result.
    $this->assertSession()->elementTextContains('css', '#history tbody tr:nth-child(1) td:nth-child(2)', '1', 'record_count found in History.');
    // Test the timestamp is shown and formatted correctly.
    $expected_time = \Drupal::service('date.formatter')->format(floor($test_user->getLastLoginTime() / 86400) * 86400, 'short');
    $this->assertSession()->elementTextContains('css', '#history tbody tr:nth-child(1) td:nth-child(1)', $expected_time);

    // The username should be replaced in the message.
    $this->drupalGet('/admin/reports/monitoring/sensors/dblog_event_severity_notice');
    $this->assertText('Session opened for ' . $test_user->label());
    // 'No results' text is displayed when the query has 0 results.
    $this->drupalGet('/admin/reports/monitoring/sensors/dblog_event_severity_warning');
    $this->assertText('There are no results for this sensor to display.');
  }

  /**
   * Tests the twig debug sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\TwigDebugSensorPlugin
   */
  public function doTestTwigDebugSensor() {

    // Ensure that the sensor does not report an error with the default
    // configuration.
    $result = $this->runSensor('twig_debug_mode');
    $this->assertTrue($result->isOk());
    $this->assertEqual($result->getMessage(), 'Optimal configuration');

    $twig_config = $this->container->getParameter('twig.config');
    // Set parameters to the optimal configuration to make sure implicit changes
    // does not trigger any notices and check sensor message.
    $twig_config['debug'] = FALSE;
    $twig_config['cache'] = TRUE;
    $twig_config['auto_reload'] = NULL;
    $this->setContainerParameter('twig.config', $twig_config);
    $this->rebuildContainer();

    $result = $this->runSensor('twig_debug_mode');
    $this->assertTrue($result->isOk());
    $this->assertEqual($result->getMessage(), 'Optimal configuration');

    $twig_config = $this->container->getParameter('twig.config');
    // Change parameters and check sensor message.
    $twig_config['debug'] = TRUE;
    $twig_config['cache'] = FALSE;
    $twig_config['auto_reload'] = TRUE;
    $this->setContainerParameter('twig.config', $twig_config);
    $this->rebuildContainer();

    $result = $this->runSensor('twig_debug_mode');
    $this->assertTrue($result->isWarning());
    $this->assertEqual($result->getMessage(), 'Twig debug mode is enabled, Twig cache disabled, Automatic recompilation of Twig templates enabled');
  }

  /**
   * Tests the user integrity sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\UserIntegritySensorPlugin
   */
  protected function doTestUserIntegritySensorPlugin() {
    $test_user_first = $this->drupalCreateUser(array('administer monitoring'), 'test_user');
    $this->runSensor('user_integrity');

    // Delete the user and run the sensor.
    $test_user_first->delete();
    $result = $this->runSensor('user_integrity');
    $this->assertTrue($result->isOk());

    // Create the user again.
    $test_user_first = $this->drupalCreateUser(array('administer monitoring'), 'test_user_1');
    $this->drupalLogin($test_user_first);

    // Check sensor message after first privilege user creation.
    $result = $this->runSensor('user_integrity');
    $this->assertEqual($result->getMessage(), '1 privileged user(s), 1 new user(s)');

    // Create second privileged user.
    $test_user_second = $this->drupalCreateUser(array(), 'test_user_2', TRUE);
    $this->drupalLogin($test_user_second);
    // Check sensor message after new privilege user creation.
    $result = $this->runSensor('user_integrity');
    $this->assertEqual($result->getMessage(), '2 privileged user(s), 2 new user(s)');

    // Reset the user data, button is tested in UI tests.
    \Drupal::keyValue('monitoring.users')->deleteAll();
    $result = $this->runSensor('user_integrity');
    $this->assertEqual($result->getMessage(), '2 privileged user(s)');

    // Make changes to a user.
    $test_user_second->setUsername('changed');
    $test_user_second->save();
    // Check sensor message for user changes.
    $result = $this->runSensor('user_integrity');
    $this->assertEqual($result->getMessage(), '2 privileged user(s), 1 changed user(s)');

    // Reset the user data again, check sensor message.
    \Drupal::keyValue('monitoring.users')->deleteAll();
    $result = $this->runSensor('user_integrity');
    $this->assertEqual($result->getMessage(), '2 privileged user(s)');

    // Add permissions to authenticated user with no privilege of registration.
    \Drupal::configFactory()->getEditable('user.settings')->set('register', 'admin_only')->save();
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, array('administer account settings'));
    \Drupal::keyValue('monitoring.users')->deleteAll();
    $result = $this->runSensor('user_integrity');
    $this->assertTrue($result->isWarning());

    // Count users included admin.
    $this->assertEqual($result->getMessage(), '3 privileged user(s), Privileged access for roles Authenticated user');

    // Add permissions to anonymous user and check the sensor.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array('administer account settings'));
    $result = $this->runSensor('user_integrity');
    $this->assertEqual($result->getMessage(), '3 privileged user(s), Privileged access for roles Anonymous user, Authenticated user');

    // Authenticated user with privilege of register.
    \Drupal::configFactory()->getEditable('user.settings')->set('register', 'visitors')->save();
    $result = $this->runSensor('user_integrity');
    $this->assertTrue($result->isCritical());
    $this->assertEqual($result->getMessage(), '3 privileged user(s), Privileged access for roles Anonymous user, Authenticated user, Self registration possible.');

    // Create an authenticated user and test that the sensor counter increments.
    $test_user_third = $this->drupalCreateUser(array(), 'test_user_3');
    \Drupal::keyValue('monitoring.users')->deleteAll();
    $result = $this->runSensor('user_integrity');
    $this->assertEqual($result->getMessage(), '4 privileged user(s), Privileged access for roles Anonymous user, Authenticated user, Self registration possible.');

    $test_user_third->setUsername('changed2');
    $test_user_third->save();

    // Check sensor message for user changes.
    $result = $this->runSensor('user_integrity');
    $this->assertEqual($result->getMessage(), '4 privileged user(s), 1 changed user(s), Privileged access for roles Anonymous user, Authenticated user, Self registration possible.');

    // Check sensor message with permissions revoked.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, array('administer account settings'));
    user_role_revoke_permissions(RoleInterface::AUTHENTICATED_ID, array('administer account settings'));
    \Drupal::keyValue('monitoring.users')->deleteAll();
    $result = $this->runSensor('user_integrity');
    $this->assertEqual($result->getMessage(), '2 privileged user(s)');

  }

  /**
   * Tests the user integrity sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\UserIntegritySensorPlugin
   */
  protected function doTestPhpNoticesSensor() {
    $test_user_first = $this->drupalCreateUser(array(
      'administer monitoring',
      'monitoring reports',
      'monitoring verbose',
    ), 'test_user_php');
    $this->drupalLogin($test_user_first);

    // Prepare a fake PHP error.
    $error = [
      '%type' => 'Recoverable fatal error',
      '@message' => 'Argument 1 passed to Drupal\Core\Form\ConfigFormBase::buildForm() must be of the type array, null given, called in /usr/local/var/www/d8/www/core/modules/system/src/Form/CronForm.php on line 127 and defined',
      '%function' => 'Drupal\Core\Form\ConfigFormBase->buildForm()',
      '%line' => '42',
      '%file' => DRUPAL_ROOT . '/core/lib/Drupal/Core/Form/ConfigFormBase.php',
      'severity_level' => 3,
    ];
    // Prepare another fake PHP notice.
    $new_error = [
      '%type' => 'Notice',
      '@message' => 'Use of undefined constant B - assumed \'B\'',
      '%function' => 'Drupal\system\Form\CronForm->buildForm()',
      '%line' => '126',
      '%file' => DRUPAL_ROOT . '/core/modules/system/src/Form/CronForm.php',
      'severity_level' => 5,
    ];
    // Log them.
    \Drupal::logger('php')->log($error['severity_level'], '%type: @message in %function (line %line of %file).', $error);
    \Drupal::logger('php')->log($error['severity_level'], '%type: @message in %function (line %line of %file).', $error);
    \Drupal::logger('php')->log($new_error['severity_level'], '%type: @message in %function (line %line of %file).', $new_error);

    $this->drupalGet('/admin/reports/monitoring/sensors/dblog_php_notices');
    $expected_header = [
      'count',
      'type',
      'message',
      'caller',
      'file',
    ];
    $expected_body_one = [
      '2',
      'Recoverable fatal error',
      'Argument 1 passed to Drupal\Core\Form\ConfigFormBase::buildForm() must be of the type array, null given, called in /usr/local/var/www/d8/www/core/modules/system/src/Form/CronForm.php on line 127 and defined',
      'Drupal\Core\Form\ConfigFormBase->buildForm()',
      'core/lib/Drupal/Core/Form/ConfigFormBase.php:42',
    ];
    $expected_body_two = [
      '1',
      'Notice',
      'Use of undefined constant B - assumed \'B\'',
      'Drupal\system\Form\CronForm->buildForm()',
      'core/modules/system/src/Form/CronForm.php:126',
    ];

    $convert_to_array = function (NodeElement $header) {
      return $header->getText();
    };

    // Check out sensor result page.
    $this->drupalPostForm('/admin/reports/monitoring/sensors/dblog_php_notices', [], t('Run now'));
    $headers = $this->getSession()->getPage()->findAll('css', '#unaggregated_result thead tr th');
    $headers = array_map($convert_to_array, $headers);
    $this->assertEquals($expected_header, $headers, 'The header is correct.');

    $rows = $this->getSession()->getPage()->findAll('css', '#unaggregated_result tbody tr');
    $this->assertEquals(2, count($rows), 'Two PHP notices were logged.');

    $first_message = array_map($convert_to_array, $rows[0]->findAll('css', 'td'));
    $second_message = array_map($convert_to_array, $rows[1]->findAll('css', 'td'));
    $this->assertEqual($first_message, $expected_body_one, 'The first notice is as expected.');
    $this->assertEqual($second_message, $expected_body_two, 'The second notice is as expected');

    // Test Filename shortening.
    $this->assertEqual(str_replace(DRUPAL_ROOT . '/', '', $error['%file'] . ':' . $error['%line']), $first_message[4], 'Filename was successfully shortened.');
  }

  /**
   * Tests the user failed login sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\UserFailedLoginsSensorPlugin
   */
  public function testUserFailedLoginSensorPlugin() {

    // Add a failed attempt for the admin account.
    $this->drupalPostForm('user/login', [
      'name' => 'admin',
      'pass' => '123'
    ], t('Log in'));

    // Check the verbose sensor result.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/reports/monitoring/sensors/user_failed_logins');
    $rows = $this->getSession()->getPage()->findAll('css', '#unaggregated_result tbody tr');

    $this->assertEquals(1, count($rows), 'Found 1 results in table');
    $this->assertSession()->elementTextContains('css', '#unaggregated_result tbody tr:nth-child(1) td:nth-child(2)', 'Login attempt failed for admin');

    // Test the timestamp is formatted correctly.
    $wid = $rows[0]->find('css', 'td:nth-child(1) a')->getText();
    $query = \Drupal::database()->select('watchdog');
    $query->addField('watchdog', 'timestamp');
    $query->condition('wid', $wid);
    $result = $query->range(0, 10)->execute()->fetchObject();
    $expected_time = \Drupal::service('date.formatter')->format($result->timestamp, 'short');
    $this->assertSession()->elementTextContains('css', '#unaggregated_result tbody tr:nth-child(1) td:nth-child(3)', $expected_time);
  }

  /**
   * Tests the non existing user failed login sensor.
   */
  public function testNonExistingUserFailedLoginSensorPlugin() {
    // Insert a failed login event.
    \Drupal::database()->insert('watchdog')->fields(array(
      'type' => 'user',
      'message' => 'Login attempt failed from %ip.',
      'variables' => serialize(['%ip' => '127.0.0.1']),
      'location' => 'http://example.com/user/login',
      'timestamp' => REQUEST_TIME,
    ))->execute();

    // Check the verbose sensor result.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/reports/monitoring/sensors/user_void_failed_logins');

    $rows = $this->getSession()->getPage()->findAll('css', '#unaggregated_result tbody tr');

    $this->assertEquals(1, count($rows), 'Found 1 results in table');
    $this->assertSession()->elementTextContains('css', '#unaggregated_result tbody tr:nth-child(1) td:nth-child(2)', 'Login attempt failed from 127.0.0.1');
  }

  /**
   * Tests for disappearing sensors.
   *
   * We provide a separate test method for the DisappearedSensorsSensorPlugin as we
   * need to install and uninstall additional modules.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\DisappearedSensorsSensorPlugin
   */
  public function testSensorDisappearedSensors() {
    // Install the comment module.
    $this->installModules(array('comment'));

    // Run the disappeared sensor - it should not report any problems.
    $result = $this->runSensor('monitoring_disappeared_sensors');
    $this->assertTrue($result->isOk());

    $log = $this->loadWatchdog();
    $this->assertEqual(count($log), 1, 'There should be one log entry: all sensors enabled by default added.');

    $sensor_config_all = monitoring_sensor_manager()->getAllSensorConfig();
    $this->assertEqual(new FormattableMarkup($log[0]->message, unserialize($log[0]->variables)),
      new FormattableMarkup('@count new sensor/s added: @names', array(
        '@count' => count($sensor_config_all),
        '@names' => implode(', ', array_keys($sensor_config_all))
      )));

    // Uninstall the comment module so that the comment_new sensor goes away.
    $this->uninstallModules(array('comment'));

    // The comment_new sensor has gone away and therefore we should have the
    // critical status.
    $result = $this->runSensor('monitoring_disappeared_sensors');
    $this->assertTrue($result->isCritical());
    $this->assertEqual($result->getMessage(), 'Missing sensor comment_new');
    // There should be no new logs.
    $this->assertEqual(count($this->loadWatchdog()), 1);

    // Install the comment module to test the correct procedure of removing
    // sensors.
    $this->installModules(array('comment'));
    monitoring_sensor_manager()->enableSensor('comment_new');

    // Now we should be back to normal.
    $result = $this->runSensor('monitoring_disappeared_sensors');
    $this->assertTrue($result->isOk());
    $this->assertEqual(count($this->loadWatchdog()), 1);

    // Do the correct procedure to remove a sensor - first disable the sensor
    // and then uninstall the comment module.
    monitoring_sensor_manager()->disableSensor('comment_new');
    $this->uninstallModules(array('comment'));

    // The sensor should not report any problem this time.
    $result = $this->runSensor('monitoring_disappeared_sensors');
    $this->assertTrue($result->isOk());
    $log = $this->loadWatchdog();
    $this->assertEqual(count($log), 2, 'Removal of comment_new sensor should be logged.');
    $this->assertEqual(new FormattableMarkup($log[1]->message, unserialize($log[1]->variables)),
      new FormattableMarkup('@count new sensor/s removed: @names', array(
          '@count' => 1,
          '@names' => 'comment_new'
        )));
  }

  /**
   * Tests enabled modules sensor.
   *
   * We use separate test method as we need to install/uninstall modules.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\EnabledModulesSensorPlugin
   */
  public function testSensorInstalledModulesAPI() {
    // The initial run of the sensor will acknowledge all installed modules as
    // expected and so the status should be OK.
    $result = $this->runSensor('monitoring_installed_modules');
    $this->assertTrue($result->isOk());

    // Install additional module. As the setting "allow_additional" is not
    // enabled by default this should result in sensor escalation to critical.
    $this->installModules(array('contact'));
    $result = $this->runSensor('monitoring_installed_modules');
    $this->assertTrue($result->isCritical());
    $this->assertEqual($result->getMessage(), '1 modules delta, expected 0, Following modules are NOT expected to be installed: Contact (contact)');
    $this->assertEqual($result->getValue(), 1);

    // Allow additional modules and run the sensor - it should not escalate now.
    $sensor_config = SensorConfig::load('monitoring_installed_modules');
    $sensor_config->settings['allow_additional'] = TRUE;
    $sensor_config->save();
    $result = $this->runSensor('monitoring_installed_modules');
    $this->assertTrue($result->isOk());

    // Install comment module to be expected and uninstall the module again.
    // The sensor should escalate to critical.
    $sensor_config->settings['modules']['contact'] = 'contact';
    $sensor_config->save();
    $this->uninstallModules(array('contact'));
    $result = $this->runSensor('monitoring_installed_modules');
    $this->assertTrue($result->isCritical());
    $this->assertEqual($result->getMessage(), '1 modules delta, expected 0, Following modules are expected to be installed: Contact (contact)');
    $this->assertEqual($result->getValue(), 1);
  }


  /**
   * Tests the entity aggregator.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ContentEntityAggregatorSensorPlugin
   */
  public function testEntityAggregator() {
    // Create content types and nodes.
    $type1 = $this->drupalCreateContentType();
    $type2 = $this->drupalCreateContentType();
    $sensor_config = SensorConfig::load('entity_aggregate_test');
    $node1 = $this->drupalCreateNode(array('type' => $type1->id()));
    $node2 = $this->drupalCreateNode(array('type' => $type2->id()));
    $this->drupalCreateNode(array('type' => $type2->id()));
    // One node should not meet the time_interval condition.
    $node = $this->drupalCreateNode(array('type' => $type2->id()));
    \Drupal::database()->update('node_field_data')
      ->fields(array('created' => REQUEST_TIME - ($sensor_config->getTimeIntervalValue() + 10)))
      ->condition('nid', $node->id())
      ->execute();

    // Test for the node type1.
    $sensor_config = SensorConfig::load('entity_aggregate_test');
    $sensor_config->settings['conditions'] = array(
      'test' => array('field' => 'type', 'value' => $type1->id()),
    );
    $sensor_config->save();
    $result = $this->runSensor('entity_aggregate_test');
    $this->assertEqual($result->getValue(), '1');

    // Test for node type2.
    $sensor_config->settings['conditions'] = array(
      'test' => array('field' => 'type', 'value' => $type2->id()),
    );
    $sensor_config->save();
    $result = $this->runSensor('entity_aggregate_test');
    // There should be two nodes with node type2 and created in last 24 hours.
    $this->assertEqual($result->getValue(), 2);

    // Test support for configurable fields, create a taxonomy reference field.
    $vocabulary = $this->createVocabulary();

    entity_create('field_storage_config', array(
      'field_name' => 'term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'entity_types' => array('node'),
      'settings' => array(
        'target_type' => 'taxonomy_term',
      ),
    ))->save();

    entity_create('field_config', array(
      'label' => 'Term reference',
      'field_name' => 'term_reference',
      'entity_type' => 'node',
      'bundle' => $type2->id(),
      'settings' => array('bundles' => [$vocabulary->id() => $vocabulary->id()]),
      'required' => FALSE,
    ))->save();

    entity_create('field_storage_config', array(
      'field_name' => 'term_reference2',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'entity_types' => array('node'),
      'settings' => array(
        'target_type' => 'taxonomy_term',
      ),
    ))->save();

    entity_create('field_config', array(
      'label' => 'Term reference 2',
      'field_name' => 'term_reference2',
      'entity_type' => 'node',
      'bundle' => $type2->id(),
      'settings' => array('bundles' => [$vocabulary->id() => $vocabulary->id()]),
      'required' => FALSE,
    ))->save();

    // Create some terms.
    $term1 = $this->createTerm($vocabulary);
    $term2 = $this->createTerm($vocabulary);

    // Create node that only references the first term.
    $node1 = $this->drupalCreateNode(array(
      'created' => REQUEST_TIME,
      'type' => $type2->id(),
      'term_reference' => array(array('target_id' => $term1->id())),
    ));

    // Create node that only references both terms.
    $node2 = $this->drupalCreateNode(array(
      'created' => REQUEST_TIME,
      'type' => $type2->id(),
      'term_reference' => array(
        array('target_id' => $term1->id()),
        array('target_id' => $term2->id()),
      ),
    ));

    // Create a third node that references both terms but in different fields.
    $node3 = $this->drupalCreateNode(array(
      'created' => REQUEST_TIME,
      'type' => $type2->id(),
      'term_reference' => array(array('target_id' => $term1->id())),
      'term_reference2' => array(array('target_id' => $term2->id())),
    ));

    // Update the sensor to look for nodes with a reference to term1 in the
    // first field.
    $sensor_config->settings['conditions'] = array(
      'test' => array('field' => 'term_reference.target_id', 'value' => $term1->id()),
    );
    $sensor_config->settings['entity_type'] = 'node';
    $sensor_config->save();
    $result = $this->runSensor('entity_aggregate_test');
    // There should be three nodes with that reference.
    $this->assertEqual($result->getValue(), 3);

    // Check the content entity aggregator verbose output and other UI elements.
    $this->drupalLogin($this->createUser(['monitoring reports', 'administer monitoring']));
    $this->drupalPostForm('admin/reports/monitoring/sensors/entity_aggregate_test', [], t('Run now'));
    $this->assertText('id');
    $this->assertText('label');
    $this->assertLink($node1->label());
    $this->assertLink($node2->label());
    $this->assertLink($node3->label());

    // Assert Query result appears.
    $assert_session = $this->assertSession();
    $assert_session->elementTextContains('css', '#result', 'base_table');

    // Check timestamp is formated correctly.
    $timestamp = \Drupal::service('date.formatter')->format($node1->getCreatedTime(), 'short');
    $assert_session->elementTextContains('css', '#result tbody tr:nth-child(2) td:nth-child(3)', $timestamp);

    $this->clickLink(t('Edit'));
    // Assert some of the 'available fields'.
    $this->assertText('Available Fields for entity type Content: changed, created, default_langcode, id, label, langcode, nid, promote, revision_default, revision_log, revision_timestamp, revision_translation_affected, revision_uid, status, sticky, title, type, uid, uuid, vid.');
    $this->assertFieldByName('conditions[0][field]', 'term_reference.target_id');
    $this->assertFieldByName('conditions[0][value]', $term1->id());

    // Test adding another field.
    $this->drupalPostForm(NULL, [
      'settings[verbose_fields][2]' => 'revision_timestamp',
    ] , t('Add another field'));
    // Repeat for a condition, add an invalid field while we are at it.
    $this->drupalPostForm(NULL, [
    'conditions[1][field]' => 'nid',
      'conditions[1][operator]' => '>',
      'conditions[1][value]' => 4,
      // The invalid field.
      'settings[verbose_fields][3]' => 'test_wrong_field',
    ] , t('Add another condition'));

    $this->drupalPostForm(NULL, [], t('Save'));
    $this->clickLink('Entity Aggregate test');

    // Assert the new field and it's formatted output.
    $this->assertText('revision_timestamp');
    $this->assertText(\Drupal::service('date.formatter')->format($node1->getRevisionCreationTime(), 'short'));
    $this->assertText(\Drupal::service('date.formatter')->format($node2->getRevisionCreationTime(), 'short'));
    $this->assertText(\Drupal::service('date.formatter')->format($node3->getRevisionCreationTime(), 'short'));

    // Update the sensor to look for nodes with a reference to term1 in the
    // first field and term2 in the second.
    $sensor_config->settings['conditions'] = array(
      'test' => array('field' => 'term_reference.target_id', 'value' => $term1->id()),
      'test2' => array(
        'field' => 'term_reference2.target_id',
        'value' => $term2->id(),
      ),
    );
    $sensor_config->save();
    $result = $this->runSensor('entity_aggregate_test');
    // There should be one nodes with those references.
    $this->assertEqual($result->getValue(), 1);
  }

  /**
   * Tests the page not found errors.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\Dblog404SensorPlugin
   */
  public function testPageNotFoundErrors() {
    $test_user = $this->drupalCreateUser([
      'administer monitoring',
      'monitoring reports',
      'monitoring verbose',
    ]);
    $this->drupalLogin($test_user);

    $event_time = REQUEST_TIME;

    // Insert three page not found events.
    Database::getConnection('default')->insert('watchdog')->fields([
      'type' => 'page not found',
      'message' => '@uri',
      'variables' => serialize(['%ip' => '127.0.0.1']),
      'location' => 'http://example.com/non_existing_page',
      'timestamp' => $event_time - 10,
    ])->execute();
    Database::getConnection('default')->insert('watchdog')->fields([
      'type' => 'page not found',
      'message' => '@uri',
      'variables' => serialize(['%ip' => '127.0.0.1']),
      'location' => 'http://example.com/non_existing_page',
      'timestamp' => $event_time,
    ])->execute();
    Database::getConnection('default')->insert('watchdog')->fields([
      'type' => 'page not found',
      'message' => '@uri',
      'variables' => serialize(['%ip' => '127.0.0.1']),
      'location' => 'http://example.com/another_non_existing_page',
      'timestamp' => $event_time - 10,
    ])->execute();

    $this->drupalGet('admin/reports/monitoring/sensors/dblog_404');

    $rows = $this->getSession()->getPage()->findAll('css', '#unaggregated_result tbody tr');

    $this->assertEquals(2, count($rows), 'Two rows found.');

    $this->assertEquals('2', $rows[0]->find('css', 'td:nth-child(2)')->getText(), 'Two access to "/non_existing_page"');

    // Test the timestamp is the last one and that is formatted correctly.
    $login_time = $rows[0]->find('css', 'td:nth-child(3)')->getText();
    $expected_time = \Drupal::service('date.formatter')->format($event_time, 'short');
    $this->assertEquals($expected_time, $login_time);
  }
  /**
   * Tests the default used temporary files sensor.
   */
  public function testTemporaryFilesUsages() {

    $test_user = $this->drupalCreateUser([
      'administer site configuration',
      'access site reports',
      'administer monitoring',
      'monitoring reports',
      'monitoring verbose',
      'monitoring force run',
    ]);
    $this->drupalLogin($test_user);

    // Make sure there is no used temporary files.
    $result = $this->runSensor('temporary_files_usages');
    $this->assertEqual($result->getValue(), 0);
    $this->drupalPostForm('admin/reports/monitoring/sensors/temporary_files_usages', [], t('Run now'));
    $this->assertText('0 used temporary files');

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    // Create two nodes.
    $node1 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Example article 1',
    ]);
    $node2 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Example article 2',
    ]);

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');

    // Insert two temporary files which are used by the monitoring_test module.
    $file1 = File::create([
      'fid' => 1,
      'uuid' => 'aa',
      'langcode' => 'en',
      'uid' => 1,
      'filename' => 'example_file_1',
      'uri' => 'public://example_file_1',
      'filemime' => 'example_mime',
      'filesize' => 10,
      'status' => 0,
      'created' => time(),
      'changed' => time(),
    ]);
    $file_usage->add($file1, 'monitoring_test', 'node', $node1->id());
    $file1->setTemporary();
    $file1->save();

    $file2 = File::create([
      'fid' => 2,
      'uuid' => 'bb',
      'langcode' => 'en',
      'uid' => 2,
      'filename' => 'example_file_2',
      'uri' => 'public://example_file_2',
      'filemime' => 'example_mime',
      'filesize' => 10,
      'status' => 0,
      'created' => time(),
      'changed' => time(),
    ]);
    $file_usage->add($file2, 'monitoring_test', 'node', $node2->id());
    $file2->setTemporary();
    $file2->save();

    // Insert one permanent file which is used by the monitoring module.
    $file3 = File::create([
      'fid' => 3,
      'uuid' => 'cc',
      'langcode' => 'en',
      'uid' => 3,
      'filename' => 'example_file_3',
      'uri' => 'public://example_file_3',
      'filemime' => 'example_mime',
      'filesize' => 10,
      'status' => 1,
      'created' => time(),
      'changed' => time(),
    ]);
    $file_usage->add($file3, 'monitoring', 'node', $node1->id());
    $file3->save();

    // Run sensor and make sure there are two temporary files which are used.
    $this->drupalPostForm('admin/reports/monitoring/sensors/temporary_files_usages', [], t('Run now'));
    $result = $this->runSensor('temporary_files_usages');
    $this->assertEqual($result->getValue(), 2);
    $this->assertText('2 used temporary files');
    $this->assertLink('example_file_1');
    $this->assertLink('example_file_2');
    $this->assertLink($node1->label());
    $this->assertLink($node2->label());
    $this->assertLink('Make permanent');

    // Make the first file permanent and assert message.
    $this->clickLink('Make permanent');
    $this->assertText(t('File @file is now permanent.', ['@file' => 'example_file_1']));

    // Make sure that the temporary files are in the list.
    $this->assertText('1 used temporary files');
    $this->assertLink('example_file_2');
    $this->assertLink($node2->label());

    // Make sure that the permanent files are not in the list.
    $this->assertNoLink('example_file_3');
    $this->assertNoLink('example_file_1');
    $this->assertNoLink($node1->label());
  }

  /**
   * Returns a new vocabulary with random properties.
   *
   * @return \Drupal\taxonomy\VocabularyInterface;
   *   Vocabulary object.
   */
  protected function createVocabulary() {
    // Create a vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
    ));
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary where the term will belong to.
   *
   * @return \Drupal\taxonomy\TermInterface;
   *   Term object.
   */
  protected function createTerm($vocabulary) {
    $term = entity_create('taxonomy_term', array('vid' => $vocabulary->id()));
    $term->name = $this->randomMachineName();
    $term->description = $this->randomMachineName();
    $term->save();
    return $term;
  }

  /**
   * Loads watchdog entries by type.
   *
   * @param string $type
   *   Watchdog type.
   *
   * @return array
   *   List of dblog entries.
   */
  protected function loadWatchdog($type = 'monitoring') {
    return \Drupal::database()->query("SELECT * FROM {watchdog} WHERE type = :type", array(':type' => $type))
      ->fetchAll();
  }

}
