<?php

/**
 * @file
 * Drush command to cli config import.
 */

use Drupal\gathercontent\DrupalGatherContentClient;
use Drupal\gathercontent\Entity\Mapping;
use Drupal\gathercontent\Import\Importer;
use Drupal\gathercontent\Import\ImportOptions;

/**
 * Implements hook_drush_command().
 */
function gathercontent_drush_command() {
  $commands = [];

  $commands['gathercontent-list-mappings'] = [
    'aliases' => ['gc-lm'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'description' => dt('Lists the available mapping definitions.'),
    'engines' => [
      'outputformat' => [
        'default' => 'table',
        'pipe-format' => 'json',
        'output-data-type' => 'format-table',
        'field-labels' => [
          'mapping_id' => dt('Mapping ID'),
          'project_id' => dt('Project ID'),
          'project_label' => dt('Project label'),
          'template_id' => dt('Template ID'),
          'template_label' => dt('Template label'),
          'content_type' => dt('Content type'),
        ],
      ],
    ],
  ];

  $commands['gathercontent-list-status'] = [
    'aliases' => ['gc-ls'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'description' => dt('Lists the node status definitions.'),
    'required-arguments' => FALSE,
    'arguments' => [
      'project_id' => dt('GatherContent project ID. Use: gathercontent-list-mappings'),
    ],
    'engines' => [
      'outputformat' => [
        'default' => 'table',
        'pipe-format' => 'json',
        'output-data-type' => 'format-table',
        'field-labels' => [
          'status_id' => dt('Status ID'),
          'status_label' => dt('Status label'),
        ],
      ],
    ],
  ];

  $commands['gathercontent-import'] = [
    'aliases' => ['gc-i'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'description' => dt('Import content from GatherContent site.'),
    'required-arguments' => FALSE,
    'arguments' => [
      'mapping_id' => dt('The drupal side content mapping ID. Use: gathercontent-list-mappings'),
      'status_id' => dt('Change the document status on GC side. Use: gathercontent-list-status'),
      'parent_menu_item' => dt("Parent menu item. E.g.: Create under 'My account' menu, use: 'account:user.page'"),
    ],
    'options' => [
      'publish' => [
        'description' => dt('Use --publish or --no-publish.'),
        'required' => FALSE,
        'hidden' => FALSE,
      ],
      'create-new-revision' => [
        'description' => dt('Use --create-new-revision or --no-create-new-revision.'),
        'required' => FALSE,
        'hidden' => FALSE,
      ],
    ],
    'engines' => [
      'outputformat' => [
        'default' => 'table',
        'pipe-format' => 'json',
        'output-data-type' => 'format-table',
        'field-labels' => [
          'id' => dt('ID'),
          'item_name' => dt('Item name'),
          'node_status' => dt('Node status'),
          'import_status' => dt('Import status'),
        ],
      ],
    ],
  ];

  return $commands;
}

/**
 * Implements drush_COMMAND().
 */
function drush_gathercontent_list_mappings() {
  /** @var \Drupal\gathercontent\Entity\MappingInterface[] $gc_mappings */
  $gc_mappings = \Drupal::entityTypeManager()->getStorage('gathercontent_mapping')->loadMultiple();

  $mappings = [];
  foreach ($gc_mappings as $gc_mapping) {
    $mappings[$gc_mapping->id()] = [
      'mapping_id' => $gc_mapping->id(),
      'project_id' => $gc_mapping->getGathercontentProjectId(),
      'project_label' => $gc_mapping->getGathercontentProject(),
      'template_id' => $gc_mapping->getGathercontentTemplateId(),
      'template_label' => $gc_mapping->getGathercontentTemplate(),
      'content_type' => $gc_mapping->getContentType(),
    ];
  }

  return $mappings;
}

/**
 * Implements drush_COMMAND().
 */
function drush_gathercontent_list_status($project_id = NULL) {
  /** @var \Drupal\gathercontent\DrupalGatherContentClient $client */
  $client = \Drupal::service('gathercontent.client');

  if ($project_id === NULL) {
    $account_id = DrupalGatherContentClient::getAccountId();
    if (!$account_id) {
      return drush_set_error('gathercontent_no_accounts_configured', t('No accounts configured.'));
    }
    $projects = $client->projectsGet($account_id);

    $options = [];
    foreach ($projects as $id => $project) {
      $options[$id] = $id . ' | ' . $project->name;
    }
    $project_id = drush_choice($options, dt('Select a project ID: '));
  }

  if (!$project_id) {
    return drush_set_error('gathercontent_unknown_mapping_id', dt('Unknown mapping ID.'));
  }

  $statuses = $client->projectStatusesGet($project_id);
  $mappings = [];
  foreach ($statuses as $status) {
    $mappings[$status->id] = [
      'status_id' => $status->id,
      'status_label' => $status->name,
    ];
  }

  return $mappings;
}

/**
 * Implements drush_COMMAND_validate().
 */
function drush_gathercontent_import_validate($mapping_id = NULL) {
  $publish = drush_get_option('publish');
  if ($publish !== NULL && !is_bool($publish)) {
    drush_set_error('gathercontent_invalid_option', dt("The --publish option doesn't accept value."));
  }

  $createNewRevision = drush_get_option('create-new-revision');
  if ($createNewRevision !== NULL && !is_bool($createNewRevision)) {
    drush_set_error('gathercontent_invalid_option', dt("The --create-new-revision option doesn't accept value."));
  }

  if ($mapping_id !== NULL) {
    $mapping = Mapping::load($mapping_id);
    if (!$mapping) {
      drush_set_error('gathercontent_invalid_argument', dt('Invalid mapping ID: @mapping_id', ['@mapping_id' => $mapping_id]));
    }
  }
}

/**
 * Implements drush_COMMAND().
 */
function drush_gathercontent_import($mapping_id = NULL, $status_id = '', $parent_menu_item = NULL) {
  if ($mapping_id === NULL) {
    /** @var \Drupal\gathercontent\Entity\MappingInterface[] $gc_mappings */
    $gc_mappings = \Drupal::entityTypeManager()->getStorage('gathercontent_mapping')->loadMultiple();

    $options = [];
    foreach ($gc_mappings as $gc_mapping) {
      $options[$gc_mapping->id()] = $gc_mapping->id() . ' | ' . $gc_mapping->getGathercontentProject() . ' | ' . $gc_mapping->getGathercontentTemplate();
    }
    $mapping_id = drush_choice($options, dt('Select a mapping ID: '));
  }

  if (!$mapping_id) {
    drush_set_error('gathercontent_unknown_mapping_id', dt('Unknown mapping ID.'));
    return;
  }

  $mapping = Mapping::load($mapping_id);
  $project_id = $mapping->getGathercontentProjectId();
  $template_id = $mapping->getGathercontentTemplateId();

  /** @var \Drupal\gathercontent\DrupalGatherContentClient $client */
  $client = \Drupal::service('gathercontent.client');
  /** @var \Cheppers\GatherContent\DataTypes\Item[] $items */
  $items = $client->itemsGet($project_id);

  $publish = drush_get_option('publish', \Drupal::config('gathercontent.import')->get('node_default_status'));
  $publish = $publish ? '1' : '0';

  $createNewRevision = drush_get_option('create-new-revision', \Drupal::config('gathercontent.import')->get('node_create_new_revision'));
  $createNewRevision = $createNewRevision ? '1' : '0';

  $sql = drush_sql_get_class();
  if (!in_array('batch', $sql->listTables())) {
    $bs = \Drupal::service('batch.storage');
    $db = \Drupal::database()->schema();
    $db->createTable('batch', $bs->schemaDefinition());
  }

  // Create and start Batch processes.
  $isItemFromSelectedTemplate = function ($item) use ($template_id) {
    return $item->templateId === $template_id;
  };
  $itemToId = function ($item) {
    return $item->id;
  };

  $selected_items = array_filter($items, $isItemFromSelectedTemplate);
  $gc_ids = array_map($itemToId, $selected_items);

  $operations = [];

  foreach ($gc_ids as $gc_id) {
    $import_options[$gc_id] = new ImportOptions(
      $publish,
      $createNewRevision,
      $status_id,
      $parent_menu_item
    );
  }

  $operations[] = [
    'gathercontent_import_process',
    [
      $gc_ids,
      $import_options,
      $mapping,
    ],
  ];

  $batch = [
    'title' => t('Importing'),
    'init_message' => t('Starting import'),
    'error_message' => t('An error occurred during processing'),
    'progress_message' => t('Processed @current out of @total.'),
    'progressive' => TRUE,
    'operations' => $operations,
    'finished' => 'gathercontent_drush_import_process_finished',
  ];

  batch_set($batch);
  drush_backend_batch_process();
}

/**
 * Batch process "finished" callback.
 */
function gathercontent_drush_import_process_finished($success, $results, $operations) {
  if ($success) {
    if ($results['success'] > 0) {
      drush_log(\Drupal::translation()
        ->formatPlural($results['success'], '1 item was imported successfully.', '@count items were imported successfully.'));
    }

    if ($results['failed'] > 0) {
      drush_log(\Drupal::translation()
        ->formatPlural($results['failed'], '1 item was not imported. Check errors below.', '@count items were not imported. Check errors below.'), 'error');
    }

    if ($results['failed'] == 0 && $results['success'] == 0) {
      drush_log(\Drupal::translation()
        ->translate('Nothing was imported or updated.'));
    }
  }
  else {
    $error_operation = reset($operations);

    drush_set_error(
      'gathercontent_import_failed',
      dt('An error occurred while processing @operation with arguments : @args',
        [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0], TRUE),
        ]
      )
    );
  }
}
