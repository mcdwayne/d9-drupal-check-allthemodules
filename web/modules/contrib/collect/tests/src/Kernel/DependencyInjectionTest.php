<?php

namespace Drupal\Tests\collect\Kernel;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that Collect classes use dependency injection correctly.
 *
 * @group collect
 */
class DependencyInjectionTest extends KernelTestBase {

  public static $modules = [
    'collect',
    'hal',
    'rest',
    'node',
    'serialization',
    'user',
    // Database Logging needed to test model plugin serialization.
    'dblog',
  ];

  protected static $model_plugin_ids = [
    'collectjson',
    'collect_field_definition',
    'collect_fetch_url',
    'json',
    'default',
  ];

  /**
   * Tests that the plugin list is complete.
   */
  public function testModelPluginListIsComplete() {
    $manager_plugin_ids = array_keys(collect_model_manager()->getDefinitions());
    sort(static::$model_plugin_ids);
    sort($manager_plugin_ids);
    $this->assertEqual($manager_plugin_ids, static::$model_plugin_ids);
  }

  /**
   * Tests that all model plugins can be serialized.
   */
  public function testSerialization() {
    foreach (static::$model_plugin_ids as $id) {
      serialize(collect_model_manager()->createInstance($id, ['foo' => 'bar']));
      // If serialize() did not throw an exception or error, serialization went fine.
      $this->pass(SafeMarkup::format('Plugin %id is serializable', ['%id' => $id]));
    }
  }
}
