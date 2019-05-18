<?php

// https://www.weareaccess.co.uk/blog/2016/07/smack-my-batch-batch-processing-drupal-8
namespace Drupal\migrate_gathercontent;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Defines a migrate executable class for batch migrations through UI.
 */
class MigrateBatchExecutable extends MigrateExecutable {

  /**
   * How many items we want to process at a time.
   */
  // TODO: Should make this dynamic.
  const BATCH_LIMIT = 50;

  /**
   * Indicates if we need to update existing rows or skip them.
   *
   * @var int
   */
  protected $updateExistingRows = FALSE;

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The batch context.
   */
  protected $batchContext;

  /**
   * {@inheritdoc}
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message) {

    $this->migrationPluginManager = \Drupal::getContainer()->get('plugin.manager.migration');

    // Register listeners for import.
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::POST_ROW_SAVE, [$this, 'onRowSave']);
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::POST_IMPORT, [$this, 'onPostImport']);

    parent::__construct($migration, $message);
  }

  /**
   * Helper to generate the batch operations for importing migrations.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
   *   The migrations.
   * @param string $operation
   *   The batch operation to perform.
   * @param array $options
   *   The migration options.
   *
   * @return array
   *   The batch operations to perform.
   */
  protected function batchOperations(array $migrations, $operation, array $options = []) {
    $operations = [];
    foreach ($migrations as $id => $migration) {

      // TODO: remove this later. It should automatically be handled by migrate core.
      $migration->setStatus(MigrationInterface::STATUS_IDLE);

      if (!empty($options['update'])) {
        $migration->getIdMap()->prepareUpdate();
      }

      if (!empty($options['force'])) {
        $migration->set('requirements', []);
      }
      // Get migration dependencies (if any).
      else {
        $dependencies = $migration->getMigrationDependencies();
        $required_migrations = [];
        if (!empty($dependencies['required'])) {

          // Load required migrations.
          // Using createInstance instead of createInstances to make sure that
          // the migrations are loaded in the order specified.
          foreach ($dependencies['required'] as $migration_id) {
            $required_migrations[$migration_id] = $this->migrationPluginManager->createInstance($migration_id);
          }
          $operations += $this->batchOperations($required_migrations, $operation, [
            'limit' => $options['limit'],
            'update' => $options['update'],
            //'force' => $options['force'],
          ]);
        }
      }

      $operations[] = [
        '\Drupal\migrate_gathercontent\MigrateBatchExecutable::batchExecuteImport',
        [$migration->id(), $options],
      ];
    }

    return $operations;
  }

  /**
   * Update existing rows.
   */
  public function updateExistingRows() {
    $this->updateExistingRows = TRUE;
  }

  /**
   * Setup batch operations for running the migration.
   */
  public function batchImport() {
    // Create the batch operations for each migration that needs to be executed.
    // This includes the migration for this executable, but also the dependent
    // migrations.
    $operations = $this->batchOperations([$this->migration], 'import', [
      'limit' => self::BATCH_LIMIT,
      'update' => $this->updateExistingRows,
      //'force' => $this->checkDependencies,
    ]);

    if (count($operations) > 0) {
      $batch = [
        'operations' => $operations,
        'title' => t('Migrating %migrate', ['%migrate' => $this->migration->label()]),
        'init_message' => t('Start migrating %migrate', ['%migrate' => $this->migration->label()]),
        'progressive' => TRUE,
        'progress_message' => t('Migrating %migrate', ['%migrate' => $this->migration->label()]),
        'error_message' => t('An error occurred while migrating %migrate.', ['%migrate' => $this->migration->label()]),
        'finished' => '\Drupal\migrate_gathercontent\MigrateBatchExecutable::batchFinishedImport',
      ];

      batch_set($batch);
    }
  }

