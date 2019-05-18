<?php

namespace Drupal\Tests\monitoring\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Kernel tests for the core pieces of monitoring.
 *
 * @group monitoring
 */
class MonitoringCoreKernelTest extends MonitoringUnitTestBase {

  public static $modules = [
    'dblog',
    'image',
    'file',
    'node',
    'taxonomy',
    'automated_cron',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('dblog', ['watchdog']);
    $this->installConfig(array('system'));

    \Drupal::moduleHandler()->loadAllIncludes('install');
    monitoring_install();
  }

  /**
   * Tests monitoring_cron.
   */
  public function testCronRunAllEnabledSensors() {
    // Per default cron_run_sensors is false, this should not run any sensors.
    \Drupal::cache('default')->deleteAll();
    monitoring_cron();
    $cid = 'monitoring_sensor_result:test_sensor';
    $cache = \Drupal::cache('default')->get($cid);
    // Checks that the cache is empty when cron is disabled.
    $this->assertTrue(!is_object($cache));
    $sensorConfig = SensorConfig::load('test_sensor');
    $result = \Drupal::service('monitoring.sensor_runner')->runSensors([$sensorConfig]);
    // Since after a sensor ran its result is cached, check if it is not cached.
    $this->assertTrue(!$result[0]->isCached());

    // Allow running all enabled sensors.
    \Drupal::configFactory()
      ->getEditable('monitoring.settings')
      ->set('cron_run_sensors', TRUE)
      ->save();
    \Drupal::cache('default')->deleteAll();
    monitoring_cron();
    $cache = \Drupal::cache('default')->get($cid);
    // Checks that the cache is not empty when cron is enabled.
    $this->assertTrue(is_object($cache));
    $sensorConfig = SensorConfig::load('test_sensor');
    $result = \Drupal::service('monitoring.sensor_runner')->runSensors([$sensorConfig]);
    // Since after a sensor ran its result is cached, check if it is cached.
    $this->assertTrue($result[0]->isCached());
  }

  /**
   * Tests cron last run age sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\CronLastRunAgeSensorPlugin
   */
  public function testCronLastRunAgeSensorPlugin() {
    // Fake cron run 1d+1s ago.
    $time_shift = (60 * 60 * 24 + 1);
    \Drupal::state()->set('system.cron_last', \Drupal::time()->getRequestTime() - $time_shift);
    $result = $this->runSensor('core_cron_last_run_age');
    $this->assertTrue($result->isWarning());
    $this->assertEquals($time_shift, $result->getValue());

    // Fake cron run from 3d+1s ago.
    $time_shift = (60 * 60 * 24 * 3 + 1);
    \Drupal::state()->set('system.cron_last', \Drupal::time()->getRequestTime() - $time_shift);
    $result = $this->runSensor('core_cron_last_run_age');
    $this->assertTrue($result->isCritical());
    $this->assertEquals($time_shift, $result->getValue());

    // Run cron and check sensor.
    \Drupal::service('cron')->run();
    $result = $this->runSensor('core_cron_last_run_age');
    $this->assertTrue($result->isOk());
    $this->assertEquals(0, $result->getValue());
  }

  /**
   * Tests cron safe threshold (poormanscron) sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ConfigValueSensorPlugin
   */
  public function testConfigValueSensorPluginCronSafeThreshold() {
    // Run sensor, all is OK.
    $result = $this->runSensor('core_cron_safe_threshold');
    $this->assertTrue($result->isOk());

    // Enable cron safe threshold and run sensor.
    $this->config('automated_cron.settings')->set('interval', 3600)->save();
    $result = $this->runSensor('core_cron_safe_threshold');
    $this->assertTrue($result->isCritical());
    $this->assertEqual($result->getMessage(), 'TRUE, expected FALSE');
  }

  /**
   * Tests maintenance mode sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\StateValueSensorPlugin
   */
  public function testStateValueSensorPluginMaintenanceMode() {
    // Run sensor, all is OK.
    $result = $this->runSensor('core_maintenance_mode');
    $this->assertTrue($result->isOk());

    // Enable maintenance mode and run sensor.
    \Drupal::state()->set('system.maintenance_mode', TRUE);
    $result = $this->runSensor('core_maintenance_mode');
    $this->assertTrue($result->isCritical());

    // Switch out of maintenance mode to continue regularly.
    \Drupal::state()->set('system.maintenance_mode', FALSE);
    $this->assertEqual($result->getMessage(), 'TRUE, expected FALSE');
  }

