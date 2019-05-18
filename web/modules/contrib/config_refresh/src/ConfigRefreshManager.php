<?php

namespace Drupal\config_refresh;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\FileStorage;

/**
 * Class ConfigRefreshManager
 *
 * @package Drupal\config_refresh
 */
Class ConfigRefreshManager {

  protected $configManager;

  protected $entityManager;

  protected $folderTypes = [];

  public function __construct(ConfigManagerInterface $config_manager, EntityManagerInterface $entity_manager) {
    $this->configManager = $config_manager;
    $this->entityManager =  $entity_manager;

    $this->folderTypes[] = InstallStorage::CONFIG_INSTALL_DIRECTORY;
    $this->folderTypes[] = InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
  }

  /**
   * @param $module_name
   * @param string $config_type
   */
  public function refreshAsBatch($module_name, $config_type) {
    $config_entity_type = \Drupal::entityManager()->getDefinition($config_type);
    $entity_storage = \Drupal::entityManager()->getStorage($config_type);
    $modules = $this->getModules();
    $class_name = get_class($this);

    if ($module_name == 'all') {
      foreach ($modules as $module) {
        $operations[] = [[$class_name, 'updateConfigEntity'], [$module, $entity_storage, $config_entity_type]];
        // $this->updateConfigEntity($module, $entity_storage, $config_entity_type);
      }
    }
    elseif (isset($modules[$module_name])) {
      $operations[] = [[$class_name, 'updateConfigEntity'], [$modules[$module_name], $entity_storage, $config_entity_type]];
      // $this->updateConfigEntity($modules[$module_name], $entity_storage, $config_entity_type);
    }

    if (!empty($operations)) {
      $batch = [
        'operations'    => $operations,
        'title'         => t('Refreshing configuration'),
        'progress_message' => '',
        'error_message' => t('Error refreshing/updating configuration'),
      ];
      batch_set($batch);
    }
  }

  /**
   * Refresh all config entity types.
   *
   * @param string $module_name
   *   The module name to refresh.
   */
  public function refreshByModule($module_name) {
    $extension = $this->getModules()[$module_name];
    foreach ($this->findConfigurationTypesByModule($module_name) as $config_entity_type_id => $config_entity_type) {
      $entity_storage = \Drupal::entityManager()->getStorage($config_entity_type->id());
      $this->updateConfigEntity($extension, $entity_storage, $config_entity_type);
    }
  }

  public function refreshById($module_name, $config_type, $config_id) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $config_entity_type */
    $config_entity_type = \Drupal::entityManager()->getDefinition($config_type);
    $entity_storage = \Drupal::entityManager()->getStorage($config_type);
    $modules = $this->getModules();
    $module = $modules[$module_name];
    $extension_path = $module->getPath();

    foreach ($this->folderTypes as $folder_type) {
      if (is_dir($extension_path . '/' . $folder_type)) {
        $install_storage = new FileStorage($extension_path . '/' . $folder_type);
        $loaded_entity = $entity_storage->load($config_id);
        $data = $loaded_entity->toArray();
        unset($data['uuid']);
        $config = new Config($config_entity_type->getConfigPrefix() . '.' . $config_id, $install_storage, \Drupal::service('event_dispatcher'), \Drupal::service('config.typed'));
        $config->setData($data)->save();
      }
    }
  }


  /**
   * @param Extension $module
   * @param EntityStorageInterface $storage
   * @param ConfigEntityTypeInterface $type
   */
  public function updateConfigEntity(Extension $module, EntityStorageInterface $storage, ConfigEntityTypeInterface $type) {
    $extension_path = $module->getPath();
    // If the extension provides configuration schema clear the definitions.
    foreach ($this->folderTypes as $folder_type) {
      if (is_dir($extension_path . '/' . $folder_type)) {
        $install_storage = new FileStorage($extension_path . '/' . $folder_type);
        $entities = $install_storage->listAll($type->getConfigPrefix());
        if (count($entities)) {
          // Ensure module is installed.
          \Drupal::service('module_installer')
            ->install([$module->getName()]);
        }
        foreach ($entities as $name) {
          $id = substr($name, strlen($type->getConfigPrefix()) + 1);
          $loaded_entity = $storage->load($id);
          $data = $loaded_entity->toArray();
          unset($data['uuid']);
          $config = new Config($name, $install_storage, \Drupal::service('event_dispatcher'), \Drupal::service('config.typed'));
          $config->setData($data)->save();
        }
      }

      if (FALSE) {
        $this->updateTestConfigEntity($extension_path, $module, $storage, $type);
      }
    }
  }

  /**
   * @param $module_name
   *
   * @return string[]
   *   The config entity type label keyed by ID.
   */
  public function findConfigurationTypesLabels($module_name) {
    return array_map(function (ConfigEntityTypeInterface $config_entity_type) {
      return $config_entity_type->getLabel();
    }, $this->findConfigurationTypesByModule($module_name));
  }

  /**
   * @param $module_name
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface[]
   *   The config entity type keyed by ID.
   */
  protected function findConfigurationTypesByModule($module_name) {
    $config_types = [];
    $modules = $this->getModules();
    $module = $modules[$module_name];
    $extension_path = $module->getPath();
    // If the extension provides configuration schema clear the definitions.
    foreach ($this->folderTypes as $folder_type) {
      if (is_dir($extension_path . '/' . $folder_type)) {
        $install_storage = new FileStorage($extension_path . '/' . $folder_type);
        $config_list = $install_storage->listAll();
        if (count($config_list)) {
          foreach ($config_list as $config_name) {
            // Handle config entities.
            if ($entity_type_id = $this->configManager->getEntityTypeIdByName($config_name)) {
              $entity_type = $this->configManager->getEntityManager()->getDefinition($entity_type_id);
              $config_types[$entity_type_id] = $entity_type;
            }
          }
        }
      }
    }
    return $config_types;
  }

  public function getEntityIds($module_name, $config_type) {
    $entity_ids = [];
    $modules = $this->getModules();
    $module = $modules[$module_name];
    $extension_path = $module->getPath();
    $definitions = $this->entityManager->getDefinitions();
    // If the extension provides configuration schema clear the definitions.
    foreach ($this->folderTypes as $folder_type) {
      if (is_dir($extension_path . '/' . $folder_type)) {
        $install_storage = new FileStorage($extension_path . '/' . $folder_type);
        $config_list = $install_storage->listAll();
        if (count($config_list)) {
          foreach ($config_list as $config_name) {
            // Handle config entities.
            if ($entity_type_id = $this->configManager->getEntityTypeIdByName($config_name)) {
              if ($entity_type_id == $config_type) {
                $id = substr($config_name, strlen($definitions[$entity_type_id]->getConfigPrefix()) + 1);
                $entity_ids[] = $id;
              }
            }
          }
        }
      }
    }
    return $entity_ids;
  }

  protected function getModules() {
    if (!isset($this->modules)) {
      $listing = new ExtensionDiscovery(\Drupal::root());
      $this->modules = $listing->scan('module') + $listing->scan('profile');
    }
    return $this->modules;
  }

  protected function updateTestConfigEntity($extension_path, Extension $module, EntityStorageInterface $entity_storage, EntityTypeInterface $config_entity_type) {
    if (is_dir($extension_path . '/test_views')) {
      // Ensure module is installed.
      \Drupal::service('module_installer')->install([$module->getName()]);
      $install_storage = new  FileStorage($extension_path . '/test_views');
      $entities = $install_storage->listAll($config_entity_type->getConfigPrefix());
      foreach ($entities as $name) {
        if (\Drupal::service('config.storage')->exists($name)) {
          $id = substr($name, strlen($config_entity_type->getConfigPrefix()) + 1);
          $entity_storage->load($id)->delete();
        }
        $entity = $entity_storage->createFromStorageRecord($install_storage->read($name));
        try {
          $entity->save();
        }
        catch (\Exception $e) {
          print "Saving $name has generated the following exception: " . $e->getMessage() . "\n";
          continue;
        }
        $data = $entity->toArray();
        unset($data['uuid']);
        $config = new Config($name, $install_storage, \Drupal::service('event_dispatcher'), \Drupal::service('config.typed'));
        $config->setData($data)->save();
      }
    }
  }

}