  /**
   * Execute batch import.
   *
   * @param integer $migration_id
   *    The migration id.
   * @param array $options
   *    The array of options.
   * @param array $context
   *    Context information.
   * @throws \Drupal\migrate\MigrateException
   */
  public static function batchExecuteImport($migration_id, array $options, array &$context) {

    // Load migration and source plugin.
    $migration = \Drupal::getContainer()->get('plugin.manager.migration')->createInstance($migration_id);
    $sourcePlugin = $migration->getSourcePlugin();

    // Initialize the batch process if it hasn't been set yet.
    if (empty($context['sandbox'])) {
      $context['finished'] = 0;
      $context['sandbox'] = [];
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['limit'] = $options['limit'];
      $context['sandbox']['max'] = $sourcePlugin->count();
      // $context['sandbox']['operation'] = MigrateBatchExecutable::BATCH_IMPORT;
    }
    // Load the max number of nodes.
    // Set initial batch values including max results to be imported.
    $message = new MigrateMessage();
    $executable = new MigrateBatchExecutable($migration, $message);

    // Make sure we know our batch context.
    if (empty($context['sandbox']['max'])) {
      $context['sandbox']['max'] = $executable->getSource()->count();
      $context['sandbox']['limit'] = $options['limit'];
      $context['results'][$migration->id()] = [
        '@imported' => 0,
        '@errors' => 0,
        '@name' => $migration->label(),
      ];
    }

    // Every iteration, we reset our batch counter.
    $context['sandbox']['counter'] = 0;

    // Make sure we know our batch context.
    $executable->setBatchContext($context);

    // Begin import
    // @see self::OnRowSave()
    $result = $executable->import();

    // Store the result; will need to combine the results of all our iterations.
    // TODO: Get more data for this?
    $context['results'][$migration->id()] = [
      '@imported' => $context['results'][$migration->id()]['@imported'],
      '@updated' => $context['results'][$migration->id()]['@updated'],
      '@errors' => $context['results'][$migration->id()]['@errors'],
      '@name' => $migration->label(),
    ];

    if ($result != MigrationInterface::RESULT_INCOMPLETE) {
        $context['finished'] = 1;
    }
    else {
        //$context['sandbox']['counter'] = $context['results'][$migration->id()]['@numitems'];
        $context['sandbox']['counter'] = $context['sandbox']['progress'];
        if ($context['sandbox']['counter'] <= $context['sandbox']['max']) {
            $context['finished'] = ((float) $context['sandbox']['counter'] / (float) $context['sandbox']['max']);
            $context['message'] = t('Importing %migration (@percent%).', [
                '%migration' => $migration->label(),
                '@percent' => (int) ($context['finished'] * 100),
            ]);
        }
    }
  }

  /**
   * Finished callback for import batches.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function batchFinishedImport($success, array $results, array $operations) {
    if ($success) {
      foreach ($results as $migration_id => $result) {
        // TODO: Gather number of processed items.
        // if there are errors for this import then
        if (!empty($result['@errors'])) {
          $message = "Errors for " . $result['@errors'] . " item(s)  - done with " . $result['@name'];
          \Drupal::messenger()->addError($message);
        }

        if (!empty($result['@imported'])) {
          $message = "Imported " . $result['@imported'] . " item(s) - done with " . $result['@name'];
          // TODO: Refactor using dependency injection.
          \Drupal::messenger()->addMessage($message);
        }
      }
    }
  }

  /**
   * Event callback. Count up any row save events.
   *
   * @param \Drupal\migrate_gathercontent\MigrateMapSaveEvent $event
   */
  public function onRowSave(MigratePostRowSaveEvent $event) {

    // Update progress.
    $context = $this->getBatchContext();
    $context['sandbox']['counter']++;
    $context['sandbox']['progress']++;
  }

  /**
   * Event callback. Get final status of import.
   *
   * @param \Drupal\migrate_gathercontent\MigrateMapSaveEvent $event
   */
  public function onPostImport(MigrateImportEvent $event) {

    $context = $this->getBatchContext();
    $migration = $event->getMigration();

    // Set migration results.
    $context['results'][$migration->id()] = [
      '@updated' => $migration->getIdMap()->updateCount(),
      '@imported' => $migration->getIdMap()->importedCount(),
      '@errors' => $migration->getIdMap()->errorCount(),
    ];

    $context['sandbox']['counter']++;
    $context['sandbox']['progress']++;
  }

  /**
   * Sets the current batch content so listeners can update the messages.
   *
   * @param array $context
   *   The batch context.
   */
  public function setBatchContext(array &$context) {
    $this->batchContext = &$context;
  }

  /**
   * Gets a reference to the current batch context.
   *
   * @return array
   *   The batch context.
   */
  public function &getBatchContext() {
    return $this->batchContext;
  }

  /**
   * {@inheritdoc}
   */
  public function checkStatus() {
    $status = parent::checkStatus();

    if ($status == MigrationInterface::RESULT_COMPLETED) {
        $context = $this->getBatchContext();
        if (!empty($context['sandbox'])) {
            $context['sandbox']['counter']++;
            if ($context['sandbox']['counter'] >= $context['sandbox']['limit']) {
                $status = MigrationInterface::RESULT_INCOMPLETE;
            }
        }
    }

    return $status;
  }
}