  /**
   * Tests queue size sensors.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\QueueSizeSensorPlugin
   */
  public function testQueueSizeSensorPlugin() {

    $this->installSchema('system', ['queue']);

    // Create queue sensor.
    $sensor_config = SensorConfig::create(array(
      'id' => 'core_queue_monitoring_test',
      'plugin_id' => 'queue_size',
      'value_type' => 'number',
      'settings' => [
        'queue' => 'monitoring_test',
      ],
    ));
    $sensor_config->save();

    // Create queue with some items and run sensor.
    $queue = \Drupal::queue('monitoring_test');
    $queue->createItem(array());
    $queue->createItem(array());
    $result = $this->runSensor('core_queue_monitoring_test');
    $this->assertEqual($result->getValue(), 2);
  }

  /**
   * Tests php notices watchdog sensor.
   */
  public function testPhpNoticesSensor() {

    // Prepare a fake PHP error.
    $error = [
      '%type' => 'Recoverable fatal error',
      '@message' => 'Argument 1 passed to Drupal\Core\Form\ConfigFormBase::buildForm() must be of the type array, null given, called in /usr/local/var/www/d8/www/core/modules/system/src/Form/CronForm.php on line 127 and defined',
      '%function' => 'Drupal\Core\Form\ConfigFormBase->buildForm()',
      '%line' => '42',
      '%file' => DRUPAL_ROOT . '/core/lib/Drupal/Core/Form/ConfigFormBase.php',
      'severity_level' => 3,
    ];
    // Log it twice.
    \Drupal::logger('php')->log($error['severity_level'], '%type: @message in %function (Line %line of %file).', $error);
    \Drupal::logger('php')->log($error['severity_level'], '%type: @message in %function (Line %line of %file).', $error);

    $result = $this->runSensor('dblog_php_notices');
    $message = $result->getMessage();
    $error['%file'] = str_replace(DRUPAL_ROOT . '/', '', $error['%file']);
    // Assert the message has been set and replaced successfully.
    $this->assertEqual($message, new FormattableMarkup('2 times: %type: @message in %function (Line %line of %file).', $error));

    // Prepare another fake PHP notice.
    $new_error = [
      '%type' => 'Notice',
      '@message' => 'Use of undefined constant B - assumed \'B\'',
      '%function' => 'Drupal\system\Form\CronForm->buildForm()',
      '%line' => '126',
      '%file' => DRUPAL_ROOT . '/core/modules/system/src/Form/CronForm.php',
      'severity_level' => 5,
    ];
    \Drupal::logger('php')->log($new_error['severity_level'], '%type: @message in %function (Line %line of %file).', $new_error);
    $result = $this->runSensor('dblog_php_notices');
    $message = $result->getMessage();
    // Assert the message is still the one from above and not the new message.
    $this->assertEqual($message, new FormattableMarkup('2 times: %type: @message in %function (Line %line of %file).', $error), 'The sensor message is still the old message.');
    $this->assertNotEqual($message, new FormattableMarkup('%type: @message in %function (Line %line of %file).', $new_error), 'The sensor message is not the new message.');

    // Log the new error twice more, check it is now the sensor message.
    \Drupal::logger('php')->log($new_error['severity_level'], '%type: @message in %function (Line %line of %file).', $new_error);
    \Drupal::logger('php')->log($new_error['severity_level'], '%type: @message in %function (Line %line of %file).', $new_error);
    $result = $this->runSensor('dblog_php_notices');
    $message = $result->getMessage();
    $new_error['%file'] = str_replace(DRUPAL_ROOT . '/', '', $new_error['%file']);
    // Assert the new message is returned as a message.
    $this->assertEqual($message, new FormattableMarkup('3 times: %type: @message in %function (Line %line of %file).', $new_error), 'The new message is now the sensor message.');
    $this->assertNotEqual($message, new FormattableMarkup('2 times: %type: @message in %function (Line %line of %file).', $error), 'The old message is not the sensor message anymore.');
  }

