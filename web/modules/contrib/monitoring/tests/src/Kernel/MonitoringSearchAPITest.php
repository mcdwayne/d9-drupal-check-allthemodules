<?php

namespace Drupal\Tests\monitoring\Kernel;

use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;

/**
 * Tests for search API sensor.
 *
 * @group monitoring
 * @dependencies search_api
 */
class MonitoringSearchAPITest extends MonitoringUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'search_api',
    'search_api_db',
    'search_api_test_db',
    'node',
    'entity_test',
    'text',
    'taxonomy',
    'search_api_solr',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Install required database tables for each module.
    $this->installSchema('search_api', ['search_api_item']);
    $this->installEntitySchema('search_api_task');
    $this->installSchema('system', ['router', 'queue', 'key_value_expire']);
    $this->installSchema('user', ['users_data']);

    // Install the schema for entity entity_test_mulrev_changed.
    $this->installEntitySchema('entity_test_mulrev_changed');

    // Set up the required bundles.
    $this->createEntityTestBundles();
    // Install the test search API index and server used by the test.
    $this->installConfig(['search_api', 'search_api_test_db']);

    \Drupal::service('search_api.index_task_manager')
      ->addItemsAll(Index::load('database_search_index'));
  }

  /**
   * Tests individual sensors.
   */
  public function testSensors() {

    // Create content first to avoid a Division by zero error.
    // Two new articles, none indexed.
    $entity = EntityTestMulRevChanged::create(array('type' => 'article'));
    $entity->save();
    $entity = EntityTestMulRevChanged::create(array('type' => 'article'));
    $entity->save();

    $result = $this->runSensor('search_api_database_search_index');
    $this->assertEqual($result->getValue(), 2);

    // Update the index to test sensor result.
    $index = Index::load('database_search_index');
    $index->indexItems();

    $entity = EntityTestMulRevChanged::create(array('type' => 'article'));
    $entity->save();
    $entity = EntityTestMulRevChanged::create(array('type' => 'article'));
    $entity->save();
    $entity = EntityTestMulRevChanged::create(array('type' => 'article'));
    $entity->save();

    // New articles are not yet indexed.
    $result = $this->runSensor('search_api_database_search_index');
    $this->assertEqual($result->getValue(), 3);

    $index = Index::load('database_search_index');
    $index->indexItems();

    // Everything should be indexed.
    $result = $this->runSensor('search_api_database_search_index');
    $this->assertEqual($result->getValue(), 0);

    // Verify that hooks do not break when sensors unexpectedly do exist or
    // don't exist.
    $sensor = SensorConfig::create(array(
      'id' => 'search_api_existing',
      'label' => 'Existing sensor',
      'plugin_id' => 'search_api_unindexed',
      'settings' => array(
        'index_id' => 'existing',
      ),
    ));
    $sensor->save();

    $index_existing = Index::create([
      'id' => 'existing',
      'status' => FALSE,
      'name' => 'Existing',
      'tracker' => 'default',
    ]);
    $index_existing->save();

    // Manually delete the sensor and then the index.
    $sensor->delete();
    $index_existing->delete();
  }

  /**
   * Tests the solr disk usage sensor.
   */
  public function testSolrDiskUsage() {
    $sensor_config = SensorConfig::create([
      'id' => 'solr_disk_usage',
      'label' => 'Solr disk usage',
      'plugin_id' => 'solr_disk_usage',
      'value_label' => 'mb',
      'caching_time' => 86400,
      'value_type' => 'number',
      'thresholds' => [
        'type' => 'exceeds',
        'warning' => 20,
        'critical' => 50,
      ],
      'settings' => [
        'server' => '',
      ],
    ]);
    $sensor_config->save();

    $sensor_result = $this->runSensor('solr_disk_usage');
    $this->assertTrue($sensor_result->isCritical());
    $this->assertEquals($sensor_result->getMessage(), 'RuntimeException: Solr server is not configured.');

    $sensor_config = SensorConfig::load('solr_disk_usage');
    $settings = $sensor_config->getSettings();
    $settings['server'] = 'search_api_server';
    $sensor_config->settings = $settings;
    $sensor_config->save();

    $sensor_result = $this->runSensor('solr_disk_usage');
    $this->assertTrue($sensor_result->isCritical());
    $this->assertEquals($sensor_result->getMessage(), "RuntimeException: Solr server doesn't exist.");

    $server = Server::create([
      'name' => 'Solr server',
      'status' => TRUE,
      'id' => 'search_api_server',
      'backend' => 'search_api_solr',
      'backend_config' => [
        'connector' => 'standard',
        'connector_config' => [
          'scheme' => 'http',
          'host' => 'localhost',
          'port' => '8983',
          'path' => '/solr',
          'core' => 'd8',
        ],
      ],
    ]);
    $server->save();

    $sensor_config->settings['server'] = 'search_api_server';
    $sensor_config->save();

    \Drupal::state()->set('monitoring.test_solr_index_size', '5 MB');
    $solr_info = [
      'server_name' => $server->label(),
      'host' => $server->getBackend()->getConfiguration()['connector_config']['host'],
      'core' => $server->getBackend()->getConfiguration()['connector_config']['core'],
      'total_physical_memory' => 10000000,
      'free_physical_memory' => 8000000,
      'total_swap_memory' => 10000000,
      'free_swap_memory' => 5000000,
      'indexed_docs' => 100,
    ];

    \Drupal::state()->set('monitoring.test_solr_info', $solr_info);
    $sensor_result = $this->runSensor('solr_disk_usage');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals($sensor_result->getMessage(), "5 MB");
    $verbose_output = $sensor_result->getVerboseOutput();
    $plain = \Drupal::getContainer()->get('renderer')->renderPlain($verbose_output);
    $this->setRawContent($plain);
    $this->assertText('Solr server: Solr server, host: localhost, core: d8');
    $this->assertText('Solr server: Solr server, host: localhost, core: d8');
    $this->assertText('Physical memory (9.54 MB available)');
    $this->assertText('Swap memory (9.54 MB available)');
    $this->assertText('1.91 MB (20.00%) used');
    $this->assertText('4.77 MB (50.00%) used');

    \Drupal::state()->set('monitoring.test_solr_index_size', '21 MB');
    $sensor_result = $this->runSensor('solr_disk_usage');
    $this->assertTrue($sensor_result->isWarning());
    $this->assertEquals($sensor_result->getMessage(), "21 MB");

    \Drupal::state()->set('monitoring.test_solr_index_size', '51 MB');
    $sensor_result = $this->runSensor('solr_disk_usage');
    $this->assertTrue($sensor_result->isCritical());
    $this->assertEquals($sensor_result->getMessage(), "51 MB");

    // Check that we correctly convert GB to MB so we get the right state.
    \Drupal::state()->set('monitoring.test_solr_index_size', '1 GB');
    $sensor_result = $this->runSensor('solr_disk_usage');
    $this->assertTrue($sensor_result->isCritical());
    $this->assertEquals($sensor_result->getMessage(), "1 GB");

    \Drupal::state()->set('monitoring.test_solr_index_size', '100 bytes');
    $sensor_result = $this->runSensor('solr_disk_usage');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals($sensor_result->getMessage(), "100 bytes");
  }

  /**
   * Sets up the necessary bundles on the test entity type.
   */
  protected function createEntityTestBundles() {
    entity_test_create_bundle('item', NULL, 'entity_test_mulrev_changed');
    entity_test_create_bundle('article', NULL, 'entity_test_mulrev_changed');
  }

}
