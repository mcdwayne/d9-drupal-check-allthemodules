<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;

/**
 * Define entity import migration lookup.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_migrate_lookup",
 *   label = @Translation("Migrate Lookup")
 * )
 */
class EntityImportMigrationLookup extends MigrationLookup implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * Define default configurations.
   *
   * @return array
   */
  public function defaultConfigurations() {
    return [
      'stub_id' => NULL,
      'no_stub' => FALSE,
      'migration' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['#prefix'] = '<div id="entity-import-processing-migration-lookup">';
    $form['#suffix'] = '</div>';

    $form['migration'] = [
      '#type' => 'select',
      '#title' => $this->t('Migration'),
      '#options' => $this->getMigrationOptions(),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#default_value' => $configuration['migration'],
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'entity-import-processing-migration-lookup',
        'callback' => [$this, 'ajaxProcessCallback']
      ]
    ];
    $form['no_stub'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No Stub'),
      '#description' => $this->t('Prevents the creation of a stub entity.'),
      '#default_value' => $configuration['no_stub'],
    ];
    $form['stub_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Stub ID'),
      '#description' => $this->t('Identifies the migration which will be used 
        to create any stub entities'),
      '#default_value' => $configuration['stub_id'],
      '#empty_option' => $this->t('- None -'),
      '#options' => $configuration['migration'],
      '#states' => [
        'visible' => [
          ':input[name="processing[configuration][plugins][entity_import_migrate_lookup][settings][no_stub]"]' => ['checked' => FALSE]
        ]
      ]
    ];

    return $form;
  }

  /**
   * Get migration options.
   *
   * @return array
   *   An array of migration options.
   */
  protected function getMigrationOptions() {
    $options = [];

    foreach ($this->migrationPluginManager->getDefinitions() as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'];
    }

    return $options;
  }
}
