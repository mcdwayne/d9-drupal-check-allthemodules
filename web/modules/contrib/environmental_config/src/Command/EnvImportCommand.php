<?php

namespace Drupal\environmental_config\Command;

use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\environmental_config\EnvironmentDetectorManager;
use Drupal\environmental_config\TmpConfigFolderManager;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class EnvImportCommand.
 *
 * @package Drupal\environmental_config
 */
class EnvImportCommand extends Command {

  const URL_PLUGIN = 'customfile';

  use CommandTrait;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\CachedStorage
   */
  protected $configStorage;

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The folder manager.
   *
   * @var mixed
   */
  protected $folderManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The database lock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The environment detector magaer.
   *
   * @var \Drupal\environmental_config\EnvironmentDetectorManager
   */
  protected $environmentDetectorManager;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $configTyped;

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
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * EnvImportCommand constructor.
   *
   * @param \Drupal\Core\Config\CachedStorage $configStorage
   *   The config storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   * @param \Drupal\environmental_config\TmpConfigFolderManager $folderManager
   *   The folder manager.
   * @param \Drupal\environmental_config\EnvironmentDetectorManager $environmentDetectorManager
   *   The environment detector manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Lock\LockBackendInterface $lockBackend
   *   The lock backend.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $configTyped
   *   The config typed.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translationManager
   *   The translation manager.
   */
  public function __construct(CachedStorage $configStorage,
                              ConfigManagerInterface $configManager,
                              TmpConfigFolderManager $folderManager,
                              EnvironmentDetectorManager $environmentDetectorManager,
                              EventDispatcherInterface $eventDispatcher,
                              LockBackendInterface $lockBackend,
                              TypedConfigManagerInterface $configTyped,
                              ModuleHandlerInterface $moduleHandler,
                              ModuleInstallerInterface $moduleInstaller,
                              ThemeHandlerInterface $themeHandler,
                              TranslationInterface $translationManager) {
    $this->configStorage = $configStorage;
    $this->configManager = $configManager;
    $this->folderManager = $folderManager;
    $this->environmentDetectorManager = $environmentDetectorManager;
    $this->eventDispatcher = $eventDispatcher;
    $this->lock = $lockBackend;
    $this->configTyped = $configTyped;
    $this->moduleHandler = $moduleHandler;
    $this->moduleInstaller = $moduleInstaller;
    $this->themeHandler = $themeHandler;
    $this->translationManager = $translationManager;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:envimport')
      ->setDescription($this->translationManager->translate('Import configuration to current application overriding environment specific configuration.'))
      ->addOption(
        'self-debug',
        FALSE,
        InputArgument::OPTIONAL,
        $this->translationManager->translate('Displays the detected environment without taking any action')
      )
      ->addOption(
        'custom-env',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->translationManager->translate('Force the current environment to use')
      )
      ->addOption(
        'url',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->translationManager->translate('Specify the URL defined in the plugin customfile to get the current environment')
      )
      ->addOption(
        'fallback',
        FALSE,
        InputOption::VALUE_OPTIONAL,
        $this->translationManager->translate('If no environment has been detected continue importing from within the default drupal config folder'),
        FALSE
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    if (!$this->verifyMandatoryOptions($input, $io)) {
      return;
    }

    $env = $input->getOption('custom-env');
    if (!$env && $envFromUrl = $this->getEnvFromUrl($input, $io)) {
      $env = $envFromUrl;
    }

    if ($input->getOption('self-debug') === '1') {
      $io->info($this->translationManager->translate('Environment detected: "@env"', ['@env' => $env]));
      $io->info($this->translationManager->translate('Debug enabled, exiting.'));
      return;
    }
    if (!$env) {
      $io->error($this->translationManager->translate('It was not possible to import configuration from a specific environment'));

      if ($input->getOption('fallback') === '1') {
        $io->info($this->translationManager->translate('Applying the configuration from the default drupal folder only.'));
        $env = CONFIG_SYNC_DIRECTORY;
        $configSyncDir = $this->getDefaultConfigSyncFolder();
      }
      else {
        return;
      }
    }
    else {
      // Overriding the detected environment with the one just determined.
      $this->folderManager->overrideEnv($env);
      $configSyncDir = $this->folderManager->determineFolder(TRUE);

      // Checking if the environment specified has a corresponding folder.
      if (!$this->folderManager->checkEnvironmentValidity()) {
        $errorMsg = 'The current environment "@env" is not a valid environment. Check if you have a valid folder matching the environment name.';
        $io->error($this->translationManager->translate($errorMsg, ['@env' => $env]));
        return;
      }

      // Checking if the detected environment folder is
      // not equal to the default config folder.
      if ($configSyncDir === $this->getDefaultConfigSyncFolder()) {
        $errorMsg = 'The current environment folder for "@env" seems to point to the default config folder rather than an environment specific one.';
        $io->error($this->translationManager->translate($errorMsg, ['@env' => $env]));
        return;
      }
    }

    $io->info($this->translationManager->translate('Environment detected: @env', ['@env' => $env]));
    $io->info($this->translationManager->translate('Importing configuration from: @dir', ['@dir' => $configSyncDir]));

    $source_storage = new FileStorage($configSyncDir);

    $storage_comparer = new StorageComparer($source_storage, $this->configStorage, $this->configManager);

    if (!$storage_comparer->createChangelist()->hasChanges()) {
      $io->success($this->trans('commands.config.import.messages.nothing-to-do'));
    }

    if ($this->configImport($io, $storage_comparer)) {
      $io->success($this->trans('commands.config.import.messages.imported'));
      drupal_flush_all_caches();
    }
  }

  /**
   * Verifies that at least one param is provided.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   The io.
   *
   * @return bool
   *   If the validation fails it will return false, true if correct.
   */
  protected function verifyMandatoryOptions(InputInterface $input, DrupalStyle $io) {
    if (!$input->getOption('url') && !$input->getOption('custom-env')) {
      $io->error($this->translationManager->translate('You must specify the option --url or --custom.env in order to continue.'));
      return TRUE;
    }
    return TRUE;
  }

  /**
   * Gets the environment from URL.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   The io.
   *
   * @return bool
   *   The return.
   */
  protected function getEnvFromUrl(InputInterface $input, DrupalStyle $io) {
    if ($url = $input->getOption('url')) {
      if ($this->environmentDetectorManager->hasDefinition(self::URL_PLUGIN)) {
        $plugin = $this->environmentDetectorManager->createInstance(self::URL_PLUGIN);

        if (!$envFromUrl = $plugin->getEnvironment($url)) {
          $io->error('There is no environment set in custom file for the URL ' . $url . '. Please check either the file and the URL provided.');
          return FALSE;
        }
        return $envFromUrl;
      }
    }
    return FALSE;
  }

  /**
   * Returns the basic config_sync folder path.
   *
   * @return string
   *   The path of the default sync folder.
   */
  protected function getDefaultConfigSyncFolder() {
    return config_get_config_directory(CONFIG_SYNC_DIRECTORY);
  }

  /**
   * Method mirror of Drupal\Command\Config\ImportCommand::configImport.
   *
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   The io.
   * @param \Drupal\Core\Config\StorageComparer $storage_comparer
   *   The storage comparer.
   *
   * @return bool
   *   The return.
   */
  private function configImport(DrupalStyle $io, StorageComparer $storage_comparer) {
    $success = FALSE;
    $config_importer = new ConfigImporter(
      $storage_comparer,
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->configTyped,
      $this->moduleHandler,
      $this->moduleInstaller,
      $this->themeHandler,
      $this->translationManager
    );

    if ($config_importer->alreadyImporting()) {
      $io->success($this->trans('commands.config.import.messages.already-imported'));
    }
    else {
      try {
        $config_importer->import();
        $io->info($this->trans('commands.config.import.messages.importing'));
        $success = TRUE;
      }
      catch (ConfigImporterException $e) {
        $message = 'The import failed due for the following reasons:' . "\n";
        $message .= implode("\n", $config_importer->getErrors());
        $io->error(
          sprintf(
            $this->trans('commands.site.import.local.messages.error-writing'),
            $message
          )
        );
      }
      catch (\Exception $e) {
        $io->error(
          sprintf(
            $this->trans('commands.site.import.local.messages.error-writing'),
            $e->getMessage()
          )
              );
      }
      return $success;
    }
  }

}
