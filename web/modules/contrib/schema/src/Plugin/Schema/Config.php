<?php
/**
 * @file
 * Contains Drupal\schema\Plugin\Schema\Config.
 */

namespace Drupal\schema\Plugin\Schema;

use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Plugin\PluginBase;
use Drupal\schema\SchemaProviderInterface;
use ReflectionClass;


/**
 * Provides schema information for config storage tables.
 *
 * @SchemaProvider(id = "config")
 */
class Config extends PluginBase implements SchemaProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function get($rebuild = FALSE) {
    $complete_schema = array();
    foreach (array(
               'config.storage.staging',
               'config.storage.snapshot',
               'config.storage'
             ) as $service_id) {
      $backend = \Drupal::service($service_id);

      // The config.storage.active service is not accessible directly, therefore
      // retrieve the actual active backend from the config.storage service.
      if ($backend instanceof CachedStorage) {
        $reflection = new ReflectionClass(get_class($backend));
        $property = $reflection->getProperty('storage');
        $property->setAccessible(TRUE);
        $backend = $property->getValue($backend);
      }

      if ($backend instanceof DatabaseStorage) {
        $reflection = new ReflectionClass(get_class($backend));
        $schema_method = $reflection->getMethod('schemaDefinition');
        $schema_method->setAccessible(TRUE);
        $name_property = $reflection->getProperty('table');
        $name_property->setAccessible(TRUE);

        $table_name = $name_property->getValue($backend);
        $schema = $schema_method->invoke($backend);
        $complete_schema[$table_name] = $schema + array('module' => 'Core\Config');
      }
    }
    return $complete_schema;
  }
}
