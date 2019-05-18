<?php
/**
 * @file
 * Contains Drupal\schema\Plugin\Schema\Menu.
 */

namespace Drupal\schema\Plugin\Schema;

use Drupal\Core\Menu\MenuTreeStorage;
use Drupal\Core\Plugin\PluginBase;
use Drupal\schema\SchemaProviderInterface;
use ReflectionClass;

/**
 * Provides schema information for menu storage tables.
 *
 * @SchemaProvider(id = "menu")
 */
class Menu extends PluginBase implements SchemaProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function get($rebuild = FALSE) {
    $complete_schema = array();
    /*
    // TODO: the menu.tree_storage is now private, so need to find another way
    // to get this table data.
    $menu_storage = \Drupal::service('menu.tree_storage');
    if ($menu_storage instanceof MenuTreeStorage) {
      $reflection = new ReflectionClass(get_class($menu_storage));
      $schema_method = $reflection->getMethod('schemaDefinition');
      $schema_method->setAccessible(TRUE);
      $name_property = $reflection->getProperty('table');
      $name_property->setAccessible(TRUE);

      $table_name = $name_property->getValue($menu_storage);
      $schema = $schema_method->invoke($menu_storage);
      $complete_schema[$table_name] = $schema + array('module' => 'Core\Menu');
    }
    */
    return $complete_schema;
  }
}
