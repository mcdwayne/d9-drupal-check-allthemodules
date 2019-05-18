<?php

namespace Drupal\config_actions_provider\Plugin\ConfigProvider;

use Drupal\config_actions\ConfigActionsServiceInterface;
use Drupal\config_actions_provider\Plugin\ConfigActionsSource\ConfigActionsProvider;
use Drupal\config_provider\InMemoryStorage;
use Drupal\config_provider\Plugin\ConfigCollectorInterface;
use Drupal\config_provider\Plugin\ConfigProviderBase;
use Drupal\config_sync\Plugin\SyncConfigProviderInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for providing configuration actions.
 *
 * @ConfigProvider(
 *   id = \Drupal\config_actions_provider\Plugin\ConfigProvider\ConfigProviderActions::ID,
 *   weight = 100,
 *   label = @Translation("Actions"),
 *   description = @Translation("Actions to be applied to configuration provided by other modules and themes."),
 * )
 */
class ConfigProviderActions extends ConfigProviderBase implements ContainerFactoryPluginInterface, SyncConfigProviderInterface {

  /**
   * The configuration provider ID.
   */
  const ID = 'config/actions';

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The config actions service.
   *
   * @var \Drupal\config_actions\ConfigActionsServiceInterface
   */
  protected $configActionsService;

  /**
   * The configuration collector.
   *
   * @var \Drupal\config_provider\Plugin\ConfigCollectorInterface
   */
  protected $configCollector;

  /**
   * Constructs a new ConfitProviderActions.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param ConfigActionsServiceInterface $config_actions_service
   *   The ConfigActionsService from the container.
   * @param \Drupal\config_provider\Plugin\ConfigCollectorInterface $config_collector
   *   The config collector.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, ModuleExtensionList $module_extension_list, ConfigManagerInterface $config_manager, ConfigActionsServiceInterface $config_actions_service, ConfigCollectorInterface $config_collector) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->moduleExtensionList = $module_extension_list;
    $this->configManager = $config_manager;
    $this->configActionsService = $config_actions_service;
    $this->configCollector = $config_collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('extension.list.module'),
      $container->get('config.manager'),
      $container->get('config_actions'),
      $container->get('config_provider.collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function providesFullConfig() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function addConfigToCreate(array &$config_to_create, StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []) {
    // No action needed as config_actions already acts on extension install.
  }

  /**
   * {@inheritdoc}
   */
  public function addInstallableConfig(array $extensions = []) {
    // We ignore the incoming list of $extensions because a given item may be
    // modified by any module's actions.
    $module_list = array_keys($this->moduleHandler->getModuleList());
    $this->doActions($module_list);
  }

  /**
   * {@inheritdoc}
   */
  public function alterConfigSnapshot(StorageInterface $snapshot_storage, array $extensions = []) {
    // If no extensions were specified, get all installed modules and the
    // install profile.
    if (empty($extensions)) {
      $extensions = $this->moduleHandler->getModuleList();
    }
    // Otherwise, filter out extensions that are not modules or profiles.
    else {
      $extensions = array_filter($extensions, function (Extension $extension) {
        return in_array($extension->getType(), ['module', 'profile']);
      });
      // If there is no specified module or install profile, bail.
      if (empty($extensions)) {
        return;
      }
    }
    $module_list = array_keys($extensions);

    // Cache the configuration storage as passed in.
    $cached_config = clone $this->providerStorage;

    // The extensions may have actions to apply to configuration items supplied
    // by other modules.

    // Set the provider storage to the current state of the snapshot storage.
    $this->configManager->createSnapshot($snapshot_storage, $this->providerStorage);

    // Run actions for the specified modules.
    $this->doActions($module_list);

    // Set up a storage comparer.
    $storage_comparer = new StorageComparer(
      $this->providerStorage,
      $snapshot_storage,
      $this->configManager
    );

    // If the values changed, that was the result of an action.
    if ($storage_comparer->createChangelist()->hasChanges()) {
      foreach ($storage_comparer->getAllCollectionNames() as $collection) {
        $changelist = $storage_comparer->getChangelist(NULL, $collection);
        // We're only concerned with updates.
        foreach ($changelist['update'] as $item_name) {
          // Switch collections if necessary.
          if ($collection !== $this->providerStorage->getCollectionName()) {
            $this->providerStorage = $this->providerStorage->createCollection($collection);
          }
          if ($collection !== $snapshot_storage->getCollectionName()) {
            $snapshot_storage = $snapshot_storage->createCollection($collection);
          }
          // Read the changed value and write it to the snapshot storage.
          $changed_value = $this->providerStorage->read($item_name);
          $snapshot_storage->write($item_name, $changed_value);
        }
      }
    }

    // Restore the previous state of the provider storage.
    $this->configManager->createSnapshot($cached_config, $this->providerStorage);
  }

  /**
   * Runs actions for a set of modules.
   *
   * @param string[] $module_list
   *   An array of module names.
   */
  protected function doActions(array $module_list) {
    $module_list = $this->listModulesInDependencyOrder($module_list);

    // Config actions apply whether or not a config item exists. We need to
    // limit our actions to provided items.

    $pre_actions_items = $this->providerStorage->listAll();

    // Run actions in module dependency order. It is safe to call
    // ::importAction() on a module that doesn't provide actions.
    foreach ($module_list as $module_name) {
      // We don't need to pass a storage as it's already injected into the
      // ConfigActionsProvider.
      $this->configActionsService->importAction($module_name, '', '', ['source_type' => ConfigActionsProvider::ID]);
    }

    // Remove any items that didn't already exist.
    // @todo: remove if
    // https://www.drupal.org/project/config_actions/issues/2989278 lands.
    $post_actions_items = $this->providerStorage->listAll();
    $added_items = array_diff($post_actions_items, $pre_actions_items);
    foreach ($added_items as $config_name) {
      $this->providerStorage->delete($config_name);
    }
  }

  /**
   * Returns a list of specified modules sorted in order of dependency.
   *
   * @param string[] $module_list
   *   An array of module names.
   *
   * @return string[]
   *   An array of module names.
   */
  protected function listModulesInDependencyOrder($module_list) {
    $module_list = array_combine($module_list, $module_list);

    // Get a list of modules with dependency weights as values.
    $module_data = $this->moduleExtensionList->getList();
    // Set the actual module weights.
    $module_list = array_map(function ($module) use ($module_data) {
      return $module_data[$module]->sort;
    }, $module_list);

    // Sort the module list by their weights (reverse).
    arsort($module_list);
    return array_keys($module_list);
  }
}
