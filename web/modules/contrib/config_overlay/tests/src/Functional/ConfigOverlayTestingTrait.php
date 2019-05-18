<?php

namespace Drupal\Tests\config_overlay\Functional;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\user\RoleInterface;

/**
 * Provides a trait for functional Config Overlay tests.
 *
 * This should only be used by tests extending BrowserTestBase.
 *
 * Classes using this should install the Config Overlay module and declare a
 * $collections property.
 *
 * @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestingTest::$collections
 */
trait ConfigOverlayTestingTrait {

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The configuration synchronization storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configSyncStorage;

  /**
   * The configuration synchronization directory.
   *
   * @var string
   */
  protected $configSyncDirectory;

  /**
   * The serializer.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->configStorage = $this->container->get('config.storage');
    $this->configSyncStorage = $this->container->get('config.storage.sync');
    $this->configSyncDirectory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
    $this->serializer = $this->container->get('serialization.yaml');
    $this->moduleHandler = $this->container->get('module_handler');
    $this->configManager = $this->container->get('config.manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests configuration export after profile installation.
   *
   * @throws \Exception
   */
  public function testConfigExport() {
    $this->doTestInitialConfig();

    // Recreate the menu with the same values (but for the UUID). This will not
    // be detected as a change.
    $menu_storage = $this->entityTypeManager->getStorage('menu');
    /* @var \Drupal\system\MenuInterface $initial_menu */
    $initial_menu = $menu_storage->load('account');
    $recreated_menu = $this->doTestRecreateInitial($initial_menu);

    $this->assertEquals('User account menu', $recreated_menu->label());
    $this->doTestEdit($recreated_menu);
    // Edit the menu again, to make sure that this is detected as an update.
    $this->doTestEdit($recreated_menu);
    $this->doTestRecreateAgain($recreated_menu);
  }

  /**
   * Checks that the configuration export is correct after installation.
   */
  protected function doTestInitialConfig() {
    $uris = $this->exportConfig();
    foreach ($this->getExpectedConfig() as $collection => $all_config) {
      $this->assertArrayHasKey($collection, $uris);

      foreach ($all_config as $name => $config) {
        $this->assertArrayHasKey($name, $uris[$collection]);

        $uri = $uris[$collection][$name];
        $this->assertSame($config, $this->readConfigFile($uri), "Collection: {$collection}, Configuration name: {$name}");

        unset($uris[$collection][$name]);
      }

      // This is functionally equivalent to Inspector::assertNotEmpty(), but
      // yields a more expressive error message in case of failure.
      foreach ($uris[$collection] as $name => $uri) {
        $this->assertSame([], $this->readConfigFile($uri), "Collection: {$collection}, Unexpected configuration: {$name}");
      }

      unset($uris[$collection]);
    }

    // This is functionally equivalent to Inspector::assertNotEmpty(), but
    // yields a more expressive error message in case of failure.
    foreach ($uris as $collection => $all_config) {
      $this->assertSame([], array_keys($all_config), 'Unexpected collection: ' . $collection);
    }

    // Make sure that the configuration storage comparer detects no changes.
    $this->assertConfigStorageChanges();
  }

  /**
   * Tests recreating a shipped configuration entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $initial_entity
   *   The entity to recreate.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The recreated entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doTestRecreateInitial(ConfigEntityInterface $initial_entity) {
    // Change some configuration and make sure that it is detected correctly.
    $config_name = $initial_entity->getConfigDependencyName();
    $this->assertExportNotHasConfig($config_name);

    $deleted_config = [];
    /* @see block_menu_delete() */
    if ($initial_entity->getEntityTypeId() === 'menu' && $this->moduleHandler->moduleExists('block')) {
      $block_storage = $this->entityTypeManager->getStorage('block');
      $block_ids = $block_storage
        ->getQuery()
        ->condition('plugin', 'system_menu_block:' . $initial_entity->id())
        ->execute();

      $deleted_config = [];
      foreach ($block_ids as $block_id) {
        $deleted_config[] = "block.block.$block_id";
      }
    }

    $recreated_entity = $this->recreateEntity($initial_entity);

    $this->assertConfigStorageChanges([], [], $deleted_config);

    $this->assertExportNotHasConfig($config_name);

