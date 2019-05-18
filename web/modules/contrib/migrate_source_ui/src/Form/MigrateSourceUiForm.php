<?php

namespace Drupal\migrate_source_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Xml;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;
use Drupal\migrate_source_ui\StubMigrationMessage;
use Drupal\migrate_source_ui\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;

/**
 * Contribute form.
 */
class MigrateSourceUiForm extends FormBase {

  /**
     * The migration plugin manager.
     *
     * @var \Drupal\migrate\Plugin\MigrationPluginManager
     */
  protected $pluginManagerMigration;

  /**
   * The migration definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * MigrateSourceUiForm constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager_migration
   *   The migration plugin manager.
   */
  public function __construct(MigrationPluginManager $plugin_manager_migration) {
    $this->pluginManagerMigration = $plugin_manager_migration;
    $this->definitions = $this->pluginManagerMigration->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_source_ui_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $migrationLabels = [];
    foreach ($this->definitions as $definition) {
      $migrationInstance = $this->pluginManagerMigration->createStubMigration($definition);
      if ($migrationInstance->getSourcePlugin() instanceof CSV || $migrationInstance->getSourcePlugin() instanceof Json || $migrationInstance->getSourcePlugin() instanceof Xml) {
        $id = $definition['id'];
        $options[$id] = $this->t('%id (supports %file_type)', [
          '%id' => isset($definition['label']) ? $definition['label'] : $id,
          '%file_type' => $this->getFileExtensionSupported($migrationInstance),
        ]);
      }
    }
    $form['migrations'] = [
      '#type' => 'select',
      '#title' => $this->t('Migrations'),
      '#options' => $options,
    ];
    $form['source_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload the source file'),
    ];
    $form['update_existing_records'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing records'),
      '#default_value' => 1,
    ];
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $migration_id = $form_state->getValue('migrations');
    $definition = $this->pluginManagerMigration->getDefinition($migration_id);
    $migrationInstance = $this->pluginManagerMigration->createStubMigration($definition);
    $extension = $this->getFileExtensionSupported($migrationInstance);

    $validators = ['file_validate_extensions' => [$extension]];
    $file = file_save_upload('source_file', $validators, FALSE, 0, FILE_EXISTS_REPLACE);

    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        $form_state->setValue('file_path', $file->getFileUri());
      }
      // File upload failed.
      else {
        $form_state->setErrorByName('source_file', $this->t('The file could not be uploaded.'));
      }
    }
    else {
      $form_state->setErrorByName('source_file', $this->t('You have to upload a source file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migration_id = $form_state->getValue('migrations');
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->pluginManagerMigration->createInstance($migration_id);

    // Reset status.
    $status = $migration->getStatus();
    if ($status != MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
      drupal_set_message($this->t('Migration @id reset to Idle', ['@id' => $migration_id]), 'warning');
    }

    $options = [
      'file_path' => $form_state->getValue('file_path'),
    ];
    // Force updates or not.
    if ($form_state->getValue('update_existing_records')) {
      $options['update'] = TRUE;
    }

    $executable = new MigrateBatchExecutable($migration, new StubMigrationMessage(), $options);
    $executable->batchImport();
  }

  /**
   * The allowed file extension for the migration.
   *
   * @param \Drupal\migrate\Plugin\Migration $migrationInstance
   *   The migration instance.
   *
   * @return string
   *   The file extension.
   */
  public function getFileExtensionSupported(Migration $migrationInstance) {
    $extension = 'csv';
    if ($migrationInstance->getSourcePlugin() instanceof CSV) {
      $extension = 'csv';
    }
    elseif ($migrationInstance->getSourcePlugin() instanceof Json) {
      $extension = 'json';
    }
    elseif ($migrationInstance->getSourcePlugin() instanceof Xml) {
      $extension = 'xml';
    }

    return $extension;
  }

}
