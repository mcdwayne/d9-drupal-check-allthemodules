<?php

namespace Drupal\Tests\commerce_migrate_commerce\Unit\Plugin\migrate\process\commerce1;

use Drupal\KernelTests\KernelTestBase;
use Drupal\commerce_migrate_ubercart\Plugin\migrate\process\uc6\UbercartFieldStorageGenerate;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\Row;

/**
 * Tests the UbercartFieldStorageGenerate plugin.
 *
 * @coversDefaultClass \Drupal\commerce_migrate_ubercart\Plugin\migrate\process\uc6\UbercartFieldStorageGenerate
 *
 * @group commerce_migrate_uc6
 */
class UbercartFieldStorageGenerateTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'migrate',
    'node',
    'system',
    'text',
    'user',
  ];
  /**
   * Process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected $processPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);
  }

  /**
   * Tests UbercartFieldStorageGenerate process plugin.
   *
   * @dataProvider providerValidInputs
   */
  public function testValidInputs($value = NULL, $source_properties = NULL, $destination_properties = NULL, $expected = NULL) {

    $migrateExecutable = $this->getMockBuilder('Drupal\migrate\MigrateExecutable')
      ->disableOriginalConstructor()
      ->getMock();

    $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
    $plugin = new UbercartFieldStorageGenerate([], 'test', [], $migration_plugin_manager, '');
    $row = new Row();
    foreach ($source_properties as $name => $datum) {
      $row->setSourceProperty($name, $datum);
    }
    foreach ($destination_properties as $name => $datum) {
      $row->setDestinationProperty($name, $datum);
    }
    $new_value = $plugin->transform($value, $migrateExecutable, $row, 'destination_property');
    $this->assertSame($expected, $new_value);

    $config_name = $value . '.' . $source_properties['field_name'];
    $storage = FieldStorageConfig::load($config_name);
    $this->assertInstanceOf(FieldStorageConfig::class, $storage);
  }

  /**
   * Data provider for testValidInputs().
   */
  public function providerValidInputs() {
    $tests[0]['value'] = 'node';
    $tests[0]['source_properties'] = [
      'field_name' => 'field_color',
    ];
    $tests[0]['destination_properties'] = [
      'type' => 'text',
      'cardinality' => '1',
      'settings' => [],
    ];
    $tests[0]['expected'] = 'field_color';
    return $tests;
  }

  /**
   * Tests UbercartFieldStorageGenerate process plugin.
   *
   * @dataProvider providerInvalidInputs
   */
  public function testProviderInvalidInputs($value = NULL, $source_properties = NULL, $destination_properties = NULL, $expected = NULL) {

    $migrateExecutable = $this->getMockBuilder('Drupal\migrate\MigrateExecutable')
      ->disableOriginalConstructor()
      ->getMock();

    $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
    $plugin = new UbercartFieldStorageGenerate([], 'test', [], $migration_plugin_manager, '');
    $row = new Row();
    foreach ($source_properties as $name => $datum) {
      $row->setSourceProperty($name, $datum);
    }
    foreach ($destination_properties as $name => $datum) {
      $row->setDestinationProperty($name, $datum);
    }
    $new_value = $plugin->transform($value, $migrateExecutable, $row, 'destination_property');
    $this->assertSame($expected, $new_value);
  }

  /**
   * Data provider for testProviderInvalidInputs().
   */
  public function providerInvalidInputs() {
    // Unknown entity type.
    $tests[0]['value'] = 'unknown';
    $tests[0]['source_properties'] = [
      'field_name' => 'field_color',
    ];
    $tests[0]['destination_properties'] = [
      'type' => 'text',
      'cardinality' => '1',
      'settings' => [],
    ];
    $tests[0]['expected'] = FALSE;

    // Unknown field type.
    $tests[1]['value'] = 'node';
    $tests[1]['source_properties'] = [
      'field_name' => 'field_color',
    ];
    $tests[1]['destination_properties'] = [
      'type' => 'unknown',
      'cardinality' => '1',
      'settings' => [],
    ];
    $tests[1]['expected'] = FALSE;

    return $tests;
  }

}
