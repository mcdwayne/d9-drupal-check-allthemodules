<?php

namespace Drupal\auditfiles;

use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * List all methods used in referenced not used functionality.
 */
class ServiceAuditFilesReferencedNotUsed {

  use StringTranslationTrait;

  /**
   * Define constructor for string translation.
   */
  public function __construct(TranslationInterface $translation) {
    $this->stringTranslation = $translation;
  }

  /**
   * Retrieves the file IDs to operate on.
   *
   * @return array
   *   The file IDs.
   */
  public function auditfilesReferencedNotUsedGetFileList() {
    $config = \Drupal::config('auditfiles.settings');
    $connection = Database::getConnection();
    $file_references = $files_referenced = [];
    // Get a list of all files that are referenced in content.
    $files_in_fields = [];
    $fields[] = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('image');
    $fields[] = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('file');
    if ($fields) {
      $count = 0;
      foreach ($fields as $key => $value) {
        foreach ($value as $table_prefix => $entity_type) {
          foreach ($entity_type as $key1 => $value1) {
            $field_data[$count]['table'] = $table_prefix . '__' . $key1;
            $field_data[$count]['column'] = $key1 . '_target_id';
            $field_data[$count]['entity_type'] = $table_prefix;
            $count++;
          }
        }
      }
      foreach ($field_data as $key => $value) {
        $table = $value['table'];
        $column = $value['column'];
        $entity_type = $value['entity_type'];
        if (Database::getConnection()->schema()->tableExists($table)) {
          $query = 'SELECT entity_id, ' . $column . ' FROM {' . $table . '}';
          $query .= ' WHERE ' . $column . ' NOT IN (SELECT DISTINCT fid FROM {file_usage})';
          $maximum_records = $config->get('auditfiles_report_options_maximum_records') ? $config->get('auditfiles_report_options_maximum_records') : 250;
          if ($maximum_records > 0) {
            $query .= ' LIMIT ' . $maximum_records;
          }
          $file_references = $connection->query($query)->fetchAll();
          foreach ($file_references as $file_reference) {
            $reference_id = $table . '.' . $column . '.' . $file_reference->entity_id . '.' . $entity_type . '.' . $file_reference->{$column};
            $files_referenced[$reference_id] = [
              'table' => $table,
              'column' => $column,
              'entity_id' => $file_reference->entity_id,
              'file_id' => $file_reference->{$column},
              'entity_type' => $entity_type,
            ];
          }
        }
      }
    }
    return $files_referenced;
  }

  /**
   * Retrieves information about an individual file from the database.
   *
   * @param array $row_data
   *   The data to use for creating the row.
   *
   * @return array
   *   The row for the table on the report, with the file's
   *   information formatted for display.
   */
  public function auditfilesReferencedNotUsedGetFileData(array $row_data) {
    $config = \Drupal::config('auditfiles.settings');
    $connection = Database::getConnection();
    $query = 'SELECT * FROM {' . $row_data['table'] . '} WHERE ' . $row_data['column'] . ' = ' . $row_data['file_id'];
    $result = $connection->query($query)->fetchAll();
    $result = reset($result);
    if ($row_data['entity_type'] == 'node') {
      $url = Url::fromUri('internal:/node/' . $result->entity_id);
      $entity_id_display = Link::fromTextAndUrl('node/' . $result->entity_id, $url)->toString();
    }
    else {
      $entity_id_display = $result->entity_id;
    }
    $row = [
      'file_id' => $result->{$row_data['column']},
      'entity_type' => $row_data['entity_type'],
      'bundle' => ['data' => $result->bundle, 'hidden' => TRUE],
      'entity_id' => ['data' => $result->entity_id, 'hidden' => TRUE],
      'entity_id_display' => $entity_id_display,
      'field' => $row_data['table'] . '.' . $row_data['column'],
      'table' => ['data' => $row_data['table'], 'hidden' => TRUE],
      'uri' => 'No file object exists for this reference.',
      'filename' => ['data' => '', 'hidden' => TRUE],
      'filemime' => '--',
      'filesize' => '--',
    ];
    // If there is a file in the file_managed table, add some of that
    // information to the row, too.
    $file_managed = File::load($result->{$row_data['column']});
    if (!empty($file_managed)) {
      $row['uri'] = $file_managed->getFileuri();
      $row['filename'] = ['data' => $file_managed->getFilename(), 'hidden' => TRUE];
      $row['filemime'] = $file_managed->getMimeType();
      $row['filesize'] = $file_managed->getSize();
    }
    return $row;
  }

