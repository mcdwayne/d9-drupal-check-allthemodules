<?php

namespace Drupal\scenarios;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\ProxyClass\Extension\ModuleInstaller;
use Drupal\Core\Extension\InfoParser;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_tools\DrushLogMigrateMessage;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\default_content_deploy\Importer;

/**
 * Class ScenariosHandler.
 *
 * @package Drupal\scenarios
 */
class ScenariosHandler implements ContainerInjectionInterface {

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\ProxyClass\Extension\ModuleInstaller definition.
   *
   * @var \Drupal\Core\ProxyClass\Extension\ModuleInstaller
   */
  protected $moduleInstaller;

  /**
   * Drupal\Core\Extension\InfoParser definition.
   *
   * @var \Drupal\Core\Extension\InfoParser
   */
  protected $infoParser;

  /**
   * Drupal\migrate\Plugin\MigrationPluginManager definition.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\default_content_deploy/Importer definition.
   *
   * @var \Drupal\default_content_deploy\Importer
   */
  protected $importer;

  /**
   * Constructor.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   * @var \Drupal\Core\ProxyClass\Extension\ModuleInstaller
   * @var \Drupal\Core\Extension\InfoParser
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   * @var \Drupal\Core\Config\ConfigFactory
   * @var \Drupal\default_content_deploy\Importer
   */
  public function __construct(ModuleHandler $module_handler, ModuleInstaller $module_installer, InfoParser $info_parser, MigrationPluginManager $migration_plugin_manager, ConfigFactory $config_factory, Importer $importer) {
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->infoParser = $info_parser;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->configFactory = $config_factory;
    $this->importer = $importer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('info_parser'),
      $container->get('plugin.manager.migration'),
      $container->get('config.factory'),
      $container->get('default_content_deploy.importer')
    );
  }

  /**
   * Sets a message to display to the user.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $message
   *   The translated message to be displayed to the user. For consistency with
   *   other messages, it should begin with a capital letter and end with a
   *   period.
   * @param string $type
   *   (optional) The message's type. Defaults to 'status'. These values are
   *   supported:
   *   - 'status'
   *   - 'warning'
   *   - 'error'
   * @param bool $repeat
   *   (optional) If this is FALSE and the message is already set, then the
   *   message won't be repeated. Defaults to FALSE.
   */
  public function setMessage($message, $type = 'status', $repeat = FALSE) {
    if (PHP_SAPI === 'cli' && function_exists('drush_log')) {
      $type = ($type == 'status' ? 'ok' : $type);
      drush_log($message, $type);
    }
    else {
      drupal_set_message($message, $type, $repeat);
    }
  }

  /**
   * Sets an error message to display to the user.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $message
   *   The translated error message to be displayed to the user. For consistency
   *   with other messages, it should begin with a capital letter and end with a
   *   period.
   */
  public function setError($message) {
    if (PHP_SAPI === 'cli' && function_exists('drush_set_error')) {
      drush_set_error('ERROR', $message);
    }
    else {
      drupal_set_message($message, 'error');
    }
  }

  /**
   * Rebuilds the site cache.
   *
   * @param string $alias
   *   The Drush alias context.
   */
  public function cacheRebuild($alias) {
    if ($alias !== NULL && function_exists('drush_invoke_process')) {
      drush_invoke_process($alias, 'cache-rebuild');
    }
    else {
      drupal_flush_all_caches();
    }
  }

  /**
   * Retrieves the Drush alias context.
   *
   * @return null|string
   *   The Drush alias context or NULL if the context does not exist.
   */
  public function getAlias() {
    $alias = NULL;
    if (PHP_SAPI === 'cli' && function_exists('drush_get_context')) {
      $alias_context = drush_get_context('alias');
      $alias = !empty($alias_context) ? $alias_context : '@self';
    }
    return $alias;
  }

  /**
   * Retrieves the logger.
   *
   * @param string $alias
   *   The Drush alias context.
   *
   * @return \Drupal\migrate\MigrateMessage|\Drupal\migrate_tools\DrushLogMigrateMessage
   */
  public function getLog($alias) {
    if ($alias != NULL && function_exists('drush_log')) {
      return new DrushLogMigrateMessage();
    }
    else {
      return new MigrateMessage();
    }
  }

  /**
   * Processes the batch.
   *
   * @param string $alias
   *   The Drush alias context.
   */
  public function processBatch($alias) {
    if (batch_get()) {
      if ($alias !== NULL && function_exists('drush_backend_batch_process')) {
        drush_backend_batch_process();
      }
      else {
        batch_process();
      }
    }
  }

  /**
   * @param $command
   * @param $migrations
   * @param $alias
   *
   * @return bool
   * @throws \Drupal\migrate\MigrateException
   */
  public function processMigrations($command, $migrations, $alias) {
    $migration_manager = \Drupal::service('plugin.manager.migration');
    $migration_manager->clearCachedDefinitions();
    // Return the correct log for the Migrate Executable.
    $log = $this->getLog($alias);
    // Set default value for return.
    $result = FALSE;
    // If we have pending batches, process them now.
    $this->processBatch($alias);
    // Run the migrations in the provided order.
    foreach ($migrations as $migration) {
      $migration = $migration_manager->createInstance($migration);
      $executable = new MigrateExecutable($migration, $log);
      switch ($command) {
        case "import":
          if ($execute = $executable->import()) {
            // Invoke the 'scenarios_migration_finished' hook from the global
            // container in case a scenario dependency implements that hook.
            \Drupal::moduleHandler()->invokeAll('scenarios_migration_finished', [$migration]);
          }
          break;
        case "rollback":
          $execute = $executable->rollback();
          break;
        default:
          $execute = FALSE;
      }
      // Return the migration results.
      if ($execute) {
        $this->setMessage(t('Executed @command for "@label" migration.', ['@label' => $migration->label(), '@command' => $command]));
        $result = TRUE;
      }
      else {
        $this->setError(t('Migration "@label" failed to execute @command', ['@label' => $migration->label(), '@command' => $command]));
        $result = FALSE;
      }
    }
    return $result;
  }

  /**
   * @param $scenario
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function scenarioEnable($scenario) {
    // Retrieve the scenario information.
    if ($info = scenarios_info($scenario)) {
      // Inform the user that the scenario is being enabled.
      $this->setMessage(t('Enabling the @scenario scenario.', ['@scenario' => $info['label']]));
      // Get the Drush alias.
      $alias = $this->getAlias();
      // Run the Migrations.
      if (isset($info['migrations'])) {
        // Retrieve the scenario migrations.
        $migrations = $info['migrations'];
        // Process the migrations.
        $this->processMigrations('import', $migrations, $alias);
      }
      // Run the Default Content Deploy imports.
      if (isset($info['imports'])) {
        // Get the Scenario profile directory location.
        $profile_dir = drupal_get_path('profile', $scenario);
        // Load the Default Content Deploy configuration.
        $config = $this->configFactory->getEditable('default_content_deploy.content_directory');
        // Run the imports over multiple content directories if necessary.
        foreach ($info['imports'] as $dir) {
          $import_dir = '../docroot/' . $profile_dir . $dir;
          $config->set('content_directory', $import_dir)->save();
          if ($this->importer->deployContent($force_update = TRUE, $writeEnable = TRUE)) {
            $this->setMessage(t('Default content for @scenario imported.', ['@scenario' => $scenario]));
          }
          if ($this->importer->importUrlAliases()) {
            $this->setMessage(t('URL aliases for @scenario imported.', ['@scenario' => $scenario]));
          }
        }
      }
      // Run the Acquia Content Hub CDF import where available.
      if (isset($info['cdf']) && $this->moduleHandler->moduleExists('scenarios_contenthub')) {
        \Drupal::service('scenarios_contenthub')->import($scenario, $info['cdf']);
      }
      // Rebuild cache after enabling scenario.
      $this->cacheRebuild($alias);
      // Provide a hook that allows modules to act after the scenario has been
      // enabled.
      $this->moduleHandler->invokeAll('scenarios_post_enable', [$scenario]);
    }
    else {
      $this->setError(t('The scenario @scenario does not exist.', ['@scenario' => $scenario]));
    }
  }

  /**
   * @param $scenario
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function scenarioUninstall($scenario) {
    // Retrieve the scenario information.
    if ($info = scenarios_info($scenario)) {
      // Inform the user that the scenario is being uninstalled.
      $this->setMessage(t('Uninstalling the @scenario scenario.', ['@scenario' => $info['label']]));
      // Get the Drush alias.
      $alias = $this->getAlias();
      if (isset($info['migrations'])) {
        // Retrieve the scenario migrations and reverse thei order.
        $migrations = array_reverse($info['migrations']);
        // Process the migrations.
        $this->processMigrations('rollback', $migrations, $alias);
      }
      // Provide a hook that allows modules to act after the scenario has been
      // uninstalled.
      $this->moduleHandler->invokeAll('scenarios_post_uninstall', [$scenario]);
    }
    else {
      $this->setError(t('The scenario @scenario does not exist.', ['@scenario' => $scenario]));
    }
  }

  /**
   * @param $scenario
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function scenarioReset($scenario) {
    // Retrieve the scenario information.
    if ($info = scenarios_info($scenario)) {
      // Inform the user that the scenario is being reset.
      $this->setMessage(t('Resetting the @scenario scenario.', ['@scenario' => $info['label']]));
      // Uninstall the scenario.
      $this->scenarioUninstall($scenario);
      // Enable the scenario.
      $this->scenarioEnable($scenario);
      // Provide a hook that allows modules to act after the scenario has been
      // reset.
      $this->moduleHandler->invokeAll('scenarios_post_reset', [$scenario]);
    }
    else {
      $this->setError(t('The scenario @scenario does not exist.', ['@scenario' => $scenario]));
    }
  }

}
