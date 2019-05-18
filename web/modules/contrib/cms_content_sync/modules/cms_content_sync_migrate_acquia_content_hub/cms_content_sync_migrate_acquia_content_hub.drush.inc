<?php

/**
 * @file
 * Contains Drush commands for CMS Content Sync.
 */

use Drupal\cms_content_sync\ExportIntent;
use Drupal\cms_content_sync\ImportIntent;
use Drupal\cms_content_sync_migrate_acquia_content_hub\CreateStatusEntities;
use Drupal\cms_content_sync_migrate_acquia_content_hub\Form\MigrateExport;
use Drupal\cms_content_sync_migrate_acquia_content_hub\Form\MigrateImport;
use Drupal\cms_content_sync\Entity\Pool;

/**
 * Implements hook_drush_command().
 */
function cms_content_sync_migrate_acquia_content_hub_drush_command() {
  $items['content_sync_migrate_acquia_content_hub'] = [
    'description' => dt('Migrate configuration from Acquia Content Hub to CMS Content Sync'),
    'aliases' => ['mach'],
    'options' => [
      'type' => [
        'required' => TRUE,
        'description' => 'The type of configuration you would like to migrate.',
      ],
      'vocabulary' => [
        'required' => FALSE,
        'description' => 'Based on the selected vocabulary, the cms content sync pools are going to be created.',
      ],
      'backend_url' => [
        'required' => TRUE,
        'description' => 'The sync core url.',
      ],
      'authentication_type' => [
        'required' => TRUE,
        'description' => 'Allowed values: "cookie", "basic_auth"',
      ],
      'site_id' => [
        'description' => 'The unique identifier for this page. If no site_id is set, the command will try to get the client_name configuration form the Acquia Content Hub.',
      ],
      'node_export_behavior' => [
        'required' => FALSE,
        'description' => 'Allowed values: "automatically", "manually".',
      ],
      'import_updates_behavior' => [
        'required' => FALSE,
        'description' => 'Allowed values: "force_and_forbid_editing", "allow_override", "force", "ignore".',
      ],
      'force_update' => [
        'description' => 'If this is set to true, already existent flow configurations are going to be overwritten.',
      ],
    ],
    'examples' => [
      'Create export configuration' => 'drush mach --type="export" --vocabulary="sites" --backend_url="http://test:test@drupal-content-sync:8691/rest" --authentication_type="basic_auth" --site_id="example-exporter" --node_export_behavior="automatically"',
      'Create import configuration' => 'drush mach --type="import" --vocabulary="sites" --backend_url="http://test:test@drupal-content-sync:8691/rest" --authentication_type="basic_auth" --site_id="example-importer" --import_updates_behavior="force_and_forbid_editing"',
    ],
  ];

  return $items;
}

/**
 * Migrate Acquia Content Hub.
 */
