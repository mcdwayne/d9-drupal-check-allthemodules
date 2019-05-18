<?php

namespace Drupal\migrate_gathercontent\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate_gathercontent\MigrateBatchExecutable;

/**
 * This form is specifically for configuring process pipelines.
 */
class MigrationExecuteForm extends FormBase {

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MigrationExecuteForm object.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   */
  public function __construct(EntityTypeManager $entityTypeManager, MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migration_execute_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Fill this out with migration information.
    $form = [];
    $form['operations'] = $this->migrateMigrateOperations();

    return $form;
  }


  /**
   * Get Operations.
   */
  private function migrateMigrateOperations() {
    // Build the 'Update options' form.

    $form = [];

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => t('Options'),
    ];

    $operations = [
      'import' => $this->t('Import'),
      'rollback' => $this->t('Rollback'),
      'reset' => $this->t('Reset'),
    ];

    $form['options']['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#options' => $operations,
    ];

    $form['options']['update'] = [
      '#type' => 'checkbox',
      '#title' => t('Update Existing Content'),
      '#default_value' => 0,
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => array('value' => 'import'),
        ]
      ],
    ];

    // Help descriptions.
    $form['options']['help_import'] = [
      '#type' => 'item',
      '#description' => $this->t('Import content from GatherContent.'),
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => array('value' => 'import'),
        ]
      ],
    ];

    $form['options']['help_rollback'] = [
      '#type' => 'item',
      '#description' => $this->t('Delete all objects created by the import and reset the import tables.'),
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => array('value' => 'rollback'),
        ]
      ],
    ];

    $form['options']['help_reset'] = [
      '#type' => 'item',
      '#description' => $this->t('Reset process back to an idle state. (Used rarely).'),
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => array('value' => 'reset'),
        ]
      ],
    ];

    $form['execute'] = [
      '#type' => 'fieldset',
      '#title' => t('Execute'),
      '#description' => $this->t('Execute the operation.'),
    ];
    $form['execute']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Run Operation'),
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Intentionally left blank.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $group = $this->getRouteMatch()->getParameter('group_id');

    // Loop through and remove any migrations that are a dependency of another.
    // Those will get executed automatically and we don't need to do it manually.
    $mappings = $this->entityTypeManager->getStorage('gathercontent_mapping')->loadByProperties([
      'group_id' => $group
    ]);
    $field_mappings = [];
    if (!empty($mappings)) {
      foreach ($mappings as $mapping) {
        $field_mappings += $mapping->getFieldMappings();
      }
      foreach ($mappings as $id => $mapping) {
        if (isset($field_mappings[$id])) {
          unset($mappings[$id]);
        }
      }
    }

    foreach ($mappings as $mapping_id => $mapping) {

      $migration = $this->migrationPluginManager->createInstance($mapping->getMigrationId());
      $operation = $form_state->getValue('operation');

      switch ($operation) {

        // Import the content (including dependencies).
        case 'import':
          $migrateMessage = new MigrateMessage();
          $batchMigration = new MigrateBatchExecutable($migration, $migrateMessage);

          // Run update.
          if (!empty($form_state->getValue('update'))) {
            $batchMigration->updateExistingRows();
          }

          $batchMigration->batchImport();
          break;

        // Rollback deletes any existing entities and clears the migrate tables.
        case 'rollback':
          // Rollback dependencies first.
          $dependencies = $migration->getMigrationDependencies();
          if (!empty($dependencies['required'])) {
            $required_migrations = $this->migrationPluginManager->createInstances($dependencies['required']);
            foreach ($required_migrations as $dependency) {
              $dependencyMessage = new MigrateMessage();
              $dependencyExecutable = new MigrateBatchExecutable($dependency, $dependencyMessage);
              $dependencyExecutable->rollback();
            }
          }

          // Finally rolling back the main migration.
          $migrateMessage = new MigrateMessage();
          $batchMigration = new MigrateBatchExecutable($migration, $migrateMessage);
          $batchMigration->rollback();

          $message = "Operation executed successfully.";
          $this->messenger()->addMessage($message);
          break;

        // Resets the migration status (sometimes it gets stuck).
        case 'reset':
          $migration->setStatus(MigrationInterface::STATUS_IDLE);
          break;
      }
    }
  }
}
