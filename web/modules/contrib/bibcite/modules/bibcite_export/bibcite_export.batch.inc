<?php

/**
 * @file
 * Batch callbacks.
 */

use Drupal\bibcite\Plugin\BibciteFormatInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Batch export callback. Import static list of entities.
 *
 * @param array $id_list
 *   List of entity identifiers.
 * @param string $entity_type
 *   Entity type identifier.
 * @param \Drupal\bibcite\Plugin\BibciteFormatInterface $format
 *   Instance of format plugin.
 * @param array $context
 *   The batch context array.
 */
function bibcite_export_batch_list(array $id_list, $entity_type, BibciteFormatInterface $format, array &$context) {
  $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
  $serializer = \Drupal::service('serializer');

  if (empty($context['sandbox'])) {
    $context['sandbox']['id_list'] = $id_list;
  }

  if (empty($context['results'])) {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $context['results']['file'] = $file_system->tempnam('temporary://', 'bibcite-');
    $context['results']['count'] = 0;
    $context['results']['format'] = $format;
    $context['results']['save_storage'] = FALSE;
  }

  $entities = $storage->loadMultiple($id_list);

  $export_string = $serializer->serialize($entities, $format->getPluginId());
  file_put_contents($context['results']['file'], $export_string, FILE_APPEND);

  $context['results']['count'] += count($entities);

  $latest_entity = end($entities);
  $context['message'] = $latest_entity->label();
}

/**
 * Batch export callback. Export all entities by entity type.
 *
 * @param string $entity_type
 *   Entity type identifier.
 * @param \Drupal\bibcite\Plugin\BibciteFormatInterface $format
 *   Instance of format plugin.
 * @param array $context
 *   The batch context array.
 */
function bibcite_export_batch_all($entity_type, BibciteFormatInterface $format, array &$context) {
  $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
  $serializer = \Drupal::service('serializer');

  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_id'] = 0;
    $context['sandbox']['max'] = $storage->getQuery()->count()->execute();
  }

  if (empty($context['results'])) {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $context['results']['file'] = $file_system->tempnam('temporary://', 'bibcite-');
    $context['results']['count'] = 0;
    $context['results']['format'] = $format;
    $context['results']['save_storage'] = TRUE;
  }

  $limit = 50;
  $result = $storage->getQuery()
    ->range(0, $limit)
    ->condition('id', $context['sandbox']['current_id'], '>')
    ->sort('id')
    ->execute();

  $ids = array_keys($result);
  if (!$ids || !($entities = $storage->loadMultiple($ids))) {
    $context['finished'] = 1;
    return;
  }

  $export_string = $serializer->serialize($entities, $format->getPluginId());
  file_put_contents($context['results']['file'], $export_string, FILE_APPEND);

  $latest_entity = end($entities);

  $context['sandbox']['progress'] += count($entities);
  $context['sandbox']['current_id'] = $latest_entity->id();
  $context['results']['count'] = $context['sandbox']['progress'];
  $context['message'] = $latest_entity->label();

  $progress = $context['sandbox']['progress'] / $context['sandbox']['max'];
  $context['finished'] = ($progress < 1) ? $progress : 0.99;
}

/**
 * Batch export finished callback.
 *
 * @param bool $success
 *   A boolean indicating whether the batch has completed successfully.
 * @param array $results
 *   The value set in $context['results'].
 *   Must be an array and contain next elements:
 *   $results['file'] - URI for temporary file contains export result.
 *   $results['format'] - Instance of format plugin.
 *   $results['count'] -  Count of exported entries.
 *   Optionally you can set $results['save_storage'] to TRUE and file info
 *   will be saved to private storage.
 * @param mixed $operations
 *   If $success is FALSE, contains the operations that remained unprocessed.
 */
function bibcite_export_batch_finished($success, array $results, $operations) {
  $messenger = \Drupal::messenger();
  if ($success && isset($results['file'], $results['format'], $results['count'])) {
    /** @var \Drupal\bibcite\Plugin\BibciteFormatInterface $format */
    $format = $results['format'];

    /** @var \Drupal\Core\TempStore\PrivateTempStore $tempstorage */
    $tempstorage = \Drupal::service('tempstore.private')->get('bibcite_export');

    $current_user = \Drupal::currentUser();

    $file_schema = 'temporary://';
    $file_name = sprintf('bibcite-export-%s-%s.%s', date('mdYHis'), $current_user->id(), $format->getExtension());

    $file = File::create([
      'uid' => $current_user->id(),
      'filename' => $file_name,
      'uri' => $results['file'],
      'status' => 0,
    ]);

    if ($file->save() && $file = file_move($file, $file_schema . $file_name)) {
      if (!empty($results['save_storage'])) {
        $file_data = [
          'id' => $file->id(),
          'timestamp' => time(),
          'format' => $format->getPluginId(),
        ];

        $data = $tempstorage->get('export_files');
        $data = !empty($data) ? $data : [];
        $data[] = array_unshift($data, $file_data);
        $tempstorage->set('export_files', $data);
      }

      $message = t('Reference export is complete (@count entries has been exported). <a href="@download_url">Download</a>.', [
        '@count' => $results['count'],
        '@download_url' => Url::fromRoute('bibcite_export.download', [
          'file' => $file->id(),
        ])->toString(),
      ]);
      $messenger->addStatus($message);
    }
    else {
      $messenger->addError(t('Temporary file can not be saved'));
    }
  }
  else {
    $messenger->addError(t('An error occurred while processing export'));
  }
}