  /**
   * Tests dblog 404 errors sensor.
   *
   * Logged through watchdog.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\Dblog404SensorPlugin
   */
  public function testDblog404SensorPlugin() {
    // Fake some not found errors.
    \Drupal::service('logger.dblog')->log(RfcLogLevel::NOTICE, '@uri', [
      'request_uri' => 'not/found',
      'uid' => 0,
      'channel' => 'page not found',
      'link' => '',
      'referer' => '',
      'ip' => '127.0.0.1',
      'timestamp' => REQUEST_TIME,
    ]);

    // Run sensor and test the output.
    $result = $this->runSensor('dblog_404');
    $this->assertTrue($result->isOk());
    $this->assertEqual($result->getMessage(), '1 watchdog events in 1 day, not/found');
    $this->assertEqual($result->getValue(), 1);

    // Fake more 404s.
    for ($i = 1; $i <= 20; $i++) {
      \Drupal::service('logger.dblog')->log(RfcLogLevel::NOTICE, '@uri', [
        'request_uri' => 'not/found',
        'uid' => 0,
        'channel' => 'page not found',
        'link' => '',
        'referer' => '',
        'ip' => '127.0.0.1',
        'timestamp' => REQUEST_TIME,
      ]);
    }

    // Run sensor and check the aggregate value.
    $result = $this->runSensor('dblog_404');
    $this->assertEqual($result->getValue(), 21);
    $this->assertTrue($result->isWarning());

    // Fake more 404s.
    for ($i = 0; $i <= 100; $i++) {
      \Drupal::service('logger.dblog')->log(RfcLogLevel::NOTICE, '@uri', [
        'request_uri' => 'not/found/another',
        'uid' => 0,
        'channel' => 'page not found',
        'link' => '',
        'referer' => '',
        'ip' => '127.0.0.1',
        'timestamp' => REQUEST_TIME,
      ]);
    }

    // Run sensor and check the aggregate value.
    $result = $this->runSensor('dblog_404');
    $this->assertEqual($result->getValue(), 101);
    $this->assertTrue($result->isCritical());
  }

  /**
   * Tests dblog missing image style sensor.
   *
   * Logged through watchdog.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ImageMissingStyleSensorPlugin
   */
  public function testImageMissingStyleSensorPlugin() {
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('system', ['router']);
    $this->installEntitySchema('file');
    $this->installConfig(['system']);

    // Fake some image style derivative errors.
    $file = file_save_data($this->randomMachineName());
    /** @var \Drupal\file\FileUsage\FileUsageInterface $usage */
    $usage = \Drupal::service('file.usage');
    $usage->add($file, 'monitoring_test', 'node', 123456789);
    for ($i = 1; $i <= 6; $i++) {
      // We use the logger.dblog service to be able to set the referer.
      \Drupal::service('logger.dblog')->notice('Source image at %source_image_path not found while trying to generate derivative image at %derivative_path.', [
          '%source_image_path' => $file->getFileUri(),
          '%derivative_path' => 'hash://styles/preview/1234.jpeg',
          'request_uri' => '',
          'uid' => 0,
          'channel' => 'image',
          'link' => '',
          'referer' => 'http://example.com/node/123456789',
          'ip' => '127.0.0.1',
          'timestamp' => \Drupal::time()->getRequestTime() - $i,
        ]);
    }
    \Drupal::logger('image')->notice('Source image at %source_image_path not found while trying to generate derivative image at %derivative_path.',
      array(
        '%source_image_path' => 'public://portrait-pictures/bluemouse.jpeg',
        '%derivative_path' => 'hash://styles/preview/5678.jpeg',
        'timestamp' => \Drupal::time()->getRequestTime(),
      ));

    // Run sensor and test the output.
    $result = $this->runSensor('dblog_image_missing_style');
    $this->assertEquals(6, $result->getValue());
    $this->assertTrue(strpos($result->getMessage(), $file->getFileUri()) !== FALSE);
    $this->assertTrue($result->isWarning());
    $verbose_output = $result->getVerboseOutput();
    $this->setRawContent(\Drupal::service('renderer')->renderPlain($verbose_output));
    $xpath = $this->xpath('//*[@id="unaggregated_result"]/div/table/tbody');
    $this->assertEquals('public://portrait-pictures/bluemouse.jpeg', (string) $xpath[0]->tr[0]->td[2]);
    $this->assertEquals('http://example.com/node/123456789', (string) $xpath[0]->tr[6]->td[1]);
    $this->assertLink('7');
  }

