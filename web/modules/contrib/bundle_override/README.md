#Bundle Override

## Introduction
Bundle override allows you to use specific class for entity bundle. As an example, if you have a bundle of node called 'article' and you want to add specific methods for this bundle (at load, save, delete or whatever) you can define a specific class (as a Plugin) and this class will be used instead of default \Drupal\node\Entity\Node.

Then you can use Article::loadMultiple() to load only 'article' bundled nodes, and other loading function limited to the entity bundle.

This approach can bring some features like adding process on entity save, without using hook or event definition and then bundle filter.


By default, bundle_override brings plugin for node and taxonomy_term entity but you can add any entity type plugin to use the bundle class.
You can see the node and taxonomy_term plugins to have examples.

## Add Bundle Override Objects plugin
A bundle override objects plugin is a plugin that defines a specific class for a bundle. For example if you
have bundle of node called 'article' you can define a class for this bundle. You just have to create a class in 
`\Drupal\{your_module}\Plugin\bundle_override\Objects\node\Article.php` :

```
<?php
namespace Drupal\{your_module}\Plugin\bundle_override\Objects\node;

use Drupal\bundle_override\Plugin\bundle_override\EntityTypes\node\NodeB;

/**
 * Plugin implementation of the 'article' bundle.
 *
 * @BundleOverrideObjects(
 *   id = "article"
 * )
 */
class Article extends NodeB{

  /**
   * {@inheritdoc}
   */
  public static function getStaticBundle() {
    return 'article';
  }
}
``` 

Note :
 - the class must extends NodeB and define the `::getStaticBundle()` static method.
Then you will be able to use `Article::loadMultiple()`, or override Node methods with specific processes.


## Add Bundle Override Entity Type plugin
The bundle_override module brings you node and taxonomy_term Objects Plugin but you would be able to use bundle objects for other entity types.

For example for an entity type `{your_entity}`, you have to define 3 elements : 

