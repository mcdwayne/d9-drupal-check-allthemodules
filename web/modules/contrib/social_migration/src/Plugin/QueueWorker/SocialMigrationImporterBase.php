<?php

namespace Drupal\social_migration\Plugin\QueueWorker;

use Drupal\migrate\MigrateMessage;
use Drupal\core\Queue\QueueWorkerBase;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base worker to process Social Migration queues.
 */
abstract class SocialMigrationImporterBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The plugin.manager.migration instance.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(MigrationPluginManager $migration_plugin_manager) {
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $logger = new MigrateMessage();
    $migrationInterface = $this->migrationPluginManager->createInstance($data['id']);
    $executable = new MigrateExecutable($migrationInterface, $logger);
    $executable->import();
    if ($count = $executable->getFailedCount()) {
      $this->logFailureMessage($this->t('Migration warning: %count failed.', ['%count' => $count]));
    }
    else {
      $this->logSuccessMessage($this->t('Migration succeeded.'));
    }
  }

  /**
   * Handle success messages.
   *
   * @param string $text
   *   The text to display.
   */
  abstract protected function logSuccessMessage($text);

  /**
   * Handle failure messages.
   *
   * @param string $text
   *   The text to display.
   */
  abstract protected function logFailureMessage($text);

}