  /**
   * Tests requirements sensors.
   *
   * The module monitoring_test implements custom requirements injected through
   * state monitoring_test.requirements.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\CoreRequirementsSensorPlugin
   */
  public function testCoreRequirementsSensorPlugin() {
    $result = $this->runSensor('core_requirements_monitoring_test');
    $this->assertTrue($result->isOk());
    $this->assertEqual($result->getMessage(), 'Requirements check OK');

    // Set basic requirements saying that all is OK.
    $requirements = array(
      'requirement1' => array(
        'title' => 'requirement1',
        'description' => 'requirement1 description',
        'severity' => REQUIREMENT_OK,
      ),
      'requirement_excluded' => array(
        'title' => 'excluded requirement',
        'description' => 'requirement that should be excluded from monitoring by the sensor',
        // Set the severity to ERROR to test if the sensor result is not
        // affected by this requirement.
        'severity' => REQUIREMENT_ERROR,
      ),
    );
    \Drupal::state()->set('monitoring_test.requirements', $requirements);

    // Set requirements exclude keys into the sensor settings.
    $sensor_config = SensorConfig::load('core_requirements_monitoring_test');
    $settings = $sensor_config->getSettings();
    $settings['exclude_keys'] = array('requirement_excluded');
    $sensor_config->settings = $settings;
    $sensor_config->save();

    // We still have OK status but with different message.
    $result = $this->runSensor('core_requirements_monitoring_test');
    // We expect OK status as REQUIREMENT_ERROR is set by excluded requirement.
    $this->assertTrue($result->isOk());
    $this->assertEqual($result->getMessage(), 'requirement1, requirement1 description');

    // Add warning state.
    $requirements['requirement2'] = array(
      'title' => 'requirement2',
      'description' => 'requirement2 description',
      'severity' => REQUIREMENT_WARNING,
    );
    \Drupal::state()->set('monitoring_test.requirements', $requirements);

    // Now the sensor escalates to the requirement in warning state.
    $result = $this->runSensor('core_requirements_monitoring_test');
    $this->assertTrue($result->isWarning());
    $this->assertEqual($result->getMessage(), 'requirement2, requirement2 description');

    // Add error state.
    $requirements['requirement3'] = array(
      'title' => 'requirement3',
      'description' => 'requirement3 description',
      'severity' => REQUIREMENT_ERROR,
    );
    \Drupal::state()->set('monitoring_test.requirements', $requirements);

    // Now the sensor escalates to the requirement in critical state.
    $result = $this->runSensor('core_requirements_monitoring_test');
    $this->assertTrue($result->isCritical());
    $this->assertEqual($result->getMessage(), 'requirement3, requirement3 description');

    // Check verbose message. All output should be part of it.
    $verbose_output = $result->getVerboseOutput();
    $this->setRawContent(\Drupal::service('renderer')->renderPlain($verbose_output));
    $this->assertText('requirement1');
    $this->assertText('requirement1 description');
    $this->assertText('requirement_excluded');
    $this->assertText('excluded requirement');
    $this->assertText('requirement that should be excluded from monitoring by the sensor');
    $this->assertText('requirement2');
    $this->assertText('requirement2 description');

    // Add error state.
    $requirements = [
      'requirement4' => [
        'title' => ['#markup' => 'requirement 4'],
        'description' => ['#markup' => 'Description as a render array'],
        'severity' => REQUIREMENT_WARNING,
      ],
    ];
    \Drupal::state()->set('monitoring_test.requirements', $requirements);

    // Check verbose message. Make sure that the render array is converted to
    // a string.
    $result = $this->runSensor('core_requirements_monitoring_test');
    $this->assertTrue($result->isWarning());
    $this->assertEquals('requirement 4, Description as a render array', $result->getMessage());
    $verbose_output = $result->getVerboseOutput();
    $this->setRawContent(\Drupal::service('renderer')->renderPlain($verbose_output));
    $this->assertText('requirement 4');
    $this->assertText('Description as a render array');
  }