    return $recreated_entity;
  }

  /**
   * Tests editing a configuration entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity to edit.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doTestEdit(ConfigEntityInterface $entity) {
    // Edit the menu, so that it will be exported to the synchronization
    // directory.
    $label_key = $entity->getEntityType()->getKey('label');
    $entity
      ->set($label_key, $entity->label() . ' EDITED')
      ->save();

    $config_name = $entity->getConfigDependencyName();
    $this->assertConfigStorageChanges([], [$config_name]);
    $uris = $this->assertExportHasConfig($config_name);
    $config = $this->readConfigFile($uris[StorageInterface::DEFAULT_COLLECTION][$config_name]);
    $this->assertArrayHasKey($label_key, $config);
    $this->assertEquals($entity->label(), $config[$label_key]);
    $this->assertArrayHasKey('uuid', $config);
  }

  /**
   * Tests recreating a non-shipped configuration entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $recreated_entity
   *   The entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doTestRecreateAgain(ConfigEntityInterface $recreated_entity) {
    // Recreate the menu again with the same values (but for the UUID). Since
    // the menu has been exported with its UUID, this will now be detected as
    // a recreation.
    $this->recreateEntity($recreated_entity);

    $config_name = $recreated_entity->getConfigDependencyName();
    $this->assertConfigStorageChanges([$config_name], [], [$config_name]);
    $uris = $this->assertExportHasConfig($config_name);
    $rerecreated_menu_config = $this->readConfigFile($uris[StorageInterface::DEFAULT_COLLECTION][$config_name]);
    $this->assertArrayHasKey('uuid', $rerecreated_menu_config);
    $this->assertNotEquals($recreated_entity->uuid(), $rerecreated_menu_config['uuid']);
    $this->assertArrayHasKey('id', $rerecreated_menu_config);
    $this->assertEquals($recreated_entity->id(), $rerecreated_menu_config['id']);
  }

  /**
   * Exports configuration for all collections and returns the exported files.
   *
   * @return string[][]
   *   An nested array where the keys are the collection names and the values
   *   are mappings of configuration names to the respective URIs of the
   *   configuration files.
   */
  protected function exportConfig() {
    $comparer = new StorageComparer($this->configStorage, $this->configSyncStorage);
    $comparer->createChangelist();
    foreach ($comparer->getAllCollectionNames() as $collection) {
      $active_storage = $this->configStorage->createCollection($collection);
      $sync_storage = $this->configSyncStorage->createCollection($collection);

      foreach ($comparer->getChangelist('delete', $collection) as $config_name_to_delete) {
        $sync_storage->delete($config_name_to_delete);
      }

      foreach ($comparer->getChangelist('rename', $collection) as $rename) {
        list($old_name, $new_name) = explode('::', $rename);
        $sync_storage->rename($old_name, $new_name);
      }

      $config_names_to_write = array_merge(
        $comparer->getChangelist('create', $collection),
        $comparer->getChangelist('update', $collection)
      );
      $active_config = $active_storage->readMultiple($config_names_to_write);
      foreach ($config_names_to_write as $config_name_to_write) {
        $sync_storage->write($config_name_to_write, $active_config[$config_name_to_write]);
      }
    }

    $extension = $this->serializer->getFileExtension();
    $files = file_scan_directory($this->configSyncDirectory, "/.\.$extension$/");

    // Build a list of URIs per configuration name and per collection.
    $uris = [];
    foreach ($files as $uri => $file) {
      $path = substr($uri, strlen($this->configSyncDirectory . '/'));
      if (strpos($path, '/') === FALSE) {
        $collection = StorageInterface::DEFAULT_COLLECTION;
      }
      else {
        $parts = explode('/', $path);
        array_pop($parts);
        $collection = implode('.', $parts);
      }

      $uris += [$collection => []];
      $uris[$collection][$file->name] = $file->uri;
    }
    return $uris;
  }

  /**
   * Recreates a given configuration entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The recreated entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function recreateEntity(ConfigEntityInterface $entity) {
    $entity->delete();
    $entity_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $values = $entity->toArray();
    unset($values['uuid']);
    $recreated_entity = $entity_storage->create($values);
    $recreated_entity->save();
    return $recreated_entity;
  }

  /**
   * Gets an array of expected configuration that will be exported.
   *
   * Due to ExtensionConfigFilter this should only contain files that have
   * overridden configuration. For those it should contain the entire
   * configuration file.
   *
   * @return array[]
   *   An array of expected configuration where the keys are the configuration
   *   names and the values are arrays which contain the expected configuration.
   *
   * @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestingTest::getOverriddenConfig()
   * @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestingTest::getUri()
   */
  protected function getExpectedConfig() {
    $all_overridden_config = $this->getOverriddenConfig();

    $initial_config = [];
    foreach ($all_overridden_config as $collection => $overridden_config) {
      $initial_config[$collection] = [];

      // Read the initial config for all configuration that is overridden and
      // merge in the overridden values below.
      foreach (array_keys($overridden_config) as $config_name) {
        if ($uri = $this->getUri($collection, $config_name)) {
          $data = $this->readConfigFile($uri);
          $initial_config[$collection][$config_name] = $this->processConfigData($config_name, $data);
        }
      }
    }

    return NestedArray::mergeDeepArray([$initial_config, $all_overridden_config], TRUE);
  }

  /**
   * Returns the overridden configuration for this test.
   *
   * @return array[]
   *   An array of overridden configuration where the keys are the configuration
   *   names and the values are arrays which contain the overridden portions of
   *   the configuration.
   */
  protected function getOverriddenConfig() {
    $site_config = $this->configStorage->read('system.site');

    $overridden_config[StorageInterface::DEFAULT_COLLECTION] = [];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension'] = [
      'module' => [
        'config_filter' => 0,
        'config_overlay' => 0,
        'dynamic_page_cache' => 0,
        'page_cache' => 0,
        'system' => 0,
        'user' => 0,
        $this->profile => 1000,
      ],
      'theme' => [
        'stable' => 0,
        'classy' => 0,
      ],
      'profile' => $this->profile,
    ];
    /* @see install_base_system() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.file'] = [
      'path' => [
        'temporary' => $this->tempFilesDirectory,
      ],
    ];
    /* @see \Drupal\Core\Test\FunctionalTestSetupTrait::initConfig() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.date'] = [
      'timezone' => [
        'default' => 'Australia/Sydney',
      ],
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.logging'] = [
      'error_level' => ERROR_REPORTING_DISPLAY_VERBOSE,
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.mail'] = [
      'interface' => [
        'default' => 'test_mail_collector',
      ],
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.performance'] = [
      'css' => [
        'preprocess' => FALSE,
      ],
      'js' => [
        'preprocess' => FALSE,
      ],
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site'] = [
      'uuid' => $site_config['uuid'],
      /* @see \Drupal\Core\Test\FunctionalTestSetupTrait::installParameters() */
      'name' => 'Drupal',
      'mail' => 'simpletest@example.com',
    ];

    // Account for various install-time configuration modifications of modules.
    $extension_config = $this->configStorage->read('core.extension');
    /* @see locale_install() */
    if (isset($extension_config['module']['locale'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['locale.settings']['translation']['path'] = $this->siteDirectory . '/files/translations';
    }
    /* @see shortcut_themes_installed() */
    if (isset($extension_config['module']['shortcut'], $extension_config['theme']['seven'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['seven.settings'] = [
        'third_party_settings' => [
          'shortcut' => ['module_link' => TRUE],
        ],
      ];
    }
    /* @see user_user_role_insert() */
    $roles = array_diff($this->configStorage->listAll('user.role.'), ['user.role.' . RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID]);
    foreach ($roles as $config_name) {
      $role_id = substr($config_name, strlen('user.role.'));
      if (in_array($role_id, [RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID], TRUE)) {
        continue;
      }

      $label = $this->configStorage->read($config_name)['label'];
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]["system.action.user_add_role_action.$role_id"] = $this->processConfigEntityData("system.action.user_add_role_action.$role_id", [
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [
          'config' => ["user.role.$role_id"],
          'module' => ['user'],
        ],
        'id' => "user_add_role_action.$role_id",
        'label' => "Add the $label role to the selected user(s)",
        'type' => 'user',
        'plugin' => 'user_add_role_action',
        'configuration' => [
          'rid' => $role_id,
        ],
      ]);
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]["system.action.user_remove_role_action.$role_id"] = $this->processConfigEntityData("system.action.user_remove_role_action.$role_id", [
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [
          'config' => ["user.role.$role_id"],
          'module' => ['user'],
        ],
        'id' => "user_remove_role_action.$role_id",
        'label' => "Remove the $label role from the selected user(s)",
        'type' => 'user',
        'plugin' => 'user_remove_role_action',
        'configuration' => [
          'rid' => $role_id,
        ],
      ]);
    }

    // Account for shipped configuration that does not match the imported state.
    // @todo Fix this in https://www.drupal.org/project/drupal/issues/2989007
    if (isset($extension_config['module']['block_content'], $extension_config['module']['views'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['views.view.block_content'] = [
        'display' => [
          'default' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
          'page_1' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
        ],
      ];
    }
    if (isset($extension_config['module']['content_moderation'], $extension_config['module']['node'], $extension_config['module']['views'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['views.view.moderated_content'] = [
        'display' => [
          'default' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
              'tags' => ['config:workflow_list'],
            ],
          ],
          'moderated_content' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
              'tags' => ['config:workflow_list'],
            ],
          ],
        ],
      ];
    }
    if (isset($extension_config['module']['dblog'], $extension_config['module']['views'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['views.view.watchdog'] = [
        'display' => [
          'default' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
          'page' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
        ],
      ];
    }
    if (isset($extension_config['module']['demo_umami_tour'], $extension_config['module']['tour'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['tour.tour.umami-front'] = [
        'dependencies' => [
          'module' => ['demo_umami_tour'],
        ],
      ];
    }
    if (isset($extension_config['module']['file'], $extension_config['module']['views'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['views.view.files'] = [
        'display' => [
          'default' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
          'page_1' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
          'page_2' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
        ],
      ];
    }
    if (isset($extension_config['module']['image'], $extension_config['module']['media'], $extension_config['module']['views']) && $this->configStorage->exists('image.style.thumbnail')) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['views.view.media'] = [
        'display' => [
          'default' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
          'media_page_list' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
        ],
      ];
    }
    if (isset($extension_config['module']['language'], $extension_config['module']['tour'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['tour.tour.language'] = [
        'dependencies' => [
          'module' => ['language'],
        ],
      ];
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['tour.tour.language-add'] = [
        'dependencies' => [
          'module' => ['language'],
        ],
      ];
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['tour.tour.language-edit'] = [
        'dependencies' => [
          'module' => ['language'],
        ],
      ];
    }
    if (isset($extension_config['module']['node'], $extension_config['module']['views']) && $this->configStorage->exists('system.menu.main')) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['views.view.glossary'] = [
        'display' => [
          'default' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
          'attachment_1' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
          'page_1' => [
            'cache_metadata' => [
              'max-age' => CacheBackendInterface::CACHE_PERMANENT,
            ],
          ],
        ],
      ];
    }


    return $overridden_config;
  }

  /**
   * Gets the file URI for the given configuration name.
   *
   * @param string $collection
   *   The collection of the configuration object.
   * @param string $config_name
   *   The configuration name to return the file URI for.
   *
   * @return string|false
   *   The file URI for the given configuration, or FALSE if no configuration
   *   file was found.
   */
  protected function getUri($collection, $config_name) {
    if ($collection === StorageInterface::DEFAULT_COLLECTION) {
      $collection_directory = '';
    }
    else {
      $collection_directory = str_replace('.', '/', $collection) . '/';
    }

    $extension = $this->serializer->getFileExtension();

    // Reverse the list of directories so that the profile directory comes first
    // so that any profile-provided configuration will be used instead of
    // the respective module-provided configuration.
    $directories = array_reverse($this->moduleHandler->getModuleDirectories());
    $directories[] = $this->root . '/core';

    foreach ($directories as $directory) {
      foreach (['install', 'optional'] as $subdirectory) {
        $uri = "{$directory}/config/{$subdirectory}/{$collection_directory}{$config_name}.{$extension}";
        if (file_exists($uri)) {
          return $uri;
        }
      }
    }

    return FALSE;
  }

  /**
   * Reads a single configuration file.
   *
   * @param string $uri
   *   The URI of the configuration file.
   *
   * @return array|null
   *   An array of configuration data contained in the file or NULL if the file
   *   is empty.
   */
  protected function readConfigFile($uri) {
    return $this->serializer->decode(file_get_contents($uri));
  }

  /**
   * Processes configuration data so that it matches the exported state.
   *
   * @param string $config_name
   *   The configuration name.
   * @param array $data
   *   The configuration data.
   *
   * @return array
   *   The processed configuration data.
   */
  protected function processConfigData($config_name, array $data) {
    // Add the default config hash.
    /* @see \Drupal\Core\Config\ConfigInstaller::createConfiguration() */
    $data['_core'] = ['default_config_hash' => Crypt::hashBase64(serialize($data))];
    if ($this->configManager->getEntityTypeIdByName($config_name)) {
      $data = $this->processConfigEntityData($config_name, $data);
    }
    return $data;
  }

  /**
   * Processes configuration entity data so that it matches the exported state.
   *
   * @param string $config_name
   *   The configuration name.
   * @param array $data
   *   The configuration entity data.
   *
   * @return array
   *   The processed configuration entity data.
   */
  protected function processConfigEntityData($config_name, array $data) {
    // Add the UUID from the active configuration.
    $top_data = ['uuid' => $this->configStorage->read($config_name)['uuid']];

    // Configuration entities are ordered in a particular way when
    // exported, so we need to recreate that here.
    $top_properties = [
      'langcode',
      'status',
      'dependencies',
      'third_party_settings',
      '_core',
    ];
    foreach ($top_properties as $property) {
      if (isset($data[$property])) {
        $top_data[$property] = $data[$property];
      }
    }
    $data = $top_data + $data;

    // Account for shipped configuration that does not match the imported state.
    // @todo Fix this in https://www.drupal.org/project/drupal/issues/2989007
    if ($config_name === 'views.view.media') {
      $data['dependencies'] = ['config' => ['image.style.thumbnail']] + $data['dependencies'];
    }
    if ($config_name === 'views.view.moderated_content') {
      $data['dependencies'] = ['config' => ['workflows.workflow.editorial']] + $data['dependencies'];
    }

    return $data;
  }

  /**
   * Reads an optional configuration file from a module.
   *
   * @param string $module_name
   *   The name of the module with the optional configuration.
   * @param string $config_name
   *   The configuration name.
   *
   * @return array
   *   An array of configuration data.
   */
  protected function readOptionalConfig($module_name, $config_name) {
    $module = $this->moduleHandler->getModule($module_name);
    $module_path = $this->root . '/' . $module->getPath();

    $data = $this->readConfigFile("$module_path/config/optional/$config_name.yml");
    return $this->processConfigData($config_name, $data);
  }

  /**
   * Asserts configuration storage changes.
   *
   * @param array $create
   *   An array of names of created configuration.
   * @param array $update
   *   An array of names of updated configuration.
   * @param array $delete
   *   An array of names of deleted configuration.
   * @param array $rename
   *   An array of renamed configuration in the format "old_name::new_name".
   */
  protected function assertConfigStorageChanges(array $create = [], array $update = [], array $delete = [], array $rename = []) {
    $expected = [
      'create' => $create,
      'update' => $update,
      'delete' => $delete,
      'rename' => $rename,
    ];
    $comparer = new StorageComparer($this->configStorage, $this->configSyncStorage);
    $this->assertEquals($expected, $comparer->createChangelist()->getChangelist());
  }

  /**
   * Asserts that a configuration name is among the exported files.
   *
   * @param string $config_name
   *   The name of the configuration object.
   * @param array $collections
   *   (optional) The list of collections that the configuration should be
   *   present in. Defaults to only the default collection.
   *
   * @return string[][]
   *   An nested array where the keys are the collection names and the values
   *   are mappings of configuration names to the respective URIs of the
   *   configuration files.
   */
  protected function assertExportHasConfig($config_name, array $collections = [StorageInterface::DEFAULT_COLLECTION]) {
    $uris = $this->exportConfig();
    $this->assertCount(count($this->collections), $uris);
    foreach ($collections as $collection) {
      $this->assertArrayHasKey($collection, $uris);
      $this->assertArrayHasKey($config_name, $uris[$collection]);
    }
    foreach (array_diff($this->collections, [$collection]) as $other_collection) {
      $this->assertArrayHasKey($other_collection, $uris);
      $this->assertArrayNotHasKey($config_name, $uris[$other_collection]);
    }

    return $uris;
  }

  /**
   * Asserts that a configuration name is not among the exported files.
   *
   * @param string $config_name
   *   The name of the configuration object.
   *
   * @return string[][]
   *   An nested array where the keys are the collection names and the values
   *   are mappings of configuration names to the respective URIs of the
   *   configuration files.
   */
  protected function assertExportNotHasConfig($config_name) {
    $uris = $this->exportConfig();
    $this->assertCount(count($this->collections), $uris);
    foreach ($this->collections as $collection) {
      $this->assertArrayHasKey($collection, $uris);
      $this->assertArrayNotHasKey($config_name, $uris[$collection]);
    }

    return $uris;
  }

}