1. The Plugin Manager (the plugin manager will manage your Bundle Override Objects Plugin :
Define the plugin : `\Drupal\{your_module}\Plugin\bundle_override\EntityTypes\{YourEntity}BPluginManager`
```
<?php

namespace Drupal\{your_module}\Plugin\bundle_override\EntityTypes;

use Drupal\bundle_override\Manager\Objects\AbstractBundleOverrideObjectsPluginManager;

/**
 * Plugin implementation of the 'BundleOverrideEntityTypes'.
 *
 * @BundleOverrideEntityTypes(
 *   id = "{your_entity}"
 * )
 */
class {YourEntity}BPluginManager extends AbstractBundleOverrideObjectsPluginManager {

  /**
   * The entity type id.
   */
  const ENTITY_TYPE_ID = '{your_entity}';

  /**
   * The service name.
   */
  const SERVICE_NAME = 'bundle_override.{your_entity}_plugin_manager';

  /**
   * {@inheritdoc}
   */
  public function getDefaultEntityClass() {
    return '\Drupal\{your_entity}\Entity\{YourEntity}';
  }

  /**
   * {@inheritdoc}
   */
  public function getRedefinerClass() {
    return 'Drupal\{your_module}\Plugin\bundle_override\EntityTypes\{your_entity}\{YourEntity}B';
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClass() {
    return 'Drupal\{your_module}\Plugin\bundle_override\EntityTypes\{your_entity}\{YourEntity}BStorage';
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceId() {
    return static::SERVICE_NAME;
  }

}

``` 

Note: 
- The plugin have to extend the `Drupal\bundle_override\Manager\Objects\AbstractBundleOverrideObjectsPluginManager`

2. Override the Storage entity by overriding the `mapFromStorageRecords` method.
```
<?php

namespace Drupal\{your_module}\Plugin\bundle_override\EntityTypes\{your_entity};

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\{your_entity}\{YourEntity}Storage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\bundle_override\Plugin\bundle_override\EntityTypes\{YourEntity}BPluginManager;

/**
 * Class NodeBStorage.
 *
 * @package Drupal\bundle_override\Plugin\bundle_override\EntityTypes\node
 */
class {YourEntity}BStorage extends {YourEntity}Storage {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager);

    if (property_exists($entity_type, 'destinationClass') &&  $entity_type->destinationClass) {
      $this->entityClass = $entity_type->destinationClass;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_ids = FALSE) {
    $query = parent::buildQuery($ids, $revision_ids);

    if (method_exists($this->entityClass, 'getStaticBundle')) {
      if ($bundle = call_user_func($this->entityClass . '::getStaticBundle')) {
        $query->condition('{bundle_property}', $bundle);
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records, $load_from_revision = FALSE) {
    if (!$records) {
      return [];
    }

    $values = [];
    foreach ($records as $id => $record) {
      $values[$id] = [];
      // Skip the item delta and item value levels (if possible) but let the
      // field assign the value as suiting. This avoids unnecessary array
      // hierarchies and saves memory here.
      foreach ($record as $name => $value) {
        // Handle columns named [field_name]__[column_name] (e.g for field types
        // that store several properties).
        if ($field_name = strstr($name, '__', TRUE)) {
          $property_name = substr($name, strpos($name, '__') + 2);
          $values[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT][$property_name] = $value;
        }
        else {
          // Handle columns named directly after the field (e.g if the field
          // type only stores one property).
          $values[$id][$name][LanguageInterface::LANGCODE_DEFAULT] = $value;
        }
      }
    }

    // Initialize translations array.
    $translations = array_fill_keys(array_keys($values), []);

    // Load values from shared and dedicated tables.
    $this->loadFromSharedTables($values, $translations, $load_from_revision);
    $this->loadFromDedicatedTables($values, $load_from_revision);

    $entities = [];
    foreach ($values as $id => $entity_values) {
      $bundle = $this->bundleKey ? $entity_values[$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] : FALSE;
      // Turn the record into an entity class.
      $entities[$id] = {YourEntity}BPluginManager::me()
        ->getEntityByStorageData($entity_values, $this->entityTypeId, $bundle, array_keys($translations[$id]));
    }

    return $entities;
  }

}
```
Note : 
- You have to replace 
`new $this->entityClass($entity_values, $this->entityTypeId, $bundle, array_keys($translations[$id]));` 
by `{YourEntity}BPluginManager::me()->getEntityByStorageData($entity_values, $this->entityTypeId, $bundle, array_keys($translations[$id]));`
- You have to be careful with the  `{bundle_property}`, it depends on your entity definition. It's used to filter
entity when you do a load or loadMultiple.

3. Redefine the class {YourEntity}B to override the {YourEntity} class.
```
<?php

namespace Drupal\{your_module}\Plugin\bundle_override\EntityTypes\{your_entity};

use Drupal\bundle_override\Manager\Objects\BundleOverrideObjectsInterface;
use Drupal\{your_entity}\Entity\{YourEntity};

/**
 * Class NodeB.
 *
 * @package Drupal\{your_module}\Plugin\bundle_override\EntityTypes\{your_entity}
 */
abstract class {YourEntity}B extends {YourEntity} implements BundleOverrideObjectsInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    $values += ['{bundle_property}' => static::getStaticBundle()];
    return static::getOverridedStorage()->create($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(array $ids = NULL) {
    return static::getOverridedStorage()->loadMultiple($ids);
  }

  /**
   * Return the storage instance.
   *
   * @return NodeBStorage
   *   The storage entity.
   */
  private static function getOverridedStorage() {
    $entity_type = \Drupal::entityTypeManager()->getDefinition('{your_entity}');
    $entity_type->destinationClass = get_called_class();
    return {YourEntity}BStorage::createInstance(\Drupal::getContainer(), $entity_type);
  }

}
```

Node : 
- You have to be careful with the  `{bundle_property}`, it depends on your entity definition.