  /**
   * Tests the node count per content type sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ContentEntityAggregatorSensorPlugin
   */
  public function testDefaultNodeTypeSensors() {

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();

    $type1 = NodeType::create(['type' => $this->randomMachineName()]);
    $type1->save();
    $type2 = NodeType::create(['type' => $this->randomMachineName()]);
    $type2->save();
    Node::create(array('title' => $this->randomString(), 'type' => $type1->id()))->save();
    Node::create(array('title' => $this->randomString(), 'type' => $type1->id()))->save();
    Node::create(array('title' => $this->randomString(), 'type' => $type2->id()))->save();

    // Make sure that sensors for the new node types are available.
    monitoring_sensor_manager()->resetCache();

    // Run sensor for type1.
    $result = $this->runSensor('node_new_' . $type1->id());
    $this->assertEqual($result->getValue(), 2);
    // Test for the ContentEntityAggregatorSensorPlugin custom message.
    $this->assertEqual($result->getMessage(), new FormattableMarkup('@count @unit in @time_interval', array(
      '@count' => $result->getValue(),
      '@unit' => strtolower($result->getSensorConfig()->getValueLabel()),
      '@time_interval' => \Drupal::service('date.formatter')
        ->formatInterval($result->getSensorConfig()
          ->getTimeIntervalValue()),
    )));

    // Run sensor for all types.
    $result = $this->runSensor('node_new_all');
    $this->assertEqual($result->getValue(), 3);

    // Verify that hooks do not break when sensors unexpectedly do exist or
    // don't exist.
    $sensor = SensorConfig::create(array(
      'id' => 'node_new_existing',
      'label' => 'Existing sensor',
      'plugin_id' => 'entity_aggregator',
      'settings' => array(
        'entity_type' => 'node',
      ),
    ));
    $sensor->save();

    $type_existing = NodeType::create(['type' => 'existing', 'label' => 'Existing']);
    $type_existing->save();

    // Manually delete the sensor and then the node type.
    $sensor->delete();
    $type_existing->delete();

    // Rename type when the target sensor already exists.
    $sensor = SensorConfig::create(array(
      'id' => 'node_new_existing',
      'label' => 'Existing sensor',
      'plugin_id' => 'entity_aggregator',
      'settings' => array(
        'entity_type' => 'node',
      ),
    ));
    $sensor->save();
    $type1->set('name', 'existing');
    $type1->save();

    // Delete the sensor for $type2 before renaming.
    $sensor = SensorConfig::load('node_new_' . $type2->id());
    $sensor->delete();

    $type2->set('name', 'different');
    $type2->save();
  }

  /**
   * Tests dblog watchdog sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\DatabaseAggregatorSensorPlugin
   */
  public function testDatabaseAggregatorSensorPluginDblog() {
    // Create watchdog entry with severity alert.
    // The testbot reported random fails with an unexpected watchdog record.
    // ALERT: "Missing filter plugin: %filter." with %filter = "filter_null"
    // Thus we drop all ALERT messages first.
    \Drupal::database()->delete('watchdog')
      ->condition('severity', RfcLogLevel::ALERT)
      ->execute();
    \Drupal::logger('test')->alert('test message');

    // Run sensor and test the output.
    $severities = monitoring_event_severities();
    $result = $this->runSensor('dblog_event_severity_' . $severities[RfcLogLevel::ALERT]);
    $this->assertEqual($result->getValue(), 1);
  }

  /**
   * Tests failed user logins sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\UserFailedLoginsSensorPlugin
   */
  public function testUserFailedLoginsSensorPlugin() {
    // Fake some login failed dblog records.
    \Drupal::logger('user')->notice('Login attempt failed for %user.', array('%user' => 'user1'));
    \Drupal::logger('user')->notice('Login attempt failed for %user.', array('%user' => 'user1'));
    \Drupal::logger('user')->notice('Login attempt failed for %user.', array('%user' => 'user2'));
    \Drupal::logger('user')->notice('Login attempt failed for %user.', array('%user' => 'user2'));
    \Drupal::logger('user')->notice('Login attempt failed for %user.', array('%user' => 'user2'));

    // Run sensor and test the output.
    $result = $this->runSensor('user_failed_logins');
    $this->assertEqual($result->getValue(), 5);
    $this->assertEqual($result->getMessage(), '5 login attempts in 1 day, user2: 3, user1: 2');
  }

  /**
   * Tests user logouts through db aggregator sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\DatabaseAggregatorSensorPlugin
   */
  public function testDatabaseAggregatorSensorPluginUserLogout() {
    // Fake some logout dblog records.
    \Drupal::logger('user')->notice('Session closed for %name.', array('%user' => 'user1'));
    \Drupal::logger('user')->notice('Session closed for %name.', array('%user' => 'user1'));
    \Drupal::logger('user')->notice('Session closed for %name.', array('%user' => 'user2'));

    // Run sensor and test the output.
    $result = $this->runSensor('user_session_logouts');
    $this->assertEqual($result->getValue(), 3);
    $this->assertEqual($result->getMessage(), '3 logouts in 1 day');
  }