  /**
   * Returns the header to use for the display table.
   *
   * @return array
   *   The header to use.
   */
  public function auditfilesReferencedNotUsedGetHeader() {
    return [
      'file_id' => [
        'data' => $this->t('File ID'),
      ],
      'entity_type' => [
        'data' => $this->t('Referencing entity type'),
      ],
      'entity_id_display' => [
        'data' => $this->t('Referencing entity ID'),
      ],
      'field' => [
        'data' => $this->t('Field referenced in'),
      ],
      'uri' => [
        'data' => $this->t('URI'),
      ],
      'filemime' => [
        'data' => $this->t('MIME'),
      ],
      'filesize' => [
        'data' => $this->t('Size (in bytes)'),
      ],
    ];
  }

  /**
   * Creates the batch for adding files to the file_usage table.
   *
   * @param array $referenceids
   *   The list of IDs to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  public function auditfilesReferencedNotUsedBatchAddCreateBatch(array $referenceids) {
    $batch['error_message'] = $this->t('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesReferencedNotUsedBatchFinishBatch';
    $batch['progress_message'] = $this->t('Completed @current of @total operations.');
    $batch['title'] = $this->t('Adding files to the file_usage table');
    $operations = $reference_ids = [];
    foreach ($referenceids as $reference_id) {
      if (!empty($reference_id)) {
        $reference_ids[] = $reference_id;
      }
    }
    foreach ($reference_ids as $reference_id) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesReferencedNotUsedBatchAddProcessBatch',
        [$reference_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Adds the specified file to the file_usage table.
   *
   * @param string $reference_id
   *   The ID for keeping track of the reference.
   */
  public function auditfilesReferencedNotUsedBatchAddProcessFile($reference_id) {
    $reference_id_parts = explode('.', $reference_id);
    $connection = Database::getConnection();
    $data = [
      'fid' => $reference_id_parts[4],
      // @todo This is hard coded for now, but need to determine how to figure out
      // which module needs to be here.
      'module' => 'file',
      'type' => $reference_id_parts[3],
      'id' => $reference_id_parts[2],
      'count' => 1,
    ];
    // Make sure the file is not already in the database.
    $query = 'SELECT fid FROM file_usage
    WHERE fid = :fid AND module = :module AND type = :type AND id = :id';
    $existing_file = $connection->query(
      $query,
      [
        ':fid' => $data['fid'],
        ':module' => $data['module'],
        ':type' => $data['type'],
        ':id' => $data['id'],
      ]
    )->fetchAll();
    if (empty($existing_file)) {
      // The file is not already in the database, so add it.
      $connection->insert('file_usage')->fields($data)->execute();
    }
    else {
      drupal_set_message(
        $this->t(
           'The file is already in the file_usage table (file id: "@fid", module: "@module", type: "@type", entity id: "@id").',
          [
            '@fid' => $data['fid'],
            '@module' => $data['module'],
            '@type' => $data['type'],
            '@id' => $data['id'],
          ]
        ),
        'error'
      );
    }
  }

  /**
   * Creates the batch for deleting file references from their content.
   *
   * @param array $referenceids
   *   The list of IDs to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  public function auditfilesReferencedNotUsedBatchDeleteCreateBatch(array $referenceids) {
    $batch['error_message'] = $this->t('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesReferencedNotUsedBatchFinishBatch';
    $batch['progress_message'] = $this->t('Completed @current of @total operations.');
    $batch['title'] = $this->t('Deleting file references from their content');
    $operations = $reference_ids = [];
    foreach ($referenceids as $reference_id) {
      if ($reference_id != '') {
        $reference_ids[] = $reference_id;
      }
    }
    // Fill in the $operations variable.
    foreach ($reference_ids as $reference_id) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesReferencedNotUsedBatchDeleteProcessBatch',
        [$reference_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the database.
   *
   * @param string $reference_id
   *   The ID for keeping track of the reference.
   */
  public function auditfilesReferencedNotUsedBatchDeleteProcessFile($reference_id) {
    $reference_id_parts = explode('.', $reference_id);
    $connection = Database::getConnection();
    $num_rows = $connection->delete($reference_id_parts[0])
      ->condition($reference_id_parts[1], $reference_id_parts[4])
      ->execute();
    if (empty($num_rows)) {
      drupal_set_message(
        $this->t(
          'There was a problem deleting the reference to file ID %fid in the %entity_type with ID %eid. Check the logs for more information.',
          [
            '%fid' => $reference_id_parts[4],
            '%entity_type' => $reference_id_parts[3],
            '%eid' => $reference_id_parts[2],
          ]
        ),
        'warning'
      );
    }
    else {
      drupal_set_message(
        $this->t(
          'file ID %fid  deleted successfully.',
          [
            '%fid' => $reference_id_parts[4],
          ]
        )
      );
    }
  }

}
