<?php

namespace Drupal\wwaf_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Url;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\file\Entity\File;

/**
 * Class MhPosImportForm.
 *
 * @package Drupal\mh_pos_loc\Form
 */
class ImportForm extends FormBase {

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config,
    MigrationPluginManagerInterface $migration_plugin_manager,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter
  ) {
    $this->configFactory = $config;
    $this->config = $this->configFactory->getEditable('wwaf_import.settings');
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.migration'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wwaff_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $fid = $this->config->get('import_file');
    $type = $this->config->get('import_type');
    if ($fid && !empty(File::load($fid))) {
      $this->renderTable($form);
    }
    $form['import_file'] = [
      '#title' => t('Import file'),
      '#type' => 'managed_file',
      '#description' => t('Available file formats - .xlsx, .xls.'),
      '#upload_location' => 'public://wwaf_import/',
      '#upload_validators'  => [
        'file_validate_extensions' => ['xlsx xls'],
      ],
      '#default_value' => [$fid],
      '#required' => TRUE,
    ];
    $vid = 'wwaf_point_types';
    /** @var \Drupal\taxonomy\TermInterface[] $terms */
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid,0,NULL,TRUE);
    $options = [];
    foreach ($terms as $term) {
      $options[$term->id()] = $term->label();
    }

    $form['type'] = [
      '#title' => t('Import as'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $type,
      '#required' => TRUE,
    ];

    $form['buttons']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#name' => 'import',
    ];

    $form['buttons']['import_update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#name' => 'update',
    ];

    $form['buttons']['import_stop'] = [
      '#type' => 'submit',
      '#value' => $this->t('Stop'),
      '#name' => 'stop',
    ];

    $form['buttons']['import_reset_status'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset status'),
      '#name' => 'reset_status',
    ];

    $form['buttons']['import_rollback'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rollback'),
      '#name' => 'rollback',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('import_file');
    $this->config->set('import_file', $value[0]);
    $value = $form_state->getValue('type');
    $this->config->set('import_type', $value);
    $this->config->save();

    // @TODO: Review this
    $plugin_id = 'wwaf_entity';
    $plugins = $this->migrationPluginManager->createInstances($plugin_id);
    switch ($form_state->getTriggeringElement()['#name']) {
      case 'import':
        self::setBatch($plugin_id, 'import');
        break;
      case 'update':
        self::setBatch($plugin_id, 'update');
        break;
      case 'rollback':
        self::setBatch($plugin_id, 'rollback');
        break;
      case 'stop':
        $status = $plugins[$plugin_id]->getStatus();
        switch ($status) {
          case MigrationInterface::STATUS_IDLE:
            drupal_set_message($this->t('Migration %id is idle.', ['%id' => $plugins[$plugin_id]->label()]), 'warning');
            break;
          case MigrationInterface::STATUS_DISABLED:
            drupal_set_message($this->t('Migration %id is disabled.', ['%id' => $plugins[$plugin_id]->label()]), 'warning');
            break;
          case MigrationInterface::STATUS_STOPPING:
            drupal_set_message($this->t('Migration %id is already stopping.', ['%id' => $plugins[$plugin_id]->label()]), 'warning');
            break;
          default:
            $plugins[$plugin_id]->interruptMigration(MigrationInterface::RESULT_STOPPED);
            drupal_set_message($this->t('Migration %id requested to stop.', ['%id' => $plugins[$plugin_id]->label()]));
            break;
        }
        break;
      case 'reset_status':
        $status = $plugins[$plugin_id]->getStatus();
        if ($status == MigrationInterface::STATUS_IDLE) {
          drupal_set_message($this->t('Migration %id is already Idle.', ['%id' => $plugins[$plugin_id]->label()]), 'warning');
        }
        else {
          $plugins[$plugin_id]->setStatus(MigrationInterface::STATUS_IDLE);
          drupal_set_message($this->t('Migration %id reset to Idle.', ['%id' => $plugins[$plugin_id]->label()]));
        }
        break;
    }
  }

  /**
   * Builds the header row for the pos_node listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see \Drupal\migrate_tools\Controller\MigrationListBuilder::buildHeader()
   */
  public function buildHeader() {
    $header['label'] = $this->t('Migration');
    $header['machine_name'] = $this->t('Machine Name');
    $header['status'] = $this->t('Status');
    $header['total'] = $this->t('Total');
    $header['imported'] = $this->t('Imported');
    $header['unprocessed'] = $this->t('Unprocessed');
    $header['messages'] = $this->t('Messages');
    $header['last_imported'] = $this->t('Last Imported');
    return $header;
  }

  /**
   * Builds a row for a migration plugin.
   *
   * @return mixed
   *
   * @see \Drupal\migrate_tools\Controller\MigrationListBuilder::buildRow()
   */
  public function buildRow() {
    $migration = $this->migrationPluginManager->createInstance('wwaf_entity');

    $row['label'] = $migration->label();
    $row['machine_name'] = $migration->id();
    $row['status'] = $migration->getStatusLabel();

    // Derive the stats.
    $source_plugin = $migration->getSourcePlugin();
    $row['total'] = $source_plugin->count();
    $map = $migration->getIdMap();
    $row['imported'] = $map->importedCount();
    // -1 indicates uncountable sources.
    if ($row['total'] == -1) {
      $row['total'] = $this->t('N/A');
      $row['unprocessed'] = $this->t('N/A');
    }
    else {
      $row['unprocessed'] = $row['total'] - $map->processedCount();
    }
    $migration_group = $migration->get('migration_group');
    if (!$migration_group) {
      $migration_group = 'default';
    }
    $route_parameters = array(
      'migration_group' => $migration_group,
      'migration' => $migration->id()
    );
//    $row['messages'] = array(
//      'data' => array(
//        '#type' => 'link',
//        '#title' => $map->messageCount(),
//        '#url' => Url::fromRoute("migrate_tools.messages", $route_parameters),
//      ),
//    );
    $migrate_last_imported_store = \Drupal::keyValue('migrate_last_imported');
    $last_imported =  $migrate_last_imported_store->get($migration->id(), FALSE);
    if ($last_imported) {
      $row['last_imported'] = $this->dateFormatter->format($last_imported / 1000,
        'custom', 'Y-m-d H:i:s');
    }
    else {
      $row['last_imported'] = '';
    }
    return $row;
  }


  /**
   * Build table import info.
   *
   * @param array $form
   */
  public function renderTable(array &$form) {
    $form['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => $this->t('There is no migrate yet.'),
      '#rows' => [$this->buildRow()],
    );
  }

  /**
   * POS import batch set.
   *
   * @param string $id
   *  Import id.
   * @param string $type
   *  Import, update, rollback.
   */
  public static function setBatch($id, $type) {
    $batch = [
      'operations' => [
        ['wwaf_import_batch', [$id, $type]],
      ],
      'title' => t('WWAF Import'),
      'error_message' => t('Import process has encountered an error.'),
      'file' => drupal_get_path('module', 'wwaf_import') . '/wwaf_import_batch.inc',
    ];
    batch_set($batch);
  }

}