  /**
   * Tests git sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\GitDirtyTreeSensorPlugin
   */
  public function testGitDirtyTreeSensorPlugin() {
    // Enable the sensor and set cmd to output something.
    // The command creates a line for every file in unexpected state.
    $sensor_config = SensorConfig::load('monitoring_git_dirty_tree');
    $sensor_config->enable();
    // Ensure that newlines are treated correctly, see
    // http://unix.stackexchange.com/questions/48106/what-does-it-mean-to-have-a-dollarsign-prefixed-string-in-a-script.
    $sensor_config->settings['status_cmd'] = 'printf "A addedfile.txt\nM sites/all/modules/monitoring/test/tests/monitoring.core.test\nD deleted file.txt"';
    $sensor_config->settings['ahead_cmd'] = 'true';
    $sensor_config->settings['submodules_cmd'] = 'true';
    $sensor_config->save();
    $result = $this->runSensor('monitoring_git_dirty_tree');
    $this->assertTrue($result->isCritical());
    // The verbose output should contain the cmd output.
    $verbose_output = $result->getVerboseOutput();
    $this->setRawContent(\Drupal::service('renderer')->renderPlain($verbose_output));
    $this->assertText('A addedfile.txt');
    $this->assertText('M sites/all/modules/monitoring/test/tests/monitoring.core.test');
    $this->assertText('D deleted file.txt');
    // Check if the sensor message has the expected value.
    $this->assertEqual($result->getMessage(), '3 files in unexpected state: A addedfile.txt, M …modules/monitoring/test/tests/monitoring.core.test');

    // Now echo empty string.
    $sensor_config->settings['status_cmd'] = 'true';
    $sensor_config->save();

    $result = $this->runSensor('monitoring_git_dirty_tree');
    $this->assertTrue($result->isOk());
    // The message should say that it is ok.
    $this->assertEqual($result->getMessage(), 'Git repository clean');

    // Test 2 commits ahead of origin.
    $sensor_config->settings['ahead_cmd'] = 'printf "a4ea5e3ac5b7cca0c96aee4d00447c36121bd128\n299d85344fab9befbf6a275a4b64bda7b464b493"';
    $sensor_config->save();

    $result = $this->runSensor('monitoring_git_dirty_tree');
    $this->assertTrue($result->isWarning());
    $verbose_output = $result->getVerboseOutput();
    $this->setRawContent(\Drupal::service('renderer')->renderPlain($verbose_output));
    $this->assertText('a4ea5e3ac5b7cca0c96aee4d00447c36121bd128');
    $this->assertText('299d85344fab9befbf6a275a4b64bda7b464b493');
    $this->assertEqual($result->getMessage(), 'Branch is 2 ahead of origin');

    // Test not in main branch.
    $sensor_config->settings['check_branch'] = TRUE;
    $sensor_config->settings['ahead_cmd'] = 'true';
    $sensor_config->settings['actual_branch_cmd'] = 'printf "7.0.x"';
    $sensor_config->settings['expected_branch'] = '8.0.x';
    $sensor_config->save();

    $result = $this->runSensor('monitoring_git_dirty_tree');
    $this->assertTrue($result->isWarning());
    $verbose_output = $result->getVerboseOutput();
    $this->setRawContent(\Drupal::service('renderer')->renderPlain($verbose_output));
    $this->assertText('7.0.x');
    $this->assertEqual($result->getMessage(), 'Active branch 7.0.x, expected 8.0.x');

    // Test same as main branch.
    $sensor_config->settings['actual_branch_cmd'] = 'printf "8.0.x"';
    $sensor_config->save();

    $result = $this->runSensor('monitoring_git_dirty_tree');
    $this->assertTrue($result->isOk());
    $this->assertEqual($result->getMessage(), 'Git repository clean');

    // Test submodule not initialized.
    $sensor_config->settings['submodules_cmd'] = 'printf -- "-a5066d1778b9ec7c86631234ff2795e777bdff12 test\n156fff6ee58497fa22b57c32e22e3f13377b4120 test (8.x-1.0-240-g156fff6)"';
    $sensor_config->save();

    $result = $this->runSensor('monitoring_git_dirty_tree');
    $this->assertTrue($result->isCritical());
    $verbose_output = $result->getVerboseOutput();
    $this->setRawContent(\Drupal::service('renderer')->renderPlain($verbose_output));
    $this->assertText('-a5066d1778b9ec7c86631234ff2795e777bdff12 test');
    $this->assertEqual($result->getMessage(), '1 submodules in unexpected state: -a5066d1778b9ec7c86631234ff2795e777bdff12 test');

    // Test submodule with uncommitted changes.
    $sensor_config->settings['submodules_cmd'] = 'printf "a5066d1778b9ec7c86631234ff2795e777bdff12 test\n+156fff6ee58497fa22b57c32e22e3f13377b4120 test (8.x-1.0-240-g156fff6)"';
    $sensor_config->save();

    $result = $this->runSensor('monitoring_git_dirty_tree');
    $this->assertTrue($result->isCritical());
    $verbose_output = $result->getVerboseOutput();
    $this->setRawContent(\Drupal::service('renderer')->renderPlain($verbose_output));
    $this->assertText('+156fff6ee58497fa22b57c32e22e3f13377b4120 test (8.x-1.0-240-g156fff6)');
    $this->assertEqual($result->getMessage(), '1 submodules in unexpected state: +156fff6ee58497fa22b57c32e22e3f13377b4120 test (8.x-1.0-240-g156fff6)');

    // Test submodules in expected state.
    $sensor_config->settings['submodules_cmd'] = 'printf "a5066d1778b9ec7c86631234ff2795e777bdff12 test\n156fff6ee58497fa22b57c32e22e3f13377b4120 test (8.x-1.0-240-g156fff6)"';
    $sensor_config->save();

    $result = $this->runSensor('monitoring_git_dirty_tree');
    $this->assertTrue($result->isOk());
    $this->assertEqual($result->getMessage(), 'Git repository clean');
  }

