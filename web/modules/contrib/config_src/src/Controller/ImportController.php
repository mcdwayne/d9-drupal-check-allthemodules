<?php

namespace Drupal\config_src\Controller;

use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\config\Form\ConfigSync;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Returns responses for config module routes.
 */
class ImportController implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The sync configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncStorage;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface;
   */
  protected $configManager;

  /**
   * The database lock object.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * Constructs a new ImportController.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   *   The source storage.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher used to notify subscribers of config import events.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend to ensure multiple imports do not occur at the same time.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(StorageInterface $sync_storage, StorageInterface $config_storage, RendererInterface $renderer, EventDispatcherInterface $event_dispatcher, ConfigManagerInterface $config_manager, LockBackendInterface $lock, TypedConfigManagerInterface $typed_config, ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, ThemeHandlerInterface $theme_handler) {
    $this->syncStorage = $sync_storage;
    $this->configStorage = $config_storage;
    $this->renderer = $renderer;

    // Services necessary for \Drupal\Core\Config\ConfigImporter.
    $this->eventDispatcher = $event_dispatcher;
    $this->configManager = $config_manager;
    $this->lock = $lock;
    $this->typedConfigManager = $typed_config;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage.sync'),
      $container->get('config.storage'),
      $container->get('renderer'),
      $container->get('event_dispatcher'),
      $container->get('config.manager'),
      $container->get('lock.persistent'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('theme_handler')
    );
  }

  /**
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   *   The source storage.
   */
  private function setSyncStorage(StorageInterface $sync_storage) {
    $this->syncStorage = $sync_storage;
  }

  /**
   * Configuration single import.
   *
   * @param string $config_source
   *   Configuration source.
   * @param string $config_name
   *   Configuration name.
   */
  public function configImport($config_source, $config_name) {
    $this->configCollectionImport($config_source, $config_name, StorageInterface::DEFAULT_COLLECTION);
  }

  /**
   * Configuration single import from collection.
   *
   * @param string $config_source
   *   Configuration source.
   * @param string $config_name
   *   Configuration name.
   * @param string $collection
   *   Configuration collection name.
   */
  public function configCollectionImport($config_source, $config_name, $collection = '') {
    $sync_storage = new FileStorage($GLOBALS['config_directories'][$config_source]);
    $this->setSyncStorage($sync_storage);

    $config_data = [];
    if ($collection == StorageInterface::DEFAULT_COLLECTION) {
      $config_storage = $this->syncStorage;
    }
    else {
      $config_storage = new FileStorage($GLOBALS['config_directories'][$config_source], $collection);
    }
    $config_data[$collection][$config_name] = $config_storage->read($config_name);

    if (!empty($config_data)) {
      // TODO Add more collection.
      $source_storage = new StorageReplaceDataWrapper($this->syncStorage);
      $source_storage->replaceData($config_name, $config_data[$collection][$config_name]);

      $storage_comparer = new StorageComparer(
        $source_storage,
        $this->configStorage,
        $this->configManager
      );

      if (!$storage_comparer->createChangelist()->hasChanges()) {
        drupal_set_message(t('There are no changes to import.'), 'error');
      }
      else {
        $config_selected_importer = new ConfigImporter(
          $storage_comparer,
          $this->eventDispatcher,
          $this->configManager,
          $this->lock,
          $this->typedConfigManager,
          $this->moduleHandler,
          $this->moduleInstaller,
          $this->themeHandler,
          $this->getStringTranslation()
        );

        try {
          $config_selected_importer->validate();

          if ($config_selected_importer->alreadyImporting()) {
            drupal_set_message(t('Another request may be importing configuration already.'), 'error');
          }
          else {
            try {
              $sync_steps = $config_selected_importer->initialize();
              $batch = [
                'operations' => [],
                'finished' => [ConfigSync::class, 'finishBatch'],
                'title' => t('Importing configuration'),
                'init_message' => t('Starting configuration import.'),
                'progress_message' => t('Completed @current step of @total.'),
                'error_message' => t('Configuration import has encountered an error.'),
              ];
              foreach ($sync_steps as $sync_step) {
                $batch['operations'][] = [[ConfigSync::class, 'processBatch'], [$config_selected_importer, $sync_step]];
              }

              batch_set($batch);

              $redirect_options = array(
                'query' => ['config_source' => $config_source],
              );
              $redirect = Url::fromRoute('config.sync', array(), $redirect_options)->toString();
              batch_process($redirect);

              $batch =& batch_get();
              if (!empty($batch) && isset($batch['url']) && !empty($batch['url'])) {
                $response = new RedirectResponse($batch['url']->toString());
                $response->send();
                return;
              }
            }
            catch (ConfigImporterException $e) {
              // There are validation errors.
              drupal_set_message(t('The configuration import failed for the following reasons:'), 'error');
              foreach ($config_selected_importer->getErrors() as $message) {
                drupal_set_message($message, 'error');
              }
            }
          }
        }
        catch (ConfigImporterException $e) {
          // There are validation errors.
          $item_list = [
            '#theme' => 'item_list',
            '#items' => $config_selected_importer->getErrors(),
            '#title' => $this->t(
              'The configuration cannot be imported because it failed validation for the following reasons:'
            ),
          ];
          drupal_set_message($this->renderer->render($item_list), 'error');
        }
      }
    }

    return new RedirectResponse(Url::fromRoute('config.sync')->toString());
  }

}
