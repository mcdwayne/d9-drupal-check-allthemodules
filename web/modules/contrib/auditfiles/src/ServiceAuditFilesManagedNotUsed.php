<?php

namespace Drupal\auditfiles;

use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service managed not used functions.
 */
class ServiceAuditFilesManagedNotUsed {

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
  public function auditfilesManagedNotUsedGetFileList() {
    $config = \Drupal::config('auditfiles.settings');
    $connection = Database::getConnection();
    $query = 'SELECT fid FROM {file_managed} WHERE fid NOT IN (SELECT fid FROM {file_usage})';
    $maximum_records = $config->get('auditfiles_report_options_maximum_records') ? $config->get('auditfiles_report_options_maximum_records') : 250;
    if ($maximum_records > 0) {
      $query .= ' LIMIT ' . $maximum_records;
    }
    return $connection->query($query)->fetchCol();
  }

  /**
   * Retrieves information about an individual file from the database.
   *
   * @param int $file_id
   *   The ID of the file to prepare for display.
   * @param int $date_format
   *   The Format of the date to prepare for display.
   *
   * @return array
   *   The row for the table on the report, with the file's
   *   information formatted for display.
   */
  public function auditfilesManagedNotUsedGetFileData($file_id, $date_format) {
    $connection = Database::getConnection();
    $query = $connection->select('file_managed', 'fm');
    $query->condition('fm.fid', $file_id);
    $query->fields('fm', [
      'fid',
      'uid',
      'filename',
      'uri',
      'filemime',
      'filesize',
      'created',
      'status',
    ]);
    $results = $query->execute()->fetchAll();
    $file = $results[0];
    return [
      'fid' => $file->fid,
      'uid' => $file->uid,
      'filename' => $file->filename,
      'uri' => $file->uri,
      'path' => drupal_realpath($file->uri),
      'filemime' => $file->filemime,
      'filesize' => number_format($file->filesize),
      'datetime' => \Drupal::service('date.formatter')->format($file->created, $date_format),
      'status' => ($file->status = 1) ? 'Permanent' : 'Temporary',
    ];
  }

  /**
   * Returns the header to use for the display table.
   *
   * @return array
   *   The header to use.
   */
  public function auditfilesManagedNotUsedGetHeader() {
    return [
      'fid' => [
        'data' => $this->t('File ID'),
      ],
      'uid' => [
        'data' => $this->t('User ID'),
      ],
      'filename' => [
        'data' => $this->t('Name'),
      ],
      'uri' => [
        'data' => $this->t('URI'),
      ],
      'path' => [
        'data' => $this->t('Path'),
      ],
      'filemime' => [
        'data' => $this->t('MIME'),
      ],
      'filesize' => [
        'data' => $this->t('Size'),
      ],
      'datetime' => [
        'data' => $this->t('When added'),
      ],
      'status' => [
        'data' => $this->t('Status'),
      ],
    ];
  }

  /**
   * Batch process.
   */
  public function auditfilesManagedNotUsedBatchDeleteCreateBatch(array $fileids) {
    $batch['error_message'] = $this->t('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesManagedNotUsedBatchFinishBatch';
    $batch['progress_message'] = $this->t('Completed @current of @total operations.');
    $batch['title'] = $this->t('Deleting files from the file_managed table');
    $operations = $file_ids = [];
    foreach ($fileids as $file_id) {
      if ($file_id != 0) {
        $file_ids[] = $file_id;
      }
    }
    foreach ($file_ids as $file_id) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesManagedNotUsedBatchDeleteProcessBatch',
        [$file_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the file_managed table.
   *
   * @param int $file_id
   *   The ID of the file to delete from the database.
   */
  public function auditfilesManagedNotUsedBatchDeleteProcessFile($file_id) {
    $connection = Database::getConnection();
    $num_rows = $connection->delete('file_managed')
      ->condition('fid', $file_id)
      ->execute();
    if (empty($num_rows)) {
      drupal_set_message(
        $this->t(
          'There was a problem deleting the record with file ID %fid from the file_managed table. Check the logs for more information.',
          ['%fid' => $file_id]
        ),
        'warning'
      );
    }
    else {
      drupal_set_message(
        $this->t(
          'Sucessfully deleted File ID : %fid from the file_managed table.',
          ['%fid' => $file_id]
        )
      );
    }
  }

}
