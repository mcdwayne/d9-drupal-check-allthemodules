<?php

/**
 * @file
 * Drush File.
 */

use Drupal\wizenoze\Helper\WizenozeAPI;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Database;

/**
 * Implements hook_drush_command().
 */
function wizenoze_drush_command() {

  $items = [];
  $items['ingest'] = [
    'description' => 'Ingest Content at Wizenoze.',
    'options' => [
      'collection' => 'A comma-separated list of groups to ingest',
    ],
    'examples' => [
      'ingest' => 'Ingest all content',
    ],
    'aliases' => [
      'ing',
    ],
  ];

  return $items;
}

/**
 * Call back function drush_custom_drush_command_say_hello().
 *
 * The call back function name in the  following format.
 *
 * drush_{module_name}_{item_id_for_command}().
 */
function drush_wizenoze_ingest() {

  $batch = NULL;
  drush_print('Welcome to "Ingest" Interface');

  $choices = [
    1 => 'Ingest for all Collections',
    2 => 'list the Collections',
  ];

  $selectedChoice = drush_choice($choices, dt("Choose the option for ingestion:"));

  if ($selectedChoice == 0) {

    drupal_set_message(t('Operation Terminated!'));
  }
  else {

    $wizenoze = WizenozeAPI::getInstance();

    // Load all collections.
    $list = $wizenoze->collectionList();
    $collectionChoices = [];
    foreach ($list as $collection) {
      $collectionChoices[$collection['id']] = $collection['name'];
    }
    switch ($selectedChoice) {
      case 1:
        $selectedCollectionChoice = -1;
        break;

      case 2:
        $selectedCollectionChoice = drush_choice($collectionChoices, dt("Choose the collection for ingestion:"));
    }

    // Db With Collection.
    if ($selectedCollectionChoice == 0) {
      drupal_set_message(t('Operation Terminated!'));
    }
    else {

      if ($selectedCollectionChoice > 0) {
        $contentType[$selectedCollectionChoice] = \Drupal::config('wizenoze.settings')->get('collection-id-' . $selectedCollectionChoice);
      }
      else {
        foreach ($collectionChoices as $key => $name) {
          $contentType[$key] = \Drupal::config('wizenoze.settings')->get('collection-id-' . $key);
        }
      }

      $connection = Database::getConnection();
      foreach ($contentType as $key => $types) {
        drush_print_r('Starting Collection - ' . $wizenoze->collectionName($key));
        if (!empty($types)) {
          $selectedTypeChoice = drush_choice(array_merge(['-1' => 'All Content Types'], $types), dt("Choose the types:"));
          if ($selectedTypeChoice == -1) {
            foreach ($types as $type) {
              $list = $connection->query('SELECT nid FROM node_field_data WHERE type=:type', [':type' => $type])->fetchCol();
              drush_print_r('Ingestion starting for ' . $type . ' : Total nodes ' . count($list));
              $sourceName = $wizenoze->collectionSourceName($key);
              $batch = ingest_batch_1($sourceName, $wizenoze, $list);

              // Initialize the batch.
              batch_set($batch);
            }
          }
          else {
            $list = $connection->query('SELECT nid FROM node_field_data WHERE type=:type', [':type' => $selectedTypeChoice])->fetchCol();
            drush_print_r('Ingestion starting for ' . $selectedTypeChoice . ' : Total nodes ' . count($list));
            $sourceName = $wizenoze->collectionSourceName($key);
            $batch = ingest_batch_1($sourceName, $wizenoze, $list);

            // Initialize the batch.
            batch_set($batch);
            // Start the batch process.
          }
        }
      }
      // Start the batch process.
      drush_backend_batch_process();
    }
  }
}

/**
 * Function to injest batch process.
 */
function ingest_batch_1($sourceName, WizenozeAPI $wizenoze, $list = []) {
  return [
    'operations' => [
      [
        'ingest_collection_1_batch',
        [$list, $sourceName, $wizenoze],
      ],
    ],
    'finished' => 'ingest_batch_finished',
    'title' => t('Ingest Data'),
    'init_message' => t('Ingestion started'),
    'progress_message' => t('Updating...'),
    'error_message' => t('Ingestion has encountered an error.'),
  ];
}

/**
 * Function to injest collection batch process.
 */
function ingest_collection_1_batch($itemList, $sourceName, WizenozeAPI $wizenoze, &$context) {

  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_id'] = 1;
    $context['sandbox']['max'] = count($itemList);
    $context['sandbox']['items_count'] = 1;
    $context['results']['result_count'] = $context['sandbox']['max'];
    $context['results']['items'] = $itemList;
    drush_print_r("Batch started for item count is {$context['sandbox']['max']}");
  }

  if ($context['sandbox']['max'] + 1 > 0) {

    $item = array_pop($context['results']['items']);
    if ($item > 0) {
      /* @var $node \Drupal\node\Entity\Node */
      $node = Node::load($item);
      if ($node) {
        $response = NULL;
        try {
          if ($node->status->value == 0) {
            $response = $wizenoze->deleteDocument($sourceName, $node->id());
          }
          else {
            $document = [
              'id' => $node->id(),
              'title' => $node->get('title')->value,
              'url' => $node->url('canonical', ['relative' => TRUE]),
              'body' => substr(strip_tags($node->get('body')->value), 0, 32000),
              'sourceName' => $sourceName,
            ];
            $response = $wizenoze->ingestDocument($document);
          }
        }
        catch (\Exception $e) {
          drush_print(t("Can't open the URl : @url.", ['@url' => $node->url()]));
        }
        $status = ($response != NULL) ? 'success' : 'failure';
        $context['sandbox']['current_id'] = $context['sandbox']['progress'];
        $context['sandbox']['progress'] = $context['sandbox']['progress'] + $context['sandbox']['items_count'];

        drush_log(t('ID is :id , Count :count , Status :status, URL is :url', [
          ':id' => $node->id(),
          ':url' => $node->url(),
          ':status' => ($node->status->value == 0) ? 'deleted' : 'ingested',
          ':count' => $context['sandbox']['progress'],
        ]
        ), ($status == 'success') ? 'success' : 'error');
      }
    }
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Batch Finished Function.
 */
function ingest_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('Run batch successfully count : @result_count.', ['@result_count' => $results['result_count']]));
  }
}
