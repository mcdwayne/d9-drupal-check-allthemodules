<?php

namespace Drupal\remote_config_sync\Controller;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SyncController.
 */
class SyncController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The sync configuration.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncStorage;

  /**
   * The active configuration.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The snapshot configuration.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $snapshotStorage;

  /**
   * The database lock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Event dispatcher.
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
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The string translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * SyncController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   * @param \Drupal\Core\Config\StorageInterface $snapshot_storage
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   */
  public function __construct(ConfigFactoryInterface $config_factory, StorageInterface $sync_storage, StorageInterface $active_storage, StorageInterface $snapshot_storage, LockBackendInterface $lock, EventDispatcherInterface $event_dispatcher, ConfigManagerInterface $config_manager, TypedConfigManagerInterface $typed_config, ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, ThemeHandlerInterface $theme_handler, TranslationManager $string_translation) {
    $this->configFactory = $config_factory;
    $this->syncStorage = $sync_storage;
    $this->activeStorage = $active_storage;
    $this->snapshotStorage = $snapshot_storage;
    $this->lock = $lock;
    $this->eventDispatcher = $event_dispatcher;
    $this->configManager = $config_manager;
    $this->typedConfigManager = $typed_config;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->themeHandler = $theme_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.storage.sync'),
      $container->get('config.storage'),
      $container->get('config.storage.snapshot'),
      $container->get('lock.persistent'),
      $container->get('event_dispatcher'),
      $container->get('config.manager'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('theme_handler'),
      $container->get('string_translation')
    );
  }

  /**
   * Get POST data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function post() {
    $token = \Drupal::request()->headers->get('token');
    $hash = \Drupal::request()->headers->get('hash');
    $import = \Drupal::request()->headers->get('import');

    $config = $this->configFactory->get('remote_config_sync.settings');

    if ($config->get('token') != $token) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Invalid token. Please check your remote site token.'),
      ]);
    }

    $result = $this->extractConfigArchive($hash);

    if (!$import) {
      return $result;
    }

    return $this->importConfig();
  }

  /**
   * Get the configuration archive from POST and extract all files from it.
   *
   * @param string $hash
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  protected function extractConfigArchive($hash) {
    $file = file_get_contents('php://input');
    $file_path = file_directory_temp() . '/remote_config_sync.tar.gz';
    file_unmanaged_delete($file_path);
    file_put_contents($file_path, $file);

    if (!file_exists($file_path)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Configuration archive not found.'),
      ]);
    }

    if (hash_file('md5', $file_path) != $hash) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Configuration archive integrity check fail.'),
      ]);
    }

    $this->syncStorage->deleteAll();
    $archiver = new ArchiveTar($file_path, 'gz');
    $files = [];
    foreach ($archiver->listContent() as $file) {
      $files[] = $file['filename'];
    }
    $archiver->extractList($files, config_get_config_directory(CONFIG_SYNC_DIRECTORY));

    // Export the current Remote Config Sync configuration.
    file_put_contents(
      config_get_config_directory(CONFIG_SYNC_DIRECTORY) . '/remote_config_sync.settings.yml',
      Yaml::encode(
        $this->configManager->getConfigFactory()->get('remote_config_sync.settings')->getRawData()
      )
    );

    return new JsonResponse([
      'status' => 'status',
      'message' => $this->t('Configuration successfully pushed.'),
      'host' => \Drupal::request()->getSchemeAndHttpHost(),
    ]);
  }

  /**
   * Import all configuration files.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  protected function importConfig() {
    $storage_comparer = new StorageComparer($this->syncStorage, $this->activeStorage, $this->configManager);
    $storage_comparer->createChangelist();

    $config_importer = new ConfigImporter(
      $storage_comparer,
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->typedConfigManager,
      $this->moduleHandler,
      $this->moduleInstaller,
      $this->themeHandler,
      $this->stringTranslation
    );

    if ($config_importer->alreadyImporting()) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Another request may be synchronizing the configuration already.'),
      ]);
    }

    try {
      $config_importer->import();
      return new JsonResponse([
        'status' => 'status',
        'message' => $this->t('Configuration pushed and imported successfully.'),
        'host' => \Drupal::request()->getSchemeAndHttpHost(),
      ]);
    }
    catch (ConfigImporterException $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Configuration import error') . ': ' . $e->getMessage(),
      ]);
    }
  }

}