function drush_cms_content_sync_migrate_acquia_content_hub_content_sync_migrate_acquia_content_hub() {
  $type = drush_get_option('type');
  $vocabulary = drush_get_option('vocabulary', '');
  $backend_url = drush_get_option('backend_url');
  $authentication_type = drush_get_option('authentication_type');
  $site_id = drush_get_option('site_id', '');
  $node_export_behavior = drush_get_option('node_export_behavior', '');
  $import_updates_behavior = drush_get_option('import_updates_behavior', '');
  $force_update = drush_get_option('force_update', FALSE);

  // Validate type option.
  if ($type != 'export' && $type != 'import') {
    drush_set_error('The option "type" has to be either "export" or "import"');
    return;
  }

  // Validate authentication_type option.
  $moduleHandler = \Drupal::service('module_handler');
  if ($moduleHandler->moduleExists('basic_auth')) {
    if ($authentication_type != Pool::AUTHENTICATION_TYPE_BASIC_AUTH && $authentication_type != Pool::AUTHENTICATION_TYPE_COOKIE) {
      drush_set_error('The option "authentication_type" has to be either "' . Pool::AUTHENTICATION_TYPE_BASIC_AUTH . '" or "' . Pool::AUTHENTICATION_TYPE_COOKIE . '".');
      return;
    }
  }
  else {
    drush_set_error('The option "authentication_type" has to be "' . Pool::AUTHENTICATION_TYPE_COOKIE . '".');
    return;
  }

  // Validate site_id option.
  $acquia_client_name = \Drupal::config('acquia_contenthub.admin_settings')->get('client_name');
  if ($site_id == '' && !isset($acquia_client_name) && $acquia_client_name == '') {
    drush_set_error('The option "site_id" has to be set since there is also no client_name configuration set for the Acquia Content Hub that could be taken over.');
    return;
  }

  // Load terms.
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary);
  $term_data = [];
  foreach ($terms as $term) {
    $term_data[$term->tid] = $term->tid;
  }

  $create_status_entities = new CreateStatusEntities();

  $operations = [];

  // Create export configuration.
  if ($type == 'export') {

    // Validate vocabulary option.
    if ($vocabulary == '') {
      drush_set_error('For the creation of export flows the vocabulary option is required.');
      return;
    }

    // Validate node_export_behavior option.
    if ($node_export_behavior == '') {
      drush_set_error('For the creation of export flows the node_export_behavior is required.');
      return;
    }
    if ($node_export_behavior != ExportIntent::EXPORT_AUTOMATICALLY && $node_export_behavior != ExportIntent::EXPORT_MANUALLY) {
      drush_set_error('The node export behavior has to be either "' . ExportIntent::EXPORT_AUTOMATICALLY . '" or "' . ExportIntent::EXPORT_MANUALLY . '"');
      return;
    }

    // Create pools.
    $pools = MigrateExport::createPools($term_data, $backend_url, $authentication_type, $site_id);

    // Create flow.
    $flow = MigrateExport::createFlow($pools, $node_export_behavior, $import_updates_behavior, $force_update);

    // Create status entities.
    $operations = $create_status_entities->prepare($flow['flow_id'], $flow['flow_configuration'], $pools, $flow['type']);

    // Done.
    drush_print('The export configuration has been created.');
  }

  // Create import configuration.
  if ($type == 'import') {

    // Validate import_updates_behavior option.
    if ($import_updates_behavior == '') {
      drush_set_error('For the creation of import flows the import_updates_behavior is required.');
      return;
    }
    if ($import_updates_behavior != ExportIntent::EXPORT_AUTOMATICALLY && $node_export_behavior != ExportIntent::EXPORT_MANUALLY && $import_updates_behavior != ImportIntent::IMPORT_UPDATE_FORCE_AND_FORBID_EDITING) {
      drush_set_error('The import_updates_behavior has to be either "' . ImportIntent::IMPORT_UPDATE_FORCE_AND_FORBID_EDITING . '" or "' . ImportIntent::IMPORT_UPDATE_FORCE_UNLESS_OVERRIDDEN . '" or "' . ImportIntent::IMPORT_UPDATE_FORCE . '" or "' . ImportIntent::IMPORT_UPDATE_IGNORE . '".');
      return;
    }

    $content_hub_filters = \Drupal::entityTypeManager()
      ->getStorage('contenthub_filter')
      ->loadMultiple();

    foreach ($content_hub_filters as $content_hub_filter_id => $content_hub_filter) {
      // Create pools.
      $pools = MigrateImport::createPools($term_data, $backend_url, $authentication_type, $site_id, $content_hub_filter);

      // Create flow.
      $flow = MigrateImport::createFlow($pools, $node_export_behavior, $import_updates_behavior, $content_hub_filter, $force_update);

      // Create status entities.
      $operations = array_merge($operations, $create_status_entities->prepare($flow['flow_id'], $flow['flow_configuration'], $pools, $flow['type']));
    }

    drush_print('The import configuration has been created.');
  }

  if (!count($operations)) {
    return;
  }

  $batch = [
    'title' => t('Creating status entities'),
    'operations' => $operations,
  ];

  batch_set($batch);

  // Execute.
  drush_backend_batch_process();
}