  /**
   * Tests the default theme sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ConfigValueSensorPlugin
   */
  public function testConfigValueSensorPluginDefaultTheme() {
    $this->config('system.theme')->set('default', 'bartik')->save();
    $result = $this->runSensor('core_theme_default');
    $this->assertTrue($result->isOk());
    $this->assertEqual($result->getMessage(), 'Value bartik');

    $this->config('system.theme')->set('default', 'garland')->save();
    $result = $this->runSensor('core_theme_default');
    $this->assertTrue($result->isCritical());
  }

  /**
   * Tests the database aggregator sensor.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\DatabaseAggregatorSensorPlugin
   */
  public function testDatabaseAggregator() {
    // Aggregate by watchdog type.
    $sensor_config = SensorConfig::load('watchdog_aggregate_test');
    $sensor_config->settings['conditions'] = array(
      array('field' => 'type', 'value' => 'test_type'),
    );
    $sensor_config->save();
    \Drupal::logger('test_type')->notice($this->randomMachineName());
    \Drupal::logger('test_type')->notice($this->randomMachineName());
    \Drupal::logger('other_test_type')->notice($this->randomMachineName());
    $result = $this->runSensor('watchdog_aggregate_test');
    $this->assertEqual($result->getValue(), 2);

    // Aggregate by watchdog message.
    $sensor_config->settings['conditions'] = array(
      array('field' => 'message', 'value' => 'test_message'),
    );
    $sensor_config->save();
    \Drupal::logger($this->randomMachineName())->notice('test_message');
    \Drupal::logger($this->randomMachineName())->notice('another_test_message');
    \Drupal::logger($this->randomMachineName())->notice('another_test_message');
    $result = $this->runSensor('watchdog_aggregate_test');
    $this->assertEqual($result->getValue(), 1);

    // Aggregate by watchdog severity.
    $sensor_config->settings['conditions'] = array(
      array('field' => 'severity', 'value' => RfcLogLevel::CRITICAL),
    );
    $sensor_config->save();
    \Drupal::logger($this->randomMachineName())
      ->critical($this->randomMachineName());
    \Drupal::logger($this->randomMachineName())
      ->critical($this->randomMachineName());
    \Drupal::logger($this->randomMachineName())
      ->critical($this->randomMachineName());
    \Drupal::logger($this->randomMachineName())
      ->critical($this->randomMachineName());
    $result = $this->runSensor('watchdog_aggregate_test');
    $this->assertEqual($result->getValue(), 4);

    // Aggregate by watchdog location.
    $sensor_config->settings['conditions'] = array(
      array('field' => 'location', 'value' => 'http://some.url.dev'),
    );
    $sensor_config->save();
    // Update the two test_type watchdog entries with a custom location.
    \Drupal::database()->update('watchdog')
      ->fields(array('location' => 'http://some.url.dev'))
      ->condition('type', 'test_type')
      ->execute();
    $result = $this->runSensor('watchdog_aggregate_test');
    $this->assertEqual($result->getValue(), 2);

    // Filter for time period.
    $sensor_config->settings['conditions'] = array();
    $sensor_config->settings['time_interval_value'] = 10;
    $sensor_config->settings['time_interval_field'] = 'timestamp';
    $sensor_config->save();

    // Make all system watchdog messages older than the configured time period.
    \Drupal::database()->update('watchdog')
      ->fields(array('timestamp' => REQUEST_TIME - 20))
      ->condition('type', 'system')
      ->execute();
    $count_latest = \Drupal::database()->query('SELECT COUNT(*) FROM {watchdog} WHERE timestamp > :timestamp', array(':timestamp' => REQUEST_TIME - 10))->fetchField();
    $result = $this->runSensor('watchdog_aggregate_test');
    $this->assertEqual($result->getValue(), $count_latest);

    // Test for thresholds and statuses.
    $sensor_config->settings['conditions'] = array(
      array('field' => 'type', 'value' => 'test_watchdog_aggregate_sensor'),
    );
    $sensor_config->save();
    $result = $this->runSensor('watchdog_aggregate_test');
    $this->assertTrue($result->isOk());
    $this->assertEqual($result->getValue(), 0);

    \Drupal::logger('test_watchdog_aggregate_sensor')->notice('testing');
    \Drupal::logger('test_watchdog_aggregate_sensor')->notice('testing');
    $result = $this->runSensor('watchdog_aggregate_test');
    $this->assertTrue($result->isWarning());
    $this->assertEqual($result->getValue(), 2);

    \Drupal::logger('test_watchdog_aggregate_sensor')->notice('testing');
    $result = $this->runSensor('watchdog_aggregate_test');
    $this->assertTrue($result->isCritical());
    $this->assertEqual($result->getValue(), 3);

    // Test database aggregator with invalid conditions.
    $sensor = SensorConfig::create(array(
      'id' => 'db_test',
      'label' => 'Database sensor invalid',
      'plugin_id' => 'database_aggregator',
      'settings' => array(
        'table' => 'watchdog',
      ),
    ));
    $sensor->settings['conditions'] = array(
      array('field' => 'invalid', 'value' => ''),
    );
    $sensor->settings['verbose_fields']['0'] = 'wid';
    $sensor->save();
    $result = $this->runSensor('db_test');
    $this->assertTrue($result->isCritical());
  }

  /**
   * Tests if the previous sensor result retrieved is the expected one.
   */
  public function testPreviousSensorResult() {
    // Allow running all enabled sensors.
    \Drupal::configFactory()
      ->getEditable('monitoring.settings')
      ->set('cron_run_sensors', TRUE)
      ->save();

    \Drupal::cache('default')->deleteAll();
    $sensor_runner = \Drupal::service('monitoring.sensor_runner');
    // There should be no logged sensor result at the moment.
    $sensorConfig = SensorConfig::load('test_sensor_falls');
    $sensor_result_0 = monitoring_sensor_result_last($sensorConfig->id());
    $this->assertNull($sensor_result_0);

    // Run a sensor that is CRITICAL and check there is no previous result.
    /** @var \Drupal\monitoring\Result\SensorResult $sensor_result_1 */
    $sensor_result_1 = $sensor_runner->runSensors([$sensorConfig])[0];
    $previous_result_1 = $sensor_result_1->getPreviousResult();
    $this->assertEquals(SensorResultInterface::STATUS_CRITICAL, $sensor_result_1->getStatus());
    $this->assertEquals($sensor_result_0, $previous_result_1);
    $this->assertNull($previous_result_1);

    // Run the same sensor and check the previous result status is
    // same as $sensor_result_1.
    /** @var \Drupal\monitoring\Result\SensorResult $sensor_result_2 */
    $sensor_result_2 = $sensor_runner->runSensors([$sensorConfig])[0];
    $previous_result_2 = $sensor_result_2->getPreviousResult();
    $this->assertEquals(SensorResultInterface::STATUS_CRITICAL, $sensor_result_2->getStatus());
    $this->assertEquals($sensor_result_1->getStatus(), $previous_result_2->getStatus());
    $this->assertNotNull($previous_result_2);

    // Change sensor threshold settings so that the sensor switches to WARNING.
    $sensorConfig->thresholds['critical'] = 0;

    // Run it again, a new log should be stored because the status has changed.
    /** @var \Drupal\monitoring\Result\SensorResult $sensor_result_3 */
    $sensor_result_3 = $sensor_runner->runSensors([$sensorConfig])[0];
    $previous_result_3 = $sensor_result_3->getPreviousResult();
    $this->assertEquals(SensorResultInterface::STATUS_WARNING, $sensor_result_3->getStatus());
    $this->assertEquals($sensor_result_2->getStatus(), $previous_result_3->getStatus());
    $this->assertNotNull($previous_result_3);
  }

}
